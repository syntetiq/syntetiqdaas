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

class SetReadyMassActionHandler implements MassActionHandlerInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private TranslatorInterface $translator,
        private bool $readyState
    ) {
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
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

            if ($item->isReady() === $this->readyState) {
                continue;
            }

            $item->setReady($this->readyState);
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
                $this->readyState
                    ? 'Selected items marked ready. Updated %count% item(s).'
                    : 'Selected items marked not ready. Updated %count% item(s).',
                ['%count%' => $updatedCount]
            ),
            ['count' => $updatedCount]
        );
    }
}
