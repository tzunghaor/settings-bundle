<?php

namespace Tzunghaor\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Tzunghaor\SettingsBundle\Model\Item;

class Configuration implements ConfigurationInterface
{
    public const CONFIG_ROOT = 'tzunghaor_settings';
    public const COLLECTIONS = 'collections';
    public const MAPPINGS = 'mappings';
    public const DIR = 'dir';
    public const PREFIX = 'prefix';
    public const CACHE = 'cache';
    public const SCOPES = 'scopes';
    public const SCOPE_PROVIDER = 'scope_provider';
    public const NAME = 'name';
    public const TITLE = 'title';
    public const CHILDREN = 'children';
    public const DEFAULT_SCOPE = 'default_scope';
    public const EXTRA = 'extra';
    public const ENTITY = 'entity';
    public const SECURITY = 'security';

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_ROOT, 'array');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode(self::SECURITY)
                    ->info('Use security voters to check editor access. Needs symfony/security-core')
                    ->defaultFalse()
                ->end()

                ->arrayNode(self::COLLECTIONS)
                    ->useAttributeAsKey(self::NAME, false)
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode(self::MAPPINGS)
                                ->info('Location of setting classes')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode(self::DIR)
                                            ->info('Root directory containing setting classes')
                                        ->end()
                                        ->scalarNode(self::PREFIX)
                                            ->info('Namespace prefix of the directory (with trailing \\)')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()

                            ->scalarNode(self::TITLE)
                                ->info('Title displayed in editor (default is collection name)')
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
                                    ->ignoreExtraKeys(false)
                                    ->children()
                                        ->scalarNode(self::NAME)
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode(self::TITLE)
                                            ->info('Title displayed in editor (default is scope name)')
                                        ->end()
                                        ->variableNode(self::CHILDREN)
                                            ->info('Same structure recursively')
                                        ->end()

                                        ->arrayNode(self::EXTRA)
                                            ->ignoreExtraKeys(false)
                                            ->info('Extra data that you can use in your templates / extensions')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()

                            ->variableNode(self::DEFAULT_SCOPE)
                            ->end()

                            ->scalarNode(self::SCOPE_PROVIDER)
                                ->info('Scope provider service - must implement ScopeProviderInterface')
                            ->end()

                            ->arrayNode(self::EXTRA)
                                ->ignoreExtraKeys(false)
                                ->info('Extra data that you can use in your templates / extensions')
                            ->end()
                       ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}