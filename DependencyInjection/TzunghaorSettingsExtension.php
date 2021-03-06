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
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;
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
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        // process configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config as $name => $collectionConfig) {
            $this->configureCollection($name, $collectionConfig, $container);
        }
    }

    private function configureCollection(string $name, array $config, ContainerBuilder $container): void
    {
        $defaultSettingsMetaServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_meta_service');
        $defaultSettingsServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_service');

        if ($name === Configuration::DEFAULT_COLLECTION) {
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
            $container
                ->setDefinition('tzunghaor_settings.settings_service.' . $name, $settingsServiceDefinition);
        }


        $settingsMetaServiceDefinition->replaceArgument(
            '$sectionClasses',
            $this->getSectionClasses($config[Configuration::MAPPINGS])
        );

        if (isset($config[Configuration::CACHE])) {
            $settingsMetaServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
            $settingsServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
        }

        if (isset($config[Configuration::SCOPES])) {
            $defaultScope = $config[Configuration::DEFAULT_SCOPE] ??
                $container->getParameter('tzunghaor_settings.default_scope');

            $scopeProviderDefinition = new Definition(
                StaticScopeProvider::class,
                [
                    '$scopeHierarchy' => $config[Configuration::SCOPES],
                    '$defaultScope' => $defaultScope,
                ]
            );

            $settingsMetaServiceDefinition->replaceArgument('$scopeProvider', $scopeProviderDefinition);
        }

        if (isset($config[Configuration::ENTITY])) {
            $entityClass = $config[Configuration::ENTITY];
            $expectedClass = AbstractPersistedSetting::class;
            if (!is_subclass_of($entityClass, $expectedClass)) {
                throw new InvalidConfigurationException(sprintf('%s.%s must be a subclass of %s',
                                                                Configuration::CONFIG_ROOT, Configuration::ENTITY, $expectedClass));
            }

            $settingsStoreDefinition = $container->getDefinition('tzunghaor_settings.settings_store');
            $settingsStoreDefinition->replaceArgument('$entityClass', $entityClass);
        }
    }

    /**
     * Retrieves the sectionName => $sectionClass mapping based on config
     *
     * @param array $mappings
     *
     * @return array
     */
    private function getSectionClasses(array $mappings): array
    {
        $sectionClasses = [];

        foreach ($mappings as $mappingName => $mappingDef) {
            $dir = $mappingDef[Configuration::DIR];
            $prefix = $mappingDef[Configuration::PREFIX];

            $finder = new Finder();
            $finder->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $path = $file->getRelativePath();
                $nameArray = empty($path) ? [] : explode(DIRECTORY_SEPARATOR, $path);
                $nameArray[] = $file->getFilenameWithoutExtension();

                $sectionClass = $prefix . implode('\\', $nameArray);
                $sectionName = implode('.', $nameArray);
                if ($mappingName !== Configuration::DEFAULT_MAPPING) {
                    $sectionName = $mappingName . '.' . $sectionName;
                }

                $sectionClasses[$sectionName] = $sectionClass;
            }
        }

        return $sectionClasses;
    }
}