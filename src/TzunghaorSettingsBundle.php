<?php


namespace Tzunghaor\SettingsBundle;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tzunghaor\SettingsBundle\DependencyInjection\TzunghaorSettingsCompilerPass;

class TzunghaorSettingsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        if (!isset($this->path)) {
            $reflected = new \ReflectionObject($this);
            // use the modern directory structure
            $this->path = \dirname($reflected->getFileName(), 2);
        }

        return $this->path;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TzunghaorSettingsCompilerPass());
    }
}
