<?php

namespace SyntetiQ\Bundle\ModelBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\DataSetBundle\Form\Type\DataSetSelectType;

class ModelType extends AbstractType
{
    const NAME = 'syntetiq_model_type_model';

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
                    'label' => 'syntetiq.model.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => 'syntetiq.model.description.label',
                    'required' => false,
                ]
            )
            // ->add(
            //     'resource',
            //     FileType::class,
            //     [
            //         'label' => 'Resource',
            //         'required' => false
            //     ]
            // )
            ->add(
                'dataSet',
                DataSetSelectType::class,
                [
                    'label' => 'syntetiq.model.data_set.label',
                    'required' => false,
                    'entity_class' => 'SyntetiQ\Bundle\DataSetBundle\Entity\DataSet',
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Model::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
