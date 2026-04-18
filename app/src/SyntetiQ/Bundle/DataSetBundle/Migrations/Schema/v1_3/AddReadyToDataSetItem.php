<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddReadyToDataSetItem implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set_item')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set_item');
        if (!$table->hasColumn('ready')) {
            $table->addColumn('ready', 'boolean', [
                'notnull' => true,
                'default' => false,
            ]);
        }

        if (!$table->hasIndex('idx_syntetiq_data_set_item_ready')) {
            $table->addIndex(['ready'], 'idx_syntetiq_data_set_item_ready');
        }

        $queries->addQuery(new SqlMigrationQuery(
            'UPDATE syntetiq_data_set_item SET ready = FALSE WHERE ready IS NULL'
        ));
    }
}
