<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes the legacy syntetiq_data_set_import table.
 * Import progress is now tracked via Oro's oro_message_queue_job system.
 * The import_id column on syntetiq_data_set_item now stores the root Job ID.
 */
class RemoveDataSetImport implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        // Drop the syntetiq_data_set_import table entirely
        if ($schema->hasTable('syntetiq_data_set_import')) {
            $schema->dropTable('syntetiq_data_set_import');
        }
    }
}
