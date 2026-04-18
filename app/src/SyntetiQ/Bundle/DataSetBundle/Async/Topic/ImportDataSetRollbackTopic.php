<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
 
class ImportDataSetRollbackTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.import.data_set.rollback';
    }

    public static function getDescription(): string
    {
        return 'Import Data Set Rollback — orchestrates deletion of partially-imported items after a failed import';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['importId', 'dataSetId', 'itemCount'])
            ->addAllowedTypes('importId', 'int')
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedTypes('itemCount', 'int');
    }
 
    public function createJobName($messageBody): string
    {
        return sprintf('%s:%d', self::getName(), $messageBody['importId']);
    }
}
