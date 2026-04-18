<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;

class ImportDataSetArtifactsManager
{
    public function __construct(
        private FileManager $fileManagerImport,
        private FileManager $fileManagerImportTmp
    ) {}

    public function getImportTmpStream(string $fileName)
    {
        return $this->fileManagerImportTmp->getStream($fileName);
    }

    public function writeImportStreamToStorage($stream, string $fileName): void
    {
        $this->fileManagerImport->writeStreamToStorage($stream, $fileName);
    }

    public function getImportFilePath(string $fileName): string
    {
        return $this->fileManagerImport->getLocalPath() . '/' . $fileName;
    }

    public function getImportFilesDir(): string
    {
        return $this->fileManagerImport->getLocalPath();
    }

    public function cleanup(?string $fileName = null, ?string $extractedDir = null): void
    {
        if ($fileName !== null && $fileName !== '') {
            $this->fileManagerImport->deleteFile($fileName);
            $this->fileManagerImportTmp->deleteFile($fileName);
        }

        if ($extractedDir !== null && $extractedDir !== '') {
            $this->deleteDirectory($extractedDir);
        }
    }

    public function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
