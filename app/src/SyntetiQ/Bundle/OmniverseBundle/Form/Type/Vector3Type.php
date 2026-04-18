<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\OmniverseBundle\Entity\Value\Vector3;

class Vector3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('x', NumberType::class, ['label' => 'X', 'required' => true])
            ->add('y', NumberType::class, ['label' => 'Y', 'required' => true])
            ->add('z', NumberType::class, ['label' => 'Z', 'required' => true]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vector3::class,
        ]);
    }
}
