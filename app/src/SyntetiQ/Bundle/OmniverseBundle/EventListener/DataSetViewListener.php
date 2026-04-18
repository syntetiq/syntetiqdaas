<?php

namespace SyntetiQ\Bundle\OmniverseBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\OmniverseBundle\Condition\IsOmniverseActive;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataSetViewListener
{
    public function __construct(
        private readonly IsOmniverseActive $isOmniverseActive,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function onBeforeDataSetView(BeforeListRenderEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof DataSet || !$this->isOmniverseActive->isConditionAllowed([])) {
            return;
        }

        $scrollData = $event->getScrollData();
        $blockId = 'syntetiq_omniverse_generate_images_requests';
        $scrollData->addNamedBlock(
            $blockId,
            $this->translator->trans('syntetiq.omniverse.generateimagesrequest.entity_plural_label')
        );

        $subBlockId = $scrollData->addSubBlock($blockId);
        $html = $event->getEnvironment()->render(
            '@SyntetiQOmniverse/DataSet/generate_images_requests.html.twig',
            [
                'entity' => $entity,
            ]
        );

        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
        $event->setScrollData($scrollData);
    }
}
