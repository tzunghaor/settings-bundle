<?php


namespace Unit\Service;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use TestApp\UnitSettings\TestBoolSetting;
use TestApp\UnitSettings\TestDateTimeSetting;
use TestApp\UnitSettings\TestMultiEnumSetting;
use TestApp\UnitSettings\TestSimpleSetting;
use TestApp\UnitSettings\TestSingleEnumSetting;
use TestApp\UnitSettings\TestSingleNumberSetting;
use TestApp\UnitSettings\TestUnknowTypeSetting;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\BoolType;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Service\MetaDataExtractor;

class MetaDataExtractorTest extends TestCase
{
    public function createSectionMetaDataProvider()
    {
        return [
            'default' => [
                TestSimpleSetting::class,
                true,
                null,
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('string'),
                        TextType::class,
                        [],
                        'foo',
                        ''
                    ),
                ],
            ],
            'number' =>  [
                TestSingleNumberSetting::class,
                false,
                null,
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('int'),
                        IntegerType::class,
                        [],
                        'cool number',
                        'help for the number'
                    ),
                ],
            ],
            'singleEnum' => [
                TestSingleEnumSetting::class,
                false,
                null,
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('string'),
                        ChoiceType::class,
                        ['choices' => ['one' => 'one', 'two' => 'two']],
                        'simple choice',
                        ''
                    ),
                ],
            ],
            'multiEnum' => [
                TestMultiEnumSetting::class,
                false,
                null,
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('string', false, null, true),
                        ChoiceType::class,
                        ['choices' => ['yay' => 'yay', 'nay' => 'nay'], 'multiple' => true],
                        'foo',
                        ''
                    ),
                ],
            ],
            'unknown type' => [
                TestUnknowTypeSetting::class,
                false,
                null,
                true,
                null,
            ],
            'too many type' => [
                TestSimpleSetting::class,
                true,
                [new Type('int'), new Type('string')],
                true,
                null,
            ],
            'datetime type' => [
                TestDateTimeSetting::class,
                false,
                null,
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('object', false, \DateTime::class),
                        DateTimeType::class,
                        [],
                        'foo',
                        ''
                    ),
                ],
            ],
            'bool' => [
                TestBoolSetting::class,
                true,
                [new Type('bool')],
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('bool'),
                        BoolType::class,
                        ['attr' => 'yay'],
                        'foo',
                        ''
                    ),
                ],
            ],
            'float' => [
                TestSimpleSetting::class,
                true,
                [new Type('float')],
                false,
                [
                    'foo' => new SettingMetaData(
                        'foo',
                        new Type('float'),
                        NumberType::class,
                        [],
                        'foo',
                        ''
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider createSectionMetaDataProvider
     *
     * @throws SettingsException
     * @throws \ReflectionException
     */
    public function testCreateSectionMetaData(
        string $settingClassName,
        bool   $expectGetTypes,
        ?array $types,
        bool   $expectException,
        ?array $expectedSettingMetaDataArray
    ) {
        $propertyInfoMock = $this->createMock(PropertyInfoExtractorInterface::class);
        if ($expectGetTypes) {
            $propertyInfoMock
                ->expects($this->once())
                ->method('getTypes')
                ->willReturn($types)
            ;
        } else {
            $propertyInfoMock->expects($this->never())->method('getTypes');
        }

        if ($expectException) {
            self::expectException(SettingsException::class);
        }

        $extractor = new MetaDataExtractor($propertyInfoMock);

        // tested method
        $metaData = $extractor->createSectionMetaData('testSection', $settingClassName);

        self::assertInstanceOf(SectionMetaData::class, $metaData);
        self::assertEquals('testSection', $metaData->getName());
        self::assertEquals($settingClassName, $metaData->getDataClass());
        self::assertEquals('Single Setting Section', $metaData->getTitle());
        self::assertEquals("My test description.\nIn multiple lines.", $metaData->getDescription());
        self::assertEquals($expectedSettingMetaDataArray, $metaData->getSettingMetaDataArray());
    }
}