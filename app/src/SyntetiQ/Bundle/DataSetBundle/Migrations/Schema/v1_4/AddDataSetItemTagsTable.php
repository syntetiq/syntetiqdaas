<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddDataSetItemTagsTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set_item') || $schema->hasTable('syntetiq_data_set_item_tag')) {
            return;
        }

        $table = $schema->createTable('syntetiq_data_set_item_tag');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('data_set_item_id', 'integer');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['data_set_item_id'], 'idx_syntetiq_ds_item_tag_item_id');
        $table->addUniqueIndex(['data_set_item_id', 'name'], 'uniq_syntetiq_ds_item_tag_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_data_set_item'),
            ['data_set_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $queries->addQuery(new SqlMigrationQuery(
            "INSERT INTO syntetiq_data_set_item_tag (data_set_item_id, name)
            SELECT id, TRIM(tag)
            FROM syntetiq_data_set_item
            WHERE tag IS NOT NULL AND TRIM(tag) <> ''"
        ));
    }
}
