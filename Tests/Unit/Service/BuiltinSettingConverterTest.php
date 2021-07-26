<?php
namespace Tzunghaor\SettingsBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
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
            'strin array' => [
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

        self::assertTrue($converter->supports($type));
        self::assertEquals($dataValue, $converter->convertFromString($type, $storedValue));
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

        self::assertTrue($converter->supports($type));
        self::assertEquals($storedValue, $converter->convertToString($type, $dataValue));
    }


    public function testNotSupports()
    {
        $converter = new BuiltinSettingConverter();

        self::assertFalse($converter->supports(new Type('object', false, BuiltinSettingConverter::class)));
    }
}