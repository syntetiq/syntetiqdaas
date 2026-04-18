<?php

namespace SyntetiQ\Bundle\ModelBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SyncModelBuildArtifactsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'syntetiq.model.build.sync_artifacts';
    }

    public static function getDescription(): string
    {
        return 'Sync model build runtime artifacts from local workdir into processing artifact storage';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['buildId'])
            ->addAllowedTypes('buildId', 'int')
            ->addAllowedValues('buildId', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "buildId" was expected to be not empty.');
                }

                return true;
            });
    }

    public function createJobName($messageBody): string
    {
        return sprintf('%s:%s', self::getName(), md5((string) $messageBody['buildId']));
    }
}
