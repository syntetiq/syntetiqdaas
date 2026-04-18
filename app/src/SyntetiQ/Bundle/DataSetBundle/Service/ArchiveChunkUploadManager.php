<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Provider\ImportDataSetProvider;

class ArchiveChunkUploadManager
{
    private string $chunkBaseDir;

    public function __construct(
        FileManager $fileManagerImportChunks,
        private ImportDataSetProvider $importDataSetProvider
    ) {
        $chunkBaseDir = $fileManagerImportChunks->getLocalPath();
        if ($chunkBaseDir === null || $chunkBaseDir === '') {
            throw new \RuntimeException('The import_chunks filesystem must use a local adapter.');
        }

        $this->chunkBaseDir = rtrim($chunkBaseDir, DIRECTORY_SEPARATOR);
    }

    public function storeChunk(
        DataSet $dataSet,
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        UploadedFile $chunk
    ): void {
        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            throw new \RuntimeException('Invalid chunk metadata');
        }

        $uploadDir = $this->ensureUploadDir($dataSet, $uploadId);
        $chunkPath = $uploadDir . '/' . $this->buildChunkFileName($chunkIndex);

        if (is_file($chunkPath) && !unlink($chunkPath)) {
            throw new \RuntimeException(sprintf('Unable to replace chunk file %s', $chunkPath));
        }

        $chunk->move($uploadDir, $this->buildChunkFileName($chunkIndex));
    }

    public function hasChunk(DataSet $dataSet, string $uploadId, int $chunkIndex, int $totalChunks): bool
    {
        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            return false;
        }

        $uploadDir = $this->getUploadDir($dataSet, $uploadId);

        return is_file($uploadDir . '/' . $this->buildChunkFileName($chunkIndex));
    }

    public function finalizeUpload(
        DataSet $dataSet,
        string $uploadId,
        string $originalName,
        int $totalChunks,
        ?string $tag = null
    ): void {
        if ($totalChunks < 1) {
            throw new \RuntimeException('Invalid total chunks count');
        }

        $uploadDir = $this->getUploadDir($dataSet, $uploadId);
        if (!is_dir($uploadDir)) {
            throw new \RuntimeException('Upload session not found');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            throw new \RuntimeException('Only ZIP archives are supported');
        }

        $assembledFilePath = $uploadDir . '/assembled.zip';
        $output = fopen($assembledFilePath, 'wb');
        if ($output === false) {
            throw new \RuntimeException('Unable to create assembled archive');
        }

        try {
            try {
                for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
                    $chunkPath = $uploadDir . '/' . $this->buildChunkFileName($chunkIndex);
                    if (!is_file($chunkPath)) {
                        throw new \RuntimeException(sprintf('Missing chunk %d', $chunkIndex));
                    }

                    $input = fopen($chunkPath, 'rb');
                    if ($input === false) {
                        throw new \RuntimeException(sprintf('Unable to open chunk %d', $chunkIndex));
                    }

                    try {
                        if (stream_copy_to_stream($input, $output) === false) {
                            throw new \RuntimeException(sprintf('Unable to append chunk %d', $chunkIndex));
                        }
                    } finally {
                        fclose($input);
                    }
                }
            } finally {
                fclose($output);
            }

            $zipArchive = new \ZipArchive();
            if ($zipArchive->open($assembledFilePath) !== true) {
                throw new \RuntimeException('Unable to open assembled ZIP archive');
            }
            $zipArchive->close();

            $this->importDataSetProvider->handleLocalFile($assembledFilePath, $originalName, $dataSet, $tag);
        } finally {
            $this->cleanupUpload($dataSet, $uploadId);
        }
    }

    public function cleanupUpload(DataSet $dataSet, string $uploadId): void
    {
        $uploadDir = $this->getUploadDir($dataSet, $uploadId);
        $this->deleteDirectory($uploadDir);
    }

    private function ensureUploadDir(DataSet $dataSet, string $uploadId): string
    {
        $uploadDir = $this->getUploadDir($dataSet, $uploadId);
        if (is_dir($uploadDir)) {
            return $uploadDir;
        }

        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Unable to create upload dir %s', $uploadDir));
        }

        return $uploadDir;
    }

    private function getUploadDir(DataSet $dataSet, string $uploadId): string
    {
        $normalizedUploadId = preg_replace('/[^A-Za-z0-9_-]/', '', $uploadId);
        if ($normalizedUploadId === null || $normalizedUploadId === '') {
            throw new \RuntimeException('Invalid upload id');
        }

        return sprintf('%s/dataset_%d_%s', $this->chunkBaseDir, $dataSet->getId(), $normalizedUploadId);
    }

    private function buildChunkFileName(int $chunkIndex): string
    {
        return sprintf('chunk_%06d.part', $chunkIndex);
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
