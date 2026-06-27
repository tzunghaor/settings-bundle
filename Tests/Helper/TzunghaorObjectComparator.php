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
        if (!$expected->equals($actual)) {
            throw new ComparisonFailure(
                $expected, $actual, (string) $expected, (string) $actual,
                'Type objects do not match',
            );
        }
    }

}
