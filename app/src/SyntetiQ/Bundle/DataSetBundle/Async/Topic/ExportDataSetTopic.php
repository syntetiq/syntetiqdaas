<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportDataSetTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.export.data_set';
    }

    public static function getDescription(): string
    {
        return 'Export Data Set';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['dataSetExportId'])
            ->setDefined(['exportTags'])
            ->addAllowedTypes('dataSetExportId', 'int')
            ->addAllowedTypes('exportTags', 'array')
            ->addAllowedValues('dataSetExportId', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "dataSetExportId" was expected to be not empty.');
                }

                return true;
            });
    }

    public function createJobName($messageBody): string
    {
        $dataSetExportId = $messageBody['dataSetExportId'];

        return sprintf('%s:%s', self::getName(), md5($dataSetExportId));
    }
}
