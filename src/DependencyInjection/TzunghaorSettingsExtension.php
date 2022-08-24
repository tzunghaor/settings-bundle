<?php


namespace Tzunghaor\SettingsBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Tzunghaor\SettingsBundle\Model\PersistedSettingInterface;
use Tzunghaor\SettingsBundle\Service\DoctrineSettingsStore;
use Tzunghaor\SettingsBundle\Service\SettingsMetaService;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Service\StaticScopeProvider;

class TzunghaorSettingsExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load bundle config yamls
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/config')
        );
        $loader->load('services.xml');

        // process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $defaultCollectionName = $container->getParameter('tzunghaor_settings.default_collection');
        $isDefaultCollectionProcessed = false;

        foreach ($config[Configuration::COLLECTIONS] as $name => $collectionConfig) {
            $isDefault = $name === $defaultCollectionName;
            $this->configureCollection($name, $collectionConfig, $container, $isDefault);
            $isDefaultCollectionProcessed = $isDefaultCollectionProcessed || $isDefault;
        }

        // if there is no collection with default name, then remove the default services
        if (!$isDefaultCollectionProcessed) {
            $container->removeDefinition('tzunghaor_settings.settings_service');
            $container->removeAlias(SettingsService::class);
            $container->removeDefinition('tzunghaor_settings.settings_meta_service');
        }

        // set up security
        if ($config[Configuration::SECURITY] ?? false) {
            $container->getDefinition('tzunghaor_settings.settings_editor_service')
                ->addMethodCall('setAuthorizationChecker', [new Reference('security.authorization_checker')]);
        }
    }

    private function configureCollection(string $name, array $config, ContainerBuilder $container, bool $isDefault): void
    {
        $config[Configuration::NAME] = $config[Configuration::NAME] ?? $name;
        $defaultSettingsMetaServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_meta_service');
        $defaultSettingsServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_service');
        $defaultMappingName = $container->getParameter('tzunghaor_settings.default_mapping');

        if ($isDefault) {
            $settingsServiceId = 'tzunghaor_settings.settings_service.' . $name;
            $container->setAlias($settingsServiceId, 'tzunghaor_settings.settings_service');
            $settingsMetaServiceDefinition = $defaultSettingsMetaServiceDefinition;
            $settingsServiceDefinition = $defaultSettingsServiceDefinition;
        } else {
            $settingsMetaServiceDefinition = new Definition(
                SettingsMetaService::class,
                $defaultSettingsMetaServiceDefinition->getArguments()
            );
            $container
                ->setDefinition('tzunghaor_settings.settings_meta_service.' . $name, $settingsMetaServiceDefinition);

            $settingsServiceDefinition = new Definition(
                SettingsService::class,
                $defaultSettingsServiceDefinition->getArguments()
            );
            $settingsServiceDefinition->replaceArgument('$settingsMetaService', $settingsMetaServiceDefinition);
            $settingsServiceId = 'tzunghaor_settings.settings_service.' . $name;
            $container->setDefinition($settingsServiceId, $settingsServiceDefinition);

            // add autowiring support for this service with using specific argument names
            $container->registerAliasForArgument($settingsServiceId, SettingsService::class, $name);
            $container->registerAliasForArgument($settingsServiceId, SettingsService::class, $name . '_settings');
        }

        $settingsServiceDefinition->addTag('tzunghaor_settings.settings_service', ['key' => $name]);
        $settingsMetaServiceDefinition->addTag('tzunghaor_settings.settings_meta_service', ['key' => $name]);

        $settingsMetaServiceDefinition->replaceArgument('$collectionName', $name);
        $settingsMetaServiceDefinition->replaceArgument(
            '$sectionClasses',
            $this->getSectionClasses($config[Configuration::MAPPINGS], $defaultMappingName)
        );

        if (isset($config[Configuration::CACHE])) {
            $settingsMetaServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
            $settingsServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
        }

        if (isset($config[Configuration::SCOPE_PROVIDER])) {
            $settingsMetaServiceDefinition->replaceArgument(
                '$scopeProvider', new Reference($config[Configuration::SCOPE_PROVIDER]));
        } elseif (isset($config[Configuration::SCOPES]) && !empty($config[Configuration::SCOPES])) {
            $defaultScopeName = $config[Configuration::DEFAULT_SCOPE] ??
                $container->getParameter('tzunghaor_settings.default_scope');

            $scopeProviderDefinition = new Definition(
                StaticScopeProvider::class,
                [
                    '$scopeHierarchy' => $config[Configuration::SCOPES],
                    '$defaultScopeName' => $defaultScopeName,
                ]
            );

            $settingsMetaServiceDefinition->replaceArgument('$scopeProvider', $scopeProviderDefinition);
        }

        if (isset($config[Configuration::ENTITY])) {
            $entityClass = $config[Configuration::ENTITY];
            $expectedInterface = PersistedSettingInterface::class;
            if (!in_array($expectedInterface, class_implements($entityClass), true)) {
                throw new InvalidConfigurationException(
                    sprintf('%s.%s must implement %s',
                            Configuration::CONFIG_ROOT, Configuration::ENTITY, $expectedInterface)
                );
            }

            $settingsStoreDefinition = new Definition(DoctrineSettingsStore::class, [
                '$em' => new Reference('doctrine.orm.entity_manager'),
                '$entityClass' => $entityClass,
            ]);
            $settingsServiceDefinition->replaceArgument('$store', $settingsStoreDefinition);
        }

        $settingsMetaServiceDefinition->replaceArgument('$collectionTitle', $config[Configuration::TITLE] ?? null);
        $settingsMetaServiceDefinition->replaceArgument('$collectionExtra', $config[Configuration::EXTRA] ?? []);
    }

    /**
     * Retrieves the sectionName => $sectionClass mapping based on config
     *
     * @param array $mappings
     * @param string $defaultMappingName
     *
     * @return array
     */
    private function getSectionClasses(array $mappings, string $defaultMappingName): array
    {
        $sectionClasses = [];

        foreach ($mappings as $mappingName => $mappingDef) {
            $dir = $mappingDef[Configuration::DIR];
            $prefix = $mappingDef[Configuration::PREFIX];

            // find all php file in the configured directory
            $finder = new Finder();
            $finder->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $path = $file->getRelativePath();
                $nameArray = empty($path) ? [] : explode(DIRECTORY_SEPARATOR, $path);
                $nameArray[] = $file->getFilenameWithoutExtension();

                $sectionClass = $prefix . implode('\\', $nameArray);
                $sectionName = implode('.', $nameArray);
                // default mapping's name is not prepended to section name
                if ($mappingName !== $defaultMappingName) {
                    $sectionName = $mappingName . '.' . $sectionName;
                }

                // abstract classes are not usable as setting sections (but they might be used as parents)
                try {
                    $reflectionClass = new \ReflectionClass($sectionClass);
                } catch (\ReflectionException $e) {
                    $message = sprintf('Error analysing section class "%s" in mapping "%s"',
                                       $sectionClass, $mappingName);
                    throw new \RuntimeException($message, 0, $e);
                }

                if ($reflectionClass->isAbstract()) {
                    continue;
                }

                $sectionClasses[$sectionName] = $sectionClass;
            }
        }

        return $sectionClasses;
    }
}
