<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemoveDataSetItemsBatchTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.remove_data_set_items_by_tag.batch';
    }

    public static function getDescription(): string
    {
        return 'Remove Data Set Items by Tag — Batch (processes a slice of item IDs)';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['jobId', 'dataSetId', 'itemIds'])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedTypes('itemIds', 'array');
    }

    public function createJobName($messageBody): string
    {
        return sprintf(
            '%s:%d:%s',
            self::getName(),
            $messageBody['dataSetId'],
            implode('_', $messageBody['itemIds'])
        );
    }
}
