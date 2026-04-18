<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;

class DataSetItemTagsFilter extends ChoiceFilter
{
    public const EMPTY_TAG_FILTER_VALUE = ':empty:';
    private const INCLUDED_TAG_PREFIX = ':has:';
    private const EXCLUDED_TAG_PREFIX = ':not:';

    public static function buildIncludedChoiceValue(string $tag): string
    {
        return self::INCLUDED_TAG_PREFIX . rawurlencode($tag);
    }

    public static function buildExcludedChoiceValue(string $tag): string
    {
        return self::EXCLUDED_TAG_PREFIX . rawurlencode($tag);
    }

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return null;
        }

        $selectedValues = $data['value'] ?? [];
        $selectedValues = is_array($selectedValues) ? $selectedValues : [$selectedValues];
        $selectedValues = array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $selectedValues
        ))));

        $includeWithoutTag = in_array(self::EMPTY_TAG_FILTER_VALUE, $selectedValues, true);
        $includedTags = [];
        $excludedTags = [];
        $hasExplicitTagModes = false;

        foreach ($selectedValues as $value) {
            if ($value === self::EMPTY_TAG_FILTER_VALUE) {
                continue;
            }

            $parsedValue = $this->parseSelectedTagValue($value);
            if ($parsedValue === null) {
                continue;
            }

            if ($parsedValue['explicit']) {
                $hasExplicitTagModes = true;
            }

            if ($parsedValue['mode'] === 'exclude') {
                $excludedTags[] = $parsedValue['tag'];
            } else {
                $includedTags[] = $parsedValue['tag'];
            }
        }

        $includedTags = array_values(array_unique($includedTags));
        $excludedTags = array_values(array_unique($excludedTags));

        if (!$includedTags && !$excludedTags && !$includeWithoutTag) {
            return null;
        }

        $alias = strtok((string) $fieldName, '.');
        if (!$alias) {
            return null;
        }

        if (!$hasExplicitTagModes && !$excludedTags) {
            return $this->buildLegacyExpression($ds, $comparisonType, $alias, $includedTags, $includeWithoutTag);
        }

        $conditions = [];
        $baseConditions = [];

        if ($includedTags) {
            $tagParameter = $ds->generateParameterName($this->getName() . '_has_tags');
            $ds->setParameter($tagParameter, $includedTags);

            $baseConditions[] = sprintf(
                'EXISTS (
                    SELECT selectedTag.id
                    FROM %s selectedTag
                    WHERE selectedTag.dataSetItem = %s
                        AND selectedTag.name IN (:%s)
                )',
                DataSetItemTag::class,
                $alias,
                $tagParameter
            );
        }

        if ($includeWithoutTag) {
            $baseConditions[] = sprintf(
                'NOT EXISTS (
                    SELECT emptyTag.id
                    FROM %s emptyTag
                    WHERE emptyTag.dataSetItem = %s
                )',
                DataSetItemTag::class,
                $alias
            );
        }

        if ($baseConditions) {
            $conditions[] = count($baseConditions) === 1
                ? $baseConditions[0]
                : '(' . implode(' OR ', $baseConditions) . ')';
        }

        if ($excludedTags) {
            $excludedTagParameter = $ds->generateParameterName($this->getName() . '_not_tags');
            $ds->setParameter($excludedTagParameter, $excludedTags);

            $conditions[] = sprintf(
                'NOT EXISTS (
                    SELECT excludedTag.id
                    FROM %s excludedTag
                    WHERE excludedTag.dataSetItem = %s
                        AND excludedTag.name IN (:%s)
                )',
                DataSetItemTag::class,
                $alias,
                $excludedTagParameter
            );
        }

        if (!$conditions) {
            return null;
        }

        return count($conditions) === 1 ? $conditions[0] : '(' . implode(' AND ', $conditions) . ')';
    }

    private function parseSelectedTagValue(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, self::INCLUDED_TAG_PREFIX)) {
            $tag = trim(rawurldecode(substr($value, strlen(self::INCLUDED_TAG_PREFIX))));

            return $tag === ''
                ? null
                : ['mode' => 'include', 'tag' => $tag, 'explicit' => true];
        }

        if (str_starts_with($value, self::EXCLUDED_TAG_PREFIX)) {
            $tag = trim(rawurldecode(substr($value, strlen(self::EXCLUDED_TAG_PREFIX))));

            return $tag === ''
                ? null
                : ['mode' => 'exclude', 'tag' => $tag, 'explicit' => true];
        }

        return ['mode' => 'include', 'tag' => $value, 'explicit' => false];
    }
    private function buildLegacyExpression(
        OrmFilterDatasourceAdapter $ds,
        mixed $comparisonType,
        string $alias,
        array $selectedTags,
        bool $includeWithoutTag
    ): ?string {
        $conditions = [];

        if ($selectedTags) {
            $tagParameter = $ds->generateParameterName($this->getName() . '_tags');
            $ds->setParameter($tagParameter, $selectedTags);

            $conditions[] = sprintf(
                'EXISTS (
                    SELECT selectedTag.id
                    FROM %s selectedTag
                    WHERE selectedTag.dataSetItem = %s
                        AND selectedTag.name IN (:%s)
                )',
                DataSetItemTag::class,
                $alias,
                $tagParameter
            );
        }

        if ($includeWithoutTag) {
            $conditions[] = sprintf(
                'NOT EXISTS (
                    SELECT emptyTag.id
                    FROM %s emptyTag
                    WHERE emptyTag.dataSetItem = %s
                )',
                DataSetItemTag::class,
                $alias
            );
        }

        if (!$conditions) {
            return null;
        }

        if (count($conditions) === 1) {
            return $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
                ? 'NOT (' . $conditions[0] . ')'
                : $conditions[0];
        }

        return '(' . implode(
            $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS ? ' AND ' : ' OR ',
            $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
                ? array_map(static fn (string $condition): string => 'NOT (' . $condition . ')', $conditions)
                : $conditions
        ) . ')';
    }
}
