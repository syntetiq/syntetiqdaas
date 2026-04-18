<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Integration channel type for Fast Shipping integration
 */
class OmniverseChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    #[\Override]
    public function getLabel(): string
    {
        return 'syntetiq.omniverse.transport.channel_type.label';
    }

    #[\Override]
    public function getIcon(): string
    {
        return 'bundles/syntetiqomniverse/img/omniverse.png';
    }
}
