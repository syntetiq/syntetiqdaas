<?php

namespace SyntetiQ\Bundle\ModelBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddModelPretrained implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createModelPretrainedTable($schema);
        $this->updateModelBuildTable($schema);
    }

    private function createModelPretrainedTable(Schema $schema): void
    {
        if (!$schema->hasTable('syntetiq_model_pretrained')) {
            $table = $schema->createTable('syntetiq_model_pretrained');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('model_id', 'integer', ['notnull' => true]);
            $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('original_filename', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('engine', 'string', ['length' => 100, 'notnull' => true]);
            $table->addColumn('engine_model', 'string', ['length' => 100, 'notnull' => true]);
            $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
            $table->setPrimaryKey(['id']);
            $table->addForeignKeyConstraint(
                $schema->getTable('syntetiq_model'),
                ['model_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        } else {
            $table = $schema->getTable('syntetiq_model_pretrained');
        }

        if (!$table->hasColumn('file_id')) {
            $this->attachmentExtension->addFileRelation(
                $schema,
                'syntetiq_model_pretrained',
                'file',
                [
                    'extend' => [
                        'cascade' => ['all'],
                        'on_delete' => 'CASCADE',
                        'nullable' => true,
                    ],
                ],
                100
            );
        }
    }

    private function updateModelBuildTable(Schema $schema): void
    {
        if (!$schema->hasTable('syntetiq_model_build') || !$schema->hasTable('syntetiq_model_pretrained')) {
            return;
        }

        $table = $schema->getTable('syntetiq_model_build');
        if (!$table->hasColumn('pretrained_model_id')) {
            $table->addColumn('pretrained_model_id', 'integer', ['notnull' => false]);
        }

        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['pretrained_model_id']) {
                return;
            }
        }

        $table->addForeignKeyConstraint(
            $schema->getTable('syntetiq_model_pretrained'),
            ['pretrained_model_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
