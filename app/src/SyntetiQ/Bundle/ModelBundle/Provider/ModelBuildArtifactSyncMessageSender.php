<?php

namespace SyntetiQ\Bundle\ModelBundle\Provider;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use SyntetiQ\Bundle\ModelBundle\Async\Topic\SyncModelBuildArtifactsTopic;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class ModelBuildArtifactSyncMessageSender
{
    public function __construct(
        private MessageProducerInterface $messageProducer
    ) {}

    public function send(ModelBuild $modelBuild): void
    {
        $buildId = $modelBuild->getId();
        if (!$buildId) {
            return;
        }

        $this->messageProducer->send(SyncModelBuildArtifactsTopic::getName(), [
            'buildId' => (int) $buildId,
        ]);
    }
}
