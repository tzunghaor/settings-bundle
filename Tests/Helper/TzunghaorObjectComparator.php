<?php

namespace Tzunghaor\SettingsBundle\Test\Helper;

use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use Tzunghaor\SettingsBundle\Model\Type;

class TzunghaorObjectComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        return $expected instanceof Type && $actual instanceof Type;
    }

    /**
     * @param Type $expected
     * @param Type $actual
     */
    public function assertEquals(
        $expected,
        $actual,
        $delta = 0.0,
        $canonicalize = false,
        $ignoreCase = false,
        array &$processed = []
    ): void {

        // compare only the interesting properties
        $expectedArray = [
            'typeIdentifier' => $expected->getTypeIdentifier(),
            'className' => $expected->getClassName(),
            'collection' => $expected->isCollection(),
            'nullable' => $expected->isNullable(),
        ];
        $actualArray = [
            'typeIdentifier' => $actual->getTypeIdentifier(),
            'className' => $actual->getClassName(),
            'collection' => $actual->isCollection(),
            'nullable' => $actual->isNullable(),
        ];

        if (
            $expected->getTypeIdentifier() !== $actual->getTypeIdentifier() ||
            $expected->getClassName() !== $actual->getClassName() ||
            $expected->isCollection() !== $actual->isCollection() ||
            $expected->isNullable() !== $actual->isNullable()
        ) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                json_encode($expectedArray, JSON_PRETTY_PRINT),
                json_encode($actualArray, JSON_PRETTY_PRINT),
                'Type objects do not match',
            );
        }
    }

}
