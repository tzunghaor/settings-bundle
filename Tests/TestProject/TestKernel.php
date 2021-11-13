<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function getProjectDir()
    {
        return __DIR__;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__ . '/config/routes.yaml');
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $environment = $this->getEnvironment();
        // the very useful test container used in KernelTestCase is defined here
        $c->import(__DIR__.'/../../vendor/symfony/framework-bundle/Resources/config/test.php');

        // test app config
        $c->import(__DIR__ . '/config/services_' . $environment . '.yaml');
        $c->import(__DIR__.'/config/packages/*.yaml');
        $c->import(__DIR__.'/config/packages/' . $environment . '/*.yaml');
    }
}