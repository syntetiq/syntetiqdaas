<?php

namespace SyntetiQ\Bundle\ModelBundle\Dataset;

use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class BuildItemGroupResolver
{
    /**
     * @param iterable<DataSetItem> $items
     *
     * @return array<int, string>
     */
    public function resolve(ModelBuild $modelBuild, iterable $items): array
    {
        $train = max(0.0, (float) ($modelBuild->getPercentTrainItems() ?? 0.82));
        $val = max(0.0, (float) ($modelBuild->getPercentValidationItems() ?? 0.15));
        $test = max(0.0, (float) ($modelBuild->getPercentTestItems() ?? 0.03));

        $total = $train + $val + $test;
        if ($total <= 0) {
            $train = 0.82;
            $val = 0.15;
            $test = 0.03;
            $total = 1.0;
        }

        $testThreshold = $test / $total;
        $valThreshold = ($test + $val) / $total;

        $groups = [];
        foreach ($items as $item) {
            if (!$item instanceof DataSetItem || null === $item->getId()) {
                continue;
            }

            $group = $item->getGroup();
            if (in_array($group, [Group::TEST, Group::VAL, Group::TRAIN], true)) {
                $groups[$item->getId()] = $group;
                continue;
            }

            $dice = $this->normalizedHash(
                sprintf('%d:%d:%d', $modelBuild->getModel()->getId(), $modelBuild->getId(), $item->getId())
            );

            if ($dice <= $testThreshold) {
                $groups[$item->getId()] = Group::TEST;
            } elseif ($dice < $valThreshold) {
                $groups[$item->getId()] = Group::VAL;
            } else {
                $groups[$item->getId()] = Group::TRAIN;
            }
        }

        return $groups;
    }

    private function normalizedHash(string $value): float
    {
        $hash = substr(md5($value), 0, 8);
        $int = hexdec($hash);

        return $int / 0xffffffff;
    }
}
