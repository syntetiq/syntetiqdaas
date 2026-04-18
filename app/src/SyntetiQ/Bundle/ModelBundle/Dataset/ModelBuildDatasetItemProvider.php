<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class ModelBuildDatasetItemProvider
{
    public function getAvailableTags(ModelBuild $modelBuild): array
    {
        $dataset = $modelBuild->getModel()->getDataset();
        if (null === $dataset) {
            return [];
        }

        $tags = [];
        foreach ($dataset->getItems() as $item) {
            foreach ($item->getTags() as $tag) {
                if ($tag === '' || in_array($tag, $tags, true)) {
                    continue;
                }

                $tags[] = $tag;
            }
        }

        sort($tags);

        return $tags;
    }

    /**
     * @return DataSetItem[]
     */
    public function getItems(ModelBuild $modelBuild, bool $requireAnnotations = false): array
    {
        $dataset = $modelBuild->getModel()->getDataset();
        if (null === $dataset) {
            return [];
        }

        $items = [];
        $selectedTags = $modelBuild->getTags();
        foreach ($dataset->getItems() as $item) {
            if ($modelBuild->isReadyOnly() && !$item->isReady()) {
                continue;
            }

            if ($selectedTags !== [] && array_intersect($item->getTags(), $selectedTags) === []) {
                continue;
            }

            if ($requireAnnotations) {
                $objects = $item->getObjectConfiguration();
                if (null === $objects || $objects->isEmpty()) {
                    continue;
                }
            }

            $items[] = $item;
        }

        return $items;
    }
}
