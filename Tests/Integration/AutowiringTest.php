<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\FunSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\Service\TestService;
use Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui\BoxSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\TestKernel;

class AutowiringTest extends KernelTestCase
{
    protected static $class = TestKernel::class;

    public function testAutowiring(): void
    {
        self::bootKernel();

        $testService = self::$container->get(TestService::class);

//        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings());
        self::assertInstanceOf(BoxSettings::class, $testService->getBoxSettings('day'));
        self::assertInstanceOf(FunSettings::class, $testService->getFunSettings());
    }
}