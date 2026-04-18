<?php

namespace SyntetiQ\Bundle\DataSetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class ItemObjectConfigurationRowType extends AbstractType
{
    const NAME = 'asc_item_object_configuration_row';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.name.label',
                ]
            )
            ->add(
                'truncated',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.truncated.label',
                    'mapped' => false,
                    'data' => false,
                ]
            )
            ->add(
                'minX',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.min_x.label',
                ]
            )
            ->add(
                'minY',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.min_y.label',
                ]
            )
            ->add(
                'maxX',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.max_x.label',
                ]
            )
            ->add(
                'maxY',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.max_y.label',
                ]
            )
            ->add(
                'latitude',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.latitude.label',
                    'scale' => 7,
                ]
            )->add(
                'longitude',
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'syntetiq.dataset.itemobjectconfiguration.longitude.label',
                    'scale' => 7,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('data_class', ItemObjectConfiguration::class);
    }
}
