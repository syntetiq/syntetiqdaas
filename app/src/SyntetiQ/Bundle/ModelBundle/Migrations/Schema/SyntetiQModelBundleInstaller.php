<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;

class SyntetiQModelBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const MAX_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    public function getMigrationVersion()
    {
        return 'v1_10';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createModelTable($schema);
        $this->createModelPretrainedTable($schema);
        $this->createModelBuildTable($schema);
        $this->createForeignKeyForModelTable($schema);
    }


    /**
     * @param Schema $schema
     */
    protected function createModelTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_model');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 256]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('data_set_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_model_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_model_owner');
    }

    /**
     * @param Schema $schema
     */
    protected function createModelBuildTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_model_build');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('model_id', 'integer', ['notnull' => false]);
        $table->addColumn('pretrained_model_id', 'integer', ['notnull' => false]);

        $table->addColumn('engine', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('engine_model', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('epoch', 'integer', ['notnull' => true]);
        $table->addColumn('image_size', 'string', ['length' => 50, 'notnull' => true, 'default' => ImageSize::SIZE_640_640]);

        $table->addColumn('percent_test_items', 'float', ['notnull' => false]);
        $table->addColumn('percent_validation_items', 'float', ['notnull' => false]);
        $table->addColumn('percent_train_items', 'float', ['notnull' => false]);

        $table->addColumn('context', 'text', ['notnull' => false]);
        $table->addColumn('output', 'text', ['notnull' => false]);
        $table->addColumn('error_output', 'text', ['notnull' => false]);
        $table->addColumn('result_file', 'text', ['notnull' => false]);
        $table->addColumn('is_initialized', 'boolean', ['default' => '0']);
        $table->addColumn('ready_only', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('tags', 'json', ['notnull' => false]);
        $table->addColumn('env', 'string', ['length' => 50]);

        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('started_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('finished_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_status', 'string', ['length' => 20, 'notnull' => true, 'default' => DatasetQaStatus::IDLE]);
        $table->addColumn('dataset_qa_started_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_finished_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_heartbeat_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('dataset_qa_progress', 'float', ['notnull' => false]);
        $table->addColumn('dataset_qa_progress_message', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('dataset_qa_error_output', 'text', ['notnull' => false]);
        $table->addColumn('calculate_dataset_qa', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('deepstream_export', 'boolean', ['notnull' => true, 'default' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_model_build_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_model_build_owner');

        $this->attachmentExtension->addFileRelation(
            $schema,
            'syntetiq_model_build',
            'artifact',
            [
                'extend' => [
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            100
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
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_model'),
            ['model_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_model_pretrained'),
            ['pretrained_model_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    protected function createModelPretrainedTable(Schema $schema)
    {
        $table = $schema->createTable('syntetiq_model_pretrained');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('model_id', 'integer', ['notnull' => true]);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('original_filename', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('engine', 'string', ['length' => 100, 'notnull' => true]);
        $table->addColumn('engine_model', 'string', ['length' => 100, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_sq_model_pretrained_organization');
        $table->addIndex(['user_owner_id'], 'idx_sq_model_pretrained_owner');

        $this->attachmentExtension->addFileRelation(
            $schema,
            'syntetiq_model_pretrained',
            'file',
            [
                'extend' => [
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            100
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
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_model'),
            ['model_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    public function createForeignKeyForModelTable(Schema $schema)
    {
        $table = $schema->getTable('syntetiq_model');
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
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
