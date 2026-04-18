<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\RemoveDataSetItemsTopic;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Entity\Repository\ItemObjectConfigurationRepository;
use SyntetiQ\Bundle\DataSetBundle\Form\Handler\DataSetItemHandler;
use SyntetiQ\Bundle\DataSetBundle\Form\Type\DataSetItemType;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemLabelResetter;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemNavigationResolver;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemSamSegmentationService;

class DataSetItemController extends AbstractController
{
    #[Route(path: '/{id}/edit', name: 'syntetiq_model_data_set_item_edit', requirements: ['id' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSetItem/edit.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function editAction(Request $request, DataSetItem $dataSetItem)
    {
        $response = $this->update($dataSetItem, $request);
        $response = $this->preserveReturnUrlOnRedirect($response, $request);

        return $this->appendTemplateData($response, $request, $dataSetItem->getDataSet(), $dataSetItem);
    }

    #[Route(
        path: '/{id}/editor-state',
        name: 'syntetiq_model_data_set_item_editor_state',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function editorStateAction(Request $request, DataSetItem $dataSetItem): JsonResponse
    {
        $dataSet = $dataSetItem->getDataSet();
        if (!$dataSet instanceof DataSet) {
            return new JsonResponse(['message' => 'Data set item is not attached to a data set.'], Response::HTTP_BAD_REQUEST);
        }

        $returnUrl = $this->resolveReturnUrl($request, $dataSet);

        return new JsonResponse(
            $this->buildEditorStatePayload($dataSetItem, $returnUrl, $this->getDatasetTitleOptions($dataSet))
        );
    }

