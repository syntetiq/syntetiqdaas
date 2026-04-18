<?php

namespace SyntetiQ\Bundle\ModelBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;

class LoadModelData extends AbstractFixture implements DependentFixtureInterface
{
    const REFERENCE_PREFIX = "Model-";

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $this->persistParent($data, $manager, null);
    }

    /**
     * @param $data
     * @param ObjectManager $manager
     * @param $parent
     */
    protected function persistParent($data, $manager, $parent)
    {
        foreach ($data as $item) {
            $model = new Model();
            $model->setType($this->getReference($item['type_id']));
            $model->setParent($parent);
            $model->setSatelliteOf($item['satellite_of_id']);
            $model->setName($item['name']);
            $model->setDescription($item['description']);

            $manager->persist($model);

            $this->addReference(self::REFERENCE_PREFIX . $item['reference_key'], $model);

            if (array_key_exists('children', $item) && is_array($item['children']) && count($item['children']) > 0) {
                $this->persistParent($item['children'], $manager, $model);
            }
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $earth_children = [
            'reference_key' => 'Earth2',
            'type_id' => LoadModelData::REFERENCE_PREFIX . 'Space',
            'parent_id' => 6,
            'satellite_of_id' => null,
            'name' => 'Earth`s Orbit sector 2',
            'description' => '42000 km',
            'width' => 100000,
            'height' => 100000,
            'x' => 30,
            'y' => 30,
            'players_min' => 0,
            'players_max' => 100,
            'ship_weight_min' => null,
            'ship_weight_max' => null,
            'round_time' => null,
            'pre_start_time' => 0,
            'ai_auto_balance' => 2,
            'active' => true,
            'multiplier_experience' => 1,
            'multiplier_silver' => 1,
            'security_status' => 1,
            'npc_ship_weight_limit' => 4,
            'npc_spawn_timeout' => 60000,
            'players_ship_weight_bonus' => 0,
            'danger_level' => 0,
        ];
        return [
            [
                'reference_key' => 'Tutorial',
                'type_id' => LoadModelData::REFERENCE_PREFIX . 'Tutorial',
                'parent_id' => null,
                'satellite_of_id' => null,
                'name' => 'Tutorial',
                'description' => '',
                'width' => 100000,
                'height' => 100000,
                'x' => 0,
                'y' => 0,
                'players_min' => 1,
                'players_max' => 1,
                'ship_weight_min' => null,
                'ship_weight_max' => null,
                'round_time' => 3600000,
                'pre_start_time' => 0,
                'ai_auto_balance' => 0,
                'active' => true,
                'multiplier_experience' => 0.2,
                'multiplier_silver' => 0.2,
                'security_status' => 1,
                'npc_ship_weight_limit' => 5,
                'npc_spawn_timeout' => 60000,
                'players_ship_weight_bonus' => 0,
                'danger_level' => 0,
            ], [
                'reference_key' => 'Earth',
                'type_id' => LoadModelData::REFERENCE_PREFIX . 'Space',
                'parent_id' => null,
                'satellite_of_id' => null,
                'name' => 'Earth',
                'description' => '42000 km',
                'width' => 200000,
                'height' => 200000,
                'x' => 30,
                'y' => 30,
                'players_min' => 0,
                'players_max' => 100,
                'ship_weight_min' => null,
                'ship_weight_max' => null,
                'round_time' => null,
                'pre_start_time' => 0,
                'ai_auto_balance' => 2,
                'active' => true,
                'multiplier_experience' => 1,
                'multiplier_silver' => 1,
                'security_status' => 1,
                'npc_ship_weight_limit' => 4,
                'npc_spawn_timeout' => 60000,
                'players_ship_weight_bonus' => 0,
                'danger_level' => 0,
                'children' => [$earth_children]
            ]
        ];
    }

    public function getDependencies()
    {
        return [
            LoadServerConfigurationDemoData::class
        ];
    }
}
