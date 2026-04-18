<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Model\Annotation;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;

class PascalVocXml implements SplitInterface
{
    public function __construct(
        private FileManager $fileManager,
        private FileManager $fileManagerModel,
        private Serializer $serializer,
        private ModelBuildDatasetItemProvider $datasetItemProvider,
    ) {}

    public function isApplicable(string $type): bool
    {
        return $type === 'pascal-voc-xml';
    }

    public function split(ModelBuild $modelBuild): array
    {
        $labels = [];
        $path = sprintf(
            '../model/workdir/builds/%d_%d/ds/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );

        $items = $this->datasetItemProvider->getItems($modelBuild, true);

        /** @var DataSetItem $item */
        foreach ($items as $k => $item) {
            $objects = $item->getObjectConfiguration();

            if ($objects->isEmpty()) {
                continue;
            }

            /** @var File $image */
            $image = $item->getImage();
            $annotation = new Annotation();
            $annotation->setFilename($image->getFilename());
            $annotation->setPath($image->getFilename());
            $annotation->setSource(['database' => 'syntetiq']);

            $annotation->setSize([
                'width' => $item->getImgWidth(),
                'height' => $item->getImgHeight(),
                'depth' => 3
            ]);
            $annotation->setSegmented('0');

            $objectsData = [];
            /** @var ItemObjectConfiguration $object */
            foreach ($objects as $object) {

                if (!array_key_exists($object->getName(), $labels)) {
                    $labels[$object->getName()] = count($labels) + 1;
                }

                $objectsData[] =
                    [
                        'name' => $object->getName(),
                        'pose' => 'Unspecified',
                        'truncated' => 0,
                        'difficult' => 0,
                        'bndbox' => [
                            'xmin' => $object->getMinX(),
                            'ymin' => $object->getMinY(),
                            'xmax' => $object->getMaxX(),
                            'ymax' => $object->getMaxY(),
                        ]
                    ];
            }
            $annotation->setObject($objectsData);

            $setTypeFolder = $k % 5 == 0 ? 'valid' : 'train';

            $data = $this->serializer->serialize($annotation, 'xml', [XmlEncoder::ROOT_NODE_NAME => 'annotation']);
            $this->fileManagerModel->writeToStorage(
                $data,
                $path . $setTypeFolder . '/' .
                    substr($image->getFilename(), 0, - (strlen($image->getExtension()) + 1)) . '.xml'
            );

            $imageContent = $this->fileManager->getFileContent($image->getFilename());
            $this->fileManagerModel->writeToStorage(
                $imageContent,
                $path . $setTypeFolder . '/'
                    . substr($image->getFilename(), 0, - (strlen($image->getExtension()) + 1)) . '.jpg'
            );
        }

        return $labels;
    }
}
