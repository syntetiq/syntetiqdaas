<?php

namespace SyntetiQ\Bundle\ModelBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;

class ModelPretrainedRepository extends EntityRepository
{
    public function createAvailableForModelQueryBuilder(?Model $model): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('mp')
            ->orderBy('mp.id', 'DESC');

        if (null === $model) {
            return $queryBuilder->where('1 = 0');
        }

        return $queryBuilder
            ->where('mp.model = :model')
            ->setParameter('model', $model);
    }
}
