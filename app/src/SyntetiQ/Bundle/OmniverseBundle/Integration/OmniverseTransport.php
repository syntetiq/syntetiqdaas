<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use SyntetiQ\Bundle\OmniverseBundle\Entity\OmniverseSettings;
use SyntetiQ\Bundle\OmniverseBundle\Form\Type\OmniverseTransportSettingsType;

/**
 * Transport for Fast Shipping integration
 */
class OmniverseTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    #[\Override]
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    #[\Override]
    public function getSettingsFormType(): string
    {
        return OmniverseTransportSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN(): string
    {
        return OmniverseSettings::class;
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'syntetiq.omniverse.transport.label';
    }
}
