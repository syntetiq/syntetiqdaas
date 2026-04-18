<?php

namespace SyntetiQ\Bundle\ModelBundle\Service;

use Psr\Log\LoggerInterface;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\ModelBuildConstants;

class ModelBuildArtifactSyncer
{
    public function __construct(
        private DatasetQaStorageManager $datasetQaStorageManager,
        private ?LoggerInterface $logger = null
    ) {}

    public function sync(ModelBuild $modelBuild): void
    {
        $this->syncBuildFiles(
            $modelBuild,
            BuildRuntimeLogViewBuilder::getRuntimeRelativePaths(),
            DatasetQaPaths::getBuildLogsStorageDir($modelBuild)
        );

        $this->syncBuildFiles(
            $modelBuild,
            DatasetQaReportViewBuilder::getRuntimeRelativePaths(),
            DatasetQaPaths::getBuildStorageDir($modelBuild)
        );

        $this->syncBuildFileMappings(
            $modelBuild,
            DatasetQaReportViewBuilder::getBuildStateSyncDefinitions(),
            DatasetQaPaths::getBuildStorageDir($modelBuild)
        );

        $localOutputDir = DatasetQaPaths::getBuildLocalOutputDir($modelBuild);
        if (is_file($localOutputDir . '/report.json')) {
            try {
                $this->datasetQaStorageManager->syncDirectory(
                    $localOutputDir,
                    DatasetQaPaths::getBuildStorageDir($modelBuild)
                );
            } catch (\Throwable $exception) {
                $this->logger?->warning(sprintf(
                    'Unable to sync dataset QA artifact directory for build #%d: %s',
                    $modelBuild->getId(),
                    $exception->getMessage()
                ));
            }
        }

        $this->syncBuildFiles(
            $modelBuild,
            DatasetQaReportViewBuilder::getRuntimeRelativePaths(),
            DatasetQaPaths::getBuildStorageDir($modelBuild)
        );

        $this->syncBuildFileMappings(
            $modelBuild,
            DatasetQaReportViewBuilder::getBuildStateSyncDefinitions(),
            DatasetQaPaths::getBuildStorageDir($modelBuild)
        );
    }

    private function syncBuildFiles(ModelBuild $modelBuild, array $relativePaths, string $storageDir): void
    {
        $buildDir = rtrim(ModelBuildConstants::getBuildDir($modelBuild), '/');

        foreach ($relativePaths as $relativePath) {
            try {
                $this->datasetQaStorageManager->syncFile(
                    $buildDir . '/' . ltrim($relativePath, '/'),
                    $storageDir,
                    $relativePath
                );
            } catch (\Throwable $exception) {
                $this->logger?->warning(sprintf(
                    'Unable to sync build artifact "%s" for build #%d: %s',
                    $relativePath,
                    $modelBuild->getId(),
                    $exception->getMessage()
                ));
            }
        }
    }

    private function syncBuildFileMappings(ModelBuild $modelBuild, array $definitions, string $storageDir): void
    {
        $buildDir = rtrim(ModelBuildConstants::getBuildDir($modelBuild), '/');

        foreach ($definitions as $definition) {
            $localPath = trim((string) ($definition['localPath'] ?? ''));
            $storagePath = trim((string) ($definition['storagePath'] ?? ''));
            if ($localPath === '' || $storagePath === '') {
                continue;
            }

            try {
                $this->datasetQaStorageManager->syncFile(
                    $buildDir . '/' . ltrim($localPath, '/'),
                    $storageDir,
                    $storagePath
                );
            } catch (\Throwable $exception) {
                $this->logger?->warning(sprintf(
                    'Unable to sync build artifact "%s" for build #%d: %s',
                    $storagePath,
                    $modelBuild->getId(),
                    $exception->getMessage()
                ));
            }
        }
    }
}
