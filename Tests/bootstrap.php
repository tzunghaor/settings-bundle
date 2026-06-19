<?php

use SebastianBergmann\Comparator\Factory;
use Tzunghaor\SettingsBundle\Test\Helper\TzunghaorObjectComparator;

require_once (__DIR__ . '/../vendor/autoload.php');

use PHPUnit\Runner\Version;

die('PHPUnit version: ' . Version::id() . "\n"); // e.g. "10.5.20"

Factory::getInstance()->register(new TzunghaorObjectComparator());

// clear test project's cache to force a container rebuild
$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/minimal');
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/test');
