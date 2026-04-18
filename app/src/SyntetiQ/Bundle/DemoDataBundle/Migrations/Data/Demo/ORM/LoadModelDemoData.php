<?php

namespace SyntetiQ\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;

class LoadModelDemoData extends AbstractFixture implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'demo-model-';

    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            LoadDataSetDemoData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $owner = $manager->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);

        if (!$owner instanceof User) {
            throw new \RuntimeException('Admin user should exist before loading demo models.');
        }

        $organization = $owner->getOrganization();
        if (null === $organization) {
            throw new \RuntimeException('Admin user should have an organization before loading demo models.');
        }

        $modelsConfig = $this->getModelsConfig();

        foreach ($modelsConfig as $index => $modelConfig) {
            $model = new Model();
            $model->setName($modelConfig['name']);
            $model->setDescription($modelConfig['description']);
            $model->setOwner($owner);
            $model->setOrganization($organization);

            $dataSet = $this->getReference(
                LoadDataSetDemoData::REFERENCE_PREFIX . $modelConfig['data_set_index']
            );
            $model->setDataSet($dataSet);

            $manager->persist($model);
            $this->addReference(self::REFERENCE_PREFIX . $index, $model);
        }

        $manager->flush();
    }

    private function getModelsConfig(): array
    {
        return [
            [
                'name' => 'Traffic Sign Detector v1',
                'description' => 'YOLO-based object detection model trained to recognise common traffic signs including stop signs, speed limits, yield signs, and traffic lights. Optimised for real-time inference on edge devices.',
                'data_set_index' => 0, // Traffic Signs Detection
            ],
            [
                'name' => 'PPE Compliance Checker',
                'description' => 'SSD-based model for detecting personal protective equipment (hard hat, safety vest, gloves) on construction sites. Designed for workplace safety monitoring with high recall.',
                'data_set_index' => 1, // PPE Compliance
            ],
        ];
    }
}
