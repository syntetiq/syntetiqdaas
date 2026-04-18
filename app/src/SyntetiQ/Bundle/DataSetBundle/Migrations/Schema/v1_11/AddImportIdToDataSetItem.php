<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddImportIdToDataSetItem implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('syntetiq_data_set_item');
        if ($table->hasColumn('import_id')) {
            return;
        }

        $table->addColumn('import_id', 'integer', ['notnull' => false]);
        $table->addIndex(['import_id'], 'idx_dataset_item_import_id');
    }
}
