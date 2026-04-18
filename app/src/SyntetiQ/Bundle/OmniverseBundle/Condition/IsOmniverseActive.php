<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Condition;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Doctrine\Persistence\ManagerRegistry;

class IsOmniverseActive extends AbstractCondition
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
    public function getName()
    {
        return 'syntetiq_omniverse_is_active';
    }

    #[\Override]
    public function isConditionAllowed($context)
    {
        $repository = $this->registry->getRepository(Channel::class);
        $channel = $repository->findOneBy(['type' => 'omniverse', 'enabled' => true]);

        return (bool) $channel;
    }

    #[\Override]
    public function initialize(array $options)
    {
        return $this;
    }
}
