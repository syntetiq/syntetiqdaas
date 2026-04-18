<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetRollbackTopic;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\RemoveDataSetItemsBatchTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;

class ImportDataSetRollbackProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const CHUNK_SIZE = 100;

    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner,
        private MessageProducerInterface $producer
    ) {}

    public static function getSubscribedTopics()
    {
        return [ImportDataSetRollbackTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $importId = $data['importId'];
        $dataSetId = $data['dataSetId'];

        $result = $this->jobRunner->runUniqueByMessage($message, function (JobRunner $jobRunner) use ($importId, $dataSetId) {
            $itemIds = $this->loadItemIdsByImportId($importId);

            if (empty($itemIds)) {
                return true;
            }

            $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

            foreach ($chunks as $index => $chunk) {
                $jobRunner->createDelayed(
                    sprintf('rollback_batch:%d:%d:%d', $dataSetId, $importId, $index),
                    function (JobRunner $jobRunner, Job $childJob) use ($dataSetId, $chunk) {
                        $this->producer->send(
                            RemoveDataSetItemsBatchTopic::getName(),
                            [
                                'jobId'     => $childJob->getId(),
                                'dataSetId' => $dataSetId,
                                'itemIds'   => $chunk,
                            ]
                        );
                    }
                );
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @return int[]
     */
    private function loadItemIdsByImportId(int $importId): array
    {
        return array_column(
            $this->doctrine->getRepository(DataSetItem::class)
                ->createQueryBuilder('i')
                ->select('i.id')
                ->where('i.importId = :importId')
                ->setParameter('importId', $importId)
                ->getQuery()
                ->getScalarResult(),
            'id'
        );
    }
}
