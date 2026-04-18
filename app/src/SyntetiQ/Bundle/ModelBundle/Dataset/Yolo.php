<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use SyntetiQ\Bundle\DataSetBundle\Service\YoloDatasetMaterializer;

class Yolo implements SplitInterface
{
    public function __construct(
        private FileManager $fileManagerModel,
        private ModelBuildDatasetItemProvider $datasetItemProvider,
        private YoloDatasetMaterializer $yoloDatasetMaterializer,
        private BuildItemGroupResolver $buildItemGroupResolver
    ) {}

    public function isApplicable(string $type): bool
    {
        return $type === 'yolo';
    }

    public function split(ModelBuild $modelBuild): array
    {
        $path = sprintf(
            './workdir/builds/%d_%d/ds/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );

        $items = $this->datasetItemProvider->getItems($modelBuild, true);
        $resolvedGroups = $this->buildItemGroupResolver->resolve($modelBuild, $items);

        return $this->yoloDatasetMaterializer->materialize(
            $items,
            $this->fileManagerModel,
            $path,
            false,
            $resolvedGroups,
            false
        );
    }
}
