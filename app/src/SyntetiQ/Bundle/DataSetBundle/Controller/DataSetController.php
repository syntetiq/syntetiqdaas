<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use Symfony\Component\Routing\Attribute\Route;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Form\Type\DataSetType;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaReportViewBuilder;
use Gaufrette\StreamMode;

class DataSetController extends AbstractController
{
    private const WITHOUT_LABELS_FILTER_VALUE = ':without-labels:';
    private const WITHOUT_LABELS_FILTER_LABEL = 'Without labels';

    public function __construct(
        private DatasetQaReportViewBuilder $datasetQaReportViewBuilder,
        private DatasetQaStorageManager $datasetQaStorageManager,
        private ManagerRegistry $doctrine
    ) {}

    #[Route(path: '/', name: 'syntetiq_model_data_set_index')]
    #[Template('@SyntetiQDataSet/DataSet/index.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_view")]
    public function indexAction()
    {
        return [
            'gridName' => 'syntetiq-model-data-set-grid',
        ];
    }

    #[Route(path: '/view/{id}', name: 'syntetiq_model_data_set_view', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSet/view.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_view")]
    public function viewAction(Request $request, DataSet $dataSet)
    {
        $jobNamePrefix = sprintf('%s:%d:', ImportDataSetTopic::getName(), $dataSet->getId());

        /** @var JobEntity[] $jobEntities */
        $jobEntities = $this->doctrine
            ->getManagerForClass(JobEntity::class)
            ->getRepository(JobEntity::class)
            ->createQueryBuilder('j')
            ->where('j.rootJob IS NULL')
            ->andWhere('j.name LIKE :prefix')
            ->setParameter('prefix', $jobNamePrefix . '%')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $activeImports = array_map(static function (JobEntity $job): array {
            $data = $job->getData() ?? [];
            return [
                'id'        => $job->getId(),
                'status'    => $job->getStatus(),
                'progress'  => $job->getJobProgress(),
                'itemCount' => $data['itemCount'] ?? null,
                'fileName'  => $data['fileName'] ?? null,
                'startedAt' => $job->getStartedAt()?->format('H:i d/m'),
                'stoppedAt' => $job->getStoppedAt()?->format('H:i d/m'),
            ];
        }, $jobEntities);

        return [
            'entity'    => $dataSet,
            'summary'   => $this->getDataSetSummary($dataSet),
            'datasetQa' => $this->datasetQaReportViewBuilder->buildForDataSet($dataSet),
            'imports'   => $activeImports,
        ];
    }

    #[Route('/view/{id}/dataset-qa/file', name: 'syntetiq_model_data_set_dataset_qa_file', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[AclAncestor('syntetiq_model_data_set_view')]
    public function datasetQaFileAction(Request $request, DataSet $dataSet): StreamedResponse
    {
        $relativePath = trim((string) $request->query->get('path', ''));
        if ($relativePath === '') {
            throw new NotFoundHttpException();
        }

        $stream = $this->datasetQaStorageManager->getStream(DatasetQaPaths::getDataSetStorageDir($dataSet), $relativePath);
        if (null === $stream) {
            throw new NotFoundHttpException();
        }

        return $this->createQaFileResponse($stream, $relativePath, $request->query->getBoolean('download'));
    }

    #[Route(path: '/{id}/items', name: 'syntetiq_model_data_set_items', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSet/items.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_view")]
    public function itemsAction(DataSet $dataSet): array
    {
        return [
            'entity' => $dataSet,
            'exportTagOptions' => $this->getTagChoicesForDataSet($dataSet),
        ];
    }

