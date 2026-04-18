<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCalculateDatasetQaToModelBuild implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model_build')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model_build');

        if ($table->hasColumn('calculate_dataset_qa')) {
            return;
        }

        $table->addColumn('calculate_dataset_qa', 'boolean', [
            'notnull' => true,
            'default' => true,
        ]);
    }
}
