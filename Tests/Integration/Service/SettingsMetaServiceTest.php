<?php

namespace Tzunghaor\SettingsBundle\Tests\Integration\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
    public function testInheritance(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        /** @var TestService $testService */
        $testService = self::getContainer()->get(TestService::class);
        $metaService = $testService->getSettingsMetaService('other');

        $metaData = $metaService->getSectionMetaData(FunSettings::class);

        $stringType = new Type('string');
        $intType = new Type('int');

        $expectedMetaData = new SectionMetaData(
            'FunSettings',
            'FunYeah',
            FunSettings::class,
            "Description\nin two lines",
            [
                // following two properties are defined in abstract parent class
                'name' => new SettingMetaData(
                    'name', $stringType, TextType::class,
                    [], 'public name label', ''
                ),
                'address' => new SettingMetaData(
                    'address', $stringType, TextType::class,
                    [], 'private address label', ''
                ),
                'minimum' => new SettingMetaData(
                    'minimum', $intType, IntegerType::class,
                    [], 'The minimum', 'This is the minimum description'
                ),
                'maximum' => new SettingMetaData(
                    'maximum', $intType, IntegerType::class,
                    ['attr' => ['class' => 'max']], 'Fun maximum', 'Higher than normal maximum'
                ),

                'foo' => new SettingMetaData(
                    'foo', $stringType, TextType::class,
                    [], 'foo', ''
                ),
                'bar' => new SettingMetaData(
                    'bar', $stringType, TextType::class,
                    [], 'bar', ''
                ),
            ]
        );
        self::assertEquals($expectedMetaData, $metaData);
        // test internal order of metadata array, which will be the display order in form
        self::assertEquals(
            array_keys($expectedMetaData->getSettingMetaDataArray()),
            array_keys($metaData->getSettingMetaDataArray())
        );
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

        $testService = self::getContainer()->get(TestService::class);
        $metaService = $testService->getSettingsMetaService($collection);

        $this->expectException(SettingsException::class);
        if ($which === 'name') {
            $metaService->getSectionMetaDataByName($subject);
        } elseif ($which === 'class') {
            $metaService->getSectionMetaData($subject);
        }
    }

}