    #[Route(path: '/{id}/gallery', name: 'syntetiq_model_data_set_gallery', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSet/gallery.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_view")]
    public function galleryAction(Request $request, DataSet $dataSet): array
    {
        $allowedPerPage = [3, 5, 10, 15, 20, 25, 50, 100];
        $allowedGroups = ['train', 'val', 'test'];
        $allowedSourceTypes = ['manual', 'omniverse'];
        $labelOptions = $this->getLabelChoicesForDataSet($dataSet);
        $hasItemsWithoutLabels = $this->hasItemsWithoutLabels($dataSet);
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = $request->query->getInt('perPage', 15);
        $perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 15;
        $search = trim((string) $request->query->get('search', ''));
        $fileName = trim((string) $request->query->get('fileName', ''));
        $group = trim((string) $request->query->get('group', ''));
        $group = in_array($group, $allowedGroups, true) ? $group : '';
        $tag = trim((string) $request->query->get('tag', ''));
        $sourceType = trim((string) $request->query->get('sourceType', ''));
        $sourceType = in_array($sourceType, $allowedSourceTypes, true) ? $sourceType : '';
        $labelFilter = $this->parseRequestedLabels(
            $this->getRequestedLabels($request, [], false),
            $labelOptions
        );
        $labels = $labelFilter['labels'];
        $selectedLabelFilters = $labelFilter['values'];
        $includeItemsWithoutLabels = $labelFilter['includeWithoutLabels'];

        /** @var EntityManagerInterface $em */
        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(DataSetItem::class);

        $tagRows = $em->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('DISTINCT itemTag.name AS tag')
            ->innerJoin('item.itemTags', 'itemTag')
            ->where('item.dataSet = :dataSet')
            ->andWhere('itemTag.name <> :emptyTag')
            ->setParameter('dataSet', $dataSet)
            ->setParameter('emptyTag', '')
            ->orderBy('itemTag.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $tagOptions = array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['tag'] ?? ''));
        }, $tagRows)));

        $qb = $em->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->leftJoin('item.image', 'image')
            ->addSelect('image')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet)
            ->orderBy('item.id', 'ASC');

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
            $qb
                ->andWhere('item.group = :group')
                ->setParameter('group', $group);
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
            $qb
                ->andWhere('item.sourceType = :sourceType')
                ->setParameter('sourceType', $sourceType);
        }

        if ($labels || $includeItemsWithoutLabels) {
            $labelConditions = [];

            if ($labels) {
                $labelConditions[] = sprintf(
                    'EXISTS (
                        SELECT labelFilter.id
                        FROM %s labelFilter
                        WHERE labelFilter.dataSetItem = item
                            AND labelFilter.name IN (:labels)
                    )',
                    ItemObjectConfiguration::class
                );
                $qb->setParameter('labels', $labels);
            }

            if ($includeItemsWithoutLabels) {
                $labelConditions[] = sprintf(
                    'NOT EXISTS (
                        SELECT unlabeledItem.id
                        FROM %s unlabeledItem
                        WHERE unlabeledItem.dataSetItem = item
                    )',
                    ItemObjectConfiguration::class
                );
            }

            $qb->andWhere('(' . implode(' OR ', $labelConditions) . ')');
        }

        $countQb = clone $qb;
        $totalItems = (int) $countQb
            ->select('COUNT(item.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'entity' => $dataSet,
            'items' => $items,
            'galleryPagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'perPageOptions' => $allowedPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'search' => $search,
                'filters' => [
                    'search' => $search,
                    'fileName' => $fileName,
                    'group' => $group,
                    'tag' => $tag,
                    'sourceType' => $sourceType,
                    'labels' => $selectedLabelFilters,
                ],
                'options' => [
                    'tags' => $tagOptions,
                    'labels' => $labelOptions,
                    'hasItemsWithoutLabels' => $hasItemsWithoutLabels,
                    'withoutLabelsFilterValue' => self::WITHOUT_LABELS_FILTER_VALUE,
                    'withoutLabelsFilterLabel' => self::WITHOUT_LABELS_FILTER_LABEL,
                ],
            ],
        ];
    }

    #[Route(path: '/delete/{id}', name: 'syntetiq_model_data_set_delete', requirements: ['id' => '\d+'])]
    #[AclAncestor("syntetiq_model_data_set_delete")]
    #[CsrfProtection]
    public function deleteAction(DataSet $entity): JsonResponse
    {
        $itemCount = $entity->getItems()->count();
        if ($itemCount > 0) {
            return new JsonResponse([
                'message' => $this->container->get(TranslatorInterface::class)->trans('syntetiq.controller.dataset.cannot_delete_has_items')
            ], Response::HTTP_BAD_REQUEST);
        }

        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(DataSet::class);
        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    private function getLabelChoicesForDataSet(DataSet $dataSet): array
    {
        return $this->container
            ->get(ManagerRegistry::class)
            ->getManagerForClass(ItemObjectConfiguration::class)
            ->getRepository(ItemObjectConfiguration::class)
            ->getDistinctNamesByDataSet($dataSet);
    }

    private function getTagChoicesForDataSet(DataSet $dataSet): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(DataSetItemTag::class);

        $rows = $em->getRepository(DataSetItemTag::class)
            ->createQueryBuilder('itemTag')
            ->select('DISTINCT itemTag.name AS tag')
            ->innerJoin('itemTag.dataSetItem', 'item')
            ->where('item.dataSet = :dataSet')
            ->andWhere('itemTag.name <> :emptyTag')
            ->setParameter('dataSet', $dataSet)
            ->setParameter('emptyTag', '')
            ->orderBy('itemTag.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static function (array $row): string {
            return trim((string) ($row['tag'] ?? ''));
        }, $rows)));
    }

    private function getDataSetSummary(DataSet $dataSet): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(DataSetItem::class);
        $repository = $em->getRepository(DataSetItem::class);
        $modelRows = $this->container
            ->get(ManagerRegistry::class)
            ->getManagerForClass(Model::class)
            ->getRepository(Model::class)
            ->createQueryBuilder('model')
            ->select('model.id AS id, model.name AS name')
            ->where('model.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet)
            ->orderBy('model.name', 'ASC')
            ->addOrderBy('model.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $totalItems = (int) $repository
            ->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet)
            ->getQuery()
            ->getSingleScalarResult();

        $groupRows = $repository
            ->createQueryBuilder('item')
            ->select('item.group AS itemGroup, COUNT(item.id) AS itemCount')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataSet)
            ->groupBy('item.group')
            ->getQuery()
            ->getArrayResult();

        $groupCounts = [
            'train' => 0,
            'val' => 0,
            'test' => 0,
        ];

        foreach ($groupRows as $row) {
            $group = (string) ($row['itemGroup'] ?? '');
            if (!array_key_exists($group, $groupCounts)) {
                continue;
            }

            $groupCounts[$group] = (int) ($row['itemCount'] ?? 0);
        }

        $readyItems = (int) $repository
            ->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->where('item.dataSet = :dataSet')
            ->andWhere('item.ready = :ready')
            ->setParameter('dataSet', $dataSet)
            ->setParameter('ready', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalItems' => $totalItems,
            'trainItems' => $groupCounts['train'],
            'validateItems' => $groupCounts['val'],
            'testItems' => $groupCounts['test'],
            'readyItems' => $readyItems,
            'labels' => $this->getLabelChoicesForDataSet($dataSet),
            'tags' => $this->getTagChoicesForDataSet($dataSet),
            'models' => array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => trim((string) ($row['name'] ?? '')),
                ];
            }, $modelRows),
        ];
    }

    private function hasItemsWithoutLabels(DataSet $dataSet): bool
    {
        $result = $this->container
            ->get(ManagerRegistry::class)
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->select('item.id')
            ->leftJoin('item.objectConfiguration', 'objectConfiguration')
            ->where('item.dataSet = :dataSet')
            ->andWhere('objectConfiguration.id IS NULL')
            ->setParameter('dataSet', $dataSet)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    private function parseRequestedLabels(mixed $labels, array $labelOptions): array
    {
        $labels = is_array($labels) ? $labels : [$labels];
        $selectedLabels = [];
        $selectedValues = [];
        $includeWithoutLabels = false;

        foreach ($labels as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if ($value === self::WITHOUT_LABELS_FILTER_VALUE) {
                if (!$includeWithoutLabels) {
                    $includeWithoutLabels = true;
                    $selectedValues[] = $value;
                }

                continue;
            }

            if (!in_array($value, $labelOptions, true) || in_array($value, $selectedLabels, true)) {
                continue;
            }

            $selectedLabels[] = $value;
            $selectedValues[] = $value;
        }

        return [
            'labels' => $selectedLabels,
            'values' => $selectedValues,
            'includeWithoutLabels' => $includeWithoutLabels,
        ];
    }

    private function getRequestedLabels(Request $request, array $gridFilters, bool $allowGridFallback): array
    {
        if ($request->query->has('labels')) {
            $labels = $request->query->all()['labels'] ?? [];

            return is_array($labels) ? $labels : [$labels];
        }

        if (!$allowGridFallback) {
            return [];
        }

        return $this->extractGridFilterValues($gridFilters, 'labels');
    }

    private function getRequestedFilterValue(
        Request $request,
        array $gridFilters,
        string $filterName,
        bool $allowGridFallback
    ): string {
        if ($request->query->has($filterName)) {
            $value = $request->query->get($filterName, '');

            return is_scalar($value) ? trim((string) $value) : '';
        }

        if (!$allowGridFallback) {
            return '';
        }

        return $this->extractGridFilterValue($gridFilters, $filterName);
    }

    private function extractGridFilterValue(array $gridFilters, string $filterName): string
    {
        $filter = $gridFilters[$filterName] ?? null;
        if (!is_array($filter)) {
            return '';
        }

        $value = $filter['value'] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function extractGridFilterValues(array $gridFilters, string $filterName): array
    {
        $filter = $gridFilters[$filterName] ?? null;
        if (!is_array($filter) || !array_key_exists('value', $filter)) {
            return [];
        }

        $value = $filter['value'];
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    #[Route(path: '/{id}/edit', name: 'syntetiq_model_data_set_edit', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSet/edit.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_edit")]
    public function editAction(Request $request, DataSet $dataSet)
    {
        return $this->update($dataSet, $request);
    }

    #[Route(path: '/create', name: 'syntetiq_model_data_set_create', options: ['expose' => true])]
    #[Template('@SyntetiQDataSet/DataSet/edit.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_create")]
    public function createAction(Request $request)
    {
        return $this->update(new DataSet(), $request);
    }

    /**
     * @param DataSet $dataSet
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(DataSet $dataSet, Request $request)
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $dataSet,
            DataSetType::class,
            $this->container->get(TranslatorInterface::class)->trans('syntetiq.controller.dataset.saved.message'),
            $request
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                FileManager::class,
                ManagerRegistry::class,
            ]
        );
    }

    private function createQaFileResponse(\Gaufrette\Stream $stream, string $relativePath, bool $download): StreamedResponse
    {
        $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'txt', 'log', 'err', 'json', 'csv'];
        $disposition = ($download || !in_array($extension, $inlineExtensions, true))
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;
        $contentType = match ($extension) {
            'log', 'err', 'txt' => 'text/plain; charset=UTF-8',
            'json' => 'application/json',
            'csv' => 'text/csv; charset=UTF-8',
            default => MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream',
        };

        $response = new StreamedResponse(static function () use ($stream) {
            $stream->open(new StreamMode('rb'));
            while (!$stream->eof()) {
                echo $stream->read(8192);
            }
        });
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, basename($relativePath)));

        return $response;
    }
}
