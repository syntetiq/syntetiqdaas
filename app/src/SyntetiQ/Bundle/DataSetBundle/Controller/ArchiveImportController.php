<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Service\ArchiveChunkUploadManager;

class ArchiveImportController extends AbstractController
{
    #[Route(
        path: '/{id}/chunk',
        name: 'syntetiq_data_set_import_archive_chunk',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
        options: ['expose' => true],
    )]
    public function uploadChunkAction(Request $request, DataSet $dataSet): JsonResponse
    {
        $request->getSession()->save();

        [$uploadId, $chunkIndex, $totalChunks] = $this->extractChunkMetadata($request);

        if ($request->isMethod(Request::METHOD_GET)) {
            $hasChunk = $this->container->get(ArchiveChunkUploadManager::class)->hasChunk(
                $dataSet,
                $uploadId,
                $chunkIndex,
                $totalChunks
            );

            return new JsonResponse(
                $hasChunk ? ['status' => 'success'] : ['status' => 'missing'],
                $hasChunk ? 200 : 204
            );
        }

        $chunk = $request->files->get('chunk') ?: $request->files->get('file');
        if (!$chunk instanceof UploadedFile || !$chunk->isValid()) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid chunk upload'], 400);
        }

        try {
            $this->container->get(ArchiveChunkUploadManager::class)->storeChunk(
                $dataSet,
                $uploadId,
                $chunkIndex,
                $totalChunks,
                $chunk
            );
        } catch (\Throwable $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()], 400);
        }

        return new JsonResponse([
            'status' => 'success',
            'uploadId' => $uploadId,
            'chunkIndex' => $chunkIndex,
        ]);
    }

    #[Route(
        path: '/{id}/complete',
        name: 'syntetiq_data_set_import_archive_complete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        options: ['expose' => true],
    )]
    public function completeUploadAction(Request $request, DataSet $dataSet): JsonResponse
    {
        $request->getSession()->save();

        $uploadId = (string) $request->request->get('uploadId', '');
        $fileName = (string) $request->request->get('fileName', '');
        $totalChunks = (int) $request->request->get('totalChunks', 0);
        $tag = trim((string) $request->request->get('tag', ''));

        try {
            $this->container->get(ArchiveChunkUploadManager::class)->finalizeUpload(
                $dataSet,
                $uploadId,
                $fileName,
                $totalChunks,
                $tag !== '' ? $tag : null
            );
        } catch (\Throwable $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()], 400);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Import started successfully',
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ArchiveChunkUploadManager::class,
            ]
        );
    }

    private function extractChunkMetadata(Request $request): array
    {
        $resumableIdentifier = (string) $request->get('resumableIdentifier', '');
        if ($resumableIdentifier !== '') {
            return [
                $resumableIdentifier,
                max(0, (int) $request->get('resumableChunkNumber', 0) - 1),
                (int) $request->get('resumableTotalChunks', 0),
            ];
        }

        return [
            (string) $request->get('uploadId', ''),
            (int) $request->get('chunkIndex', -1),
            (int) $request->get('totalChunks', 0),
        ];
    }
}
