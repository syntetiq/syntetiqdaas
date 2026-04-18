<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;

class MoveEntitiesToSqAclCategory implements Migration
{
    private const ACL_CATEGORY = 'sq_entities';
    private const PREVIOUS_CATEGORY = 'account_management';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        foreach ($this->getEntityClasses() as $entityClass) {
            $queries->addQuery(
                new UpdateEntityConfigEntityValueQuery(
                    $entityClass,
                    'security',
                    'category',
                    self::ACL_CATEGORY,
                    self::PREVIOUS_CATEGORY
                )
            );
        }
    }

    private function getEntityClasses(): array
    {
        return [
            Model::class,
            ModelBuild::class,
            ModelPretrained::class,
        ];
    }
}
