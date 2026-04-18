<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddReadyOnlyToModelBuild implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model_build')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model_build');
        if (!$table->hasColumn('ready_only')) {
            $table->addColumn('ready_only', 'boolean', [
                'notnull' => true,
                'default' => false,
            ]);
        }
    }
}
