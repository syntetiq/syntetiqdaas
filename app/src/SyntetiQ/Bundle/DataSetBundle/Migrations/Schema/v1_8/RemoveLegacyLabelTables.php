<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveLegacyLabelTables implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('sq_gen_img_req_label_rel')) {
            $schema->dropTable('sq_gen_img_req_label_rel');
        }

        if ($schema->hasTable('syntetiq_label')) {
            $schema->dropTable('syntetiq_label');
        }
    }
}
