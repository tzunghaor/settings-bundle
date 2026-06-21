<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('tzunghaor_settings.default_collection', 'default');
    $parameters->set('tzunghaor_settings.default_scope', 'default');
    $parameters->set('tzunghaor_settings.default_mapping', 'default');
    $parameters->set('tzunghaor_settings.default_scope_hierarchy', [['name' => '%tzunghaor_settings.default_scope%']]);

    $services->set('tzunghaor_settings.builtin_setting_converter', \Tzunghaor\SettingsBundle\Service\BuiltinSettingConverter::class)
        ->private()
        ->tag('tzunghaor_settings.setting_converter', ['priority' => -1000]);

    $services->set('tzunghaor_settings.settings_service', \Tzunghaor\SettingsBundle\Service\SettingsService::class)
        ->args([
            '$settingsMetaService' => service('tzunghaor_settings.settings_meta_service'),
            '$store' => service('tzunghaor_settings.doctrine_settings_store'),
            '$dataConverters' => tagged_iterator('tzunghaor_settings.setting_converter'),
            '$cache' => service('tzunghaor_settings.default_tag_aware_cache'),
        ]);

    $services->alias(\Tzunghaor\SettingsBundle\Service\SettingsService::class, 'tzunghaor_settings.settings_service');

    $services->set('tzunghaor_settings.settings_editor_service', \Tzunghaor\SettingsBundle\Service\SettingsEditorService::class)
        ->args([
            '$settingsServiceLocator' => tagged_locator('tzunghaor_settings.settings_service', indexAttribute: 'key'),
            '$settingsMetaServiceLocator' => tagged_locator('tzunghaor_settings.settings_meta_service', indexAttribute: 'key'),
            '$formFactory' => service('form.factory'),
            '$router' => service('router'),
            '$defaultCollectionName' => '%tzunghaor_settings.default_collection%',
        ]);

    $services->alias(\Tzunghaor\SettingsBundle\Service\SettingsEditorService::class, 'tzunghaor_settings.settings_editor_service');

    $services->set('tzunghaor_settings.settings_editor_type', \Tzunghaor\SettingsBundle\Form\SettingsEditorType::class)
        ->tag('form.type');

    $services->alias(\Tzunghaor\SettingsBundle\Form\SettingsEditorType::class, 'tzunghaor_settings.settings_editor_type');

    $services->set('tzunghaor_settings.doctrine_settings_store', \Tzunghaor\SettingsBundle\Service\DoctrineSettingsStore::class)
        ->args([
            '$em' => service('doctrine.orm.entity_manager'),
            '$entityClass' => \Tzunghaor\SettingsBundle\Entity\PersistedSetting::class,
        ]);

    $services->set('tzunghaor_settings.editor_controller', \Tzunghaor\SettingsBundle\Controller\SettingsEditorController::class)
        ->args([
            '$settingsEditorService' => service('tzunghaor_settings.settings_editor_service'),
            '$router' => service('router'),
            '$twig' => service('twig'),
        ])
        ->tag('controller.service_arguments');

    $services->set('tzunghaor_settings.default_cache', \Symfony\Component\Cache\Adapter\ArrayAdapter::class);

    $services->set('tzunghaor_settings.default_tag_aware_cache', \Symfony\Component\Cache\Adapter\TagAwareAdapter::class)
        ->args(['$itemsPool' => service('tzunghaor_settings.default_cache')]);

    $services->set('tzunghaor_settings.meta_data_extractor', \Tzunghaor\SettingsBundle\Service\MetaDataExtractor::class)
        ->args(['$propertyInfo' => service('property_info')]);

    $services->set('tzunghaor_settings.default_scope_provider', \Tzunghaor\SettingsBundle\Service\StaticScopeProvider::class)
        ->args([
            '$scopeHierarchy' => '%tzunghaor_settings.default_scope_hierarchy%',
            '$defaultScopeName' => '%tzunghaor_settings.default_scope%',
        ]);

    $services->set('tzunghaor_settings.settings_meta_service', \Tzunghaor\SettingsBundle\Service\SettingsMetaService::class)
        ->args([
            '$cache' => service('tzunghaor_settings.default_cache'),
            '$metaDataExtractor' => service('tzunghaor_settings.meta_data_extractor'),
            '$sectionClasses' => [],
            '$collectionName' => '%tzunghaor_settings.default_collection%',
            '$scopeProvider' => service('tzunghaor_settings.default_scope_provider'),
            '$collectionTitle' => '',
            '$collectionExtra' => [],
        ])
        ->tag('kernel.cache_warmer');
};
