<?php

namespace SyntetiQ\Bundle\ModelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class SyntetiQModelExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $container->setParameter('syntetiq_model.environments', $config['environments']);
        $container->setParameter('syntetiq_model.engines', $config['engines']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('filesystems.yml');
        $loader->load('services.yml');
        $loader->load('controllers.yml');
        $loader->load('form.yml');
        $loader->load('commands.yml');
        $loader->load('mq_topics.yml');
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
