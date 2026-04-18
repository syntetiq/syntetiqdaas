<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;

class DataSetItemLabelResetter
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function reset(DataSetItem $dataSetItem): bool
    {
        if (!$dataSetItem->hasObjectConfigurations()) {
            return false;
        }

        $dataSetItem->clearObjectConfiguration();
        $dataSetItem->touch();

        $manager = $this->registry->getManagerForClass(DataSetItem::class);
        $manager->persist($dataSetItem);

        return true;
    }
}
