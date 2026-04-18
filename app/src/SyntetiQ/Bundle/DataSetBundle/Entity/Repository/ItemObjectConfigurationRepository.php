<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class ItemObjectConfigurationRepository extends EntityRepository
{
    public function getDistinctNamesByDataSet(DataSet $dataSet): array
    {
        $rows = $this->createQueryBuilder('config')
            ->select('DISTINCT config.name AS name')
            ->innerJoin('config.dataSetItem', 'item')
            ->andWhere('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet)
            ->orderBy('config.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['name'] ?? '')),
            $rows
        )));
    }
}
