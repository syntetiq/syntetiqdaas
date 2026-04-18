<?php

namespace SyntetiQ\Bundle\DataSetBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class DataSetSelectType extends AbstractType
{
    const NAME = 'syntetiq_data_set_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'create_enabled' => true,
                'autocomplete_alias' => 'data_set_search',
                'create_form_route'   => 'syntetiq_model_data_set_create',
                'grid_name'          => 'syntetiq-model-data-set-grid',
                'configs'            => [
                    'placeholder' => 'syntetiq.dataset.form.choose',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
