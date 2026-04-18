<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetBatchTopic;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetFinalizeTopic;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetTopic;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\DataSetBundle\Service\ImportDataSetArtifactsManager;

class ImportDataSetProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private JobRunner $jobRunner,
        private ImportDataSetArtifactsManager $artifactsManager,
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [ImportDataSetTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $fileName = $data['fileName'];
        $dataSetId = $data['dataSetId'];

        $result = $this->jobRunner->runUniqueByMessage($message, function (JobRunner $jobRunner, Job $rootJob) use ($data, $fileName, $dataSetId) {
            // Re-compose unique name if needed, but runUniqueByMessage already uses message hash.
            // Actually, we'll just ensure the root job ID is correctly passed to children.
            $rootJobId = $rootJob->getId();
            $stream = $this->artifactsManager->getImportTmpStream($fileName);
            $this->artifactsManager->writeImportStreamToStorage($stream, $fileName);

            $zipPath = $this->artifactsManager->getImportFilePath($fileName);
            $zip = new \ZipArchive();
            $zipOpened = false;

            try {
                if ($zip->open($zipPath) !== true) {
                    throw new \RuntimeException(sprintf('Failed to open zip file %s', $zipPath));
                }
                $zipOpened = true;

                $datasetRoot = $this->resolveDatasetRootInZip($zip);

                $extractedPath = sprintf(
                    '%s/import_%d_%s',
                    $this->artifactsManager->getImportFilesDir(),
                    $dataSetId,
                    uniqid('', true)
                );
                if (!mkdir($extractedPath, 0777, true) && !is_dir($extractedPath)) {
                    throw new \RuntimeException(sprintf('Unable to create extracted dir %s', $extractedPath));
                }
                if (!$zip->extractTo($extractedPath)) {
                    throw new \RuntimeException(sprintf('Failed to extract zip file %s', $zipPath));
                }

                if ($zipOpened) {
                    $zip->close();
                    $zipOpened = false;
                }

                // Delete the local ZIP copy — extracted dir is what we need now
                @unlink($zipPath);

                $itemFiles = $this->collectItemFilesFromDirectory($extractedPath, $datasetRoot);
                $itemCount = count($itemFiles);

                if ($itemCount === 0) {
                    $this->artifactsManager->cleanup($fileName, $extractedPath);

                    return false;
                }

                // Store metadata in the root job's data field so the progress controller can read it
                $rootJobId = $rootJob->getId();
                $rootJob->setData([
                    'dataSetId' => $dataSetId,
                    'fileName'  => $fileName,
                    'itemCount' => $itemCount,
                ]);

                // Build extra message data shared by all batch messages
                $extraData = [];
                if (isset($data['tag'])) {
                    $extraData['tag'] = $data['tag'];
                }
                if (isset($data['sourceType'])) {
                    $extraData['sourceType'] = $data['sourceType'];
                }
                if (isset($data['sourceIntegrationId'])) {
                    $extraData['sourceIntegrationId'] = $data['sourceIntegrationId'];
                }

                // Dispatch batch child jobs
                for ($i = 0; $i < $itemCount; $i += self::BATCH_SIZE) {
                    $startIndex = $i;
                    $endIndex = min($i + self::BATCH_SIZE, $itemCount);

                    $jobRunner->createDelayed(
                        sprintf('import_batch:%d:%d:%d', $dataSetId, $rootJobId, $startIndex),
                        function (JobRunner $jobRunner, Job $childJob) use (
                            $rootJobId, $dataSetId, $extractedPath, $datasetRoot,
                            $startIndex, $endIndex, $extraData
                        ) {
                            $this->producer->send(
                                ImportDataSetBatchTopic::getName(),
                                array_merge([
                                    'jobId'      => $childJob->getId(),
                                    'rootJobId'  => $rootJobId,
                                    'dataSetId'  => $dataSetId,
                                    'extractedPath' => $extractedPath,
                                    'datasetRoot'   => $datasetRoot,
                                    'startIndex'    => $startIndex,
                                    'endIndex'      => $endIndex,
                                ], $extraData)
                            );
                        }
                    );
                }

                // Dispatch finalize child job
                $jobRunner->createDelayed(
                    sprintf('import_finalize:%d:%d', $dataSetId, $rootJobId),
                    function (JobRunner $jobRunner, Job $childJob) use ($rootJobId, $dataSetId, $extractedPath, $fileName) {
                        $this->producer->send(
                            ImportDataSetFinalizeTopic::getName(),
                            [
                                'jobId'         => $childJob->getId(),
                                'rootJobId'     => $rootJobId,
                                'dataSetId'     => $dataSetId,
                                'extractedPath' => $extractedPath,
                                'gcsFileName'   => $fileName,
                            ]
                        );
                    }
                );

                return true;
            } finally {
                if ($zipOpened) {
                    $zip->close();
                }
            }
        });

        if ($result === null) {
            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    private function resolveDatasetRootInZip(\ZipArchive $zip): string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (basename($stat['name']) === 'data.yaml') {
                $directory = dirname($stat['name']);

                return $directory === '.' ? '' : rtrim($directory, '/');
            }
        }

        throw new \RuntimeException('Unable to locate data.yaml in imported archive');
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
}
