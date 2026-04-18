<?php

namespace SyntetiQ\Bundle\ModelBundle\Service;

use Symfony\Component\Routing\RouterInterface;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;

class BuildRuntimeLogViewBuilder
{
    private const RUNTIME_FILE_DEFINITIONS = [
        ['path' => 'build-init.log', 'label' => 'Init Log', 'description' => 'Environment/bootstrap stdout before training starts.'],
        ['path' => 'build-init.err', 'label' => 'Init Errors', 'description' => 'Environment/bootstrap stderr before training starts.'],
        ['path' => 'build.log', 'label' => 'Build Log', 'description' => 'Main training stdout produced by the runner.'],
        ['path' => 'build.err', 'label' => 'Build Errors', 'description' => 'Main training stderr produced by the runner.'],
        ['path' => 'runner.out', 'label' => 'Runner Stdout', 'description' => 'Detached runner bootstrap output.'],
        ['path' => 'runner.err', 'label' => 'Runner Errors', 'description' => 'Detached runner bootstrap errors.'],
    ];

    public function __construct(
        private RouterInterface $router,
        private DatasetQaStorageManager $datasetQaStorageManager
    ) {}

    public static function getRuntimeRelativePaths(): array
    {
        return array_map(
            static fn (array $definition): string => $definition['path'],
            self::RUNTIME_FILE_DEFINITIONS
        );
    }

    public function buildForModelBuild(ModelBuild $modelBuild): array
    {
        $status = 'pending';
        if ($modelBuild->getFinishedAt() instanceof \DateTimeInterface) {
            $status = 'finished';
        } elseif ($modelBuild->getStartedAt() instanceof \DateTimeInterface) {
            $status = 'running';
        }

        return [
            'status' => $status,
            'startedAt' => $modelBuild->getStartedAt(),
            'finishedAt' => $modelBuild->getFinishedAt(),
            'files' => $this->buildFiles($modelBuild),
        ];
    }

    private function buildFiles(ModelBuild $modelBuild): array
    {
        $result = [];
        foreach (self::RUNTIME_FILE_DEFINITIONS as $definition) {
            $path = $definition['path'];
            if (!$this->datasetQaStorageManager->hasFile(DatasetQaPaths::getBuildLogsStorageDir($modelBuild), $path)) {
                continue;
            }

            $result[] = [
                'label' => $definition['label'],
                'description' => $definition['description'],
                'path' => $path,
                'url' => $this->router->generate('syntetiq_model_model_build_runtime_file', [
                    'id' => $modelBuild->getId(),
                    'path' => $path,
                    'download' => 0,
                ]),
                'downloadUrl' => $this->router->generate('syntetiq_model_model_build_runtime_file', [
                    'id' => $modelBuild->getId(),
                    'path' => $path,
                    'download' => 1,
                ]),
            ];
        }

        return $result;
    }
}
