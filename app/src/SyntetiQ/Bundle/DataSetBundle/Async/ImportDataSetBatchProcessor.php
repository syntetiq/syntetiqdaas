<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Yaml\Yaml;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetBatchTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\DataSetBundle\Service\DataSetItemImageManager;

class ImportDataSetBatchProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const FLUSH_BATCH_SIZE = 50;

    public function __construct(
        private JobRunner $jobRunner,
        private ManagerRegistry $doctrine,
        private FileManager $fileManager,
        private DataSetItemImageManager $dataSetItemImageManager
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [ImportDataSetBatchTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $jobId = $data['jobId'];

        $result = $this->jobRunner->runDelayed(
            $jobId,
            function (JobRunner $jobRunner, Job $job) use ($data) {
                return $this->processBatch($data, $job);
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function processBatch(array $data, Job $job): bool
    {
        $dataSetId = $data['dataSetId'];
        $rootJobId = $data['rootJobId'];
        $extractedPath = $data['extractedPath'];
        $datasetRoot = $data['datasetRoot'];
        $startIndex = $data['startIndex'];
        $endIndex = $data['endIndex'];
        $sourceType = $data['sourceType'] ?? 'manual';
        $sourceIntegrationId = $data['sourceIntegrationId'] ?? null;
        $tag = $data['tag'] ?? null;

        $dataSet = $this->doctrine->getRepository(DataSet::class)->find($dataSetId);
        if (!$dataSet) {
            return false;
        }

        if (!is_dir($extractedPath)) {
            return false;
        }

        /** @var Channel|null $sourceIntegration */
        $sourceIntegration = null;
        if ($sourceIntegrationId) {
            $sourceIntegration = $this->doctrine->getRepository(Channel::class)->find($sourceIntegrationId);
        }

        // Re-scan item files and take only our slice
        $itemFiles = $this->collectItemFilesFromDirectory($extractedPath, $datasetRoot);
        $slicedFiles = array_slice($itemFiles, $startIndex, $endIndex - $startIndex);

        if ($slicedFiles === []) {
            return true;
        }

        $labels = $this->loadLabels($extractedPath, $datasetRoot);
        $metadataByItemPath = $this->loadMetadata($extractedPath, $datasetRoot);

        $em = $this->doctrine->getManagerForClass(DataSetItem::class);
        $pendingResizeItems = [];
        $i = 0;
        $totalProcessed = 0;

        foreach ($slicedFiles as $itemFile) {
            $imagePath = $this->buildPath($extractedPath, $itemFile);
            if (!is_file($imagePath)) {
                continue;
            }

            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                continue;
            }

            $dataSetItem = new DataSetItem();
            $dataSetItem->setImgHeight((int) $imageInfo[1]);
            $dataSetItem->setImgWidth((int) $imageInfo[0]);
            $dataSetItem->setDataSet($dataSet);
            $dataSetItem->setOwner($dataSet->getOwner());
            $dataSetItem->setOrganization($dataSet->getOrganization());
            $dataSetItem->setSourceType($sourceType);
            if ($sourceIntegration) {
                $dataSetItem->setSourceIntegration($sourceIntegration);
            }
            $dataSetItem->setGroup($this->mapDirectoryGroupToEntityGroup($itemFile));
            $dataSetItem->setExternalId(pathinfo($itemFile, PATHINFO_FILENAME));
            $dataSetItem->setImportId($rootJobId);

            $itemMetadata = $metadataByItemPath[$this->stripDatasetRootPrefix($itemFile, $datasetRoot)] ?? null;
            $dataSetItem->setTags(is_array($itemMetadata) ? ($itemMetadata['tags'] ?? []) : ($tag !== null ? [$tag] : []));
            $this->syncTagOwnership($dataSetItem);
            if (is_array($itemMetadata) && array_key_exists('ready', $itemMetadata)) {
                $dataSetItem->setReady((bool) $itemMetadata['ready']);
            }

            $image = $this->fileManager->createFileEntity($imagePath);
            $image->setOriginalFilename(basename($itemFile));
            $dataSetItem->setImage($image);

            $labelPath = $this->resolveLabelPath($extractedPath, $itemFile);
            $labelContent = $labelPath !== null && is_file($labelPath) ? file_get_contents($labelPath) : false;
            $labelLines = $labelContent !== false
                ? preg_split('/\R/', trim($labelContent))
                : [];

            foreach ($labelLines as $labelLine) {
                $labelLine = trim((string) $labelLine);
                if ($labelLine === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $labelLine);
                if (!$parts || count($parts) < 5) {
                    continue;
                }

                $classId = (int) $parts[0];
                $labelName = $labels[$classId] ?? sprintf('class_%d', $classId);

                $itemObjectConfiguration = new ItemObjectConfiguration();
                $itemObjectConfiguration->setOwner($dataSetItem->getOwner());
                $itemObjectConfiguration->setOrganization($dataSetItem->getOrganization());
                $itemObjectConfiguration->setName($labelName);

                [$minX, $minY, $maxX, $maxY] = $this->denormalizeBoundingBox(
                    (float) $parts[1],
                    (float) $parts[2],
                    (float) $parts[3],
                    (float) $parts[4],
                    $dataSetItem->getImgWidth(),
                    $dataSetItem->getImgHeight()
                );

                $isTruncated = (float) $parts[0] === 1;

                $itemObjectConfiguration->setMinX($minX);
                $itemObjectConfiguration->setMinY($minY);
                $itemObjectConfiguration->setMaxX($maxX);
                $itemObjectConfiguration->setMaxY($maxY);
                $itemObjectConfiguration->setTruncated($isTruncated);

                $dataSetItem->addObjectConfiguration($itemObjectConfiguration);
            }

            $em->persist($dataSetItem);
            if ($this->dataSetItemImageManager->needsProcessing($dataSetItem)) {
                $pendingResizeItems[] = $dataSetItem;
            }

            $i++;
            $totalProcessed++;

            if (($i % self::FLUSH_BATCH_SIZE) === 0) {
                $this->flushAndResizeBatch($em, $pendingResizeItems);
                $pendingResizeItems = [];
                $em->clear();
                gc_collect_cycles();

                $dataSet = $em->getReference(DataSet::class, $dataSetId);
                $sourceIntegration = $sourceIntegrationId
                    ? $em->getReference(Channel::class, $sourceIntegrationId)
                    : null;
            }

            unset($imageInfo, $image, $labelContent, $labelLines, $itemMetadata);
        }

        // Flush remaining
        $this->flushAndResizeBatch($em, $pendingResizeItems);
        $em->clear();
        gc_collect_cycles();

        // Mark this child job as fully complete (Oro aggregates child progress into root job)
        $job->setJobProgress(1.0);

        return true;
    }


    private function flushAndResizeBatch($em, array $items): void
    {
        $em->flush();

        foreach ($items as $item) {
            $this->dataSetItemImageManager->syncImageSize($item);
            $em->persist($item);
        }

        if ($items !== []) {
            $em->flush();
        }
    }

    private function syncTagOwnership(DataSetItem $dataSetItem): void
    {
        foreach ($dataSetItem->getItemTags() as $itemTag) {
            if (!$itemTag instanceof DataSetItemTag) {
                continue;
            }

            $itemTag->setOwner($dataSetItem->getOwner());
            $itemTag->setOrganization($dataSetItem->getOrganization());
        }
    }

    private function collectItemFilesFromDirectory(string $extractedDir, string $datasetRoot): array
    {
        $itemFiles = [];
        $groups = [
            'train/images',
            'val/images',
            'validation/images',
            'valid/images',
            'test/images',
            'images/train',
            'images/val',
            'images/validation',
            'images/valid',
            'images/test',
        ];
        $datasetPath = $this->buildPath($extractedDir, $datasetRoot);

        foreach ($groups as $groupPath) {
            $groupDir = $this->buildPath($datasetPath, $groupPath);
            if (!is_dir($groupDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($groupDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile() || !$this->isImageFile($file->getFilename())) {
                    continue;
                }

                $itemFiles[] = $this->makeRelativePath($extractedDir, $file->getPathname());
            }
        }

        $itemFiles = array_values(array_unique($itemFiles));
        sort($itemFiles);

        return $itemFiles;
    }

    private function buildPath(string $basePath, string $path = ''): string
    {
        if ($path === '') {
            return rtrim(str_replace('\\', '/', $basePath), '/');
        }

        return rtrim(str_replace('\\', '/', $basePath), '/') . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function makeRelativePath(string $basePath, string $path): string
    {
        $normalizedBasePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $normalizedPath = str_replace('\\', '/', $path);

        if (str_starts_with($normalizedPath, $normalizedBasePath . '/')) {
            return substr($normalizedPath, strlen($normalizedBasePath) + 1);
        }

        return ltrim($normalizedPath, '/');
    }

    private function isImageFile(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif', 'tif', 'tiff'], true);
    }

    private function loadLabels(string $extractedDir, string $datasetRoot): array
    {
        $dataYamlPath = $this->buildPath($extractedDir, $this->buildRelativePath($datasetRoot, 'data.yaml'));
        $yamlContent = is_file($dataYamlPath) ? file_get_contents($dataYamlPath) : false;
        $data = $yamlContent !== false ? (Yaml::parse($yamlContent) ?: []) : [];
        $names = $data['names'] ?? [];
        if (!is_array($names)) {
            return [];
        }

        $labels = [];
        foreach ($names as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $index = is_int($key) || ctype_digit((string) $key)
                ? (int) $key
                : count($labels);

            $labels[$index] = (string) $value;
        }

        ksort($labels);

        return $labels;
    }

    private function loadMetadata(string $extractedDir, string $datasetRoot): array
    {
        $metadataPath = $this->buildPath($extractedDir, $this->buildRelativePath($datasetRoot, 'metadata.yaml'));
        $metadataContent = is_file($metadataPath) ? file_get_contents($metadataPath) : false;
        $metadata = $metadataContent !== false ? (Yaml::parse($metadataContent) ?: []) : [];
        $items = $metadata['items'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $normalizedItems = [];
        foreach ($items as $path => $itemMetadata) {
            if (!is_string($path) || !is_array($itemMetadata)) {
                continue;
            }

            $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
            if ($normalizedPath === '') {
                continue;
            }

            $normalizedItems[$normalizedPath] = [
                'ready' => array_key_exists('ready', $itemMetadata) ? (bool) $itemMetadata['ready'] : false,
                'tags' => $this->normalizeMetadataTags($itemMetadata),
            ];
        }

        return $normalizedItems;
    }

    private function resolveLabelPath(string $extractedDir, string $itemFile): ?string
    {
        $normalizedPath = str_replace('\\', '/', $itemFile);
        $fileName = pathinfo($normalizedPath, PATHINFO_FILENAME);
        $split = $this->resolveSplitFromPath($normalizedPath);
        $datasetRoot = $this->resolveDatasetPathRoot($normalizedPath);
        $candidates = [];

        $labelPath = preg_replace('~/images/~', '/labels/', $normalizedPath, 1);
        if ($labelPath !== null && $labelPath !== $normalizedPath) {
            $candidates[] = preg_replace('~\.[^.]+$~', '.txt', $labelPath) ?? ($labelPath . '.txt');
        }

        $candidates[] = $datasetRoot . '/labels/' . $split . '/' . $fileName . '.txt';
        $candidates[] = $datasetRoot . '/' . $split . '/labels/' . $fileName . '.txt';

        foreach (array_unique($candidates) as $candidate) {
            $candidatePath = $this->buildPath($extractedDir, $candidate);
            if (is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    private function mapDirectoryGroupToEntityGroup(string $itemFile): string
    {
        $group = $this->resolveSplitFromPath($itemFile);

        return match ($group) {
            Group::VAL, 'validation', 'valid' => Group::VAL,
            Group::TEST => Group::TEST,
            default => Group::TRAIN,
        };
    }

    private function resolveSplitFromPath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $pathParts = explode('/', $normalizedPath);

        $imagesIndex = array_search('images', $pathParts, true);
        if ($imagesIndex !== false && isset($pathParts[$imagesIndex + 1]) && $imagesIndex < count($pathParts) - 2) {
            return $pathParts[$imagesIndex + 1];
        }

        if ($imagesIndex !== false && isset($pathParts[$imagesIndex - 1])) {
            return $pathParts[$imagesIndex - 1];
        }

        $labelsIndex = array_search('labels', $pathParts, true);
        if ($labelsIndex !== false && isset($pathParts[$labelsIndex + 1]) && $labelsIndex < count($pathParts) - 2) {
            return $pathParts[$labelsIndex + 1];
        }

        if ($labelsIndex !== false && isset($pathParts[$labelsIndex - 1])) {
            return $pathParts[$labelsIndex - 1];
        }

        return basename(dirname(dirname($normalizedPath)));
    }

    private function resolveDatasetPathRoot(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $pathParts = explode('/', $normalizedPath);
        $imagesIndex = array_search('images', $pathParts, true);

        if ($imagesIndex !== false) {
            if ($imagesIndex > 0 && isset($pathParts[$imagesIndex - 1]) && $this->isSplitName($pathParts[$imagesIndex - 1])) {
                return implode('/', array_slice($pathParts, 0, $imagesIndex - 1));
            }

            return implode('/', array_slice($pathParts, 0, $imagesIndex));
        }

        return dirname(dirname($normalizedPath));
    }

    private function isSplitName(string $value): bool
    {
        return in_array($value, [Group::TRAIN, Group::VAL, Group::TEST, 'validation', 'valid'], true);
    }

    private function denormalizeBoundingBox(
        float $xCenter,
        float $yCenter,
        float $width,
        float $height,
        int $imageWidth,
        int $imageHeight
    ): array {
        $minX = (int) round(($xCenter - ($width / 2)) * $imageWidth);
        $maxX = (int) round(($xCenter + ($width / 2)) * $imageWidth);
        $minY = (int) round(($yCenter - ($height / 2)) * $imageHeight);
        $maxY = (int) round(($yCenter + ($height / 2)) * $imageHeight);

        return [
            max(0, min($imageWidth, $minX)),
            max(0, min($imageHeight, $minY)),
            max(0, min($imageWidth, $maxX)),
            max(0, min($imageHeight, $maxY)),
        ];
    }

    private function buildRelativePath(string $basePath, string $path): string
    {
        if ($basePath === '') {
            return $path;
        }

        return rtrim(str_replace('\\', '/', $basePath), '/') . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function stripDatasetRootPrefix(string $path, string $datasetRoot): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $normalizedRoot = trim(str_replace('\\', '/', $datasetRoot), '/');
        if ($normalizedRoot !== '' && str_starts_with($normalizedPath, $normalizedRoot . '/')) {
            return substr($normalizedPath, strlen($normalizedRoot) + 1);
        }

        return $normalizedPath;
    }

    /**
     * @return string[]
     */
    private function normalizeMetadataTags(array $itemMetadata): array
    {
        $rawTags = $itemMetadata['tags'] ?? [];
        if (!is_array($rawTags)) {
            $rawTags = array_key_exists('tag', $itemMetadata) ? [$itemMetadata['tag']] : [];
        }

        $normalizedTags = [];
        foreach ($rawTags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || in_array($tag, $normalizedTags, true)) {
                continue;
            }

            $normalizedTags[] = $tag;
        }

        return $normalizedTags;
    }
}
