<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;

class AddDatasetQaFieldsToDataSet implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set');

        if (!$table->hasColumn('dataset_qa_status')) {
            $table->addColumn('dataset_qa_status', 'string', [
                'length' => 20,
                'notnull' => true,
                'default' => DatasetQaStatus::IDLE,
            ]);
        }

        if (!$table->hasColumn('dataset_qa_started_at')) {
            $table->addColumn('dataset_qa_started_at', 'datetime', [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
            ]);
        }

        if (!$table->hasColumn('dataset_qa_finished_at')) {
            $table->addColumn('dataset_qa_finished_at', 'datetime', [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
            ]);
        }

        if (!$table->hasColumn('dataset_qa_error_output')) {
            $table->addColumn('dataset_qa_error_output', 'text', [
                'notnull' => false,
            ]);
        }
    }
}
