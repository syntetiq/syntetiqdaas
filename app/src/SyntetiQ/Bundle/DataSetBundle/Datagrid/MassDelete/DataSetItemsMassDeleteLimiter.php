<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\MassDelete;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimiter;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

class DataSetItemsMassDeleteLimiter extends MassDeleteLimiter
{
    private const DEFAULT_MAX_DELETE_RECORDS = 100;
    private const DATASET_ITEMS_GRID_NAME = 'syntetiq-model-data-set-items-grid';
    private const DATASET_ITEMS_MAX_DELETE_RECORDS = 1000;

    public function getLimitResult(MassActionHandlerArgs $args)
    {
        $query = $args->getResults()->getSource();
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }
        $queryForDelete = $this->aclHelper->apply($this->cloneQuery($query), 'DELETE');

        return new MassDeleteLimitResult(
            QueryCountCalculator::calculateCount($query),
            QueryCountCalculator::calculateCount($queryForDelete),
            $this->getMaxDeleteRecords($args)
        );
    }

    private function getMaxDeleteRecords(MassActionHandlerArgs $args): int
    {
        $datagrid = $args->getDatagrid();
        if ($datagrid && $datagrid->getName() === self::DATASET_ITEMS_GRID_NAME) {
            return self::DATASET_ITEMS_MAX_DELETE_RECORDS;
        }

        return self::DEFAULT_MAX_DELETE_RECORDS;
    }
}
