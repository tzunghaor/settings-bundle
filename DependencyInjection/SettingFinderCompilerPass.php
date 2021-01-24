<?php


namespace Tzunghaor\SettingsBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This pass finds services marked as "tzunghaor_settings.settings" and
 * * replace their definition with a factory method which fills the class from DB using the default scope.
 * * passes the class list to the setting meta service
 */
class SettingFinderCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $settingsServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_service');
        $settingsMetaServiceDefinition = $container->getDefinition('tzunghaor_settings.settings_meta_service');

        $sectionTag = 'tzunghaor_settings.setting_section';
        $settingsIds = $container->findTaggedServiceIds($sectionTag);
        $sectionClasses = [];
        foreach ($settingsIds as $settingsId => $tags) {
            $sectionDefinition = $container->getDefinition($settingsId);
            $sectionClass = $sectionDefinition->getClass();
            $removePrefix = '';
            foreach ($tags as $attributes) {
                $removePrefix = $attributes['remove_prefix'] ?? $removePrefix;
            }
            $removePrefixLength = strlen($removePrefix);
            if (substr($sectionClass, 0, $removePrefixLength) !== $removePrefix) {
                throw new \RuntimeException(sprintf('Cannot remove prefix %s from class %s tagged as %s',
                                                $removePrefix, $sectionClass, $sectionTag));
            }

            $sectionName = str_replace('\\', '.', substr($sectionClass, $removePrefixLength));

            $sectionClasses[$sectionName] = $sectionClass;
            $newSectionDefinition = new Definition($sectionClass, [$sectionClass]);
            $newSectionDefinition->setFactory([$settingsServiceDefinition, 'getSection']);

            $container->setDefinition($settingsId, $newSectionDefinition);
        }

        $settingsMetaServiceDefinition->replaceArgument('$sectionClasses', $sectionClasses);

    }
}