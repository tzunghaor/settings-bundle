<?php


namespace Tzunghaor\SettingsBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;

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

        $settingsMetaServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_meta_service');
        $settingsServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_service');

        if (isset($config[Configuration::CACHE])) {
            $settingsMetaServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
            $settingsServiceDefinition->replaceArgument('$cache', new Reference($config[Configuration::CACHE]));
        }

        if (isset($config[Configuration::SCOPES])) {
            $settingsMetaServiceDefinition->replaceArgument('$scopeHierarchy', $config[Configuration::SCOPES]);
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

        if (isset($config[Configuration::DEFAULT_SCOPE])) {
            $settingsServiceDefinition->replaceArgument('$defaultScope', $config[Configuration::DEFAULT_SCOPE]);
        }
    }
}