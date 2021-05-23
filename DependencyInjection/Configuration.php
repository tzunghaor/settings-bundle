<?php

namespace Tzunghaor\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const CONFIG_ROOT = 'tzunghaor_settings';
    public const MAPPINGS = 'mappings';
    public const DEFAULT_MAPPING = 'default';
    public const DIR = 'dir';
    public const PREFIX = 'prefix';
    public const CACHE = 'cache';
    public const SCOPES = 'scopes';
    public const SCOPE_NAME = 'name';
    public const SCOPE_CHILDREN = 'children';
    public const DEFAULT_SCOPE = 'default_scope';
    public const ENTITY = 'entity';

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_ROOT);

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode(self::MAPPINGS)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(self::DIR)
                            ->end()
                            ->scalarNode(self::PREFIX)
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->scalarNode(self::CACHE)
                    ->info('Id of a tag aware cache service to be used')
                ->end()

                ->scalarNode(self::ENTITY)
                    ->info('Entity class - must implement PersistedSettingInterface')
                ->end()

                ->arrayNode(self::SCOPES)
                    ->info('Scopes hierarchy')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(self::SCOPE_NAME)
                                ->isRequired()
                            ->end()
                            ->variableNode(self::SCOPE_CHILDREN)
                                ->info('same structure recursively')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->variableNode(self::DEFAULT_SCOPE)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}