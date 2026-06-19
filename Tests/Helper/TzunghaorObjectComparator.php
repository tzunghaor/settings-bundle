<?php

namespace Tzunghaor\SettingsBundle\Test\Helper;

use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\ObjectComparator;
use Tzunghaor\SettingsBundle\Model\Type;

class TzunghaorObjectComparator extends ObjectComparator
{
    public function accepts($expected, $actual): bool
    {
        var_dump($expected);
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
    ): void
    {
        // compare only the interesting properties
        if (
            $expected->getTypeIdentifier() !== $actual->getTypeIdentifier() ||
            $expected->getClassName() !== $actual->getClassName() ||
            $expected->isCollection() !== $actual->isCollection() ||
            $expected->isNullable() !== $actual->isNullable()
        ) {
            throw new ComparisonFailure(
                $expected, $actual, '', '', 'Types do not match'
            );
        }
    }

}
