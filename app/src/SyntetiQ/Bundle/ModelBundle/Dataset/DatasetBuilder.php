<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class DatasetBuilder
{
    private array $splitters;
    private array $engines;

    public function __construct(
        iterable $splitters,
        array $engines
    ) {
        $this->splitters = $splitters instanceof \Traversable ? iterator_to_array($splitters) : $splitters;
        $this->engines = $engines;
    }

    public function build(ModelBuild $modelBuild): array
    {
        $modelEngine = $modelBuild->getEngine();
        $datasetFormat = $this->engines[$modelEngine]['dataset_format'];

        /** @var SplitInterface $splitter */
        foreach($this->splitters as $splitter) {
            if ($splitter->isApplicable($datasetFormat ?? '')) {
                return $splitter->split($modelBuild);
            }
        }

        return [];
    }

    public function getEngineBoxFormat(ModelBuild $modelBuild): string
    {
        $modelEngine = $modelBuild->getEngine();
        $boxFormat = $this->engines[$modelEngine]['dataset_box_format'];

        return $boxFormat ?? '';
    }

    public function getEngineDatasetFormat(ModelBuild $modelBuild): string
    {
        $modelEngine = $modelBuild->getEngine();
        $datasetFormat = $this->engines[$modelEngine]['dataset_format'];

        return $datasetFormat ?? '';
    }

    public function getEnginePythonVersion(ModelBuild $modelBuild): string
    {
        $modelEngine = $modelBuild->getEngine();
        $version = $this->engines[$modelEngine]['python_version'];

        return $version ?? '';
    }
}
