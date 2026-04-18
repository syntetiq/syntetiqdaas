<?php

namespace SyntetiQ\Bundle\DataSetBundle\Datagrid\MassAction;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;

class RandomSplitMassActionHandler implements MassActionHandlerInterface
{
    private const TRAIN_PERCENTAGE_KEY = 'trainPercentage';
    private const VAL_PERCENTAGE_KEY = 'valPercentage';
    private const TEST_PERCENTAGE_KEY = 'testPercentage';
    private const TOTAL_PERCENTAGE = 100;
    private const GROUP_PRIORITIES = [
        Group::TRAIN => 0,
        Group::VAL => 1,
        Group::TEST => 2,
    ];

    public function __construct(
        private ManagerRegistry $registry,
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $percentages = $this->extractPercentages($args->getData());
        if ($percentages === null) {
            return new MassActionResponse(
                false,
                $this->translator->trans(
                    'Train, validation, and test percentages must be whole numbers from 0 to 100 and total 100.'
                )
            );
        }

        $manager = $this->registry->getManagerForClass(DataSetItem::class);
        if (!$manager instanceof ObjectManager) {
            return new MassActionResponse(
                false,
                $this->translator->trans('Could not resolve the Data Set Item manager.')
            );
        }

        $items = $this->collectItems($args, $manager);
        if ($items === []) {
            return new MassActionResponse(
                false,
                $this->translator->trans('No Data Set items were found for the selected rows.')
            );
        }

        shuffle($items);

        $splitCounts = $this->buildSplitCounts(count($items), $percentages);
        $assignments = $this->buildAssignments($splitCounts);
        $updatedCount = 0;

        foreach ($items as $index => $item) {
            $group = $assignments[$index] ?? Group::TRAIN;
            if ($item->getGroup() === $group) {
                continue;
            }

            $item->setGroup($group);
            $item->touch();
            $manager->persist($item);
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $manager->flush();
        }

        return new MassActionResponse(
            true,
            $this->translator->trans(
                'Random split applied to %selected% item(s): train %train%, validation %val%, test %test%. Updated %updated% item(s).',
                [
                    '%selected%' => count($items),
                    '%train%' => $splitCounts[Group::TRAIN],
                    '%val%' => $splitCounts[Group::VAL],
                    '%test%' => $splitCounts[Group::TEST],
                    '%updated%' => $updatedCount,
                ]
            ),
            ['count' => $updatedCount]
        );
    }

    /**
     * @return array<string, int>|null
     */
    private function extractPercentages(array $data): ?array
    {
        $percentages = [
            Group::TRAIN => $this->normalizePercentage($data[self::TRAIN_PERCENTAGE_KEY] ?? null),
            Group::VAL => $this->normalizePercentage($data[self::VAL_PERCENTAGE_KEY] ?? null),
            Group::TEST => $this->normalizePercentage($data[self::TEST_PERCENTAGE_KEY] ?? null),
        ];

        foreach ($percentages as $percentage) {
            if ($percentage === null) {
                return null;
            }
        }

        if (array_sum($percentages) !== self::TOTAL_PERCENTAGE) {
            return null;
        }

        return $percentages;
    }

    private function normalizePercentage(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        $percentage = (int) $value;
        if ($percentage < 0 || $percentage > self::TOTAL_PERCENTAGE) {
            return null;
        }

        return $percentage;
    }

    /**
     * @return DataSetItem[]
     */
    private function collectItems(MassActionHandlerArgs $args, ObjectManager $manager): array
    {
        $items = [];

        foreach ($args->getResults() as $result) {
            if (!$result instanceof ResultRecordInterface) {
                continue;
            }

            $item = $result->getRootEntity();
            if (!$item instanceof DataSetItem) {
                $itemId = (int) $result->getValue('id');
                $item = $itemId > 0 ? $manager->find(DataSetItem::class, $itemId) : null;
            }

            if (!$item instanceof DataSetItem || $item->getId() === null) {
                continue;
            }

            $items[$item->getId()] = $item;
        }

        return array_values($items);
    }

    /**
     * @param array<string, int> $percentages
     *
     * @return array<string, int>
     */
    private function buildSplitCounts(int $totalItems, array $percentages): array
    {
        $counts = array_fill_keys(array_keys(self::GROUP_PRIORITIES), 0);
        if ($totalItems <= 0) {
            return $counts;
        }

        $remainders = [];
        $allocated = 0;

        foreach ($percentages as $group => $percentage) {
            $rawCount = ($totalItems * $percentage) / self::TOTAL_PERCENTAGE;
            $count = (int) floor($rawCount);

            $counts[$group] = $count;
            $allocated += $count;
            $remainders[] = [
                'group' => $group,
                'fraction' => $rawCount - $count,
                'percentage' => $percentage,
                'priority' => self::GROUP_PRIORITIES[$group],
            ];
        }

        usort(
            $remainders,
            static function (array $left, array $right): int {
                if ($left['fraction'] !== $right['fraction']) {
                    return $right['fraction'] <=> $left['fraction'];
                }

                if ($left['percentage'] !== $right['percentage']) {
                    return $right['percentage'] <=> $left['percentage'];
                }

                return $left['priority'] <=> $right['priority'];
            }
        );

        $remaining = $totalItems - $allocated;
        for ($index = 0; $index < $remaining; $index++) {
            $counts[$remainders[$index]['group']]++;
        }

        return $counts;
    }

    /**
     * @param array<string, int> $splitCounts
     *
     * @return string[]
     */
    private function buildAssignments(array $splitCounts): array
    {
        $assignments = [];

        foreach ([Group::TRAIN, Group::VAL, Group::TEST] as $group) {
            for ($index = 0; $index < ($splitCounts[$group] ?? 0); $index++) {
                $assignments[] = $group;
            }
        }

        return $assignments;
    }
}
