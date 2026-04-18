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
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class RenameLabelMassActionHandler implements MassActionHandlerInterface
{
    private const OLD_LABEL_KEY = 'oldLabelValue';
    private const NEW_LABEL_KEY = 'newLabelValue';

    public function __construct(
        private ManagerRegistry $registry,
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $oldLabelValue = trim((string) ($args->getData()[self::OLD_LABEL_KEY] ?? ''));
        $newLabelValue = trim((string) ($args->getData()[self::NEW_LABEL_KEY] ?? ''));

        if ($oldLabelValue === '') {
            return new MassActionResponse(false, $this->translator->trans('Current label is required.'));
        }

        if ($newLabelValue === '') {
            return new MassActionResponse(false, $this->translator->trans('New label is required.'));
        }

        if ($oldLabelValue === $newLabelValue) {
            return new MassActionResponse(false, $this->translator->trans('New label must differ from current label.'));
        }

        $manager = $this->registry->getManagerForClass(DataSetItem::class);
        $updatedItemCount = 0;
        $renamedLabelCount = 0;

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

            $itemUpdated = false;

            foreach ($item->getObjectConfiguration() as $objectConfiguration) {
                if (!$objectConfiguration instanceof ItemObjectConfiguration) {
                    continue;
                }

                if (trim((string) $objectConfiguration->getName()) !== $oldLabelValue) {
                    continue;
                }

                $objectConfiguration->setName($newLabelValue);
                $manager->persist($objectConfiguration);
                $renamedLabelCount++;
                $itemUpdated = true;
            }

            if (!$itemUpdated) {
                continue;
            }

            $item->touch();
            $manager->persist($item);
            $updatedItemCount++;
        }

        if ($renamedLabelCount > 0) {
            $manager->flush();
        }

        return new MassActionResponse(
            true,
            $this->translator->trans(
                $renamedLabelCount > 0
                    ? 'Renamed label "%old%" to "%new%" on %count% item(s).'
                    : 'No selected items contain label "%old%".',
                [
                    '%old%' => $oldLabelValue,
                    '%new%' => $newLabelValue,
                    '%count%' => $updatedItemCount,
                ]
            ),
            [
                'count' => $updatedItemCount,
                'labelsRenamed' => $renamedLabelCount,
            ]
        );
    }
}
