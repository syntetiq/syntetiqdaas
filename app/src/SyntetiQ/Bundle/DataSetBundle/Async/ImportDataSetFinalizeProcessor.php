<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Doctrine\Persistence\ManagerRegistry;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetFinalizeTopic;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetRollbackTopic;
use SyntetiQ\Bundle\DataSetBundle\Service\ImportDataSetArtifactsManager;

class ImportDataSetFinalizeProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private JobRunner $jobRunner,
        private ManagerRegistry $doctrine,
        private ImportDataSetArtifactsManager $artifactsManager,
        private MessageProducerInterface $producer,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [ImportDataSetFinalizeTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $jobId = $data['jobId'];

        try {
            $result = $this->jobRunner->runDelayed(
                $jobId,
                function (JobRunner $jobRunner, Job $job) use ($data) {
                    return $this->finalize($data, $job);
                }
            );
        } catch (JobRedeliveryException) {
            // Batch siblings still running — re-queue this message and try again later
            return self::REQUEUE;
        }

        return $result ? self::ACK : self::REJECT;
    }

    private function finalize(array $data, Job $job): bool
    {
        $rootJobId  = $data['rootJobId'];
        $dataSetId  = $data['dataSetId'];
        $extractedPath = $data['extractedPath'];
        $gcsFileName   = $data['gcsFileName'];

        $stats = $this->doctrine->getConnection()->fetchAllAssociative(
            'SELECT status, COUNT(id) as cnt FROM oro_message_queue_job WHERE root_job_id = :rootJobId GROUP BY status',
            ['rootJobId' => $job->getRootJob()->getId()]
        );

        $childStatuses = [];
        foreach ($stats as $row) {
            $childStatuses[$row['status']] = (int)$row['cnt'];
        }

        $this->logger->info(sprintf(
            '[ImportDataSetFinalizeProcessor] Child statuses for Root Job #%d: %s',
            $rootJobId,
            json_encode($childStatuses)
        ));

        $terminalStatuses = [
            Job::STATUS_SUCCESS,
            Job::STATUS_FAILED,
            Job::STATUS_CANCELLED,
            Job::STATUS_STALE,
        ];

        $totalChildren = array_sum($childStatuses);
        $doneChildren = 0;
        foreach ($terminalStatuses as $status) {
            $doneChildren += ($childStatuses[$status] ?? 0);
        }

        $failedBatches = ($childStatuses[Job::STATUS_FAILED] ?? 0)
                       + ($childStatuses[Job::STATUS_CANCELLED] ?? 0)
                       + ($childStatuses[Job::STATUS_STALE] ?? 0);

        $finalizeStatus = $job->getStatus();
        $pendingBatches = $totalChildren - $doneChildren;

        // If finalize itself is running (which it should be), it's part of pending but we don't wait for it
        if (!in_array($finalizeStatus, $terminalStatuses, true)) {
            $pendingBatches--;
        }

        $this->logger->info(sprintf(
            '[ImportDataSetFinalizeProcessor] Root Job #%d: finalizeStatus=%s, pendingBatches=%d, totalChildren=%d, doneChildren=%d',
            $rootJobId,
            $finalizeStatus,
            $pendingBatches,
            $totalChildren,
            $doneChildren
        ));

        if ($pendingBatches > 0) {
            $this->logger->info(sprintf(
                '[ImportDataSetFinalizeProcessor] Finalization deferred. Pending batches: %d (Root Job #%d)',
                $pendingBatches,
                $rootJobId
            ));

            // Race condition: batch jobs still queued — re-queue finalize, leave extracted directory intact
            throw new JobRedeliveryException();
        }

        try {
            $this->artifactsManager->deleteDirectory($extractedPath);
            $this->artifactsManager->cleanup($gcsFileName);

            // Ensure we have the latest metadata from the DB (itemCount, etc.)
            $rootJobDataJson = $this->doctrine->getConnection()->fetchOne(
                'SELECT data FROM oro_message_queue_job WHERE id = :id',
                ['id' => $job->getRootJob()->getId()]
            );
            $rootJobData = json_decode($rootJobDataJson ?: '{}', true);

            $expectedCount = (int) ($rootJobData['itemCount'] ?? 0);
            $processedCount = $this->countItemsByRootJobId($rootJobId);

            $this->logger->info(sprintf(
                '[ImportDataSetFinalizeProcessor] Finalizing Root Job #%d. Expected: %d, Processed: %d, Failed batches: %d',
                $rootJobId,
                $expectedCount,
                $processedCount,
                $failedBatches
            ));

            if ($failedBatches > 0 || ($expectedCount > 0 && $processedCount === 0)) {
                $this->logger->warning(sprintf(
                    '[ImportDataSetFinalizeProcessor] Rollback triggered for Root Job #%d. Reasons: failedBatches=%d, processedCount=%d',
                    $rootJobId,
                    $failedBatches,
                    $processedCount
                ));

                if ($processedCount > 0) {
                    $this->producer->send(ImportDataSetRollbackTopic::getName(), [
                        'importId'  => $rootJobId,
                        'dataSetId' => $dataSetId,
                        'itemCount' => $processedCount,
                    ]);
                }

                return false;
            }

            $this->logger->info(sprintf('[ImportDataSetFinalizeProcessor] Root Job #%d finalized successfully.', $rootJobId));

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                '[ImportDataSetFinalizeProcessor] Critical error during finalization of Root Job #%d: %s',
                $rootJobId,
                $e->getMessage()
            ));

            return false;
        }
    }

    /**
     * Count items that were imported in this job (import_id column stores root job ID).
     */
    private function countItemsByRootJobId(int $rootJobId): int
    {
        $conn = $this->doctrine->getConnection();

        return (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM syntetiq_data_set_item WHERE import_id = :importId',
            ['importId' => $rootJobId]
        );
    }
}
