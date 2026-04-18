<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateDataSetImportTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('syntetiq_data_set_import')) {
            return;
        }

        $table = $schema->createTable('syntetiq_data_set_import');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('data_set_id', 'integer');
        $table->addColumn('status', 'string', ['length' => 20]);
        $table->addColumn('item_count', 'integer', ['notnull' => false]);
        $table->addColumn('processed_count', 'integer', ['default' => 0]);
        $table->addColumn('started_at', 'datetime');
        $table->addColumn('finished_at', 'datetime', ['notnull' => false]);
        $table->addColumn('error', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            'syntetiq_data_set',
            ['data_set_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
