<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter\DataSetItemLabelsFilter;
use SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter\DataSetItemTagsFilter;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class DataSetItemNavigationResolver
{
    private const VIEW_MODE_GALLERY = 'gallery';
    private const VIEW_MODE_GRID = 'grid';
    private const GRID_NAME = 'syntetiq-model-data-set-items-grid';
    private const ALLOWED_GROUPS = ['train', 'val', 'test'];
    private const ALLOWED_SOURCE_TYPES = ['manual', 'omniverse'];
    private const GRID_SORT_MAP = [
        'id' => 'item.id',
        'ready' => 'item.ready',
        'fileName' => 'image.originalFilename',
        'imgWidth' => 'item.imgWidth',
        'imgHeight' => 'item.imgHeight',
        'updatedAt' => 'item.updatedAt',
    ];

    private array $orderedIdsCache = [];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getSiblingAction(DataSetItem $currentItem, ?string $returnUrl, int $direction): ?array
    {
        $itemId = $currentItem->getId();
        $dataSet = $currentItem->getDataSet();
        if (!$itemId || !$dataSet instanceof DataSet) {
            return null;
        }

        $orderedIds = $this->getOrderedItemIds($dataSet, $returnUrl);
        $targetItemId = $this->findSiblingItemId(
            $orderedIds,
            $this->getAnchorOrderedItemIds($dataSet, $returnUrl),
            $itemId,
            $direction
        );
        if ($targetItemId === null) {
            return null;
        }

        return [
            'route' => 'syntetiq_model_data_set_item_edit',
            'params' => [
                'id' => $targetItemId,
                '_enableContentProviders' => 'mainMenu',
                'returnUrl' => $returnUrl,
            ],
        ];
    }

    /**
     * @return int[]
     */
    public function getOrderedItemIds(DataSet $dataSet, ?string $returnUrl): array
    {
        $cacheKey = $dataSet->getId() . '|' . ($returnUrl ?? '');
        if (array_key_exists($cacheKey, $this->orderedIdsCache)) {
            return $this->orderedIdsCache[$cacheKey];
        }

        $context = $this->resolveContext($dataSet, $returnUrl);
        $qb = $this->managerRegistry
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('item.id')
            ->leftJoin('item.image', 'image')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet);

        if ($context['viewMode'] === self::VIEW_MODE_GRID) {
            $this->applyGridFilters($qb, $context['query']);
            $this->applyGridSorting($qb, $context['query']);
        } else {
            $this->applyGalleryFilters($qb, $context['query']);
            $qb->orderBy('item.id', 'ASC');
        }

        $rows = $qb->getQuery()->getScalarResult();
        $orderedIds = array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        );

        return $this->orderedIdsCache[$cacheKey] = array_values(array_filter($orderedIds));
    }

    /**
     * @return int[]
     */
    private function getAnchorOrderedItemIds(DataSet $dataSet, ?string $returnUrl): array
    {
        $cacheKey = $dataSet->getId() . '|anchor|' . ($returnUrl ?? '');
        if (array_key_exists($cacheKey, $this->orderedIdsCache)) {
            return $this->orderedIdsCache[$cacheKey];
        }

        $context = $this->resolveContext($dataSet, $returnUrl);
        $qb = $this->managerRegistry
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('item.id')
            ->leftJoin('item.image', 'image')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet);

        if ($context['viewMode'] === self::VIEW_MODE_GRID) {
            $this->applyGridFilters($qb, $context['query'], ['group', 'ready']);
            $this->applyGridSorting($qb, $context['query']);
        } else {
            $this->applyGalleryFilters($qb, $context['query'], ['group']);
            $qb->orderBy('item.id', 'ASC');
        }

        $rows = $qb->getQuery()->getScalarResult();
        $orderedIds = array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        );

        return $this->orderedIdsCache[$cacheKey] = array_values(array_filter($orderedIds));
    }

    private function resolveContext(DataSet $dataSet, ?string $returnUrl): array
    {
        $defaultContext = [
            'viewMode' => self::VIEW_MODE_GALLERY,
            'query' => [],
        ];

        if (!$returnUrl || trim($returnUrl) === '') {
            return $defaultContext;
        }

        $parts = parse_url($returnUrl);
        if ($parts === false) {
            return $defaultContext;
        }

        $expectedPath = parse_url(
            $this->urlGenerator->generate('syntetiq_model_data_set_view', ['id' => $dataSet->getId()]),
            PHP_URL_PATH
        );
        $expectedGalleryPath = parse_url(
            $this->urlGenerator->generate('syntetiq_model_data_set_gallery', ['id' => $dataSet->getId()]),
            PHP_URL_PATH
        );
        $expectedItemsPath = parse_url(
            $this->urlGenerator->generate('syntetiq_model_data_set_items', ['id' => $dataSet->getId()]),
            PHP_URL_PATH
        );
        $path = $parts['path'] ?? null;

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($this->isGridRequestPath($path, $query, $dataSet)) {
            return [
                'viewMode' => self::VIEW_MODE_GRID,
                'query' => $this->resolveGridQueryContext(is_array($query) ? $query : []),
            ];
        }

        if ($expectedItemsPath && $path === $expectedItemsPath) {
            return [
                'viewMode' => self::VIEW_MODE_GRID,
                'query' => $this->resolveGridQueryContext(is_array($query) ? $query : []),
            ];
        }

        if ($expectedGalleryPath && $path === $expectedGalleryPath) {
            return [
                'viewMode' => self::VIEW_MODE_GALLERY,
                'query' => is_array($query) ? $query : [],
            ];
        }

        if (!$expectedPath || !$path || $path !== $expectedPath) {
            return $defaultContext;
        }

        $viewMode = (string) ($query['itemsView'] ?? self::VIEW_MODE_GALLERY);
        if (!in_array($viewMode, [self::VIEW_MODE_GALLERY, self::VIEW_MODE_GRID], true)) {
            $viewMode = self::VIEW_MODE_GALLERY;
        }

        return [
            'viewMode' => $viewMode,
            'query' => $viewMode === self::VIEW_MODE_GRID
                ? $this->resolveGridQueryContext(is_array($query) ? $query : [])
                : (is_array($query) ? $query : []),
        ];
    }

    private function applyGalleryFilters(QueryBuilder $qb, array $query, array $ignoredFilters = []): void
    {
        $search = $this->sanitizeScalar($query['search'] ?? '');
        $fileName = $this->sanitizeScalar($query['fileName'] ?? '');
        $group = in_array('group', $ignoredFilters, true)
            ? ''
            : $this->sanitizeEnum($query['group'] ?? '', self::ALLOWED_GROUPS);
        $tag = $this->sanitizeScalar($query['tag'] ?? '');
        $sourceType = $this->sanitizeEnum($query['sourceType'] ?? '', self::ALLOWED_SOURCE_TYPES);

        if ($search !== '') {
            $qb
                ->andWhere(
                    'LOWER(COALESCE(image.originalFilename, \'\')) LIKE :search
                    OR LOWER(COALESCE(item.group, \'\')) LIKE :search
                    OR EXISTS (
                        SELECT searchTag.id
                        FROM ' . DataSetItemTag::class . ' searchTag
                        WHERE searchTag.dataSetItem = item
                            AND LOWER(searchTag.name) LIKE :search
                    )'
                )
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        } elseif ($fileName !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(image.originalFilename, \'\')) LIKE :fileName')
                ->setParameter('fileName', '%' . mb_strtolower($fileName) . '%');
        }

        if ($group !== '') {
            $qb->andWhere('item.group = :group')->setParameter('group', $group);
        }

        if ($tag !== '') {
            $qb
                ->andWhere(
                    'EXISTS (
                        SELECT filterTag.id
                        FROM ' . DataSetItemTag::class . ' filterTag
                        WHERE filterTag.dataSetItem = item
                            AND LOWER(filterTag.name) LIKE :tag
                    )'
                )
                ->setParameter('tag', '%' . mb_strtolower($tag) . '%');
        }

        if ($sourceType !== '') {
            $qb->andWhere('item.sourceType = :sourceType')->setParameter('sourceType', $sourceType);
        }

        $labelValues = $this->normalizeArray($query['labels'] ?? []);
        $this->applyLabelFilter($qb, $labelValues, 'item');
    }

    private function applyGridFilters(QueryBuilder $qb, array $query, array $ignoredFilters = []): void
    {
        $gridFilters = isset($query['_filter']) && is_array($query['_filter']) ? $query['_filter'] : [];

        $fileName = $this->extractGridFilterValue($gridFilters, 'fileName');
        if ($fileName !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(image.originalFilename, \'\')) LIKE :fileName')
                ->setParameter('fileName', '%' . mb_strtolower($fileName) . '%');
        }

        $group = in_array('group', $ignoredFilters, true)
            ? ''
            : $this->sanitizeEnum($this->extractGridFilterValue($gridFilters, 'group'), self::ALLOWED_GROUPS);
        if ($group !== '') {
            $qb->andWhere('item.group = :group')->setParameter('group', $group);
        }

        $ready = in_array('ready', $ignoredFilters, true)
            ? null
            : $this->extractGridBooleanFilterValue($gridFilters, 'ready');
        if ($ready !== null) {
            $qb->andWhere('item.ready = :ready')->setParameter('ready', $ready);
        }

        $sourceType = $this->sanitizeEnum(
            $this->extractGridFilterValue($gridFilters, 'sourceType'),
            self::ALLOWED_SOURCE_TYPES
        );
        if ($sourceType !== '') {
            $qb->andWhere('item.sourceType = :sourceType')->setParameter('sourceType', $sourceType);
        }

        $tagValues = $this->normalizeArray($this->extractGridFilterValues($gridFilters, 'tag'));
        $this->applyTagFilter($qb, $tagValues);

        $labelValues = $this->normalizeArray($this->extractGridFilterValues($gridFilters, 'labels'));
        $this->applyLabelFilter($qb, $labelValues, 'item');
    }

    private function applyGridSorting(QueryBuilder $qb, array $query): void
    {
        $sortBy = isset($query['_sort_by']) && is_array($query['_sort_by']) ? $query['_sort_by'] : [];
        $field = null;
        $direction = 'DESC';

        foreach ($sortBy as $sortField => $sortDirection) {
            if (!array_key_exists($sortField, self::GRID_SORT_MAP)) {
                continue;
            }

            $field = self::GRID_SORT_MAP[$sortField];
            $direction = strtoupper((string) $sortDirection) === 'ASC' ? 'ASC' : 'DESC';
            break;
        }

        if ($field === null) {
            $field = self::GRID_SORT_MAP['id'];
            $direction = 'DESC';
        }

        $qb->orderBy($field, $direction);

        if ($field !== self::GRID_SORT_MAP['id']) {
            $qb->addOrderBy('item.id', $direction);
        }
    }

    private function applyTagFilter(QueryBuilder $qb, array $selectedValues): void
    {
        $includeWithoutTag = in_array(DataSetItemTagsFilter::EMPTY_TAG_FILTER_VALUE, $selectedValues, true);
        $selectedTags = array_values(array_filter(
            $selectedValues,
            static fn (string $value): bool => $value !== DataSetItemTagsFilter::EMPTY_TAG_FILTER_VALUE
        ));

        if (!$selectedTags && !$includeWithoutTag) {
            return;
        }

        $conditions = [];
        if ($selectedTags) {
            $conditions[] = sprintf(
                'EXISTS (
                    SELECT selectedTag.id
                    FROM %s selectedTag
                    WHERE selectedTag.dataSetItem = item
                        AND selectedTag.name IN (:selectedTags)
                )',
                DataSetItemTag::class
            );
            $qb->setParameter('selectedTags', $selectedTags);
        }

        if ($includeWithoutTag) {
            $conditions[] = sprintf(
                'NOT EXISTS (
                    SELECT emptyTag.id
                    FROM %s emptyTag
                    WHERE emptyTag.dataSetItem = item
                )',
                DataSetItemTag::class
            );
        }

        $qb->andWhere('(' . implode(' OR ', $conditions) . ')');
    }

    private function applyLabelFilter(QueryBuilder $qb, array $selectedValues, string $itemAlias): void
    {
        $includeWithoutLabels = in_array(DataSetItemLabelsFilter::WITHOUT_LABELS_FILTER_VALUE, $selectedValues, true);
        $selectedLabels = array_values(array_filter(
            $selectedValues,
            static fn (string $value): bool => $value !== DataSetItemLabelsFilter::WITHOUT_LABELS_FILTER_VALUE
        ));

        if (!$selectedLabels && !$includeWithoutLabels) {
            return;
        }

        $conditions = [];
        if ($selectedLabels) {
            $conditions[] = sprintf(
                'EXISTS (
                    SELECT labelFilter.id
                    FROM %s labelFilter
                    WHERE labelFilter.dataSetItem = %s
                        AND labelFilter.name IN (:selectedLabels)
                )',
                ItemObjectConfiguration::class,
                $itemAlias
            );
            $qb->setParameter('selectedLabels', $selectedLabels);
        }

        if ($includeWithoutLabels) {
            $conditions[] = sprintf(
                'NOT EXISTS (
                    SELECT unlabeledItem.id
                    FROM %s unlabeledItem
                    WHERE unlabeledItem.dataSetItem = %s
                )',
                ItemObjectConfiguration::class,
                $itemAlias
            );
        }

        $qb->andWhere('(' . implode(' OR ', $conditions) . ')');
    }

    private function sanitizeScalar(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function sanitizeEnum(mixed $value, array $allowed): string
    {
        $value = $this->sanitizeScalar($value);

        return in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * @return string[]
     */
    private function normalizeArray(mixed $values): array
    {
        $values = is_array($values) ? $values : [$values];

        $normalized = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }

            $normalized[] = $value;
        }

        return $normalized;
    }

    private function extractGridFilterValue(array $gridFilters, string $filterName): string
    {
        $filter = $gridFilters[$filterName] ?? null;
        if (!is_array($filter)) {
            return '';
        }

        return $this->sanitizeScalar($filter['value'] ?? '');
    }

    /**
     * @return string[]
     */
    private function extractGridFilterValues(array $gridFilters, string $filterName): array
    {
        $filter = $gridFilters[$filterName] ?? null;
        if (!is_array($filter) || !array_key_exists('value', $filter)) {
            return [];
        }

        return $this->normalizeArray($filter['value']);
    }

    private function resolveGridQueryContext(array $query): array
    {
        $resolved = $query;
        $directGridState = $query[self::GRID_NAME] ?? null;

        if (is_array($directGridState)) {
            if (!isset($resolved['_filter']) && isset($directGridState['_filter']) && is_array($directGridState['_filter'])) {
                $resolved['_filter'] = $directGridState['_filter'];
            }

            if (!isset($resolved['_sort_by']) && isset($directGridState['_sort_by']) && is_array($directGridState['_sort_by'])) {
                $resolved['_sort_by'] = $directGridState['_sort_by'];
            }
        }

        $gridState = $query['grid'][self::GRID_NAME] ?? null;

        if (!is_string($gridState) || trim($gridState) === '') {
            return $resolved;
        }

        parse_str($gridState, $parsedGridState);
        if (!is_array($parsedGridState)) {
            return $resolved;
        }

        if (
            (!isset($resolved['_filter']) || !is_array($resolved['_filter']))
            && isset($parsedGridState['f'])
            && is_array($parsedGridState['f'])
        ) {
            $resolved['_filter'] = $parsedGridState['f'];
        }

        if (
            (!isset($resolved['_sort_by']) || !is_array($resolved['_sort_by']))
            && isset($parsedGridState['s'])
            && is_array($parsedGridState['s'])
        ) {
            $resolved['_sort_by'] = $this->normalizeGridSortState($parsedGridState['s']);
        }

        return $resolved;
    }

    private function normalizeGridSortState(array $sortState): array
    {
        $normalized = [];

        foreach ($sortState as $field => $direction) {
            $direction = is_scalar($direction) ? trim((string) $direction) : '';
            if ($direction === '') {
                continue;
            }

            $normalized[$field] = str_starts_with($direction, '-') ? 'ASC' : 'DESC';
        }

        return $normalized;
    }

    private function extractGridBooleanFilterValue(array $gridFilters, string $filterName): ?bool
    {
        $rawValue = $this->extractGridFilterValue($gridFilters, $filterName);
        if ($rawValue === '') {
            return null;
        }

        return match ($rawValue) {
            '1', 'true', 'yes' => true,
            '2', '0', 'false', 'no' => false,
            default => null,
        };
    }

    private function isGridRequestPath(?string $path, array $query, DataSet $dataSet): bool
    {
        if (!is_string($path) || !str_starts_with($path, '/datagrid/')) {
            return false;
        }

        $directGridState = $query[self::GRID_NAME] ?? null;
        if (!is_array($directGridState)) {
            return false;
        }

        return (int) ($directGridState['dataSetId'] ?? 0) === (int) $dataSet->getId();
    }

    private function findSiblingItemId(array $orderedIds, array $anchorIds, int $currentItemId, int $direction): ?int
    {
        $currentIndex = array_search($currentItemId, $orderedIds, true);
        if ($currentIndex !== false) {
            $targetIndex = $currentIndex + $direction;

            return $orderedIds[$targetIndex] ?? null;
        }

        $anchorIndex = array_search($currentItemId, $anchorIds, true);
        if ($anchorIndex === false) {
            return null;
        }

        $visibleIds = array_flip($orderedIds);
        for ($index = $anchorIndex + $direction; isset($anchorIds[$index]); $index += $direction) {
            $candidateId = $anchorIds[$index];
            if (isset($visibleIds[$candidateId])) {
                return $candidateId;
            }
        }

        return null;
    }
}
