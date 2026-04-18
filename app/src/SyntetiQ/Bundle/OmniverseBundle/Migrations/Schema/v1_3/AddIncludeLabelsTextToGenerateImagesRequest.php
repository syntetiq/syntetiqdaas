<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIncludeLabelsTextToGenerateImagesRequest implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('sq_generate_images_request');
        if (!$table->hasColumn('include_labels_text')) {
            $table->addColumn('include_labels_text', 'text', ['notnull' => false]);
        }
    }
}