    #[Route(
        path: '/{id}/segment-click',
        name: 'syntetiq_model_data_set_item_segment_click',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function segmentClickAction(Request $request, DataSetItem $dataSetItem): JsonResponse
    {
        $payload = $this->parseJsonRequest($request);
        if ($payload === null) {
            return new JsonResponse(['message' => 'Invalid JSON request body.'], Response::HTTP_BAD_REQUEST);
        }

        $xPct = $this->normalizePercent($payload['xPct'] ?? null);
        $yPct = $this->normalizePercent($payload['yPct'] ?? null);
        if ($xPct === null || $yPct === null) {
            return new JsonResponse(['message' => 'xPct and yPct must be numeric values between 0 and 100.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->container
                ->get(DataSetItemSamSegmentationService::class)
                ->segmentClick($dataSetItem, $xPct, $yPct);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(
                [
                    'message' => 'Automatic segmentation failed.',
                    'details' => $e->getMessage(),
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        return new JsonResponse($result);
    }

    #[Route(path: '/{dataSetId}/create', name: 'syntetiq_model_data_set_item_create', requirements: ['dataSetId' => '\d+'])]
    #[Template('@SyntetiQDataSet/DataSetItem/edit.html.twig')]
    #[AclAncestor("syntetiq_model_data_set_item_create")]
    public function createAction(
        Request $request,
        #[MapEntity(class: DataSet::class, id: 'dataSetId')] DataSet $dataSet
    )
    {
        $dataSetItem = new DataSetItem();
        $dataSetItem->setDataSet($dataSet);
        $response = $this->update($dataSetItem, $request);
        $response = $this->preserveReturnUrlOnRedirect($response, $request);

        return $this->appendTemplateData($response, $request, $dataSet);
    }

    #[Route(path: 'delete/{id}', name: 'syntetiq_model_data_set_item_delete', requirements: ["id" => "[-\d\w]+"], methods: ['DELETE'])]
    #[AclAncestor("syntetiq_model_data_set_item_delete")]
    #[CsrfProtection]
    public function deleteAction(DataSetItem $entity)
    {
        $em = $this->container->get(DoctrineHelper::class)->getEntityManagerForClass(DataSetItem::class);
        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    #[Route(path: '/{dataSetId}/remove-by-tag', name: 'syntetiq_model_data_set_item_remove_by_tag', requirements: ['dataSetId' => '\d+'], methods: ['POST'])]
    #[AclAncestor('syntetiq_model_data_set_item_delete')]
    public function queueRemoveItemsByTagAction(Request $request, #[MapEntity(class: DataSet::class, id: 'dataSetId')] DataSet $dataSet): JsonResponse
    {
        $payload = $this->parseJsonRequest($request) ?? [];
        $tags = isset($payload['tags']) && is_array($payload['tags']) ? $payload['tags'] : [];

        $this->container->get(MessageProducerInterface::class)->send(
            RemoveDataSetItemsTopic::getName(),
            [
                'dataSetId' => $dataSet->getId(),
                'tags' => $tags,
            ]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Items are set to be removed in the background.',
        ]);
    }

    #[Route(
        path: '/{id}/editor-delete',
        name: 'syntetiq_model_data_set_item_editor_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true]
    )]
    #[AclAncestor('syntetiq_model_data_set_item_delete')]
    public function editorDeleteAction(Request $request, DataSetItem $entity): JsonResponse
    {
        $dataSet = $entity->getDataSet();
        if (!$dataSet instanceof DataSet) {
            return new JsonResponse(['message' => 'Item is not attached to a data set.'], Response::HTTP_BAD_REQUEST);
        }

        $returnUrl = $this->resolveRequestedReturnUrl($request, $dataSet);
        $redirectUrl = $this->resolvePostDeleteRedirectUrl($entity, $returnUrl);

        $em = $this->container->get(DoctrineHelper::class)->getEntityManagerForClass(DataSetItem::class);
        $em->remove($entity);
        $em->flush();

        return new JsonResponse([
            'successful' => true,
            'message' => 'Item deleted.',
            'redirectUrl' => $redirectUrl,
        ]);
    }

    #[Route(
        path: '/{id}/mark-ready',
        name: 'syntetiq_model_data_set_item_mark_ready',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function markReadyAction(DataSetItem $entity): JsonResponse
    {
        return $this->updateReadyState($entity, true);
    }

    #[Route(
        path: '/{id}/mark-not-ready',
        name: 'syntetiq_model_data_set_item_mark_not_ready',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function markNotReadyAction(DataSetItem $entity): JsonResponse
    {
        return $this->updateReadyState($entity, false);
    }

    #[Route(
        path: '/{id}/reset-labels',
        name: 'syntetiq_model_data_set_item_reset_labels',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function resetLabelsAction(DataSetItem $entity): JsonResponse
    {
        /** @var DataSetItemLabelResetter $labelResetter */
        $labelResetter = $this->container->get(DataSetItemLabelResetter::class);
        $updated = $labelResetter->reset($entity);

        if ($updated) {
            $this->container->get(DoctrineHelper::class)
                ->getEntityManagerForClass(DataSetItem::class)
                ->flush();
        }

        return new JsonResponse([
            'successful' => true,
            'message' => $updated ? 'Item labels reset.' : 'Item has no labels to reset.',
        ]);
    }

    #[Route(
        path: '/{id}/inline-update-tags',
        name: 'syntetiq_model_data_set_item_inline_update_tags',
        requirements: ['id' => '\d+'],
        methods: ['PATCH'],
        options: ['expose' => true]
    )]
    #[AclAncestor("syntetiq_model_data_set_item_edit")]
    public function inlineUpdateTagsAction(Request $request, DataSetItem $entity): JsonResponse
    {
        $payload = $this->parseJsonRequest($request);
        $tags = is_array($payload) && array_key_exists('tag', $payload)
            ? $this->parseTagsPayload($payload['tag'])
            : null;

        if ($tags === null) {
            return new JsonResponse([
                'errors' => [
                    'children' => [
                        'tag' => [
                            'errors' => ['Tags value has invalid format.'],
                        ],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $entity->setTags($tags);
        $entity->touch();

        $em = $this->container->get(DoctrineHelper::class)->getEntityManagerForClass(DataSetItem::class);
        $em->persist($entity);
        $em->flush();

        return new JsonResponse([
            'fields' => [
                'tag' => $entity->getTagsDisplay() ?? '',
            ],
        ]);
    }

    /**
     * @param DataSetItem $dataSetItem
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(DataSetItem $dataSetItem, Request $request)
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $dataSetItem,
            DataSetItemType::class,
            $this->container->get(TranslatorInterface::class)->trans('syntetiq.controller.data_set_item.saved.message'),
            $request,
            $this->container->get(DataSetItemHandler::class)
        );
    }

    private function appendTemplateData(
        array|RedirectResponse $response,
        Request $request,
        ?DataSet $dataSet,
        ?DataSetItem $currentItem = null
    ): array|RedirectResponse
    {
        if (!$dataSet || !is_array($response)) {
            return $response;
        }

        $returnUrl = $this->resolveReturnUrl($request, $dataSet);
        $response['titleOptions'] = $this->getDatasetTitleOptions($dataSet);
        $response['returnUrl'] = $returnUrl;
        $response['previousItemAction'] = $this->getSiblingItemAction($currentItem, -1, $returnUrl);
        $response['nextItemAction'] = $this->getSiblingItemAction($currentItem, 1, $returnUrl);

        return $response;
    }

    private function getDatasetTitleOptions(DataSet $dataSet): array
    {
        /** @var ItemObjectConfigurationRepository $repository */
        $repository = $this->container->get(DoctrineHelper::class)
            ->getEntityRepository(ItemObjectConfiguration::class);

        return $repository->getDistinctNamesByDataSet($dataSet);
    }

    private function getSiblingItemAction(?DataSetItem $currentItem, int $direction, string $returnUrl): ?array
    {
        if (!$currentItem || !$currentItem->getId()) {
            return null;
        }

        return $this->container
            ->get(DataSetItemNavigationResolver::class)
            ->getSiblingAction($currentItem, $returnUrl, $direction);
    }

    private function buildEditorStatePayload(DataSetItem $dataSetItem, string $returnUrl, array $titleOptions): array
    {
        return [
            'itemId' => $dataSetItem->getId(),
            'editUrl' => $this->generateUrl(
                'syntetiq_model_data_set_item_edit',
                [
                    'id' => $dataSetItem->getId(),
                    'returnUrl' => $returnUrl,
                    '_enableContentProviders' => 'mainMenu',
                ],
                UrlGeneratorInterface::ABSOLUTE_PATH
            ),
            'returnUrl' => $returnUrl,
            'imageSrc' => $this->getItemImageUrl($dataSetItem),
            'hiddenObjectInfo' => $this->buildHiddenObjectInfo($dataSetItem),
            'annotations' => $this->buildAnnotations($dataSetItem),
            'canDeleteItem' => $this->canDeleteItem(),
            'groupOptions' => $this->getGroupOptions(),
            'groupValue' => $dataSetItem->getGroup() ?: 'train',
            'readyValue' => $dataSetItem->isReady(),
            'titleOptions' => $titleOptions,
            'previousItemAction' => $this->getSiblingItemAction($dataSetItem, -1, $returnUrl),
            'nextItemAction' => $this->getSiblingItemAction($dataSetItem, 1, $returnUrl),
        ];
    }

    private function getGroupOptions(): array
    {
        return [
            ['value' => 'train', 'label' => 'Train'],
            ['value' => 'val', 'label' => 'Validation'],
            ['value' => 'test', 'label' => 'Test'],
        ];
    }

    private function canDeleteItem(): bool
    {
        return $this->isGranted('syntetiq_model_data_set_item_delete');
    }

    private function getItemImageUrl(DataSetItem $dataSetItem): ?string
    {
        $image = $dataSetItem->getImage();

        return $image ? $this->container->get(AttachmentManager::class)->getFileUrl($image) : null;
    }

    private function buildHiddenObjectInfo(DataSetItem $dataSetItem): array
    {
        return [
            'imgWidth' => $dataSetItem->getImgWidth(),
            'imgHeight' => $dataSetItem->getImgHeight(),
            'areas' => $this->buildAreas($dataSetItem),
        ];
    }

    private function buildAnnotations(DataSetItem $dataSetItem): array
    {
        $imgWidth = $dataSetItem->getImgWidth();
        $imgHeight = $dataSetItem->getImgHeight();

        return array_map(static function (array $area) use ($imgWidth, $imgHeight): array {
            $annotation = [
                ...$area,
                'title' => $area['name'] ?? '',
            ];

            if ($imgWidth > 0 && $imgHeight > 0) {
                $annotation['x'] = ($area['x'] / $imgWidth) * 100;
                $annotation['y'] = ($area['y'] / $imgHeight) * 100;
                $annotation['width'] = ($area['width'] / $imgWidth) * 100;
                $annotation['height'] = ($area['height'] / $imgHeight) * 100;
            }

            return $annotation;
        }, $this->buildAreas($dataSetItem));
    }

    private function buildAreas(DataSetItem $dataSetItem): array
    {
        $areas = [];

        /** @var ItemObjectConfiguration $item */
        foreach ($dataSetItem->getObjectConfiguration() as $index => $item) {
            $areas[] = [
                'id' => sprintf('area-%d-%d', $dataSetItem->getId() ?? 0, $index),
                'x' => $item->getMinX(),
                'y' => $item->getMinY(),
                'width' => $item->getMaxX() - $item->getMinX(),
                'height' => $item->getMaxY() - $item->getMinY(),
                'name' => $item->getName() ?: '',
                'index' => $index,
            ];
        }

        return $areas;
    }

    private function updateReadyState(DataSetItem $entity, bool $ready): JsonResponse
    {
        $entity->setReady($ready);
        $entity->touch();

        $em = $this->container->get(DoctrineHelper::class)->getEntityManagerForClass(DataSetItem::class);
        $em->persist($entity);
        $em->flush();

        return new JsonResponse([
            'successful' => true,
            'message' => $ready ? 'Item marked ready.' : 'Item marked not ready.',
        ]);
    }

    private function resolveReturnUrl(Request $request, DataSet $dataSet): string
    {
        $requestedReturnUrl = $request->query->get('returnUrl');
        if (is_string($requestedReturnUrl)) {
            $normalizedReturnUrl = $this->normalizeReturnUrl($requestedReturnUrl, $request);
            if ($normalizedReturnUrl !== null) {
                return $normalizedReturnUrl;
            }
        }

        $derivedReturnUrl = $this->deriveReturnUrlFromCurrentRequest($request, $dataSet);
        if ($derivedReturnUrl !== null) {
            return $derivedReturnUrl;
        }

        $referer = $request->headers->get('referer');
        if (is_string($referer)) {
            $normalizedReferer = $this->normalizeReturnUrl($referer, $request);
            if ($normalizedReferer !== null) {
                return $normalizedReferer;
            }
        }

        return $this->generateUrl('syntetiq_model_data_set_items', [
            'id' => $dataSet->getId(),
            '_enableContentProviders' => 'mainMenu',
        ]);
    }

    private function resolveRequestedReturnUrl(Request $request, DataSet $dataSet): string
    {
        $payload = $this->parseJsonRequest($request) ?? [];
        $requestedReturnUrl = is_array($payload) ? ($payload['returnUrl'] ?? null) : null;
        if (is_string($requestedReturnUrl)) {
            $normalizedReturnUrl = $this->normalizeReturnUrl($requestedReturnUrl, $request);
            if ($normalizedReturnUrl !== null) {
                return $normalizedReturnUrl;
            }
        }

        return $this->resolveReturnUrl($request, $dataSet);
    }

    private function resolvePostDeleteRedirectUrl(DataSetItem $dataSetItem, string $returnUrl): string
    {
        $nextAction = $this->getSiblingItemAction($dataSetItem, 1, $returnUrl);
        if ($nextAction) {
            return $this->generateUrl(
                $nextAction['route'],
                $nextAction['params'] ?? [],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
        }

        $previousAction = $this->getSiblingItemAction($dataSetItem, -1, $returnUrl);
        if ($previousAction) {
            return $this->generateUrl(
                $previousAction['route'],
                $previousAction['params'] ?? [],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
        }

        $dataSet = $dataSetItem->getDataSet();
        if ($dataSet instanceof DataSet) {
            return $this->generateUrl(
                'syntetiq_model_data_set_item_create',
                [
                    'dataSetId' => $dataSet->getId(),
                    '_enableContentProviders' => 'mainMenu',
                    'returnUrl' => $returnUrl,
                ],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
        }

        return $returnUrl;
    }

    private function deriveReturnUrlFromCurrentRequest(Request $request, DataSet $dataSet): ?string
    {
        $query = $request->query->all();
        unset($query['returnUrl']);

        if (!$this->queryContainsNavigationContext($query)) {
            return null;
        }

        if (isset($query['grid']) && is_array($query['grid']) && !isset($query['itemsView'])) {
            $query['itemsView'] = 'grid';
        }

        $baseUrl = $this->generateUrl('syntetiq_model_data_set_items', [
            'id' => $dataSet->getId(),
            '_enableContentProviders' => 'mainMenu',
        ]);

        $queryString = http_build_query($query);

        return $queryString !== '' ? $baseUrl . '&' . $queryString : $baseUrl;
    }

    private function queryContainsNavigationContext(array $query): bool
    {
        $keys = ['grid', 'itemsView', 'search', 'fileName', 'group', 'tag', 'sourceType', 'labels'];

        foreach ($keys as $key) {
            if (array_key_exists($key, $query)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeReturnUrl(string $url, Request $request): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        if (!isset($parts['scheme']) && !isset($parts['host'])) {
            return str_starts_with($url, '/') ? $url : null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host !== strtolower($request->getHost())) {
            return null;
        }

        return $url;
    }

    private function preserveReturnUrlOnRedirect(
        array|RedirectResponse $response,
        Request $request
    ): array|RedirectResponse {
        if (!$response instanceof RedirectResponse || !$request->isMethod(Request::METHOD_POST)) {
            return $response;
        }

        $postedReturnUrl = $request->request->get('returnUrlState');
        if (!is_string($postedReturnUrl)) {
            return $response;
        }

        $returnUrl = $this->normalizeReturnUrl($postedReturnUrl, $request);
        if ($returnUrl === null) {
            return $response;
        }

        $actionData = $this->parseInputAction($request);
        if (isset($actionData['redirectUrl'])) {
            return new RedirectResponse($returnUrl, $response->getStatusCode());
        }

        if (($actionData['route'] ?? null) !== 'syntetiq_model_data_set_item_edit') {
            return $response;
        }

        return new RedirectResponse(
            $this->replaceUrlQueryParameter($response->getTargetUrl(), 'returnUrl', $returnUrl),
            $response->getStatusCode()
        );
    }

    private function parseInputAction(Request $request): array
    {
        $rawAction = $request->request->get('input_action');
        if (!is_string($rawAction) || $rawAction === '') {
            return [];
        }

        $decodedAction = json_decode($rawAction, true);

        return is_array($decodedAction) ? $decodedAction : [];
    }

    private function parseJsonRequest(Request $request): ?array
    {
        $content = trim((string) $request->getContent());
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizePercent(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $percent = (float) $value;
        if ($percent < 0.0 || $percent > 100.0) {
            return null;
        }

        return $percent;
    }

    /**
     * @return string[]
     */
    private function parseTagsInput(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @return string[]|null
     */
    private function parseTagsPayload(mixed $value): ?array
    {
        if (is_string($value) || is_numeric($value) || $value === null) {
            return $this->parseTagsInput((string) $value);
        }

        if (!is_array($value)) {
            return null;
        }

        $tags = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                return null;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $tags[] = $name;
        }

        return $tags;
    }

    private function replaceUrlQueryParameter(string $url, string $name, string $value): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $queryParams = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
        }

        $queryParams[$name] = $value;
        $parts['query'] = http_build_query($queryParams);

        return $this->buildUrlFromParts($parts);
    }

    private function buildUrlFromParts(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $auth = $user !== '' ? $user . $pass . '@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . $auth . $host . $port . $path . $query . $fragment;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                DoctrineHelper::class,
                ConfigManager::class,
                DataSetItemHandler::class,
                DataSetItemLabelResetter::class,
                DataSetItemNavigationResolver::class,
                DataSetItemSamSegmentationService::class,
                AttachmentManager::class,
                MessageProducerInterface::class,
            ]
        );
    }
}
