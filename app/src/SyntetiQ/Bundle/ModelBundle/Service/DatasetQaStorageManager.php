<?php

namespace SyntetiQ\Bundle\ModelBundle\Service;

use Gaufrette\Stream;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Path;

class DatasetQaStorageManager
{
    public function __construct(
        private FileManager $fileManager
    ) {}

    public function clearPrefix(string $storageDir): void
    {
        $storageDir = trim($storageDir, '/');
        if ($storageDir === '') {
            return;
        }

        $this->fileManager->deleteAllFiles($storageDir . '/');
    }

    public function syncDirectory(string $localDir, string $storageDir): void
    {
        $storageDir = trim($storageDir, '/');
        $localDir = rtrim($localDir, '/');

        $this->clearPrefix($storageDir);

        if (!is_dir($localDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', $iterator->getSubPathname()), '/');
            $targetPath = $this->buildStoragePath($storageDir, $relativePath);

            $this->fileManager->writeFileToStorage($fileInfo->getPathname(), $targetPath);
        }
    }

    public function syncFile(string $localPath, string $storageDir, string $relativePath): void
    {
        if (!is_file($localPath)) {
            return;
        }

        $this->fileManager->writeFileToStorage(
            $localPath,
            $this->buildStoragePath($storageDir, $relativePath)
        );
    }

    public function hasFile(string $storageDir, string $relativePath): bool
    {
        return $this->fileManager->hasFile($this->buildStoragePath($storageDir, $relativePath));
    }

    public function getFileContent(string $storageDir, string $relativePath): ?string
    {
        return $this->fileManager->getFileContent($this->buildStoragePath($storageDir, $relativePath), false);
    }

    public function getStream(string $storageDir, string $relativePath): ?Stream
    {
        return $this->fileManager->getStream($this->buildStoragePath($storageDir, $relativePath), false);
    }

    public function buildStoragePath(string $storageDir, string $relativePath): string
    {
        $storageDir = trim(str_replace('\\', '/', $storageDir), '/');
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '' || str_contains($relativePath, '../') || str_starts_with($relativePath, '..')) {
            throw new \InvalidArgumentException('Invalid QA report path.');
        }

        return trim(Path::canonicalize($storageDir . '/' . $relativePath), '/');
    }
}
