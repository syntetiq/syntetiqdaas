<?php

namespace SyntetiQ\Bundle\ModelBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CleanModelBuildFilesTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'syntetiq.model.build.clean_files';
    }

    public static function getDescription(): string
    {
        return 'Clean model build files (workdir, tensorboard, dataset QA)';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['modelId', 'buildId'])
            ->addAllowedTypes('modelId', 'int')
            ->addAllowedTypes('buildId', 'int');
    }
}
