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
            'string' => [new Type('string'), 'foo', 'foo'],
            'float' => [new Type('float'), 1.34, '1.34'],
            'int' => [new Type('int'), 123, '123'],
            'bool true' => [new Type('bool'), true, '1'],
            'bool false' => [new Type('bool'), false, '0'],
            'datetime' => [
                new Type('object', false, \DateTime::class),
                new \DateTime('2020-12-15T08:31:12+00:00'),
                '2020-12-15T08:31:12+00:00'
            ],
            'int array' => [
                new Type('int', false, null, true),
                [12, 13],
                '[12,13]'
            ],
            'string array' => [
                new Type('string', false, null, true),
                ['foo', 'bar'],
                '["foo","bar"]'
            ]
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

        if (class_exists(Symfony\Component\PropertyInfo\Type::class)) {
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

        if (class_exists(Symfony\Component\PropertyInfo\Type::class)) {
            self::assertTrue($converter->supports($type->getTypeInfoType()));
            self::assertEquals($storedValue, $converter->convertToString($type->getPropertyInfoType(), $dataValue));
        }
    }


    public function testNotSupports()
    {
        $converter = new BuiltinSettingConverter();
        $type = new Type('object', false, BuiltinSettingConverter::class);

        if (class_exists(\Symfony\Component\TypeInfo\Type::class)) {
            self::assertFalse($converter->supports($type->getTypeInfoType()));
        }

        if (class_exists(Symfony\Component\PropertyInfo\Type::class)) {
            self::assertFalse($converter->supports($type->getPropertyInfoType()));
        }
    }
}
