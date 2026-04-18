<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\ModelBundle\Model\Yolo as YoloModel;

class YoloDatasetMaterializer
{
    public function __construct(
        private FileManager $fileManager,
        private Serializer $serializer
    ) {}

    /**
     * @param iterable<DataSetItem> $items
     * @param array<int, string> $resolvedGroups
     *
     * @return array<string, int>
     */
    public function materialize(
        iterable $items,
        FileManager $targetFileManager,
        string $datasetRootPath,
        bool $includeUnlabeled = false,
        array $resolvedGroups = [],
        bool $useOriginalImageFilename = true
    ): array {
        $labels = [];
        $knownTags = [];
        $metadataItems = [];
        $datasetRootPath = rtrim($datasetRootPath, '/');

        foreach ($items as $item) {
            if (!$item instanceof DataSetItem) {
                continue;
            }

            $image = $item->getImage();
            if (!$image instanceof File) {
                continue;
            }

            $objects = $item->getObjectConfiguration();
            $hasAnnotations = null !== $objects && !$objects->isEmpty();
            if (!$hasAnnotations && !$includeUnlabeled) {
                continue;
            }

            $imageFileName = $this->resolveImageFileName($image, $useOriginalImageFilename);
            $fileStem = pathinfo($imageFileName, PATHINFO_FILENAME);
            $group = $this->resolveGroup($item, $resolvedGroups);
            $objectsData = '';

            if ($hasAnnotations) {
                $dw = 1.0 / max(1, $item->getImgWidth());
                $dh = 1.0 / max(1, $item->getImgHeight());

                /** @var ItemObjectConfiguration $object */
                foreach ($objects as $object) {
                    if (!array_key_exists($object->getName(), $labels)) {
                        $labels[$object->getName()] = count($labels);
                    }

                    $relXCenter = ($object->getMinX() + $object->getMaxX()) / 2.0 * $dw;
                    $relYCenter = ($object->getMinY() + $object->getMaxY()) / 2.0 * $dh;
                    $relWidth = ($object->getMaxX() - $object->getMinX()) * $dw;
                    $relHeight = ($object->getMaxY() - $object->getMinY()) * $dh;

                    $objectsData .= $labels[$object->getName()]
                        . ' ' . $relXCenter
                        . ' ' . $relYCenter
                        . ' ' . $relWidth
                        . ' ' . $relHeight
                        . "\n";
                }
            }

            $labelPath = sprintf('%s/%s/labels/%s.txt', $datasetRootPath, $group, $fileStem);
            $imagePath = sprintf('%s/%s/images/%s', $datasetRootPath, $group, $imageFileName);

            $targetFileManager->writeToStorage($objectsData, $labelPath);
            $targetFileManager->writeStreamToStorage($this->fileManager->getStream($image->getFilename()), $imagePath);

            $metadataItems[$group . '/images/' . $imageFileName] = [
                'ready' => $item->isReady(),
                'tags' => $item->getTags(),
            ];

            foreach ($item->getTags() as $tag) {
                $knownTags[$tag] = true;
            }
        }

        $yolo = new YoloModel();
        $yolo->setTrain('train/images');
        $yolo->setVal('val/images');
        $yolo->setTest('test/images');
        $yolo->setNames(array_flip($labels));

        $targetFileManager->writeToStorage(
            $this->serializer->serialize($yolo, 'yaml', ['yaml_inline' => 2]),
            $datasetRootPath . '/data.yaml'
        );

        $targetFileManager->writeToStorage(
            implode("\n", array_keys($labels)),
            $datasetRootPath . '/labels.txt'
        );

        ksort($metadataItems);
        $knownTags = array_keys($knownTags);
        sort($knownTags);
        $targetFileManager->writeToStorage(
            Yaml::dump([
                'version' => 1,
                'known_tags' => $knownTags,
                'items' => $metadataItems,
            ], 4, 2),
            $datasetRootPath . '/metadata.yaml'
        );

        return $labels;
    }

    /**
     * @param array<int, string> $resolvedGroups
     */
    private function resolveGroup(DataSetItem $item, array $resolvedGroups): string
    {
        if (null !== $item->getId() && isset($resolvedGroups[$item->getId()])) {
            return $resolvedGroups[$item->getId()];
        }

        return match ($item->getGroup()) {
            Group::TEST => Group::TEST,
            Group::VAL, 'validation' => Group::VAL,
            Group::TRAIN, null, '' => Group::TRAIN,
            default => Group::TRAIN,
        };
    }

    private function resolveImageFileName(File $image, bool $useOriginalImageFilename): string
    {
        if ($useOriginalImageFilename) {
            return $image->getFilename();
        }

        $fileName = pathinfo($image->getFilename(), PATHINFO_FILENAME);

        return $fileName . '.jpg';
    }
}
