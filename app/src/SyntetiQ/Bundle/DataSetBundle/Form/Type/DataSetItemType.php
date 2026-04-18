<?php

namespace SyntetiQ\Bundle\DataSetBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DataSetItemType extends AbstractType
{
    const NAME = 'syntetiq_model_type_data_set';

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'image',
                ImageType::class,
                [
                    'label' => 'syntetiq.dataset.datasetitem.image.label',
                    'required' => true,
                    'attr' => [
                        'class' => 'imageFile',
                    ],
                    'fileOptions' => [
                        'attr' => [
                            'data-max-size' => $this->configManager->get('oro_attachment.maxsize') * 1024 * 1024
                        ],
                        'constraints' => [
                            new File([
                                'maxSize' => $this->configManager->get('oro_attachment.maxsize') . 'M'
                            ])
                        ]
                    ]
                ]
            )
            ->add(
                'group',
                ChoiceType::class,
                [
                    'label' => 'syntetiq.dataset.datasetitem.group.label',
                    'required' => true,
                    'placeholder' => 'Choose Group',
                    'choices' => [
                        'Train' => Group::TRAIN,
                        'Validation' => Group::VAL,
                        'Test' => Group::TEST
                    ],
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'hiddenObjectInfo',
                HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => [
                        'class' => 'hidden-object-info'
                    ]
                ]
            )
            ->add(
                'readyDirtyState',
                HiddenType::class,
                [
                    'mapped' => false,
                    'data' => '0',
                ]
            )
            ->add(
                'ready',
                CheckboxType::class,
                [
                    'label' => 'Ready',
                    'required' => false,
                    'mapped' => false,
                    'false_values' => [null, false, 0, '0', '', 'false'],
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'preSetData'], -10);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'postSubmitData'], -10);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var DataSetItem $data */
        $data = $event->getData();

        $i = 0;
        $dataInfo = array_map(function (ItemObjectConfiguration $item) use (&$i) {
            $i = ++$i;
            return [
                'x' => $item->getMinX(),
                'y' => $item->getMinY(),
                'width' => $item->getMaxX() - $item->getMinX(),
                'height' => $item->getMaxY() - $item->getMinY(),
                'name' => $item->getName(),
                'index' => $i - 1
            ];
        }, $data->getObjectConfiguration()->toArray());


        $info = [
            'imgWidth' => $data->getImgWidth(),
            'imgHeight' => $data->getImgHeight(),
            'areas' => $dataInfo
        ];


        $form['hiddenObjectInfo']->setData(json_encode($info));
        $form['group']->setData($data->getGroup() ?: Group::TRAIN);
        $form['ready']->setData($data->isReady());
    }


    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var array<string, mixed> $data */
        $data = $event->getData();
        if (!is_array($data)) {
            return;
        }

        /** @var DataSetItem $formData */
        $formData = $form->getData();
        if (!$formData instanceof DataSetItem) {
            return;
        }

        $hiddenObjectInfoData = $data['hiddenObjectInfo'] ?? null;
        if (!is_string($hiddenObjectInfoData)) {
            return;
        }

        $hiddenObjectInfo = json_decode($hiddenObjectInfoData);
        if (!$hiddenObjectInfo) {
            return;
        }

        $formData->touch();

        $formData->getObjectConfiguration()->clear();

        if (property_exists($hiddenObjectInfo, 'imgHeight')) {
            $formData->setImgHeight($hiddenObjectInfo->imgHeight);
        }
        if (property_exists($hiddenObjectInfo, 'imgWidth')) {
            $formData->setImgWidth($hiddenObjectInfo->imgWidth);
        }

        if (property_exists($hiddenObjectInfo, 'areas') && is_array($hiddenObjectInfo->areas)) {
            foreach ($hiddenObjectInfo->areas as $objectData) {
                $objectConfiguration = new ItemObjectConfiguration();
                if (property_exists($objectData, 'name')) {
                    $objectConfiguration->setName($objectData->name);
                }
                $objectConfiguration->setTruncated(
                    property_exists($objectData, 'truncated') ? (bool) $objectData->truncated : false
                );
                $objectConfiguration->setMinX($objectData->x);
                $objectConfiguration->setMinY($objectData->y);
                $objectConfiguration->setMaxX($objectData->x + $objectData->width);
                $objectConfiguration->setMaxY($objectData->y + $objectData->height);

                if (property_exists($objectData, 'latitude')) {
                    $objectConfiguration->setLatitude((float)$objectData->latitude);
                }
                if (property_exists($objectData, 'longitude')) {
                    $objectConfiguration->setLongitude((float)$objectData->longitude);
                }
                $objectConfiguration->setDataSetItem($formData);
                $formData->getObjectConfiguration()->add($objectConfiguration);
            }
        }

        $event->getForm()->setData($formData);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DataSetItem::class,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
