<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDatasetQaProgressToDataSet implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set');

        if (!$table->hasColumn('dataset_qa_progress')) {
            $table->addColumn('dataset_qa_progress', 'float', [
                'notnull' => false,
            ]);
        }

        if (!$table->hasColumn('dataset_qa_progress_message')) {
            $table->addColumn('dataset_qa_progress_message', 'string', [
                'length' => 255,
                'notnull' => false,
            ]);
        }
    }
}
