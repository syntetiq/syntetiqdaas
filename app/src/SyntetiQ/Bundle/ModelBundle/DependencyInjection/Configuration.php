<?php

namespace SyntetiQ\Bundle\ModelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('syntetiq_model');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('environments')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('engines')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->cannotBeEmpty()
                            ->end()
                            ->enumNode('dataset_format')
                                ->values(['pascal-voc', 'pascal-voc-xml', 'yolo'])
                            ->end()
                            ->enumNode('dataset_split')
                                ->values(['train-valid-test'])
                            ->end()
                            ->enumNode('dataset_box_format')
                                ->values(['xxyy', 'xyxy'])
                            ->end()
                            ->scalarNode('python_version')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('models')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
               'environments' => ['value' => ['venv', 'conda']],
               'engines' => ['value' => null]
            ]
        );

        return $treeBuilder;
    }
}
