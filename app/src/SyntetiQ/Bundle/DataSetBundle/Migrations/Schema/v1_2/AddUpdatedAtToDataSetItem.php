<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddUpdatedAtToDataSetItem implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set_item')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set_item');
        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
            ]);
        }

        if (!$table->hasIndex('idx_syntetiq_data_set_item_updated_at')) {
            $table->addIndex(['updated_at'], 'idx_syntetiq_data_set_item_updated_at');
        }

        $queries->addQuery(new SqlMigrationQuery(
            'UPDATE syntetiq_data_set_item SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL'
        ));
    }
}
