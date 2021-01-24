<?php


namespace Tzunghaor\SettingsBundle;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tzunghaor\SettingsBundle\DependencyInjection\SettingFinderCompilerPass;

class TzunghaorSettingsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SettingFinderCompilerPass());
    }
}