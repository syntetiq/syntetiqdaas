<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Entity with settings for Omniverse integration
 */
#[ORM\Entity]
class OmniverseSettings extends Transport
{
    /**
     * @var Collection|LocalizedFallbackValue[]
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'sq_omniverse_transport_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', onDelete: 'CASCADE', unique: true)]
    private $labels;

    /**
     * @var ParameterBag
     */
    private $settings;

    #[ORM\Column(name: "sq_omniverse_target_url", type: "string", length: 256, nullable: false)]
    private $targetUrl;

    #[ORM\Column(name: "sq_omniverse_callback_url", type: "string", length: 255, nullable: true)]
    private $callbackUrl;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(LocalizedFallbackValue $label): OmniverseSettings
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(LocalizedFallbackValue $label): OmniverseSettings
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    #[\Override]
    public function getSettingsBag(): ParameterBag
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([]);
        }

        return $this->settings;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl($targetUrl): void
    {
        $this->targetUrl = $targetUrl;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl(?string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }
}
