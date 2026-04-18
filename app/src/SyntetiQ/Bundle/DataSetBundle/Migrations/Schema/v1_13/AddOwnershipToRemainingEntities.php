<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOwnershipToRemainingEntities implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOwnership(
            $schema,
            'syntetiq_data_set_export',
            'idx_sq_data_set_export_organization',
            'idx_sq_data_set_export_owner',
            'fk_sq_data_set_export_organization',
            'fk_sq_data_set_export_owner'
        );
        $this->addOwnership(
            $schema,
            'syntetiq_data_set_item',
            'idx_sq_data_set_item_organization',
            'idx_sq_data_set_item_owner',
            'fk_sq_data_set_item_organization',
            'fk_sq_data_set_item_owner'
        );
        $this->addOwnership(
            $schema,
            'syntetiq_data_set_item_tag',
            'idx_sq_ds_item_tag_organization',
            'idx_sq_ds_item_tag_owner',
            'fk_sq_ds_item_tag_organization',
            'fk_sq_ds_item_tag_owner'
        );
        $this->addOwnership(
            $schema,
            'syntetiq_data_set_item_obj_config',
            'idx_sq_item_obj_cfg_organization',
            'idx_sq_item_obj_cfg_owner',
            'fk_sq_item_obj_cfg_organization',
            'fk_sq_item_obj_cfg_owner'
        );
    }

    private function addOwnership(
        Schema $schema,
        string $tableName,
        string $organizationIndex,
        string $ownerIndex,
        string $organizationForeignKey,
        string $ownerForeignKey
    ): void {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);

        if (!$table->hasColumn('organization_id')) {
            $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasColumn('user_owner_id')) {
            $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        }

        if (!$table->hasIndex($organizationIndex)) {
            $table->addIndex(['organization_id'], $organizationIndex);
        }

        if (!$table->hasIndex($ownerIndex)) {
            $table->addIndex(['user_owner_id'], $ownerIndex);
        }

        $this->addForeignKeyIfMissing(
            $schema,
            $table,
            $organizationForeignKey,
            'oro_organization',
            ['organization_id']
        );
        $this->addForeignKeyIfMissing(
            $schema,
            $table,
            $ownerForeignKey,
            'oro_user',
            ['user_owner_id']
        );
    }

    private function addForeignKeyIfMissing(
        Schema $schema,
        Table $table,
        string $foreignKeyName,
        string $foreignTable,
        array $localColumns
    ): void {
        if ($table->hasForeignKey($foreignKeyName) || $this->hasEquivalentForeignKey($table, $foreignTable, $localColumns)) {
            return;
        }

        $table->addForeignKeyConstraint(
            $schema->getTable($foreignTable),
            $localColumns,
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            $foreignKeyName
        );
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
