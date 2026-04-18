<?php

namespace SyntetiQ\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;

class LoadDataSetDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const REFERENCE_PREFIX = 'demo-data-set-';

    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $datasetsDir = __DIR__ . '/datasets';
        if (!is_dir($datasetsDir)) {
            return;
        }

        $owner = $manager->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);

        if (!$owner instanceof User) {
            throw new \RuntimeException('Admin user should exist before loading demo datasets.');
        }

        $organization = $owner->getOrganization();
        if (null === $organization) {
            throw new \RuntimeException('Admin user should have an organization before loading demo datasets.');
        }

        $finder = new Finder();
        $finder->files()->in($datasetsDir)->name('*.yml')->depth(0);

        $fileManager = $this->container->get('oro_attachment.file_manager');

        $index = 0;
        foreach ($finder as $file) {
            $dsConfig = Yaml::parse($file->getContents());
            $dataSet = new DataSet();
            $dataSet->setName($dsConfig['name']);
            $dataSet->setOwner($owner);
            $dataSet->setOrganization($organization);

            $imagesDir = $file->getPath() . '/' . $file->getBasename('.yml') . '/images';

            foreach ($dsConfig['items'] as $itemConfig) {
                $item = new DataSetItem();
                $item->setOwner($owner);
                $item->setOrganization($organization);
                $item->setImgWidth($itemConfig['img_width']);
                $item->setImgHeight($itemConfig['img_height']);
                $item->setSourceType($itemConfig['source_type'] ?? 'manual');
                $item->setGroup($itemConfig['group'] ?? null);
                $item->setTag($itemConfig['tag'] ?? null);

                if (isset($itemConfig['image']) && is_dir($imagesDir)) {
                    $imagePath = $imagesDir . '/' . $itemConfig['image'];
                    if (file_exists($imagePath) && is_readable($imagePath)) {
                        $fileEntity = new File();
                        $fileManager->setFileFromPath($fileEntity, $imagePath);

                        try {
                            $item->setImage($fileEntity);
                            $manager->persist($fileEntity);
                        } catch (\Exception $e) {
                            throw new \RuntimeException(sprintf(
                                'Failed to upload image "%s" for dataset "%s" using oro_attachment.file_manager. Error: %s',
                                $imagePath,
                                $dsConfig['name'],
                                $e->getMessage()
                            ), 0, $e);
                        }
                    }
                }

                if (isset($itemConfig['objects'])) {
                    foreach ($itemConfig['objects'] as $objConfig) {
                        $objConfiguration = new ItemObjectConfiguration();
                        $objConfiguration->setOwner($owner);
                        $objConfiguration->setOrganization($organization);
                        $objConfiguration->setName($objConfig['name']);
                        $objConfiguration->setTruncated($objConfig['truncated'] ?? false);
                        $objConfiguration->setMinX($objConfig['min_x']);
                        $objConfiguration->setMaxX($objConfig['max_x']);
                        $objConfiguration->setMinY($objConfig['min_y']);
                        $objConfiguration->setMaxY($objConfig['max_y']);

                        $item->addObjectConfiguration($objConfiguration);
                    }
                }

                $dataSet->addItem($item);
            }

            $manager->persist($dataSet);
            $this->addReference(self::REFERENCE_PREFIX . $index, $dataSet);
            $index++;
        }

        $manager->flush();
    }
}
