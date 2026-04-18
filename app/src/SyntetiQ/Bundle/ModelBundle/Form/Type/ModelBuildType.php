<?php

namespace SyntetiQ\Bundle\ModelBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\ModelBundle\Dataset\ModelBuildDatasetItemProvider;
use SyntetiQ\Bundle\DataSetBundle\Model\ImageSize;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;
use SyntetiQ\Bundle\ModelBundle\Entity\Repository\ModelPretrainedRepository;

class ModelBuildType extends AbstractType
{
    const NAME = 'syntetiq_model_type_model_build';

    public function __construct(
        private $dataEnvironments,
        private $dataEngines,
        private ModelBuildDatasetItemProvider $datasetItemProvider
    ) {}

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $environments = $this->getEnviroments();
        $engine = $this->getEngine();
        $models = $this->getModels();

        $builder
            ->add(
                'env',
                ChoiceType::class,
                [
                    'label' => 'syntetiq.modelbuild.env.label',
                    'required' => true,
                    'choices' => $environments
                ]
            )
            ->add(
                'engine',
                ChoiceType::class,
                [
                    'label' => 'syntetiq.modelbuild.engine.label',
                    'required' => true,
                    'choices' => $engine
                ]
            )
            ->add(
                'engineModel',
                ChoiceType::class,
                [
                    'label' => 'syntetiq.modelbuild.engine_model.label',
                    'required' => true,
                    'choices' => $models
                ]
            )
            ->add(
                'epoch',
                IntegerType::class,
                [
                    'label' => 'syntetiq.modelbuild.epoch.label',
                    'required' => true,
                ]
            )
            ->add(
                'imageSize',
                ChoiceType::class,
                [
                    'label' => 'syntetiq.modelbuild.image_size.label',
                    'required' => true,
                    'choices' => [
                        '320 x 320' => ImageSize::SIZE_320_320,
                        '640 x 640' => ImageSize::SIZE_640_640,
                        '1280 x 1280' => ImageSize::SIZE_1280_1280,
                    ],
                ]
            )
            ->add(
                'percentTestItems',
                PercentType::class,
                [
                    'label' => 'syntetiq.modelbuild.percent_test_items.label',
                    'required' => true,
                ]
            )
            ->add(
                'percentValidationItems',
                PercentType::class,
                [
                    'label' => 'syntetiq.modelbuild.percent_validation_items.label',
                    'required' => true,
                ]
            )
            ->add(
                'percentTrainItems',
                PercentType::class,
                [
                    'label' => 'syntetiq.modelbuild.percent_train_items.label',
                    'required' => true,
                ]
            )
            ->add(
                'readyOnly',
                CheckboxType::class,
                [
                    'label' => 'syntetiq.modelbuild.ready_only.label',
                    'required' => false,
                ]
            )
            ->add(
                'calculateDatasetQa',
                CheckboxType::class,
                [
                    'label' => 'syntetiq.modelbuild.calculate_dataset_qa.label',
                    'required' => false,
                ]
            )
            ->add(
                'deepstreamExport',
                CheckboxType::class,
                [
                    'label' => 'syntetiq.modelbuild.deepstream_export.label',
                    'required' => false,
                ]
            )
            ->add(
                'tags',
                OroChoiceType::class,
                [
                    'label' => 'syntetiq.modelbuild.tags.label',
                    'required' => false,
                    'multiple' => true,
                    'choices' => [],
                    'configs' => [
                        'placeholder' => 'syntetiq.modelbuild.tags.placeholder',
                        'allowClear' => true,
                        'closeOnSelect' => false,
                    ],
                ]
            )
            ->add(
                'hiddenEngines',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'class' => 'hidden-engines-info'
                    ]
                ]
            );


        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'preSetData'], -10);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData'], -10);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        $info = $this->dataEngines;

        $form['hiddenEngines']->setData(json_encode($info));
        $this->addTagsField($form, $data instanceof ModelBuild ? $data : null);
        $this->addPretrainedModelField($form, $data instanceof ModelBuild ? $data : null);
    }


    public function preSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!is_array($data)) {
            return;
        }

        $engine = $data['engine'];
        $choice = $this->getModels($engine);

        $form->remove('engineModel');
        $form->add(
            'engineModel',
            ChoiceType::class,
            [
                'label' => 'syntetiq.modelbuild.engine_model.label',
                'required' => true,
                'choices' => $choice
            ]
        );

        $modelBuild = $form->getData();
        $this->addTagsField($form, $modelBuild instanceof ModelBuild ? $modelBuild : null);
        $this->addPretrainedModelField(
            $form,
            $modelBuild instanceof ModelBuild ? $modelBuild : null,
            $data['engine'] ?? null,
            $data['engineModel'] ?? null
        );
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ModelBuild::class,
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

    /**
     * @return array
     */
    public function getEnviroments(): array
    {
        $environments = [];
        foreach ($this->dataEnvironments as $item) {
            $environments[$item] = $item;
        }

        return $environments;
    }

    /**
     * @return array
     */
    public function getEngine(): array
    {
        $engine = [];
        foreach ($this->dataEngines as $key => $item) {
            $engine[$key] = $key;
        }

        return $engine;
    }

    /**
     * @return array
     */
    public function getModels($engineName = null): array
    {
        if ($engineName) {
            foreach ($this->dataEngines as $key => $engine) {
                if ($key === $engineName) {
                    $firstEngine = $engine;
                }
            }
        } else {
            $firstEngine = reset($this->dataEngines);
        }

        $models = [];

        foreach ($firstEngine['models'] as $model) {
            $models[$model] = $model;
        }

        return $models;
    }

    private function addTagsField(FormInterface $form, ?ModelBuild $modelBuild): void
    {
        $choices = [];
        if ($modelBuild instanceof ModelBuild) {
            foreach ($this->datasetItemProvider->getAvailableTags($modelBuild) as $tag) {
                $choices[$tag] = $tag;
            }
        }

        $form->remove('tags');
        $form->add(
            'tags',
            OroChoiceType::class,
            [
                'label' => 'syntetiq.modelbuild.tags.label',
                'required' => false,
                'multiple' => true,
                'choices' => $choices,
                'configs' => [
                    'placeholder' => 'syntetiq.modelbuild.tags.placeholder',
                    'allowClear' => true,
                    'closeOnSelect' => false,
                ],
            ]
        );
    }

    private function resolveEngine(?ModelBuild $modelBuild, ?string $engine = null): ?string
    {
        if ($engine) {
            return $engine;
        }

        if ($modelBuild instanceof ModelBuild && $modelBuild->getEngine()) {
            return $modelBuild->getEngine();
        }

        return array_key_first($this->dataEngines);
    }

    private function resolveEngineModel(
        ?ModelBuild $modelBuild,
        ?string $engine,
        ?string $engineModel = null
    ): ?string {
        if ($engineModel) {
            return $engineModel;
        }

        if ($modelBuild instanceof ModelBuild && $modelBuild->getEngineModel()) {
            return $modelBuild->getEngineModel();
        }

        $models = $this->getModels($engine);

        return array_key_first($models);
    }

    private function addPretrainedModelField(
        FormInterface $form,
        ?ModelBuild $modelBuild,
        ?string $engine = null,
        ?string $engineModel = null
    ): void {
        $currentEngine = $this->resolveEngine($modelBuild, $engine);
        $currentEngineModel = $this->resolveEngineModel($modelBuild, $currentEngine, $engineModel);
        $model = $modelBuild instanceof ModelBuild && $modelBuild->hasModel() ? $modelBuild->getModel() : null;

        $form->remove('pretrainedModel');
        $form->add(
            'pretrainedModel',
            EntityType::class,
            [
                'class' => ModelPretrained::class,
                'label' => 'syntetiq.modelbuild.pretrained_model.label',
                'required' => false,
                'placeholder' => 'syntetiq.modelbuild.pretrained_model.placeholder',
                'query_builder' => fn (ModelPretrainedRepository $repository) => $repository
                    ->createAvailableForModelQueryBuilder($model),
                'choice_label' => fn (ModelPretrained $candidate) => sprintf(
                    '%s - %s / %s',
                    $candidate->getDisplayLabel(),
                    $candidate->getEngine(),
                    $candidate->getEngineModel()
                ),
                'choice_attr' => fn (ModelPretrained $candidate) => [
                    'data-engine' => $candidate->getEngine(),
                    'data-engine-model' => $candidate->getEngineModel(),
                    'data-compatible' => (int) (
                        $candidate->getEngine() === $currentEngine
                        && $candidate->getEngineModel() === $currentEngineModel
                    ),
                ],
            ]
        );
    }
}
