<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OmniverseChannelSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Channel::class,
            'choice_label' => 'name',
            'query_builder' => static function (EntityRepository $repository) {
                return $repository
                    ->createQueryBuilder('channel')
                    ->andWhere('channel.type = :type')
                    ->andWhere('channel.enabled = :enabled')
                    ->setParameter('type', 'omniverse')
                    ->setParameter('enabled', true)
                    ->orderBy('channel.name', 'ASC');
            },
            'configs' => [
                'allowClear' => false,
            ],
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2EntityType::class;
    }
}
