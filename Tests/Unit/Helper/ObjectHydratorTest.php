<?php

namespace Unit\Helper;

use PHPUnit\Framework\TestCase;
use TestApp\UnitSettings\HydratorSetting;
use Tzunghaor\SettingsBundle\Helper\ObjectHydrator;

class ObjectHydratorTest extends TestCase
{
    public function testHydrator(): void
    {
        $values = [
            'privateA' => 'one',
            'privateB' => 'two',
            'privateC' => 'three',
            'protectedA' => 'four',
            'publicA' => 'five',
        ];

        /** @var \TestApp\UnitSettings\HydratorSetting $object */
        $object = ObjectHydrator::hydrate(HydratorSetting::class, $values);

        self::assertInstanceOf(HydratorSetting::class, $object);
        self::assertEquals('one', $object->getPrivateA());
        self::assertEquals('two', $object->getPrivateB());
        self::assertEquals('three', $object->getPrivateC());
        self::assertEquals('four', $object->getProtectedA());
        self::assertEquals('five', $object->publicA);
    }
}