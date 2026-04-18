<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

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
            DataSet::class,
            DataSetExport::class,
            DataSetItem::class,
            DataSetItemTag::class,
            ItemObjectConfiguration::class,
        ];
    }
}
