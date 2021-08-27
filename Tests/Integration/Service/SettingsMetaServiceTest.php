<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyInfo\Type;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\AbstractBaseSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\FunSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\Service\TestService;
use Tzunghaor\SettingsBundle\Tests\TestProject\TestKernel;

class SettingsMetaServiceTest extends KernelTestCase
{
    protected static $class = TestKernel::class;

    public function testInheritance(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        $testService = self::$container->get(TestService::class);
        $metaService = $testService->getSettingsMetaService('other');

        $metaData = $metaService->getSectionMetaData(FunSettings::class);

        $stringType = new Type('string');

        $expectedMetaData = new SectionMetaData(
            'FunSettings',
            'FunYeah',
            FunSettings::class,
            "Description\nin two lines",
            [
                'foo' => new SettingMetaData(
                    'foo', $stringType, TextType::class,
                    [], 'foo', ''
                ),
                'bar' => new SettingMetaData(
                    'bar', $stringType, TextType::class,
                    [], 'bar', ''
                ),
                // following two properties are defined in abstract parent class
                'name' => new SettingMetaData(
                    'name', $stringType, TextType::class,
                    [], 'public name label', ''
                ),
                'address' => new SettingMetaData(
                    'address', $stringType, TextType::class,
                    [], 'private address label', ''
                ),
            ]
        );
        self::assertEquals($expectedMetaData, $metaData);
    }

    public function unknownSectionProvider(): array
    {
        return [
            ['default', 'class', FunSettings::class],
            ['other', 'class', AbstractBaseSettings::class],
            ['other', 'name', 'AbstractBaseSettings'],
            ['other', 'name', ''],
        ];
    }

    /**
     * @dataProvider unknownSectionProvider
     */
    public function testUnknownSection(string $collection, string $which, string $subject): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        $testService = self::$container->get(TestService::class);
        $metaService = $testService->getSettingsMetaService($collection);

        $this->expectException(SettingsException::class);
        if ($which === 'name') {
            $metaService->getSectionMetaDataByName($subject);
        } elseif ($which === 'class') {
            $metaService->getSectionMetaData($subject);
        }
    }

}