<?php
namespace Tzunghaor\SettingsBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Tzunghaor\SettingsBundle\Model\Type;
use Tzunghaor\SettingsBundle\Service\BuiltinSettingConverter;

class BuiltinSettingConverterTest extends TestCase
{
    public function dataProvider()
    {
        return [
            'string 1' => [new Type('string'), 'foo', 'foo'],
            'string 2' => [new Type('string'), '', ''],
            'nullable string 1' => [new Type('string', true), 'foo', '"foo"'],
            'nullable string 2' => [new Type('string', true), '', '""'],
            'nullable string 3' => [new Type('string', true), null, 'null'],
            'float' => [new Type('float'), 1.34, '1.34'],
            'nullable float 1' => [new Type('float', true), 1.34, '1.34'],
            'nullable float 2' => [new Type('float', true), null, ''],
            'int' => [new Type('int'), 123, '123'],
            'nullable int 1' => [new Type('int', true), 123, '123'],
            'nullable int 2' => [new Type('int', true), null, ''],
            'bool true' => [new Type('bool'), true, '1'],
            'bool false' => [new Type('bool'), false, '0'],
            'nullable bool true' => [new Type('bool', true), true, '1'],
            'nullable bool null' => [new Type('bool', true), null, ''],
            'datetime' => [
                new Type('object', false, \DateTime::class),
                new \DateTime('2020-12-15T08:31:12+00:00'),
                '2020-12-15T08:31:12+00:00',
            ],
            'nullable datetime' => [
                new Type('object', true, \DateTime::class),
                new \DateTime('2020-12-15T08:31:12+00:00'),
                '2020-12-15T08:31:12+00:00',
            ],
            'nullable datetime null' => [
                new Type('object', true, \DateTime::class),
                null,
                '',
            ],
            'int array' => [
                new Type('int', false, null, true),
                [12, 13],
                '[12,13]',
            ],
            'bool array' => [
                new Type('bool', false, null, true),
                [true, false, false],
                '[true,false,false]',
            ],
            'string array' => [
                new Type('string', false, null, true),
                ['foo', 'bar'],
                '["foo","bar"]',
            ],
            'datetime array' => [
                new Type('object', false, \DateTime::class, true),
                [new \DateTime('2020-12-15T08:31:12+00:00'), new \DateTime('2021-12-15T08:31:12+00:00')],
                '["2020-12-15T08:31:12+00:00","2021-12-15T08:31:12+00:00"]',
            ],
        ];
    }


    /**
     * @dataProvider dataProvider
     *
     * @param Type $type
     * @param $dataValue
     * @param string $storedValue
     *
     * @throws \Exception
     */
    public function testConvertFromString(Type $type, $dataValue, string $storedValue)
    {
        $converter = new BuiltinSettingConverter();
        if (class_exists(\Symfony\Component\TypeInfo\Type::class)) {
            self::assertTrue($converter->supports($type->getTypeInfoType()));
            self::assertEquals($dataValue, $converter->convertFromString($type->getTypeInfoType(), $storedValue));
        }

        if (class_exists(\Symfony\Component\PropertyInfo\Type::class)) {
            self::assertTrue($converter->supports($type->getPropertyInfoType()));
            self::assertEquals($dataValue, $converter->convertFromString($type->getPropertyInfoType(), $storedValue));
        }
    }


    /**
     * @dataProvider dataProvider
     *
     * @param Type $type
     * @param $dataValue
     * @param string $storedValue
     *
     * @throws \Exception
     */
    public function testConvertToString(Type $type, $dataValue, string $storedValue)
    {
        $converter = new BuiltinSettingConverter();

        if (class_exists(\Symfony\Component\TypeInfo\Type::class)) {
            self::assertTrue($converter->supports($type->getTypeInfoType()));
            self::assertEquals($storedValue, $converter->convertToString($type->getTypeInfoType(), $dataValue));
        }

        if (class_exists(\Symfony\Component\PropertyInfo\Type::class)) {
            self::assertTrue($converter->supports($type->getPropertyInfoType()));
            self::assertEquals($storedValue, $converter->convertToString($type->getPropertyInfoType(), $dataValue));
        }
    }

    public function notSupportsProvider(): array
    {
        return [
            'object' => [new Type('object', false, BuiltinSettingConverter::class)],
            'nullable collection' => [new Type('string', true, null, true)],
            'object collection' => [new Type('object', false, BuiltinSettingConverter::class, true)],
        ];
    }

    /**
     * @dataProvider notSupportsProvider
     */
    public function testNotSupports($type)
    {
        $converter = new BuiltinSettingConverter();

        if (class_exists(\Symfony\Component\TypeInfo\Type::class)) {
            self::assertFalse($converter->supports($type->getTypeInfoType()));
        }

        if (class_exists(\Symfony\Component\PropertyInfo\Type::class)) {
            self::assertFalse($converter->supports($type->getPropertyInfoType()));
        }
    }
}
