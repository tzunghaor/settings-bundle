<?php

namespace Tzunghaor\SettingsBundle\Test\Integration\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use TestApp\OtherSettings\SadSettings;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\Type;
use TestApp\OtherSettings\AbstractBaseSettings;
use TestApp\OtherSettings\FunSettings;
use TestApp\Service\TestService;

class SettingsMetaServiceTest extends KernelTestCase
{
    public function testInheritance(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => false]);

        /** @var TestService $testService */
        $testService = self::getContainer()->get(TestService::class);
        $metaService = $testService->getSettingsMetaService('other');

        $funMetaData = $metaService->getSectionMetaData(FunSettings::class);
        $sadMetaData = $metaService->getSectionMetaData(SadSettings::class);

        $stringType = new Type('string');
        $intType = new Type('int');

        $baseMetaDataArray = [
            'name' => new SettingMetaData(
                'name', $stringType, TextType::class,
                [], 'assets name label', ''
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
                ['attr' => ['class' => 'max']], 'The maximum', 'This is the maximum description',
            ),
        ];

        $funMetaDataArray = array_merge($baseMetaDataArray, [
            'minimum' => new SettingMetaData(
                'minimum', $intType, IntegerType::class,
                [], 'Fun minimum', 'Higher than normal minimum'
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
        ]);


        $expectedFunMetaData = new SectionMetaData(
            'FunSettings',
            'FunYeah',
            FunSettings::class,
            "Description\nin two lines",
            $funMetaDataArray,
        );

        $expectedSadMetaData = new SectionMetaData(
        'SadSettings',
            'Sadness',
            SadSettings::class,
            'Sadness gives no help',
            array_merge($baseMetaDataArray, [
                'reason' => new SettingMetaData(
                    'reason', $stringType, TextType::class,
                    [], 'reason', ''
                ),
            ]),
            ['foo' => 'bar']
        );

        self::assertEquals($expectedFunMetaData, $funMetaData);
        self::assertEquals($expectedSadMetaData, $sadMetaData);

        // test internal order of metadata array, which will be the display order in form
        self::assertEquals(
            array_keys($expectedFunMetaData->getSettingMetaDataArray()),
            array_keys($funMetaData->getSettingMetaDataArray())
        );
        self::assertEquals(
            array_keys($expectedSadMetaData->getSettingMetaDataArray()),
            array_keys($sadMetaData->getSettingMetaDataArray())
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
