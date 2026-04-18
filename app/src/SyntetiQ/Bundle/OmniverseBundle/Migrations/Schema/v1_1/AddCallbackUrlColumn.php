<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCallbackUrlColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');
        if (!$table->hasColumn('sq_omniverse_callback_url')) {
            $table->addColumn('sq_omniverse_callback_url', 'string', ['notnull' => false, 'length' => 255]);
        }
    }
}
