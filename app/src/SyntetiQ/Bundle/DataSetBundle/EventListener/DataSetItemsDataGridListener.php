<?php

namespace SyntetiQ\Bundle\DataSetBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter\DataSetItemLabelsFilter;
use SyntetiQ\Bundle\DataSetBundle\Datagrid\Filter\DataSetItemTagsFilter;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class DataSetItemsDataGridListener
{
    /**
     * @internal
     */
    const COLUMNS_OBJECTS = 'objects';
    private const COLUMNS_OBJECTS_JSON = 'objectsJson';
    private const LABELS_PARAMETER = 'labels';
    private const WITHOUT_LABELS_FILTER_VALUE = ':without-labels:';


    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
    ) {
    }

    public function onPreBuild(BuildBefore $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMNS_OBJECTS => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_KEY,
                ],
            ]
        );

        $dataSetId = $event->getDatagrid()->getParameters()->get('dataSetId');
        if (!$dataSetId) {
            return;
        }

        $tagChoices = $this->buildTagFilterChoices($this->getTagChoicesForDataSet((int) $dataSetId));
        $labelChoices = $this->buildChoiceMap($this->getLabelChoicesForDataSet((int) $dataSetId));

        $config->offsetSetByPath(
            '[filters][columns][tag]',
            [
                'type' => 'dataset-item-tags',
                'label' => 'syntetiq.dataset.datasetitem.grid.tags.label',
                'data_name' => 'misBuild.id',
                'options' => [
                    'field_options' => [
                        'choices' => $this->prependEmptyTagChoice($tagChoices),
                        'multiple' => true,
                        'translatable_options' => false,
                    ],
                ],
            ]
        );

        $config->offsetSetByPath(
            '[filters][columns][labels]',
            [
                'type' => 'dataset-item-labels',
                'label' => 'syntetiq.dataset.datasetitem.filter.label.label',
                'data_name' => 'misBuild.id',
                'options' => [
                    'field_options' => [
                        'choices' => $this->prependWithoutLabelsChoice(
                            $labelChoices,
                            $this->hasItemsWithoutLabels((int) $dataSetId)
                        ),
                        'multiple' => true,
                        'translatable_options' => false,
                    ],
                ],
            ]
        );
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $objectsConfiguration = $record->getValue('objectConfiguration');

            $objects = [];
            $objectLabels = [];
            /** @var ItemObjectConfiguration $objectConfiguration */
            foreach ($objectsConfiguration as $objectConfiguration) {
                $name = $this->normalizeObjectLabelName($objectConfiguration->getName());
                $colors = $this->getLabelColors($name);
                $area = $this->calculateObjectArea($objectConfiguration);
                $objects[] = [
                    'name' => $name,
                    'minX' => $objectConfiguration->getMinX(),
                    'maxX' => $objectConfiguration->getMaxX(),
                    'minY' => $objectConfiguration->getMinY(),
                    'maxY' => $objectConfiguration->getMaxY(),
                    'borderColor' => $colors['borderColor'],
                    'backgroundColor' => $colors['backgroundColor'],
                    'textColor' => $colors['textColor'],
                ];

                if (!isset($objectLabels[$name])) {
                    $objectLabels[$name] = [
                        'colors' => $colors,
                        'areas' => [],
                    ];
                }

                $objectLabels[$name]['areas'][] = $this->formatObjectArea($area);
            }

            $objectLabelsHtml = [];
            foreach ($objectLabels as $name => $labelData) {
                $colors = $labelData['colors'];
                $objectLabelsHtml[] = sprintf(
                    '<span class="sq-data-set-object-label-group" ' .
                    'style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;vertical-align:middle;">' .
                    '<span class="sq-data-set-object-label" data-object-label="%1$s" ' .
                    'style="display:inline-flex;align-items:center;min-height:22px;padding:2px 8px;' .
                    'border:1px solid %2$s;border-radius:999px;background-color:%3$s;color:%4$s;' .
                    'font-size:11px;font-weight:600;line-height:1.3;white-space:nowrap;">%1$s</span>' .
                    '<span class="sq-data-set-object-areas" ' .
                    'style="font-size:11px;font-weight:500;line-height:1.3;color:#6b7280;">%5$s</span></span>',
                    htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    $colors['borderColor'],
                    $colors['backgroundColor'],
                    $colors['textColor'],
                    htmlspecialchars(implode(', ', $labelData['areas']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                );
            }

            $record->addData([
                'dataSetId' => $record->getRootEntity()?->getDataSet()?->getId(),
                'update_link' => $this->generateUpdateLink((int) $record->getValue('id')),
                'tag' => $record->getRootEntity()?->getTagsDisplay() ?? '',
                self::COLUMNS_OBJECTS => $objectLabelsHtml
                    ? sprintf(
                        '<span class="sq-data-set-object-labels" style="display:inline-flex;flex-wrap:wrap;gap:6px;vertical-align:middle;">%s</span>',
                        implode('', $objectLabelsHtml)
                    )
                    : 'N/A',
                self::COLUMNS_OBJECTS_JSON => json_encode($objects),
            ]);
        }
    }

    public function onResultBeforeQuery(OrmResultBeforeQuery $event): void
    {
        $dataSetId = (int) $event->getDatagrid()->getParameters()->get('dataSetId');
        if ($dataSetId <= 0) {
            return;
        }

        $labelFilter = $this->parseRequestedLabels(
            $event->getDatagrid()->getParameters()->get(self::LABELS_PARAMETER, []),
            $this->getLabelChoicesForDataSet($dataSetId)
        );
        $selectedLabels = $labelFilter['labels'];
        $includeWithoutLabels = $labelFilter['includeWithoutLabels'];

        if (!$selectedLabels && !$includeWithoutLabels) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $labelConditions = [];

        if ($selectedLabels) {
            $labelConditions[] = sprintf(
                'EXISTS (
                    SELECT labelFilter.id
                    FROM %s labelFilter
                    WHERE labelFilter.dataSetItem = misBuild
                        AND labelFilter.name IN (:selectedLabels)
                )',
                ItemObjectConfiguration::class
            );
            $queryBuilder->setParameter('selectedLabels', $selectedLabels);
        }

        if ($includeWithoutLabels) {
            $labelConditions[] = sprintf(
                'NOT EXISTS (
                    SELECT unlabeledItem.id
                    FROM %s unlabeledItem
                    WHERE unlabeledItem.dataSetItem = misBuild
                )',
                ItemObjectConfiguration::class
            );
        }

        $queryBuilder->andWhere('(' . implode(' OR ', $labelConditions) . ')');
    }

    public function getActionConfiguration(ResultRecordInterface $record): array
    {
        $isReady = (bool) $record->getValue('ready');
        $hasLabels = false;
        $objectConfiguration = $record->getValue('objectConfiguration');

        if (is_countable($objectConfiguration)) {
            $hasLabels = count($objectConfiguration) > 0;
        }

        return [
            'mark_ready' => !$isReady,
            'mark_not_ready' => $isReady,
            'reset_labels' => $hasLabels,
        ];
    }

    private function getTagChoicesForDataSet(int $dataSetId): array
    {
        $rows = $this->managerRegistry
            ->getRepository(DataSetItemTag::class)
            ->createQueryBuilder('itemTag')
            ->select('DISTINCT itemTag.name AS tag')
            ->innerJoin('itemTag.dataSetItem', 'item')
            ->where('item.dataSet = :dataSetId')
            ->andWhere('itemTag.name <> :emptyTag')
            ->setParameter('dataSetId', $dataSetId)
            ->setParameter('emptyTag', '')
            ->orderBy('itemTag.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach ($rows as $row) {
            $tag = trim((string) ($row['tag'] ?? ''));
            if ($tag === '') {
                continue;
            }

            $choices[$tag] = $tag;
        }

        return $choices;
    }

    private function prependEmptyTagChoice(array $choices): array
    {
        return ['Without tags' => DataSetItemTagsFilter::EMPTY_TAG_FILTER_VALUE] + $choices;
    }

    private function buildTagFilterChoices(array $choices): array
    {
        $tagChoices = [];
        foreach ($choices as $tag) {
            $tagChoices['+ ' . $tag] = DataSetItemTagsFilter::buildIncludedChoiceValue($tag);
        }

        foreach ($choices as $tag) {
            $tagChoices['- ' . $tag] = DataSetItemTagsFilter::buildExcludedChoiceValue($tag);
        }

        return $tagChoices;
    }

    private function prependWithoutLabelsChoice(array $choices, bool $hasItemsWithoutLabels): array
    {
        if (!$hasItemsWithoutLabels) {
            return $choices;
        }

        return ['Without labels' => DataSetItemLabelsFilter::WITHOUT_LABELS_FILTER_VALUE] + $choices;
    }

    private function buildChoiceMap(array $values): array
    {
        $choices = [];
        foreach ($values as $value) {
            $choices[$value] = $value;
        }

        return $choices;
    }

    private function getLabelChoicesForDataSet(int $dataSetId): array
    {
        $rows = $this->managerRegistry
            ->getRepository(ItemObjectConfiguration::class)
            ->createQueryBuilder('config')
            ->select('DISTINCT config.name AS name')
            ->innerJoin('config.dataSetItem', 'item')
            ->where('item.dataSet = :dataSetId')
            ->andWhere('config.name IS NOT NULL')
            ->andWhere('config.name <> :emptyLabel')
            ->setParameter('dataSetId', $dataSetId)
            ->setParameter('emptyLabel', '')
            ->orderBy('config.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $choices = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['name'] ?? ''));
            if ($label === '') {
                continue;
            }

            $choices[] = $label;
        }

        return $choices;
    }

    private function hasItemsWithoutLabels(int $dataSetId): bool
    {
        $result = $this->managerRegistry
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('item.id')
            ->leftJoin('item.objectConfiguration', 'objectConfiguration')
            ->where('item.dataSet = :dataSetId')
            ->andWhere('objectConfiguration.id IS NULL')
            ->setParameter('dataSetId', $dataSetId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    private function parseRequestedLabels(mixed $labels, array $allowedLabels): array
    {
        $labels = is_array($labels) ? $labels : [$labels];
        $selectedLabels = [];
        $includeWithoutLabels = false;

        foreach ($labels as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if ($value === self::WITHOUT_LABELS_FILTER_VALUE) {
                $includeWithoutLabels = true;

                continue;
            }

            if (!in_array($value, $allowedLabels, true) || in_array($value, $selectedLabels, true)) {
                continue;
            }

            $selectedLabels[] = $value;
        }

        return [
            'labels' => $selectedLabels,
            'includeWithoutLabels' => $includeWithoutLabels,
        ];
    }

    private function getLabelColors(string $label): array
    {
        $normalized = $label !== '' ? $label : 'N/A';
        $hash = 2166136261;

        foreach (preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $hash ^= mb_ord($char, 'UTF-8');
            $hash = ($hash * 16777619) & 0xffffffff;
        }

        $hue = $hash % 360;
        $saturation = 62 + (($hash >> 9) % 24);
        $lightness = 34 + (($hash >> 17) % 18);
        $backgroundLightness = 90 + (($hash >> 25) % 6);

        return [
            'borderColor' => sprintf('hsl(%d, %d%%, %d%%)', $hue, $saturation, $lightness),
            'backgroundColor' => sprintf(
                'hsla(%d, %d%%, %d%%, 0.95)',
                $hue,
                min($saturation + 8, 92),
                $backgroundLightness
            ),
            'textColor' => sprintf(
                'hsl(%d, %d%%, %d%%)',
                $hue,
                max($saturation - 6, 52),
                max($lightness - 18, 18)
            ),
        ];
    }

    private function generateUpdateLink(int $itemId): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $params = ['id' => $itemId];

        if ($request !== null) {
            $params['returnUrl'] = $request->getUri();
        }

        return $this->urlGenerator->generate('syntetiq_model_data_set_item_edit', $params);
    }

    private function normalizeObjectLabelName(?string $name): string
    {
        $label = trim((string) $name);

        return $label !== '' ? $label : 'N/A';
    }

    private function calculateObjectArea(ItemObjectConfiguration $objectConfiguration): int
    {
        $width = max(0, $objectConfiguration->getMaxX() - $objectConfiguration->getMinX());
        $height = max(0, $objectConfiguration->getMaxY() - $objectConfiguration->getMinY());

        return $width * $height;
    }

    private function formatObjectArea(int $area): string
    {
        return sprintf('%s px²', number_format($area, 0, '.', ' '));
    }
}
