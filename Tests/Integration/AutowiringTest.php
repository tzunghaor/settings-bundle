<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\FunSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\Service\MinimalTestService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Service\TestService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui\BoxSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\TestKernel;

class AutowiringTest extends KernelTestCase
{
    protected static $class = TestKernel::class;

    /**
     * test environment="test" which has complex config
     */
    public function testAutowiring(): void
    {
        self::bootKernel();

        $testService = self::$container->get(TestService::class);

        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings('day'));
        self::assertInstanceOf(FunSettings::class, $testService->getFunSettings());
    }

    /**
     * test environment="minimal" which has as little config as possible
     */
    public function testMinimalAutowiring(): void
    {
        self::bootKernel(['environment' => 'minimal']);

        $testService = self::$container->get(MinimalTestService::class);

        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings());
        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings('default'));
    }

}