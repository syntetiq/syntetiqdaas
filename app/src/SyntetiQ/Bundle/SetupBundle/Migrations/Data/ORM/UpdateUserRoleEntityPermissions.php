<?php

namespace SyntetiQ\Bundle\SetupBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\LoadAclRoles;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\ModelBundle\Entity\Model;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelPretrained;
use SyntetiQ\Bundle\OmniverseBundle\Entity\GenerateImagesRequest;

class UpdateUserRoleEntityPermissions extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    private const USER_PERMISSIONS = [
        'VIEW_BASIC',
        'CREATE_BASIC',
        'EDIT_BASIC',
        'DELETE_BASIC',
    ];

    private const MANAGER_PERMISSIONS = [
        'VIEW_LOCAL',
        'CREATE_LOCAL',
        'EDIT_LOCAL',
        'DELETE_LOCAL',
    ];

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
        return [LoadAclRoles::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $userRole = $this->getRole($manager, LoadRolesData::ROLE_USER);
        $managerRole = $this->getRole($manager, LoadRolesData::ROLE_MANAGER);

        foreach (self::ENTITY_CLASSES as $entityClass) {
            $this->replaceEntityPermissions($aclManager, $userRole, $entityClass, self::USER_PERMISSIONS);
            $this->replaceEntityPermissions($aclManager, $managerRole, $entityClass, self::MANAGER_PERMISSIONS);
        }

        $aclManager->flush();
    }
}
