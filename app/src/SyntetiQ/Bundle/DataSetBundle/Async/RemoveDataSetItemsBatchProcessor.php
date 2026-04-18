<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Google\Cloud\Storage\StorageClient;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\RemoveDataSetItemsBatchTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;

class RemoveDataSetItemsBatchProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner,
        private StorageClient $storageClient,
        private string $bucketName,
        private string $gcsPrefix
    ) {}

    public static function getSubscribedTopics(): array
    {
        return [RemoveDataSetItemsBatchTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();
        $jobId = $data['jobId'];
        $itemIds = $data['itemIds'] ?? [];

        if (empty($itemIds)) {
            return self::ACK;
        }

        $result = $this->jobRunner->runDelayed(
            $jobId,
            function (JobRunner $jobRunner, Job $job) use ($itemIds): bool {
                $em = $this->doctrine->getManagerForClass(DataSetItem::class);

                // 1. Fetch file names and File entity IDs
                $itemsData = $this->fetchItemsData($itemIds);
                $fileIds = array_filter(array_column($itemsData, 'fileId'));
                $fileNames = array_filter(array_column($itemsData, 'filename'));

                // 2. Perform Batch GCS DELETE
                if (!empty($fileNames)) {
                    $this->deleteFilesFromGcs($fileNames);
                }

                // 3. Perform DQL DELETE for DataSetItems
                $em->createQueryBuilder()
                    ->delete(DataSetItem::class, 'item')
                    ->where('item.id IN (:ids)')
                    ->setParameter('ids', $itemIds)
                    ->getQuery()
                    ->execute();

                // 4. Perform DQL DELETE for File entities
                if (!empty($fileIds)) {
                    $em->createQueryBuilder()
                        ->delete(File::class, 'f')
                        ->where('f.id IN (:ids)')
                        ->setParameter('ids', $fileIds)
                        ->getQuery()
                        ->execute();
                }

                $em->clear();

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function fetchItemsData(array $itemIds): array
    {
        $repository = $this->doctrine->getRepository(DataSetItem::class);
        $qb = $repository->createQueryBuilder('item');
        
        // We join with the image field (which is an Oro extended field for File entity)
        $qb->select('item.id as itemId, f.id as fileId, f.filename')
            ->leftJoin('item.image', 'f')
            ->where('item.id IN (:ids)')
            ->setParameter('ids', $itemIds);

        return $qb->getQuery()->getScalarResult();
    }

    private function deleteFilesFromGcs(array $fileNames): void
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        $prefix = trim($this->gcsPrefix, '/') . '/';

        foreach ($fileNames as $fileName) {
            try {
                $objectName = $prefix . $fileName;
                $bucket->object($objectName)->delete();
            } catch (\Exception $e) {
                // Ignore if file already deleted or other transient errors to prevent job failure
            }
        }
    }
}
