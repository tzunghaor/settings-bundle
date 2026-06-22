<?php

namespace Tzunghaor\SettingsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tzunghaor\SettingsBundle\Service\SerializerSettingConverter;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class TzunghaorSettingsCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        // setup serializer converter if serializer service exists
        if ($container->hasDefinition('serializer')) {
            $definition = new Definition(SerializerSettingConverter::class);
            $definition->setArguments([
                '$serializer' => new Reference('serializer'),
            ]);
            $definition->setPublic(false);
            // put it behind the builtin converter
            $definition->addTag('tzunghaor_settings.setting_converter', ['priority' => -1100]);

            $container->setDefinition('tzunghaor_settings.serializer_setting_converter', $definition);

            $settingsService = $container->getDefinition('tzunghaor_settings.settings_service');
            $settingsService->setArgument('$dataConverters', tagged_iterator('tzunghaor_settings.setting_converter'));
        }
    }
}