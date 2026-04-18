<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class DataSetItemLabelsFilter extends ChoiceFilter
{
    public const WITHOUT_LABELS_FILTER_VALUE = ':without-labels:';

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return null;
        }

        $selectedValues = $data['value'] ?? [];
        $selectedValues = is_array($selectedValues) ? $selectedValues : [$selectedValues];
        $selectedValues = array_values(array_filter(
            array_map(
                static fn ($value): string => trim((string) $value),
                $selectedValues
            ),
            static fn (string $value): bool => $value !== ''
        ));

        $includeWithoutLabels = in_array(self::WITHOUT_LABELS_FILTER_VALUE, $selectedValues, true);
        $selectedLabels = array_values(array_filter(
            $selectedValues,
            static fn (string $value): bool => $value !== self::WITHOUT_LABELS_FILTER_VALUE
        ));

        if (!$selectedLabels && !$includeWithoutLabels) {
            return null;
        }

        $rootAlias = $this->extractRootAlias($fieldName);
        $conditions = [];

        if ($selectedLabels) {
            $labelsParameter = $ds->generateParameterName($this->getName() . '_labels');
            $ds->setParameter($labelsParameter, $selectedLabels);

            $labelSubQuery = $ds->createQueryBuilder()
                ->select('labelFilter.id')
                ->from(ItemObjectConfiguration::class, 'labelFilter')
                ->where(sprintf('labelFilter.dataSetItem = %s', $rootAlias))
                ->andWhere(sprintf('labelFilter.name IN (:%s)', $labelsParameter));

            $labelExistsExpr = $ds->expr()->exists($labelSubQuery->getDQL());
            $conditions[] = $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
                ? $ds->expr()->not($labelExistsExpr)
                : $labelExistsExpr;
        }

        if ($includeWithoutLabels) {
            $unlabeledSubQuery = $ds->createQueryBuilder()
                ->select('unlabeledFilter.id')
                ->from(ItemObjectConfiguration::class, 'unlabeledFilter')
                ->where(sprintf('unlabeledFilter.dataSetItem = %s', $rootAlias));

            $hasLabelsExpr = $ds->expr()->exists($unlabeledSubQuery->getDQL());
            $conditions[] = $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
                ? $hasLabelsExpr
                : $ds->expr()->not($hasLabelsExpr);
        }

        if (!$conditions) {
            return null;
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
            ? $ds->expr()->andX(...$conditions)
            : $ds->expr()->orX(...$conditions);
    }

    private function extractRootAlias(string $fieldName): string
    {
        $fieldPath = explode('.', $fieldName, 2);

        return $fieldPath[0] ?: 'misBuild';
    }
}
