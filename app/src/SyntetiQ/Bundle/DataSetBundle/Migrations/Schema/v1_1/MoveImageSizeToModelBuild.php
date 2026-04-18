<?php

namespace SyntetiQ\Bundle\DataSetBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;

class MoveImageSizeToModelBuild implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('syntetiq_model_build')) {
            return;
        }

        $modelBuildTable = $schema->getTable('syntetiq_model_build');
        if (!$modelBuildTable->hasColumn('image_size')) {
            $modelBuildTable->addColumn('image_size', 'string', [
                'notnull' => true,
                'length' => 50,
                'default' => ImageSize::SIZE_640_640,
            ]);
        }

        if ($schema->hasTable('syntetiq_data_set') && $schema->getTable('syntetiq_data_set')->hasColumn('image_size')) {
            $queries->addQuery(new SqlMigrationQuery(sprintf(
                "UPDATE syntetiq_model_build AS mb
                SET image_size = CASE ds.image_size
                    WHEN '%s' THEN '%s'
                    WHEN '%s' THEN '%s'
                    ELSE '%s'
                END
                FROM syntetiq_model AS m
                LEFT JOIN syntetiq_data_set AS ds ON ds.id = m.data_set_id
                WHERE mb.model_id = m.id",
                ImageSize::SIZE_320_320,
                ImageSize::SIZE_320_320,
                ImageSize::SIZE_1280_1280,
                ImageSize::SIZE_1280_1280,
                ImageSize::SIZE_640_640
            )));

            $queries->addQuery(new SqlMigrationQuery(
                'ALTER TABLE syntetiq_data_set DROP COLUMN image_size'
            ));
        }
    }
}
