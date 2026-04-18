<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;

class AddDatasetQaFieldsToModelBuild implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model_build')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model_build');

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
