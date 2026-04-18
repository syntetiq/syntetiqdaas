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
use SyntetiQ\Bundle\DataSetBundle\Model\Group;

class PascalVoc implements SplitInterface
{
    public function __construct(
        private FileManager $fileManager,
        private FileManager $fileManagerModel,
        private Serializer $serializer,
        private ModelBuildDatasetItemProvider $datasetItemProvider,
        private BuildItemGroupResolver $buildItemGroupResolver,
    ) {}

    public function isApplicable(string $type): bool
    {
        return $type === 'pascal-voc';
    }

    public function split(ModelBuild $modelBuild): array
    {
        $labels = [];
        $path = sprintf(
            './workdir/builds/%d_%d/ds/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );

        $items = $this->datasetItemProvider->getItems($modelBuild, true);
        $resolvedGroups = $this->buildItemGroupResolver->resolve($modelBuild, $items);

        $imgsName = [];
        $annotationsLabels = [];

        $i = 0;
        /** @var DataSetItem $item */
        foreach ($items as $k => $item) {
            $objects = $item->getObjectConfiguration();

            if ($objects->isEmpty()) {
                continue;
            }

            /** @var File $image */
            $image = $item->getImage();

            $fileName = substr($image->getFilename(), 0, - (strlen($image->getExtension()) + 1));

            $annotationsLabels = $this->addImageToAnnotationLabels($annotationsLabels, $fileName);


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
                        'occluded' => 0,
                        'bndbox' => [
                            'xmin' => $object->getMinX(),
                            'ymin' => $object->getMinY(),
                            'xmax' => $object->getMaxX(),
                            'ymax' => $object->getMaxY(),
                        ]
                    ];

                $annotationsLabels = $this->addLabelToAnnotationLabels($annotationsLabels, $object->getName(), $fileName);
            }
            $annotation->setObject($objectsData);

            $data = $this->serializer->serialize($annotation, 'xml', [XmlEncoder::ROOT_NODE_NAME => 'annotation']);

            $this->fileManagerModel->writeToStorage(
                $data,
                $path . 'Annotations/'
                    . $fileName . '.xml'
            );

            $imgsName[] = [
                'fileName' => $fileName,
                'group' => $resolvedGroups[$item->getId()] ?? Group::TRAIN,
            ];

            $this->fileManagerModel->writeStreamToStorage(
                $this->fileManager->getStream($image->getFilename()),
                $path . 'JPEGImages/' .
                    substr($image->getFilename(), 0, - (strlen($image->getExtension()) + 1)) . '.jpg'
            );

            $i++;
        }

        $this->fileManagerModel->writeToStorage(
            implode("\n", array_keys($labels)),
            $path . 'labels.txt'
        );
        $this->fileManagerModel->writeToStorage(
            implode(",", array_keys($labels)),
            $path . 'labels_gen.txt'
        );

        $train_list = [];
        $val_list = [];
        $test_list = [];
        foreach ($imgsName as $item) {
            if ($item['group'] === Group::TEST) {
                $test_list[] = $item['fileName'];
            } elseif ($item['group'] === Group::VAL) {
                $val_list[] = $item['fileName'];
            } else {
                $train_list[] = $item['fileName'];
            }
        }

        # create the dataset files
        $this->fileManagerModel->writeToStorage(
            implode("\n", $train_list),
            $path . 'ImageSets/Main/train.txt'
        );

        $this->fileManagerModel->writeToStorage(
            implode("\n", $val_list),
            $path . 'ImageSets/Main/val.txt'
        );

        $this->fileManagerModel->writeToStorage(
            implode(
                "\n",
                [
                    implode("\n", $train_list),
                    implode("\n", $val_list)
                ]
            ),
            $path . 'ImageSets/Main/trainval.txt'
        );

        $this->fileManagerModel->writeToStorage(
            implode("\n", $test_list),
            $path . 'ImageSets/Main/test.txt'
        );

        # create the individiual files for each label
        foreach ($labels as $label => $index) {
            $trainData = [];
            foreach ($train_list as $name) {
                if (isset($annotationsLabels[$name][$label])) {
                    $trainData[] = $name . " 1";
                } else {
                    $trainData[] = $name . " -1";
                }
            }

            $this->fileManagerModel->writeToStorage(
                implode("\n", $trainData),
                $path . 'ImageSets/Main/' . $label  . '_train.txt'
            );

            $valData = [];
            foreach ($val_list as $name) {
                if (isset($annotationsLabels[$name])) {
                    if (isset($annotationsLabels[$name][$label])) {
                        $valData[] = $name . " 1";
                    } else {
                        $valData[] = $name . " -1";
                    }
                }
            }
            $this->fileManagerModel->writeToStorage(
                implode("\n", $valData),
                $path . 'ImageSets/Main/' . $label . '_val.txt'
            );


            $testData = [];
            foreach ($test_list as $name) {
                if (isset($annotationsLabels[$name])) {
                    if (isset($annotationsLabels[$name][$label])) {
                        $testData[] = $name . " 1";
                    } else {
                        $testData[] = $name . " -1";
                    }
                }
            }

            $this->fileManagerModel->writeToStorage(
                implode("\n", $testData),
                $path . 'ImageSets/Main/' . $label . '_test.txt'
            );
        }

        return $labels;
    }

    private function addLabelToAnnotationLabels($annotationsLabels, $label, $fileName)
    {
        if (!isset($annotationsLabels[$label])) {
            $annotationsLabels[$label] = [];
        }

        $annotationsLabels[$label][] = $fileName;
        $annotationsLabels[$fileName][$label] = $label;

        return $annotationsLabels;
    }

    private function addImageToAnnotationLabels($annotationsLabels, $fileName)
    {
        if (!isset($annotationsLabels[$fileName])) {
            $annotationsLabels[$fileName] = [];
        }

        return $annotationsLabels;
    }
}
