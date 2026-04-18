<?php

namespace SyntetiQ\Bundle\DataSetBundle\Entity;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Compatibility shim for legacy Oro extend metadata.
 *
 * The real Label entity was removed, but some installations still carry
 * stale extend config that is cleaned up by the v1_9 migration.
 */
class Label implements ExtendEntityInterface
{
    use ExtendEntityTrait;
}
