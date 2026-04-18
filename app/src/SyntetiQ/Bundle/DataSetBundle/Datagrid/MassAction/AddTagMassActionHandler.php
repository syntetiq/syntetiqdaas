<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\MassAction;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;

class AddTagMassActionHandler implements MassActionHandlerInterface
{
    private const TAG_VALUE_KEY = 'tagValue';

    public function __construct(
        private ManagerRegistry $registry,
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $tagValue = trim((string) ($args->getData()[self::TAG_VALUE_KEY] ?? ''));
        if ($tagValue === '') {
            return new MassActionResponse(
                false,
                $this->translator->trans('Tag is required.')
            );
        }

        $manager = $this->registry->getManagerForClass(DataSetItem::class);
        $updatedCount = 0;

        foreach ($args->getResults() as $result) {
            if (!$result instanceof ResultRecordInterface) {
                continue;
            }

            $item = $result->getRootEntity();
            if (!$item instanceof DataSetItem) {
                $itemId = (int) $result->getValue('id');
                $item = $itemId > 0 ? $manager->find(DataSetItem::class, $itemId) : null;
            }

            if (!$item instanceof DataSetItem) {
                continue;
            }

            if (!$item->addTag($tagValue)) {
                continue;
            }

            $item->touch();
            $manager->persist($item);
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $manager->flush();
        }

        return new MassActionResponse(
            true,
            $this->translator->trans(
                $updatedCount > 0
                    ? 'Tag "%tag%" added to %count% item(s).'
                    : 'All selected items already contain tag "%tag%".',
                [
                    '%tag%' => $tagValue,
                    '%count%' => $updatedCount,
                ]
            ),
            ['count' => $updatedCount]
        );
    }
}
