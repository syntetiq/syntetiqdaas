<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;

class MoveGenerateImagesRequestToSqAclCategory implements Migration
{
    private const ACL_CATEGORY = 'sq_entities';
    private const PREVIOUS_CATEGORY = 'shopping';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                GenerateImagesRequest::class,
                'security',
                'category',
                self::ACL_CATEGORY,
                self::PREVIOUS_CATEGORY
            )
        );
    }
}
