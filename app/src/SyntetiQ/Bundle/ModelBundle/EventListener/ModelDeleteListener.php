<?php

namespace SyntetiQ\Bundle\ModelBundle\EventListener;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use SyntetiQ\Bundle\ModelBundle\Async\Topic\CleanModelBuildFilesTopic;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class ModelDeleteListener
{
    public function __construct(
        private MessageProducerInterface $messageProducer
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof ModelBuild) {
            $this->dispatchCleanup($entity);
        } elseif ($entity instanceof Model) {
            foreach ($entity->getModelBuilds() as $build) {
                $this->dispatchCleanup($build);
            }
        }
    }

    private function dispatchCleanup(ModelBuild $build): void
    {
        $this->messageProducer->send(CleanModelBuildFilesTopic::getName(), [
            'modelId' => $build->getModel()->getId(),
            'buildId' => $build->getId(),
        ]);
    }
}
