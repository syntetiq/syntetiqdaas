<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOwnershipToDataSet implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_data_set')) {
            return;
        }

        $table = $schema->getTable('syntetiq_data_set');

        if (!$table->hasColumn('organization_id')) {
            $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('user_owner_id')) {
            $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasIndex('idx_sq_data_set_organization')) {
            $table->addIndex(['organization_id'], 'idx_sq_data_set_organization');
        }

        if (!$table->hasIndex('idx_sq_data_set_owner')) {
            $table->addIndex(['user_owner_id'], 'idx_sq_data_set_owner');
        }

        if (!$table->hasForeignKey('fk_sq_data_set_organization')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_organization'),
                ['organization_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null],
                'fk_sq_data_set_organization'
            );
        }

        if (!$table->hasForeignKey('fk_sq_data_set_owner')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_user'),
                ['user_owner_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null],
                'fk_sq_data_set_owner'
            );
        }
    }
}
