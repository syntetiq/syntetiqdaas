<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportDataSetTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.import.data_set';
    }

    public static function getDescription(): string
    {
        return 'Import Data Set';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['fileName', 'dataSetId'])
            ->addAllowedTypes('fileName', 'string')
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedValues('fileName', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "fileName" was expected to be not empty.');
                }

                return true;
            })
            ->setDefined(['tag', 'sourceType', 'sourceIntegrationId', 'requestId'])
            ->addAllowedTypes('tag', ['string', 'null'])
            ->addAllowedTypes('sourceType', ['string', 'null'])
            ->addAllowedTypes('sourceIntegrationId', ['int', 'null'])
            ->addAllowedTypes('requestId', ['string', 'null']);
    }
 
    public function createJobName($messageBody): string
    {
        $fileName = $messageBody['fileName'];
        $requestId = $messageBody['requestId'] ?? '';
 
        return sprintf('%s:%d:%s', self::getName(), $messageBody['dataSetId'], md5($fileName . $requestId));
    }
}
