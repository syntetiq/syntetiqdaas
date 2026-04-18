<?php

namespace SyntetiQ\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadDemoUsers extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadRolesData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $organization = $this->hasReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)
            ? $this->getReference(LoadOrganizationAndBusinessUnitData::REFERENCE_DEFAULT_ORGANIZATION)
            : $manager->getRepository(Organization::class)->getFirst();

        $businessUnit = $this->hasReference('default_business_unit')
            ? $this->getReference('default_business_unit')
            : $manager->getRepository(BusinessUnit::class)->getFirst();
        $userManager = $this->container->get('oro_user.manager');

        $roles = [
            LoadRolesData::ROLE_USER => $manager->getRepository(Role::class)
                ->findOneBy(['role' => LoadRolesData::ROLE_USER]),
            LoadRolesData::ROLE_MANAGER => $manager->getRepository(Role::class)
                ->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]),
        ];

        $users = [
            [
                'username' => 'demo.user1',
                'password' => 'demo.user1',
                'email' => 'demo.user1@example.com',
                'first_name' => 'Demo',
                'last_name' => 'UserOne',
                'role' => LoadRolesData::ROLE_USER,
            ],
            [
                'username' => 'demo.user2',
                'password' => 'demo.user2',
                'email' => 'demo.user2@example.com',
                'first_name' => 'Demo',
                'last_name' => 'UserTwo',
                'role' => LoadRolesData::ROLE_USER,
            ],
            [
                'username' => 'demo.manager1',
                'password' => 'demo.manager1',
                'email' => 'demo.manager1@example.com',
                'first_name' => 'Demo',
                'last_name' => 'ManagerOne',
                'role' => LoadRolesData::ROLE_MANAGER,
            ],
        ];

        foreach ($users as $userData) {
            if ($manager->getRepository(User::class)->findOneBy(['username' => $userData['username']])) {
                continue;
            }

            $role = $roles[$userData['role']] ?? null;
            if (!$role instanceof Role) {
                throw new \RuntimeException(sprintf('Role "%s" was not found.', $userData['role']));
            }

            $user = $userManager->createUser();
            $user->setUsername($userData['username'])
                ->setPlainPassword($userData['password'])
                ->setEmail($userData['email'])
                ->setFirstName($userData['first_name'])
                ->setLastName($userData['last_name'])
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setOwner($businessUnit)
                ->addBusinessUnit($businessUnit)
                ->addUserRole($role)
                ->setEnabled(true);

            $userManager->updateUser($user);
        }
    }
}
