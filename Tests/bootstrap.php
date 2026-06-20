<?php

use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use PHPUnitPHAR\SebastianBergmann\Comparator\Factory as PharComparatorFactory;

use Tzunghaor\SettingsBundle\Test\Helper\TzunghaorObjectComparator;

require_once (__DIR__ . '/../vendor/autoload.php');

// @todo: generate TestApp autoload for tests even if composer is run with --no-dev or use phpunit PHAR only
if (class_exists(PharComparatorFactory::class)) {
    $comparatorFactory = PharComparatorFactory::class;
    // alias PHPUnit classes used in my custom Comparator class
    class_alias(PHPUnitPHAR\SebastianBergmann\Comparator\Comparator::class, 'SebastianBergmann\Comparator\Comparator');
    class_alias(PHPUnitPhar\SebastianBergmann\Comparator\ComparisonFailure::class, 'SebastianBergmann\Comparator\ComparisonFailure');
} elseif (class_exists(ComparatorFactory::class)) {
    $comparatorFactory = ComparatorFactory::class;
} else {
    throw new RuntimeException('SebastianBergmann\Comparator\Factory not found');
}

$comparatorFactory::getInstance()->register(new TzunghaorObjectComparator());

// clear test project's cache to force a container rebuild
$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/minimal');
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/test');
