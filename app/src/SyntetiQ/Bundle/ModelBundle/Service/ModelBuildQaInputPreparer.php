<?php

namespace SyntetiQ\Bundle\ModelBundle\Service;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\Filesystem\Filesystem;
use SyntetiQ\Bundle\DataSetBundle\Service\YoloDatasetMaterializer;
use SyntetiQ\Bundle\ModelBundle\Dataset\BuildItemGroupResolver;
use SyntetiQ\Bundle\ModelBundle\Dataset\DatasetBuilder;
use SyntetiQ\Bundle\ModelBundle\Dataset\ModelBuildDatasetItemProvider;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;

class ModelBuildQaInputPreparer
{
    public function __construct(
        private DatasetBuilder $datasetBuilder,
        private ModelBuildDatasetItemProvider $datasetItemProvider,
        private YoloDatasetMaterializer $yoloDatasetMaterializer,
        private FileManager $fileManagerModel,
        private BuildItemGroupResolver $buildItemGroupResolver
    ) {}

    public function prepare(ModelBuild $modelBuild): string
    {
        if ($this->datasetBuilder->getEngineDatasetFormat($modelBuild) === 'yolo') {
            return 'ds';
        }

        $items = $this->datasetItemProvider->getItems($modelBuild, true);
        $resolvedGroups = $this->buildItemGroupResolver->resolve($modelBuild, $items);

        $filesystem = new Filesystem();
        $filesystem->remove(DatasetQaPaths::getBuildInputDir($modelBuild));

        $this->yoloDatasetMaterializer->materialize(
            $items,
            $this->fileManagerModel,
            DatasetQaPaths::getBuildInputRelativePath($modelBuild),
            false,
            $resolvedGroups,
            true
        );

        return 'dataset_qa_input';
    }
}
