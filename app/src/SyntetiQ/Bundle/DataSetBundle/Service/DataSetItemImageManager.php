<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class DataSetItemImageManager
{
    public const MAX_IMAGE_SIZE_WIDTH = 1280;
    public const MAX_IMAGE_SIZE_HEIGHT = 1280;
    public const IMPORT_IMAGE_QUALITY = 85;

    public function __construct(
        private FileManager $fileManager,
        private ImagineInterface $imagine,
        private LoggerInterface $logger,
    ) {}

    public function syncImageSize(DataSetItem $dataSetItem): bool
    {
        $image = $dataSetItem->getImage();
        if (!$image || !$image->getFilename()) {
            return false;
        }

        if (!$this->needsProcessing($dataSetItem)) {
            return true;
        }

        $imageContent = $this->fileManager->getContent($image, false);
        if ($imageContent === null) {
            return false;
        }

        try {
            $imageSrc = $this->imagine->load($imageContent);
            $originalWidth = $imageSrc->getSize()->getWidth();
            $originalHeight = $imageSrc->getSize()->getHeight();
            $dataSetItem->setImgWidth($originalWidth);
            $dataSetItem->setImgHeight($originalHeight);

            [$newWidth, $newHeight] = $this->getNewImgSize($originalWidth, $originalHeight);

            $format = $this->resolveOutputFormat($image->getExtension(), $image->getMimeType());
            if ($format === null) {
                return true;
            }

            $options = [];
            if ($format === 'jpeg') {
                $options['jpeg_quality'] = self::IMPORT_IMAGE_QUALITY;
            } elseif ($format === 'webp') {
                $options['webp_quality'] = self::IMPORT_IMAGE_QUALITY;
            }

            $processedImageContent = $imageSrc
                ->thumbnail(new Box($newWidth, $newHeight), ManipulatorInterface::THUMBNAIL_INSET)
                ->get($format, $options);

            $this->fileManager->writeToStorage($processedImageContent, $image->getFilename());

            $scaleX = $newWidth / $originalWidth;
            $scaleY = $newHeight / $originalHeight;

            $dataSetItem->setImgWidth($newWidth);
            $dataSetItem->setImgHeight($newHeight);

            foreach ($dataSetItem->getObjectConfiguration() as $objectConfiguration) {
                if (!$objectConfiguration instanceof ItemObjectConfiguration) {
                    continue;
                }

                $objectConfiguration->setMinX((int) round($objectConfiguration->getMinX() * $scaleX));
                $objectConfiguration->setMaxX((int) round($objectConfiguration->getMaxX() * $scaleX));
                $objectConfiguration->setMinY((int) round($objectConfiguration->getMinY() * $scaleY));
                $objectConfiguration->setMaxY((int) round($objectConfiguration->getMaxY() * $scaleY));
            }

            $image->setFileSize(strlen($processedImageContent));
            $image->setMimeType($this->resolveMimeType($format));
            $image->setFile(null);

            // Update extension and filename if converted to jpeg
            if ($format === 'jpeg' && !in_array(strtolower((string) $image->getExtension()), ['jpg', 'jpeg'], true)) {
                $image->setExtension('jpg');
                $originalFilename = $image->getOriginalFilename();
                if ($originalFilename) {
                    $image->setOriginalFilename(pathinfo($originalFilename, PATHINFO_FILENAME) . '.jpg');
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'Failed to process image for DataSetItem %d (%s): %s',
                $dataSetItem->getId(),
                $image->getOriginalFilename(),
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    public function needsProcessing(DataSetItem $item): bool
    {
        if ($this->needsResize($item->getImgWidth(), $item->getImgHeight())) {
            return true;
        }

        $image = $item->getImage();
        if (!$image) {
            return false;
        }

        $extension = strtolower((string) $image->getExtension());

        return !in_array($extension, ['jpg', 'jpeg', 'webp'], true);
    }

    public function needsResize(int $imageWidth, int $imageHeight): bool
    {
        return $imageWidth > self::MAX_IMAGE_SIZE_WIDTH || $imageHeight > self::MAX_IMAGE_SIZE_HEIGHT;
    }

    private function getNewImgSize(int $imageWidth, int $imageHeight): array
    {
        $width = $imageWidth;
        $height = $imageHeight;

        if ($this->needsResize($imageWidth, $imageHeight)) {
            $ratio = min(
                self::MAX_IMAGE_SIZE_WIDTH / $imageWidth,
                self::MAX_IMAGE_SIZE_HEIGHT / $imageHeight
            );

            $width = (int) round($imageWidth * $ratio);
            $height = (int) round($imageHeight * $ratio);
        }

        return [$width, $height];
    }

    private function resolveOutputFormat(?string $extension, ?string $mimeType): ?string
    {
        $normalizedExtension = strtolower((string) $extension);

        if (in_array($normalizedExtension, ['jpg', 'jpeg'], true)) {
            return 'jpeg';
        }

        if ($normalizedExtension === 'webp') {
            return 'webp';
        }

        return 'jpeg';
    }

    private function resolveMimeType(string $format): string
    {
        return match ($format) {
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => 'application/octet-stream',
        };
    }
}
