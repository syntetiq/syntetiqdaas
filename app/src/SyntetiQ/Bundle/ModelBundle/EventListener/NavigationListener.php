<?php

namespace SyntetiQ\Bundle\ModelBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class NavigationListener
{
    public function __construct(private readonly string $environment)
    {
    }

    public function onNavigationConfigure(ConfigureMenuEvent $event): void
    {
        if ($this->environment !== 'dev') {
            return;
        }

        $modelMenuItem = MenuUpdateUtils::findMenuItem($event->getMenu(), 'model_tab');
        if (!$modelMenuItem || $modelMenuItem->getChild('syntetiq_model_test_script_tab')) {
            return;
        }

        $modelMenuItem->addChild('syntetiq_model_test_script_tab', [
            'label' => 'Test Script',
            'route' => 'syntetiq_model_test_script_index',
            'extras' => [
                'position' => 999,
            ],
        ]);
    }
}
