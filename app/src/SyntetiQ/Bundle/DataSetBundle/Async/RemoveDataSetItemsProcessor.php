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
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\RemoveDataSetItemsBatchTopic;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\RemoveDataSetItemsTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;

class RemoveDataSetItemsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const CHUNK_SIZE = 50;

    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner,
        private MessageProducerInterface $producer
    ) {}

    public static function getSubscribedTopics(): array
    {
        return [RemoveDataSetItemsTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();
        $dataSetId = $data['dataSetId'] ?? null;
        $tags = $data['tags'] ?? [];

        if (!$dataSetId) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($dataSetId, $tags): bool {
                // Fetch IDs only — no entity hydration, very fast even for 100k items
                $itemIds = $this->loadItemIds($dataSetId, $tags);

                if (empty($itemIds)) {
                    return true;
                }

                $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

                foreach ($chunks as $index => $chunk) {
                    $jobRunner->createDelayed(
                        sprintf('remove_items_batch:%d:%d', $dataSetId, $index),
                        function (JobRunner $jobRunner, Job $childJob) use ($dataSetId, $chunk): void {
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
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * Returns only IDs — scalar result, zero entity hydration overhead.
     *
     * @return int[]
     */
    private function loadItemIds(int $dataSetId, array $tags): array
    {
        $queryBuilder = $this->doctrine
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('item.id')
            ->where('item.dataSet = :dataSetId')
            ->setParameter('dataSetId', $dataSetId);

        if ($tags !== []) {
            $queryBuilder
                ->andWhere(
                    'EXISTS (
                        SELECT tagFilter.id
                        FROM ' . DataSetItemTag::class . ' tagFilter
                        WHERE tagFilter.dataSetItem = item
                            AND tagFilter.name IN (:tags)
                    )'
                )
                ->setParameter('tags', $tags);
        }

        return array_column(
            $queryBuilder->getQuery()->getScalarResult(),
            'id'
        );
    }
}
