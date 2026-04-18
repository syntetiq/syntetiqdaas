<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SyntetiOmiverseBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOmniverseTransportLabelTable($schema);

        /** Foreign keys generation **/
        $this->addSyntetiqOmniverseTransportLabelForeignKeys($schema);

        $this->updateOroIntegrationTransportTable($schema);
        $this->addGenerateImagesRequestTable($schema);
    }

    /**
     * Create sq_omniverse_transport_label table
     */
    protected function createOmniverseTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('sq_omniverse_transport_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_15E6E6F3EB576E89');
        $table->addIndex(['transport_id'], 'IDX_15E6E6F39909C13F', []);
    }

    /**
     * Add sq_omniverse_transport_label foreign keys.
     */
    protected function addSyntetiqOmniverseTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('sq_omniverse_transport_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @throws SchemaException
     */
    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('sq_omniverse_target_url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('sq_omniverse_callback_url', 'string', ['notnull' => false, 'length' => 255]);
    }

    protected function addGenerateImagesRequestTable(Schema $schema)
    {
        $table = $schema->createTable('sq_generate_images_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('frames', 'integer', ['notnull' => false]);
        $table->addColumn('focal_length', 'integer', ['notnull' => false]);
        $table->addColumn('width', 'integer', ['notnull' => false]);
        $table->addColumn('height', 'integer', ['notnull' => false]);
        $table->addColumn('label_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('scene', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('spawn_cube', 'boolean', ['notnull' => false]);
        $table->addColumn('cube_size', 'float', ['notnull' => false]);
        $table->addColumn('tmp_root', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('convert_images_to_jpeg', 'boolean', ['notnull' => false]);
        $table->addColumn('jpeg_quality', 'integer', ['notnull' => false]);
        $table->addColumn('cleanup_after_zip', 'boolean', ['notnull' => false]);
        $table->addColumn('status', 'string', ['notnull' => false, 'length' => 255, 'default' => 'new']);
        $table->addColumn('hash', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('sent_at', 'datetime', ['notnull' => false]);
        $table->addColumn('handled_at', 'datetime', ['notnull' => false]);
        $table->addColumn('response', 'text', ['notnull' => false]);
        $table->addColumn('include_labels_text', 'text', ['notnull' => false]);
        $table->addColumn('integration_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $this->addVector3Columns($table, 'camera_pos');
        $this->addVector3Columns($table, 'camera_pos_end');
        $this->addVector3Columns($table, 'camera_rotation');
        $this->addVector3Columns($table, 'cube_translate');
        $this->addVector3Columns($table, 'cube_scale');

        $table->addColumn('data_set_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['data_set_id'], 'IDX_9D6A9749CB90730', []);
        $table->addIndex(['integration_id'], 'IDX_SQ_GEN_IMG_REQ_INTEGRATION', []);

        /** Foreign keys generation **/
        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_data_set'),
            ['data_set_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['integration_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

    }

    private function addVector3Columns($table, $prefix)
    {
        $table->addColumn($prefix . '_x', 'float', ['notnull' => false]);
        $table->addColumn($prefix . '_y', 'float', ['notnull' => false]);
        $table->addColumn($prefix . '_z', 'float', ['notnull' => false]);
    }
}
