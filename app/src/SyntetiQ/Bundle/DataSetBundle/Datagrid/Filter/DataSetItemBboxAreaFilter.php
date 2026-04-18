<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class DataSetItemBboxAreaFilter extends NumberRangeFilter
{
    #[\Override]
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata[FilterUtility::TYPE_KEY] = 'number-range';

        return $metadata;
    }

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter || !is_array($data)) {
            return null;
        }

        $rootAlias = $this->extractRootAlias((string) $fieldName);
        if ($rootAlias === '') {
            return null;
        }

        $comparisonType = is_numeric($comparisonType) ? (int) $comparisonType : $comparisonType;
        $subQuery = $ds->createQueryBuilder()
            ->select('bboxFilter.id')
            ->from(ItemObjectConfiguration::class, 'bboxFilter')
            ->where(sprintf('bboxFilter.dataSetItem = %s', $rootAlias));

        if ($comparisonType === FilterUtility::TYPE_EMPTY) {
            return $ds->expr()->not($ds->expr()->exists($subQuery->getDQL()));
        }

        if ($comparisonType === FilterUtility::TYPE_NOT_EMPTY) {
            return $ds->expr()->exists($subQuery->getDQL());
        }

        if (($data['value'] ?? null) === null && ($data['value_end'] ?? null) === null) {
            return null;
        }

        $areaExpression = '(bboxFilter.maxX - bboxFilter.minX) * (bboxFilter.maxY - bboxFilter.minY)';
        $comparisonExpression = $this->buildRangeComparisonExpr(
            $ds,
            $comparisonType,
            $areaExpression,
            $data['value'] ?? null,
            $data['value_end'] ?? null
        );

        if (!$comparisonExpression) {
            return null;
        }

        $subQuery->andWhere($comparisonExpression);

        return $ds->expr()->exists($subQuery->getDQL());
    }

    private function extractRootAlias(string $fieldName): string
    {
        $fieldPath = explode('.', $fieldName, 2);

        return $fieldPath[0] ?: 'misBuild';
    }
}
