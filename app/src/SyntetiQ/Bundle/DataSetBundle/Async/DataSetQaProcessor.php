<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\DataSetQaTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\ModelBundle\Model\ModelBuildConstants;
use SyntetiQ\Bundle\DataSetBundle\Service\YoloDatasetMaterializer;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;

class DataSetQaProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const PROCESS_POLL_INTERVAL_SECONDS = 5;
    private const HEARTBEAT_UPDATE_INTERVAL_SECONDS = 10;
    private const RUNTIME_LOG_FILES = [
        'dataset-qa-init.log',
        'dataset-qa-init.err',
        'dataset-qa-run.log',
        'dataset-qa-run.err',
    ];
    private const PROGRESS_FILE = 'progress.json';

    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner,
        private YoloDatasetMaterializer $yoloDatasetMaterializer,
        private FileManager $fileManagerImportExport,
        private DatasetQaStorageManager $datasetQaStorageManager
    ) {}

    public static function getSubscribedTopics()
    {
        return [DataSetQaTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $dataSetId = (int) ($data['dataSetId'] ?? 0);
        if ($dataSetId <= 0) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($dataSetId) {
                $dataSet = $this->loadDataSet($dataSetId);
                if (!$dataSet instanceof DataSet) {
                    return false;
                }

                $filesystem = new Filesystem();
                $legacyOutputDir = DatasetQaPaths::getDataSetLocalBaseDir($dataSet);
                $tempInputDir = DatasetQaPaths::getDataSetTempInputDir($dataSet);
                $tempWorkDir = DatasetQaPaths::getDataSetTempWorkDir($dataSet);

                $filesystem->remove([$legacyOutputDir, $tempInputDir, $tempWorkDir]);
                $this->datasetQaStorageManager->clearPrefix(DatasetQaPaths::getDataSetBaseStorageDir($dataSet));

                $this->setRunning($dataSet);
                $filesystem->mkdir($tempWorkDir);
                $this->initializeRuntimeLogFiles($tempWorkDir);
                $this->syncRuntimeFilesSafely($tempWorkDir, DatasetQaPaths::getDataSetStorageDir($dataSet));

                try {
                    $this->yoloDatasetMaterializer->materialize(
                        $dataSet->getItems(),
                        $this->fileManagerImportExport,
                        $this->toImportExportRelativePath($tempInputDir),
                        true,
                        [],
                        true
                    );

                    $modelRoot = ModelBuildConstants::getModelRoot();
                    $process = new Process(
                        ['bash', ModelBuildConstants::getDatasetQaRunnerScriptPath()],
                        $modelRoot,
                        [
                            'QA_DATASET_PATH' => $tempInputDir,
                            'QA_OUTPUT_DIR' => $tempWorkDir,
                            'QA_WORKDIR' => sprintf('%s/workdir/dataset_qa/%d', $modelRoot, $dataSet->getId()),
                            'DATASET_QA_SCOPE' => 'dataset',
                            'DATASET_QA_SOURCE_ID' => (string) $dataSet->getId(),
                            'PYTHON_ENV' => 'conda',
                            'PYTHON_VERSION' => '3.10.12',
                            ModelBuildConstants::MODEL_ROOT_ENV => $modelRoot,
                        ]
                    );
                    $process->setTimeout(null);
                    $process->start();

                    $lastHeartbeatUpdateAt = time();
                    while ($process->isRunning()) {
                        $this->syncRuntimeFilesSafely($tempWorkDir, DatasetQaPaths::getDataSetStorageDir($dataSet));
                        $shouldUpdateHeartbeat = (time() - $lastHeartbeatUpdateAt) >= self::HEARTBEAT_UPDATE_INTERVAL_SECONDS;
                        if ($this->syncProgressState($dataSet, $tempWorkDir, $shouldUpdateHeartbeat)) {
                            $lastHeartbeatUpdateAt = time();
                        }

                        sleep(self::PROCESS_POLL_INTERVAL_SECONDS);
                    }

                    $this->syncRuntimeFilesSafely($tempWorkDir, DatasetQaPaths::getDataSetStorageDir($dataSet));
                    $this->syncProgressState($dataSet, $tempWorkDir, true);

                    if (!$process->isSuccessful()) {
                        throw new \RuntimeException($this->buildFailureMessage($process, $tempWorkDir));
                    }

                    $this->datasetQaStorageManager->syncDirectory(
                        $tempWorkDir,
                        DatasetQaPaths::getDataSetStorageDir($dataSet)
                    );

                    $this->setSucceeded($dataSet);
                } catch (\Throwable $exception) {
                    $this->syncRuntimeFilesSafely($tempWorkDir, DatasetQaPaths::getDataSetStorageDir($dataSet));
                    $this->setFailed($dataSet, $exception->getMessage());

                    return true;
                } finally {
                    $filesystem->remove([$legacyOutputDir, $tempInputDir, $tempWorkDir]);
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function loadDataSet(int $dataSetId): ?DataSet
    {
        return $this->doctrine->getRepository(DataSet::class)->find($dataSetId);
    }

    private function setRunning(DataSet $dataSet): void
    {
        $em = $this->doctrine->getManagerForClass(DataSet::class);
        $dataSet
            ->setDatasetQaStatus(DatasetQaStatus::RUNNING)
            ->setDatasetQaStartedAt(new \DateTime())
            ->setDatasetQaFinishedAt(null)
            ->setDatasetQaHeartbeatAt(new \DateTime())
            ->setDatasetQaProgress(0.02)
            ->setDatasetQaProgressMessage('Preparing dataset QA workspace.')
            ->setDatasetQaErrorOutput(null);

        $em->persist($dataSet);
        $em->flush();
    }

    private function setSucceeded(DataSet $dataSet): void
    {
        $em = $this->doctrine->getManagerForClass(DataSet::class);
        $dataSet
            ->setDatasetQaStatus(DatasetQaStatus::SUCCEEDED)
            ->setDatasetQaFinishedAt(new \DateTime())
            ->setDatasetQaHeartbeatAt(new \DateTime())
            ->setDatasetQaProgress(1.0)
            ->setDatasetQaProgressMessage('Dataset QA report is ready.')
            ->setDatasetQaErrorOutput(null);

        $em->persist($dataSet);
        $em->flush();
    }

    private function setFailed(DataSet $dataSet, string $error): void
    {
        $em = $this->doctrine->getManagerForClass(DataSet::class);
        $dataSet
            ->setDatasetQaStatus(DatasetQaStatus::FAILED)
            ->setDatasetQaFinishedAt(new \DateTime())
            ->setDatasetQaHeartbeatAt(new \DateTime())
            ->setDatasetQaProgressMessage(
                $dataSet->getDatasetQaProgressMessage() ?: 'Dataset QA failed.'
            )
            ->setDatasetQaErrorOutput(substr(trim($error), -4000));

        $em->persist($dataSet);
        $em->flush();
    }

    private function syncProgressState(DataSet $dataSet, string $tempWorkDir, bool $updateHeartbeat): bool
    {
        $em = $this->doctrine->getManagerForClass(DataSet::class);
        $shouldFlush = false;

        if ($updateHeartbeat) {
            $dataSet->setDatasetQaHeartbeatAt(new \DateTime());
            $shouldFlush = true;
        }

        $progressPayload = $this->readProgressPayload($tempWorkDir . '/' . self::PROGRESS_FILE);
        if (is_array($progressPayload)) {
            $progress = $progressPayload['progress'] ?? null;
            if ($progress !== null) {
                $normalizedProgress = max(0.0, min(1.0, (float) $progress));
                $currentProgress = $dataSet->getDatasetQaProgress();
                if ($currentProgress === null || abs($currentProgress - $normalizedProgress) > 0.0001) {
                    $dataSet->setDatasetQaProgress($normalizedProgress);
                    $shouldFlush = true;
                }
            }

            $message = trim((string) ($progressPayload['message'] ?? ''));
            $message = $message !== '' ? substr($message, 0, 255) : null;
            if ($dataSet->getDatasetQaProgressMessage() !== $message) {
                $dataSet->setDatasetQaProgressMessage($message);
                $shouldFlush = true;
            }
        }

        if (!$shouldFlush) {
            return false;
        }

        $em->persist($dataSet);
        $em->flush();

        return true;
    }

    private function initializeRuntimeLogFiles(string $tempWorkDir): void
    {
        foreach (self::RUNTIME_LOG_FILES as $fileName) {
            if (!is_file($tempWorkDir . '/' . $fileName)) {
                file_put_contents($tempWorkDir . '/' . $fileName, '');
            }
        }
    }

    private function syncRuntimeFilesSafely(string $tempWorkDir, string $storageDir): void
    {
        try {
            foreach (self::RUNTIME_LOG_FILES as $fileName) {
                $this->datasetQaStorageManager->syncFile(
                    $tempWorkDir . '/' . $fileName,
                    $storageDir,
                    $fileName
                );
            }
        } catch (\Throwable) {
        }
    }

    private function buildFailureMessage(Process $process, string $tempWorkDir): string
    {
        $messages = [];
        foreach (['dataset-qa-run.err', 'dataset-qa-init.err'] as $fileName) {
            $content = $this->readFileTail($tempWorkDir . '/' . $fileName);
            if ($content !== null) {
                $messages[] = $content;
            }
        }

        $processErrorOutput = trim((string) $process->getErrorOutput());
        if ($processErrorOutput !== '') {
            $messages[] = $processErrorOutput;
        }

        $message = trim(implode("\n\n", array_filter($messages)));

        return $message !== '' ? substr($message, -4000) : 'Dataset QA runner failed.';
    }

    private function readFileTail(string $path, int $maxBytes = 4000): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = trim((string) file_get_contents($path));

        return $content === '' ? null : substr($content, -$maxBytes);
    }

    private function readProgressPayload(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function toImportExportRelativePath(string $absolutePath): string
    {
        $prefix = '/data/var/data/import_export/';
        if (str_starts_with($absolutePath, $prefix)) {
            return ltrim(substr($absolutePath, strlen($prefix)), '/');
        }

        return ltrim($absolutePath, '/');
    }
}
