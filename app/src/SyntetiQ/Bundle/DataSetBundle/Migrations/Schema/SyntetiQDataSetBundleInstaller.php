<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;

class SyntetiQDataSetBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const MAX_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    public function getMigrationVersion()
    {
        return 'v1_13';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createDataSetExportTable($schema);

        $this->createDataSetTable($schema);
        $this->createDataSetItemTable($schema);
        $this->createDataSetItemTagTable($schema);
        $this->createItemObjectConfigurationTable($schema);
        $this->createForeignKeyForDataSetTable($schema);
        $this->createForeignKeyForDataSetItemTable($schema);
        $this->createForeignKeyForDataSetItemTagTable($schema);
        $this->createForeignKeyForDataSetExportTable($schema);
    }

    protected function createDataSetTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_data_set');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 256]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_status', 'string', ['length' => 20, 'notnull' => true, 'default' => DatasetQaStatus::IDLE]);
        $table->addColumn('dataset_qa_started_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_finished_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_heartbeat_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_progress', 'float', ['notnull' => false]);
        $table->addColumn('dataset_qa_progress_message', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('dataset_qa_error_output', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_data_set_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_data_set_owner');
    }

    protected function createDataSetItemTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_data_set_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('data_set_id', 'integer');
        $table->addColumn('img_width', 'integer', ['default' => 640]);
        $table->addColumn('img_height', 'integer', ['default' => 640]);
        $table->addColumn('item_group', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('tag', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('source_type', 'string', ['length' => 32, 'default' => 'manual']);
        $table->addColumn('source_integration_id', 'integer', ['notnull' => false]);
        $table->addColumn('external_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('ready', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('import_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'syntetiq_data_set_item',
            'image',
            [
                'attachment' => [
                    'acl_protected' => false,
                ]
            ],
            self::MAX_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );

        $table->addIndex(['source_integration_id']);
        $table->addIndex(['organization_id'], 'idx_sq_data_set_item_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_data_set_item_owner');
        $table->addIndex(['updated_at'], 'idx_syntetiq_data_set_item_updated_at');
        $table->addIndex(['ready'], 'idx_syntetiq_data_set_item_ready');
        $table->addIndex(['import_id'], 'idx_dataset_item_import_id');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['source_integration_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    protected function createItemObjectConfigurationTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_data_set_item_obj_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 256]);
        $table->addColumn('data_set_item_id', 'integer');
        $table->addColumn('min_x', 'integer');
        $table->addColumn('min_y', 'integer');
        $table->addColumn('max_x', 'integer');
        $table->addColumn('max_y', 'integer');

        $table->addColumn('truncated', 'boolean');

        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_item_obj_cfg_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_item_obj_cfg_owner');

        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_data_set_item'),
            ['data_set_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    protected function createDataSetItemTagTable(Schema $schema): void
    {
        $table = $schema->createTable('syntetiq_data_set_item_tag');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('data_set_item_id', 'integer');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_ds_item_tag_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_ds_item_tag_owner');
        $table->addIndex(['data_set_item_id'], 'idx_syntetiq_ds_item_tag_item_id');
        $table->addUniqueIndex(['data_set_item_id', 'name'], 'uniq_syntetiq_ds_item_tag_name');
    }

    public function createDataSetExportTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_data_set_export');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('data_set_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);

        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('started_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('finished_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_data_set_export_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_data_set_export_owner');

        $this->attachmentExtension->addFileRelation(
            $schema,
            'syntetiq_data_set_export',
            'result_file',
            [
                'extend' => [
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            100
        );
    }

    public function createForeignKeyForDataSetItemTable(Schema $schema)
    {
        $table = $schema->getTable('syntetiq_data_set_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_data_set'),
            ['data_set_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    public function createForeignKeyForDataSetTable(Schema $schema): void
    {
        $table = $schema->getTable('syntetiq_data_set');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    public function createForeignKeyForDataSetItemTagTable(Schema $schema): void
    {
        $table = $schema->getTable('syntetiq_data_set_item_tag');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_data_set_item'),
            ['data_set_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    public function createForeignKeyForDataSetExportTable(Schema $schema)
    {
        $table = $schema->getTable('syntetiq_data_set_export');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
