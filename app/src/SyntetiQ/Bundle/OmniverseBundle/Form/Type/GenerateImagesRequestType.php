<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;

class GenerateImagesRequestType extends AbstractType
{
    private const BLOCK_PREFIX = 'syntetiq_generate_images_request';
    private const SCENE_DATALIST_ID = 'syntetiq-generate-images-request-scene-options';
    private const SCENE_OPTIONS = [
        '/Isaac/Environments/Simple_Warehouse/warehouse.usd',
        '/Isaac/Environments/Simple_Warehouse/warehouse2.usd',
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('integration', OmniverseChannelSelectType::class, [
                'label' => 'syntetiq.omniverse.generateimagesrequest.integration.label',
                'required' => true,
                'tooltip' => 'syntetiq.omniverse.generateimagesrequest.integration.tooltip',
            ])
            ->add('scene', TextType::class, [
                'required' => true,
                'label' => 'Scene (USD Path)',
                'tooltip' => 'syntetiq.omniverse.generateimagesrequest.scene.tooltip',
                'attr' => [
                    'class' => 'omniverse-scene-input',
                    'list' => self::SCENE_DATALIST_ID,
                ],
            ])
            ->add(
                'frames',
                IntegerType::class,
                [
                    'label'    => 'Frames',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.frames.tooltip',
                ]
            )
            ->add(
                'focalLength',
                IntegerType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.focalLength.label',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.focalLength.tooltip',
                ]
            )
            ->add(
                'width',
                IntegerType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.width.label',
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.width.tooltip',
                ]
            )
            ->add(
                'height',
                IntegerType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.height.label',
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.height.tooltip',
                ]
            )
            ->add(
                'labelName',
                TextType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.labelName.label',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.labelName.tooltip',
                ]
            )
            ->add(
                'spawnCube',
                CheckboxType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.spawnCube.label',
                    'required' => false,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.spawnCube.tooltip',
                ]
            )
            ->add(
                'cubeSize',
                NumberType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.cubeSize.label',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.cubeSize.tooltip',
                ]
            )
            ->add(
                'tmpRoot',
                TextType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.tmpRoot.label',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.tmpRoot.tooltip',
                ]
            )
            ->add(
                'convertImagesToJpeg',
                CheckboxType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.convertImagesToJpeg.label',
                    'required' => false,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.convertImagesToJpeg.tooltip',
                ]
            )
            ->add(
                'jpegQuality',
                IntegerType::class,
                [
                    'label'    => 'syntetiq.omniverse.generateimagesrequest.jpegQuality.label',
                    'required' => true,
                    'tooltip'  => 'syntetiq.omniverse.generateimagesrequest.jpegQuality.tooltip',
                ]
            )
            ->add(
                'cleanupAfterZip',
                CheckboxType::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cleanupAfterZip.label',
                    'required' => false,
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cleanupAfterZip.tooltip',
                ]
            )
            ->add(
                'cameraPos',
                Vector3Type::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cameraPos.label',
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cameraPos.tooltip',
                ]
            )
            ->add(
                'cameraPosEnd',
                Vector3Type::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cameraPosEnd.label',
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cameraPosEnd.tooltip',
                ]
            )
            ->add(
                'cameraRotation',
                Vector3Type::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cameraRotation.label',
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cameraRotation.tooltip',
                ]
            )
            ->add(
                'cubeTranslate',
                Vector3Type::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cubeTranslate.label',
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cubeTranslate.tooltip',
                ]
            )
            ->add(
                'cubeScale',
                Vector3Type::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.cubeScale.label',
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.cubeScale.tooltip',
                ]
            )
            ->add(
                'includeLabelsText',
                TextareaType::class,
                [
                    'label' => 'syntetiq.omniverse.generateimagesrequest.includeLabels.label',
                    'required' => false,
                    'tooltip' => 'syntetiq.omniverse.generateimagesrequest.includeLabels.tooltip',
                    'attr' => [
                        'rows' => 4,
                        'placeholder' => 'Enter labels, one per line or comma separated',
                    ]
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GenerateImagesRequest::class,
            'scene_options' => self::SCENE_OPTIONS,
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['scene_options'] = $options['scene_options'];
    }

    #[\Override]
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
