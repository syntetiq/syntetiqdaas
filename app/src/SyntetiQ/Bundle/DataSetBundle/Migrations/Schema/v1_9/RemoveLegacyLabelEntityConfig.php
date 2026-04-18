<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class RemoveLegacyLabelEntityConfig implements Migration
{
    private const LEGACY_LABEL_CLASS = 'SyntetiQ\\Bundle\\DataSetBundle\\Entity\\Label';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config_log
            WHERE id IN (
                SELECT log_id
                FROM oro_entity_config_log_diff
                WHERE class_name = '%s'
            )",
            self::LEGACY_LABEL_CLASS
        )));

        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config_log_diff
            WHERE class_name = '%s'",
            self::LEGACY_LABEL_CLASS
        )));

        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config_index_value
            WHERE field_id IN (
                SELECT id
                FROM oro_entity_config_field
                WHERE entity_id = (
                    SELECT id
                    FROM oro_entity_config
                    WHERE class_name = '%s'
                )
            )",
            self::LEGACY_LABEL_CLASS
        )));

        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config_index_value
            WHERE entity_id = (
                SELECT id
                FROM oro_entity_config
                WHERE class_name = '%s'
            )",
            self::LEGACY_LABEL_CLASS
        )));

        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config_field
            WHERE entity_id = (
                SELECT id
                FROM oro_entity_config
                WHERE class_name = '%s'
            )",
            self::LEGACY_LABEL_CLASS
        )));

        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "DELETE FROM oro_entity_config
            WHERE class_name = '%s'",
            self::LEGACY_LABEL_CLASS
        )));
    }
}
