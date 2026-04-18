<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportDataSetBatchTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.import.data_set.batch';
    }

    public static function getDescription(): string
    {
        return 'Import Data Set Batch — processes a slice of items from an extracted archive';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'rootJobId',
                'dataSetId',
                'extractedPath',
                'datasetRoot',
                'startIndex',
                'endIndex',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('rootJobId', 'int')
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedTypes('extractedPath', 'string')
            ->addAllowedTypes('datasetRoot', 'string')
            ->addAllowedTypes('startIndex', 'int')
            ->addAllowedTypes('endIndex', 'int')
            ->setDefined(['tag', 'sourceType', 'sourceIntegrationId'])
            ->addAllowedTypes('tag', ['string', 'null'])
            ->addAllowedTypes('sourceType', ['string', 'null'])
            ->addAllowedTypes('sourceIntegrationId', ['int', 'null']);
    }

    public function createJobName($messageBody): string
    {
        return sprintf(
            '%s:%d:%d:%d',
            self::getName(),
            $messageBody['dataSetId'],
            $messageBody['rootJobId'],
            $messageBody['startIndex']
        );
    }
}
