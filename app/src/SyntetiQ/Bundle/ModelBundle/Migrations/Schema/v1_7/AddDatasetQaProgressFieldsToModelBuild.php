<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDatasetQaProgressFieldsToModelBuild implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model_build')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model_build');

        if (!$table->hasColumn('dataset_qa_heartbeat_at')) {
            $table->addColumn('dataset_qa_heartbeat_at', 'datetime', [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
            ]);
        }

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
