<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOwnershipToModel implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model');

        if (!$table->hasColumn('organization_id')) {
            $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('user_owner_id')) {
            $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasIndex('idx_sq_model_organization')) {
            $table->addIndex(['organization_id'], 'idx_sq_model_organization');
        }

        if (!$table->hasIndex('idx_sq_model_owner')) {
            $table->addIndex(['user_owner_id'], 'idx_sq_model_owner');
        }

        if (!$table->hasForeignKey('fk_sq_model_organization')
            && !$this->hasEquivalentForeignKey($table, 'oro_organization', ['organization_id'])) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_organization'),
                ['organization_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null],
                'fk_sq_model_organization'
            );
        }

        if (!$table->hasForeignKey('fk_sq_model_owner')
            && !$this->hasEquivalentForeignKey($table, 'oro_user', ['user_owner_id'])) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_user'),
                ['user_owner_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null],
                'fk_sq_model_owner'
            );
        }
    }

    private function hasEquivalentForeignKey(Table $table, string $foreignTable, array $localColumns): bool
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if (!$this->isEquivalentForeignKey($foreignKey, $foreignTable, $localColumns)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isEquivalentForeignKey(
        ForeignKeyConstraint $foreignKey,
        string $foreignTable,
        array $localColumns
    ): bool {
        return $foreignKey->getForeignTableName() === $foreignTable
            && $foreignKey->getLocalColumns() === $localColumns
            && $foreignKey->getForeignColumns() === ['id'];
    }
}
