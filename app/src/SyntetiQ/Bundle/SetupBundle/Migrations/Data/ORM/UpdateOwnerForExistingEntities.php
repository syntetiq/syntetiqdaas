<?php

namespace SyntetiQ\Bundle\SetupBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;

class UpdateOwnerForExistingEntities extends AbstractFixture implements DependentFixtureInterface
{
    private const ENTITY_CLASSES = [
        DataSet::class,
        DataSetExport::class,
        DataSetItem::class,
        DataSetItemTag::class,
        ItemObjectConfiguration::class,
        Model::class,
        ModelBuild::class,
        ModelPretrained::class,
        GenerateImagesRequest::class,
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAdminUserData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $admin = $manager->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);

        if (!$admin instanceof User) {
            throw new \RuntimeException('Admin user must exist before updating entity owners.');
        }

        $organization = $admin->getOrganization();
        if (null === $organization) {
            throw new \RuntimeException('Admin user must have an organization before updating entity owners.');
        }

        foreach (self::ENTITY_CLASSES as $entityClass) {
            $manager->createQueryBuilder()
                ->update($entityClass, 'e')
                ->set('e.owner', ':owner')
                ->set('e.organization', ':organization')
                ->where('e.owner IS NULL')
                ->setParameter('owner', $admin)
                ->setParameter('organization', $organization)
                ->getQuery()
                ->execute();
        }
    }
}
