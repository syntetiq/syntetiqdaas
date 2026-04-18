<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIntegrationToGenerateImagesRequest implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('sq_generate_images_request');
        if (!$table->hasColumn('integration_id')) {
            $table->addColumn('integration_id', 'integer', ['notnull' => false]);
            $table->addIndex(['integration_id'], 'IDX_SQ_GEN_IMG_REQ_INTEGRATION');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_integration_channel'),
                ['integration_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );
        }
    }
}
