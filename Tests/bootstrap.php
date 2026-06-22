<?php

use PHPUnitPHAR\SebastianBergmann\Comparator\Factory as PharComparatorFactory;

use Tzunghaor\SettingsBundle\Test\Helper\TzunghaorObjectComparator;

require_once (__DIR__ . '/../vendor/autoload.php');

if (class_exists(PharComparatorFactory::class)) {
    $comparatorFactory = PharComparatorFactory::class;
    // alias PHPUnit classes used in my custom Comparator class
    class_alias(
        PHPUnitPHAR\SebastianBergmann\Comparator\Comparator::class,
        'SebastianBergmann\Comparator\Comparator'
    );
    class_alias(
        PHPUnitPHAR\SebastianBergmann\Comparator\ComparisonFailure::class,
        'SebastianBergmann\Comparator\ComparisonFailure'
    );
} else {
    throw new RuntimeException('SebastianBergmann\Comparator\Factory not found');
}

$comparatorFactory::getInstance()->register(new TzunghaorObjectComparator());

// clear test project's cache to force a container rebuild
$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/minimal');
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/test');
