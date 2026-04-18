<?php

namespace SyntetiQ\Bundle\SetupBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\AbstractDashboardFixture;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\LoadDashboardData as LoadEmailDashboardData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\UpdateUserEntitiesWithOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;

/**
 * Creates "main" dashboard and configures it with specific widgets.
 */
class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            UpdateUserEntitiesWithOrganization::class,
            LoadEmailDashboardData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $dashboard = $this->findAdminDashboardModel($manager, 'main');
        if ($dashboard) {
            $this->removeWidgetByName($dashboard, 'recent_emails');

            // Remove existing widgets
            $dashboard->getWidgets()->clear();
            $dashboard->getEntity()->resetWidgets();

            // Add dashboard widgets
            $dashboard->addWidget($this->createWidgetModel('recent_data_sets', [0, 0]));
            $dashboard->addWidget($this->createWidgetModel('recent_models', [1, 0]));
            $dashboard->addWidget($this->createWidgetModel('recent_model_builds', [0, 1]));

            $manager->flush();
        }
    }

    private function removeWidgetByName($dashboard, string $widgetName): void
    {
        foreach ($dashboard->getWidgets() as $widget) {
            if ($widget->getName() !== $widgetName) {
                continue;
            }

            $dashboard->getEntity()->removeWidget($widget->getEntity());
            $dashboard->getWidgets()->removeElement($widget);
        }
    }

    private function getAdminUser(ObjectManager $manager): User
    {
        $repository = $manager->getRepository(Role::class);
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);
        if (!$user) {
            throw new InvalidArgumentException('Administrator user should exist to load dashboard configuration.');
        }

        return $user;
    }

    private function getDashboardManager(): Manager
    {
        return $this->container->get('oro_dashboard.manager');
    }
}
