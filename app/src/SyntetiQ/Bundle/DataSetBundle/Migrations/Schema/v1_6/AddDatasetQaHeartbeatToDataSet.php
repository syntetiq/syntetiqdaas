<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDatasetQaHeartbeatToDataSet implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set');

        if ($table->hasColumn('dataset_qa_heartbeat_at')) {
            return;
        }

        $table->addColumn('dataset_qa_heartbeat_at', 'datetime', [
            'notnull' => false,
            'comment' => '(DC2Type:datetime)',
        ]);
    }
}
