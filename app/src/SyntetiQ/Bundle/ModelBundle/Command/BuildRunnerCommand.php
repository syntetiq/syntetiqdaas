<?php

namespace SyntetiQ\Bundle\ModelBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;
use SyntetiQ\Bundle\ModelBundle\Model\ModelBuildConstants;
use Symfony\Component\Process\Process;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use SyntetiQ\Bundle\ModelBundle\Dataset\DatasetBuilder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;
use SyntetiQ\Bundle\ModelBundle\Provider\ModelBuildArtifactSyncMessageSender;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;
use SyntetiQ\Bundle\ModelBundle\Service\ModelBuildQaInputPreparer;

#[AsCommand(name: 'oro:cron:syntetiq-build-runner', description: 'Run builds and manage statuses.')]
class BuildRunnerCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    const PARALLEL_RUN = 1;
    private const PROCESS_STARTUP_GRACE_SECONDS = 30;
    private const DATASET_QA_PROGRESS_FILE = 'dataset_qa/progress.json';

    public function __construct(
        private ManagerRegistry $doctrine,
        private FileManager $fileManager,
        private FileManager $fileManagerModel,
        private DatasetBuilder $datasetBuilder,
        private ModelBuildQaInputPreparer $modelBuildQaInputPreparer,
        private DatasetQaStorageManager $datasetQaStorageManager,
        private ModelBuildArtifactSyncMessageSender $modelBuildArtifactSyncMessageSender
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return true;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Run builds and manage statuses.');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('<comment>Cron running</comment>'));

        /**
         * @var ObjectRepository
         */
        $modelBuildRepository = $this->doctrine->getRepository(ModelBuild::class);

        $queryBuilder = $modelBuildRepository->createQueryBuilder('e')
            ->where('e.finishedAt IS NULL')
            ->andWhere('e.startedAt IS NOT NULL')
            ->andWhere('e.initialized = true')
            ->orderBy('e.id', 'ASC')
            ->getQuery();

        $startedBuilds = $queryBuilder->getResult();

        foreach ($startedBuilds as $build) {
            $this->processStarted($build);
        }

        // lock to have one run in time. run cron on one instance only
        if (empty($startedBuilds) || count($startedBuilds) < self::PARALLEL_RUN) {
            $queryBuilder = $modelBuildRepository->createQueryBuilder('e')
                ->where('e.finishedAt IS NULL')
                ->andWhere('e.startedAt IS NULL')
                ->andWhere('e.initialized = true')
                ->orderBy('e.id', 'ASC')
                ->getQuery();

            $newBuilds = $queryBuilder->getResult();

            foreach ($newBuilds as $build) {
                $this->startBuild($build);

                break; // todo: refactor to start defined count (PARALLEL_RUN)
            }
        }

        $output->writeln(sprintf('<info>Cron finished success</info>: [data]'));

        return Command::SUCCESS;
    }

    private function processStarted(ModelBuild $modelBuild): void
    {
        $path = sprintf(
            './workdir/builds/%d_%d/pid.txt',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );

        try {
            $this->updateBuildStatuses($modelBuild);
            $this->modelBuildArtifactSyncMessageSender->send($modelBuild);

            $pid = trim((string) $this->fileManagerModel->getContent($path));
            if (!$this->hasValidPid($pid)) {
                if (!$this->isBuildStartupTimedOut($modelBuild)) {
                    return;
                }

                throw new \RuntimeException(sprintf(
                    'Build #%d did not produce a valid PID within %d seconds.',
                    $modelBuild->getId(),
                    self::PROCESS_STARTUP_GRACE_SECONDS
                ));
            }

            if (!$this->isProcessRunningAndNotZombie($pid)) {
                $this->updateBuildStatuses($modelBuild);
                $this->finalizeIncompleteDatasetQa($modelBuild);
                $this->modelBuildArtifactSyncMessageSender->send($modelBuild);
                $this->finishBuild($modelBuild);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->updateBuildStatuses($modelBuild);
            $this->finalizeIncompleteDatasetQa($modelBuild);
            $this->appendBuildError($modelBuild, $e->getMessage());
            $this->modelBuildArtifactSyncMessageSender->send($modelBuild);
            $this->finishBuild($modelBuild);
        }
    }

    private function finishBuild(ModelBuild $modelBuild): void
    {
        $modelBuildEM = $this->doctrine->getManager();

        $finishedDatetime = new \DateTime();
        $modelBuild->setFinishedAt($finishedDatetime);
        $modelBuildEM->persist($modelBuild);
        $modelBuildEM->flush();
    }

    private function updateBuildStatuses(ModelBuild $modelBuild): void
    {
        $modelBuildEM = $this->doctrine->getManager();
        $this->updateDatasetQaStatus($modelBuild);

        $path = sprintf(
            './workdir/builds/%d_%d/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
        $outputFilePath = $path . 'build.log';
        $errorFilePath = $path . 'build.err';
        $runnerErrorFilePath = $path . 'runner.err';

        try {
            $output = $this->fileManagerModel->getContent($outputFilePath);
        } catch (Exception $e) {
            $modelBuildEM->flush();
            return;
        }

        $errorOutputParts = [];
        try {
            $buildErrorOutput = trim(substr((string) $this->fileManagerModel->getContent($errorFilePath), -1024));
            if ($buildErrorOutput !== '') {
                $errorOutputParts[] = $buildErrorOutput;
            }
        } catch (Exception) {
        }

        try {
            $runnerErrorOutput = trim(substr((string) $this->fileManagerModel->getContent($runnerErrorFilePath), -1024));
            if ($runnerErrorOutput !== '') {
                $errorOutputParts[] = $runnerErrorOutput;
            }
        } catch (Exception) {
        }

        $errorOutputParts = array_values(array_unique($errorOutputParts));
        $errorOutput = implode("\n", $errorOutputParts);

        preg_match_all('/=(INFO|WARN|ERROR)=(.*)\n/', $output, $infoMsgs);
        preg_match_all('/=MODEL=(.*)\n/', $output, $model);

        if (isset($model[1][0])) {
            try {
                if (null === $modelBuild->getArtifact()) {
                    $modelEntity = $this->fileManager->createFileEntity($model[1][0]);
                    $modelBuildEM->persist($modelEntity);
                    $modelBuild->setArtifact($modelEntity);
                }
            } catch (\Exception $e) {
                $errorOutput .= $e->getMessage();
            }
        }

        $msg = implode("\n", $infoMsgs[2]);
        $modelBuild->setOutput($msg);
        $modelBuild->setErrorOutput($errorOutput);
        $modelBuildEM->flush();
    }

    private function startBuild(ModelBuild $modelBuild): void
    {
        $env = $modelBuild->getEnv();
        $epoch = $modelBuild->getEpoch();
        $pretrainedModel = $modelBuild->getPretrainedModel();
        $calculateDatasetQa = $modelBuild->isCalculateDatasetQa();

        $modelBuildEM = $this->doctrine->getManager();
        $datetime = new \DateTime();

        $labels = $this->buildDataset($modelBuild);
        $labels = array_map(function ($k, $v) {
            return $k;
        }, array_keys($labels), $labels);
        $datasetQaInputRelativePath = $calculateDatasetQa
            ? $this->modelBuildQaInputPreparer->prepare($modelBuild)
            : '';

        $modelBuild
            ->setStartedAt($datetime)
            ->setDatasetQaStatus($calculateDatasetQa ? DatasetQaStatus::QUEUED : DatasetQaStatus::IDLE)
            ->setDatasetQaStartedAt(null)
            ->setDatasetQaFinishedAt(null)
            ->setDatasetQaHeartbeatAt(null)
            ->setDatasetQaProgress($calculateDatasetQa ? 0.0 : null)
            ->setDatasetQaProgressMessage($calculateDatasetQa ? 'Queued for dataset QA execution.' : null)
            ->setDatasetQaErrorOutput(null);

        try {
            $this->datasetQaStorageManager->clearPrefix(DatasetQaPaths::getBuildBaseStorageDir($modelBuild));
        } catch (\Throwable $exception) {
            $this->logger?->warning(sprintf(
                'Unable to clear processing artifacts for build #%d: %s',
                $modelBuild->getId(),
                $exception->getMessage()
            ));
        }

        $modelBuildEM->persist($modelBuild);
        $modelBuildEM->flush();

        $imgSize = $this->resolveImageSize($modelBuild->getImageSize());

        $buildRelativeDir = sprintf(
            './workdir/builds/%d_%d/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
        $buildDir = ModelBuildConstants::getBuildDir($modelBuild);
        $pretrainedModelPath = $this->prepareUploadedPretrainedModel($modelBuild, $pretrainedModel, $buildRelativeDir);

        // Run detached process with nohup and setsid to avoid hangups and to spy
        $runnerScriptPath = ModelBuildConstants::getRunnerScriptPath();
        $modelRoot = ModelBuildConstants::getModelRoot();
        $runnerOutputPath = $buildDir . 'runner.out';
        $runnerErrorPath = $buildDir . 'runner.err';
        $command = 'bash -lc ' . escapeshellarg(sprintf(
            'nohup setsid %s > %s 2> %s & echo $!',
            escapeshellarg($runnerScriptPath),
            escapeshellarg($runnerOutputPath),
            escapeshellarg($runnerErrorPath)
        ));

        $process = Process::fromShellCommandline(
            $command,
            $modelRoot,
            [
                'MODEL_TRAIN_ENGINE' => $modelBuild->getEngine(),
                'MODEL_BUILD_ID' => sprintf(
                    '%d_%d',
                    $modelBuild->getModel()->getId(),
                    $modelBuild->getId()
                ),
                'USE_PRETRAINED' => $pretrainedModel instanceof ModelPretrained ? 'true' : 'false',
                'PRETRAINED_MODEL_FILE' => $pretrainedModelPath,
                'LABEL_LIST' => json_encode($labels),
                'EPOCH' => $epoch,
                'PYTHON_ENV' => $env,
                'PYTHON_VERSION' => $this->datasetBuilder->getEnginePythonVersion($modelBuild),
                'MODEL_NAME' => $modelBuild->getEngineModel(),
                'BOX_FORMAT' => $this->datasetBuilder->getEngineBoxFormat($modelBuild),
                'IMG_SIZE' => $imgSize,
                'DATASET_QA_ENABLED' => $calculateDatasetQa ? 'true' : 'false',
                'DATASET_QA_INPUT_RELATIVE_PATH' => $datasetQaInputRelativePath,
                'DATASET_QA_SCOPE' => 'build',
                'DATASET_QA_SOURCE_ID' => sprintf('%d_%d', $modelBuild->getModel()->getId(), $modelBuild->getId()),
                'DEEPSTREAM_EXPORT' => $modelBuild->isDeepstreamExport() ? 'true' : 'false',
                ModelBuildConstants::MODEL_ROOT_ENV => $modelRoot,
            ]
        );

        $process->run(); // runs synchronously
        $pid = trim($process->getOutput());
        if (!$this->hasValidPid($pid)) {
            throw new \RuntimeException(sprintf(
                'Unable to start build #%d. Runner did not return a valid PID. stderr: %s',
                $modelBuild->getId(),
                trim($process->getErrorOutput())
            ));
        }

        $this->fileManagerModel->writeToStorage($pid, $buildRelativeDir . 'pid.txt');
        $this->modelBuildArtifactSyncMessageSender->send($modelBuild);
    }

    protected function loadModelBuildEntity(int $modelBuildId)
    {
        $modelBuildRepository = $this->doctrine->getRepository(ModelBuild::class);

        return $modelBuildRepository->find($modelBuildId);
    }

    protected function buildDataset(ModelBuild $modelBuild): array
    {
        return $this->datasetBuilder->build($modelBuild);
    }

    private function resolveImageSize(string $imageSize): int
    {
        return match ($imageSize) {
            ImageSize::SIZE_320_320 => 320,
            ImageSize::SIZE_1280_1280 => 1280,
            default => 640,
        };
    }

    private function isProcessRunningAndNotZombie($pid): bool
    {
        $process = new Process(['ps', '-o', 'stat=', '-p', (string) $pid]);
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        $state = strtoupper(trim($process->getOutput()));
        if ($state === '') {
            return false;
        }

        return !str_starts_with($state, 'Z');
    }

    private function prepareUploadedPretrainedModel(
        ModelBuild $modelBuild,
        ?ModelPretrained $pretrainedModel,
        string $buildRelativeDir
    ): string {
        if (!$pretrainedModel instanceof ModelPretrained) {
            return '';
        }

        $file = $pretrainedModel->getFile();
        if (null === $file) {
            throw new \RuntimeException(sprintf('Uploaded pretrained model #%d does not have an attached file.', $pretrainedModel->getId()));
        }

        $content = $this->fileManager->getContent($file);
        if ($content === null || $content === '') {
            throw new \RuntimeException(sprintf('Uploaded pretrained model #%d is empty or unavailable.', $pretrainedModel->getId()));
        }

        $extension = strtolower((string) pathinfo($pretrainedModel->getOriginalFilename(), PATHINFO_EXTENSION));
        $fileName = sprintf(
            'uploaded-pretrained-%d_%d.%s',
            $modelBuild->getId(),
            $pretrainedModel->getId(),
            $extension !== '' ? $extension : 'bin'
        );

        $this->fileManagerModel->writeToStorage($content, $buildRelativeDir . $fileName);

        return $fileName;
    }

    private function hasValidPid(string $pid): bool
    {
        return ctype_digit($pid) && (int) $pid > 0;
    }

    private function isBuildStartupTimedOut(ModelBuild $modelBuild): bool
    {
        $startedAt = $modelBuild->getStartedAt();
        if (!$startedAt instanceof \DateTimeInterface) {
            return true;
        }

        return (time() - $startedAt->getTimestamp()) >= self::PROCESS_STARTUP_GRACE_SECONDS;
    }

    private function appendBuildError(ModelBuild $modelBuild, string $message): void
    {
        $modelBuildEM = $this->doctrine->getManager();
        $existingError = trim((string) $modelBuild->getErrorOutput());
        $fullMessage = trim($existingError . "\n" . $message);

        $modelBuild->setErrorOutput($fullMessage);
        $modelBuildEM->persist($modelBuild);
        $modelBuildEM->flush();
    }

    private function updateDatasetQaStatus(ModelBuild $modelBuild): void
    {
        $status = $this->readBuildFile($modelBuild, 'dataset_qa.status');
        if (null === $status || !in_array($status, DatasetQaStatus::values(), true)) {
            return;
        }

        $modelBuild->setDatasetQaStatus($status);

        $startedAt = $this->readBuildFile($modelBuild, 'dataset_qa.started_at');
        if ($startedAt !== null) {
            try {
                $modelBuild->setDatasetQaStartedAt(new \DateTime($startedAt));
            } catch (\Throwable) {
            }
        }

        $finishedAt = $this->readBuildFile($modelBuild, 'dataset_qa.finished_at');
        if ($finishedAt !== null) {
            try {
                $modelBuild->setDatasetQaFinishedAt(new \DateTime($finishedAt));
            } catch (\Throwable) {
            }
        }

        $heartbeatAt = $this->readBuildFile($modelBuild, 'dataset_qa.heartbeat_at');
        if ($heartbeatAt !== null) {
            try {
                $modelBuild->setDatasetQaHeartbeatAt(new \DateTime($heartbeatAt));
            } catch (\Throwable) {
            }
        }

        $progressPayload = $this->readBuildJsonFile($modelBuild, self::DATASET_QA_PROGRESS_FILE);
        if (is_array($progressPayload)) {
            $progress = $progressPayload['progress'] ?? null;
            if ($progress !== null) {
                $modelBuild->setDatasetQaProgress(max(0.0, min(1.0, (float) $progress)));
            }

            $message = trim((string) ($progressPayload['message'] ?? ''));
            $modelBuild->setDatasetQaProgressMessage($message !== '' ? substr($message, 0, 255) : null);
        }

        $qaError = $this->readBuildFile($modelBuild, 'dataset_qa.err');
        if (null !== $qaError) {
            $modelBuild->setDatasetQaErrorOutput(substr($qaError, -4000));
        }

        if ($status === DatasetQaStatus::SUCCEEDED) {
            $modelBuild
                ->setDatasetQaHeartbeatAt(new \DateTime())
                ->setDatasetQaProgress(1.0)
                ->setDatasetQaProgressMessage('Dataset QA report is ready.')
                ->setDatasetQaErrorOutput(null);
        } elseif ($status === DatasetQaStatus::FAILED && !$modelBuild->getDatasetQaProgressMessage()) {
            $modelBuild->setDatasetQaProgressMessage('Dataset QA failed.');
        }
    }

    private function readBuildFile(ModelBuild $modelBuild, string $fileName): ?string
    {
        $path = DatasetQaPaths::getBuildStatusRelativePath($modelBuild, $fileName);

        try {
            $content = $this->fileManagerModel->getContent($path);
        } catch (\Throwable) {
            return null;
        }

        $content = trim((string) $content);

        return $content === '' ? null : $content;
    }

    private function readBuildJsonFile(ModelBuild $modelBuild, string $fileName): ?array
    {
        $content = $this->readBuildFile($modelBuild, $fileName);
        if (null === $content) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function finalizeIncompleteDatasetQa(ModelBuild $modelBuild): void
    {
        if (
            !$modelBuild->isCalculateDatasetQa()
            || !in_array($modelBuild->getDatasetQaStatus(), [DatasetQaStatus::QUEUED, DatasetQaStatus::RUNNING], true)
        ) {
            return;
        }

        $message = 'Dataset QA did not report completion before the build runner stopped.';
        $progressMessage = trim((string) $modelBuild->getDatasetQaProgressMessage());
        if ($progressMessage === '' || $progressMessage === 'Queued for dataset QA execution.') {
            $progressMessage = $message;
        }

        $modelBuild
            ->setDatasetQaStatus(DatasetQaStatus::FAILED)
            ->setDatasetQaFinishedAt(new \DateTime())
            ->setDatasetQaHeartbeatAt(new \DateTime())
            ->setDatasetQaProgressMessage(substr($progressMessage, 0, 255));

        if (!trim((string) $modelBuild->getDatasetQaErrorOutput())) {
            $modelBuild->setDatasetQaErrorOutput($message);
        }
    }
}
