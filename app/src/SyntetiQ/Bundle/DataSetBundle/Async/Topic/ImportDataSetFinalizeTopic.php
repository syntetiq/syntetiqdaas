<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportDataSetFinalizeTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.import.data_set.finalize';
    }

    public static function getDescription(): string
    {
        return 'Import Data Set Finalize — cleans up extracted files and GCS temp after all batches complete';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'rootJobId',
                'dataSetId',
                'extractedPath',
                'gcsFileName',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('rootJobId', 'int')
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedTypes('extractedPath', 'string')
            ->addAllowedTypes('gcsFileName', 'string');
    }

    public function createJobName($messageBody): string
    {
        return sprintf(
            '%s:%d:%d',
            self::getName(),
            $messageBody['dataSetId'],
            $messageBody['rootJobId']
        );
    }
}
