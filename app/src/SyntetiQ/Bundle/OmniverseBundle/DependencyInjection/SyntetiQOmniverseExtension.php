<?php

namespace SyntetiQ\Bundle\OmniverseBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class SyntetiQOmniverseExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form.yml');
        $loader->load('services.yml');
        $loader->load('controllers.yml');
    }

     /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {

        if ($container instanceof ExtendedContainerBuilder) {
            // todo
        }
    }
}
