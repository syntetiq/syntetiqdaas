<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataSetQaTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.data_set_qa';
    }

    public static function getDescription(): string
    {
        return 'Recalculate Data Set QA';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['dataSetId'])
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedValues('dataSetId', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "dataSetId" was expected to be not empty.');
                }

                return true;
            });
    }

    public function createJobName($messageBody): string
    {
        return sprintf('%s:%s', self::getName(), md5((string) $messageBody['dataSetId']));
    }
}
