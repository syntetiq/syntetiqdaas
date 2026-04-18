<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

class OmniverseResultHandler
{
    private EntityManagerInterface $entityManager;
    private FileManager $fileManager;
    private LoggerInterface $logger;
    private string $tmpDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        FileManager $fileManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
        $this->tmpDir = sys_get_temp_dir() . '/omniverse_imports';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
    }

    public function handleResult(int $requestId, string $resultUrl): void
    {
        $request = $this->entityManager->getRepository(GenerateImagesRequest::class)->find($requestId);
        if (!$request) {
            $this->logger->error("GenerateImagesRequest not found for ID: $requestId");
            return;
        }

        $dataSet = $request->getDataSet();
        if (!$dataSet) {
            $this->logger->warning("No DataSet linked to GenerateImagesRequest ID: $requestId");
            return;
        }

        $zipPath = $this->downloadZip($resultUrl);
        if (!$zipPath) {
            return;
        }

        $extractPath = $this->extractZip($zipPath);
        if (!$extractPath) {
            return;
        }

        $this->importImages($extractPath, $dataSet);

        // Cleanup
        $this->removeDirectory($extractPath);
        unlink($zipPath);
    }

    private function downloadZip(string $url): ?string
    {
        try {
            $client = new Client();
            $response = $client->get($url);
            $content = $response->getBody()->getContents();
            $filename = $this->tmpDir . '/' . uniqid('omniverse_') . '.zip';
            file_put_contents($filename, $content);
            return $filename;
        } catch (\Exception $e) {
            $this->logger->error("Failed to download zip from $url: " . $e->getMessage());
            return null;
        }
    }

    private function extractZip(string $zipPath): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $extractPath = $this->tmpDir . '/' . uniqid('extracted_');
            mkdir($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();
            return $extractPath;
        } else {
            $this->logger->error("Failed to open zip file: $zipPath");
            return null;
        }
    }

    private function importImages(string $path, $dataSet): void
    {
        $files = scandir($path);
        $mimeTypes = new MimeTypes();

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->importImages($filePath, $dataSet); // Recursive
                continue;
            }

            // Check if image
            $mimeType = $mimeTypes->guessMimeType($filePath);
            if (!str_starts_with($mimeType ?? '', 'image/')) {
                continue;
            }

            try {
                $this->createDataSetItem($filePath, $dataSet, $mimeType);
            } catch (\Exception $e) {
                $this->logger->error("Failed to import image $file: " . $e->getMessage());
            }
        }
    }

    private function createDataSetItem(string $filePath, $dataSet, string $mimeType): void
    {
        $content = file_get_contents($filePath);
        $originalFilename = basename($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileSize = filesize($filePath);

        // Create File entity
        $file = new File();
        $file->setOriginalFilename($originalFilename);
        $file->setExtension($extension);
        $file->setMimeType($mimeType);
        $file->setFileSize($fileSize);

        // Generate internal filename and save content
        $internalFilename = $this->fileManager->generateFileName($extension);
        $this->fileManager->writeToStorage($content, $internalFilename);
        $file->setFilename($internalFilename);

        $this->entityManager->persist($file);

        // Create DataSetItem
        $item = new DataSetItem();
        $item->setDataSet($dataSet);
        $item->setImage($file); // Assuming magic method or trait handles this

        // Get image dimensions
        $imageSize = getimagesize($filePath);
        if ($imageSize) {
            $item->setImgWidth($imageSize[0]);
            $item->setImgHeight($imageSize[1]);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush(); // Flush per item or batch? Flushing per item for safety now.
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
