<?php

namespace SyntetiQ\Bundle\DataSetBundle\Autocomplete;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;

class DataSetItemTagSearchHandler implements SearchHandlerInterface
{
    private const SEPARATOR = ';;';

    public function __construct(
        private ManagerRegistry $registry,
        private RequestStack $requestStack
    ) {
    }

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset = ($page - 1) * $perPage;

        $qb = $this->registry
            ->getRepository(DataSetItemTag::class)
            ->createQueryBuilder('itemTag')
            ->select('DISTINCT itemTag.name AS name')
            ->orderBy('itemTag.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage + 1);

        $request = $this->requestStack->getCurrentRequest();
        $dataSetId = (int) ($request?->query->get('dataSetId') ?? $request?->request->get('dataSetId') ?? 0);
        if ($dataSetId > 0) {
            $qb
                ->innerJoin('itemTag.dataSetItem', 'dataSetItem')
                ->andWhere('dataSetItem.dataSet = :dataSetId')
                ->setParameter('dataSetId', $dataSetId);
        }

        $query = trim((string) $query);
        if ($query !== '') {
            if ($searchById) {
                $tagNames = $this->extractTagNames($query);
                $qb
                    ->andWhere('itemTag.name IN (:tagNames)')
                    ->setParameter('tagNames', $tagNames);
            } else {
                $qb
                    ->andWhere('LOWER(itemTag.name) LIKE :tagName')
                    ->setParameter('tagName', '%' . mb_strtolower($query) . '%');
            }
        }

        $rows = $qb->getQuery()->getArrayResult();
        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $perPage);
        }

        return [
            'results' => array_map([$this, 'convertItem'], $rows),
            'more' => $hasMore,
        ];
    }

    #[\Override]
    public function convertItem($item)
    {
        if ($item instanceof DataSetItemTag) {
            $name = trim($item->getName());
        } elseif (is_array($item)) {
            $name = trim((string) ($item['name'] ?? ''));
        } else {
            $name = trim((string) $item);
        }

        return [
            'id' => json_encode(
                [
                    'id' => $name,
                    'name' => $name,
                ]
            ),
            'name' => $name,
            'owner' => false,
        ];
    }

    #[\Override]
    public function getProperties()
    {
        return ['name'];
    }

    #[\Override]
    public function getEntityName()
    {
        return DataSetItemTag::class;
    }

    private function extractTagNames(string $query): array
    {
        $tags = [];
        foreach (explode(self::SEPARATOR, $query) as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            $decoded = json_decode($value, true);
            $tagName = is_array($decoded)
                ? trim((string) ($decoded['name'] ?? $decoded['id'] ?? ''))
                : $value;

            if ($tagName === '' || in_array($tagName, $tags, true)) {
                continue;
            }

            $tags[] = $tagName;
        }

        return $tags;
    }
}
