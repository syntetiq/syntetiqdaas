<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemoveDataSetItemsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.remove_data_set_items_by_tag';
    }

    public static function getDescription(): string
    {
        return 'Remove Data Set Items by Tag';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['dataSetId'])
            ->setDefined(['tags'])
            ->addAllowedTypes('dataSetId', 'int')
            ->addAllowedTypes('tags', 'array')
            ->addAllowedValues('dataSetId', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "dataSetId" was expected to be not empty.');
                }

                return true;
            });
    }

    public function createJobName($messageBody): string
    {
        $dataSetId = $messageBody['dataSetId'];
        $tagsStr = implode(',', $messageBody['tags'] ?? []);

        return sprintf('%s:%s', self::getName(), md5($dataSetId . '_' . $tagsStr . '_' . uniqid('', true)));
    }
}
