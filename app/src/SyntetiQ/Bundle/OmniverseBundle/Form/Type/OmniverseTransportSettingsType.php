<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use SyntetiQ\Bundle\OmniverseBundle\Entity\OmniverseSettings;

/**
 * Form type for Omniverse integration settings
 */
class OmniverseTransportSettingsType extends AbstractType
{
    private const BLOCK_PREFIX = 'omniverse_settings';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label'    => 'syntetiq.omniverse.settings.labels.label',
                    'required' => true,
                    'entry_options'  => ['constraints' => [new NotBlank(), new Length(['max' => 255])]],
                ]
            )
            ->add(
                'targetUrl',
                TextType::class,
                [
                    'label' => 'syntetiq.omniverse.targetUrl.label',
                    'required' => true,
                    'constraints' => [new NotBlank(), new Length(['max' => 255])],
                ]
            )
            ->add(
                'callbackUrl',
                TextType::class,
                [
                    'label' => 'syntetiq.omniverse.callbackUrl.label',
                    'required' => true,
                    'tooltip' => 'syntetiq.omniverse.callbackUrl.tooltip',
                    'constraints' => [new NotBlank(), new Length(['max' => 255])],
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OmniverseSettings::class
        ]);
    }

    #[\Override]
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
