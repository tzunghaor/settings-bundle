<?php


namespace Tzunghaor\SettingsBundle\Tests\Service;


use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Tzunghaor\SettingsBundle\Annotation\Setting;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\BoolType;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Service\MetaDataExtractor;
use Tzunghaor\SettingsBundle\Tests\Setting\TestSingleSetting;

class MetaDataExtractorTest extends TestCase
{
    public function createSectionMetaDataProvider()
    {
        return [
            'default' => [
                [],
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
                [$this->createAnnotationSetting('cool number', 'help for the number', 'int')],
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
                [$this->createAnnotationSetting('simple choice', null, 'string', null, null, ['one', 'two'])],
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
                [$this->createAnnotationSetting(null, null, 'string[]', null, null, ['yay', 'nay'])],
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
                [$this->createAnnotationSetting(null, null, 'nosuchtype')],
                false,
                null,
                true,
                null,
            ],
            'too many type' => [
                [],
                true,
                [new Type('int'), new Type('string')],
                true,
                null,
            ],
            'datetime type' => [
                [$this->createAnnotationSetting(null, null, 'DateTime')],
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
                [$this->createAnnotationSetting(null, null, '', null, ['attr' => 'yay'])],
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
                [],
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
     * @param array $annotations
     * @param bool $expectGetTypes
     * @param array|null $types
     * @param bool $expectException
     * @param array|null $expectedSettingMetaDataArray
     *
     * @throws SettingsException
     * @throws \ReflectionException
     */
    public function testCreateSectionMetaData(
        array $annotations,
        bool $expectGetTypes,
        ?array $types,
        bool $expectException,
        ?array $expectedSettingMetaDataArray
    ) {
        $readerMock = $this->createMock(Reader::class);
        $readerMock
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn($annotations)
        ;

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

        $extractor = new MetaDataExtractor($readerMock, $propertyInfoMock);

        // tested method
        $metaData = $extractor->createSectionMetaData('testSection', TestSingleSetting::class);

        self::assertInstanceOf(SectionMetaData::class, $metaData);
        self::assertEquals('testSection', $metaData->getName());
        self::assertEquals(TestSingleSetting::class, $metaData->getDataClass());
        self::assertEquals('Single Setting Section', $metaData->getTitle());
        self::assertEquals("My test description.\nIn multiple lines.", $metaData->getDescription());
        self::assertEquals($expectedSettingMetaDataArray, $metaData->getSettingMetaDataArray());
    }

    private function createAnnotationSetting($label, $help, $dataType, $formType = null, $formOptions = null, $enum = null)
    {
        $setting = new Setting();
        $setting->label = $label;
        $setting->help = $help;
        $setting->dataType = $dataType;
        $setting->formType = $formType;
        $setting->formOptions = $formOptions;
        $setting->enum = $enum;

        return $setting;
    }
}