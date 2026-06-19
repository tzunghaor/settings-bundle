<?php

use SebastianBergmann\Comparator\Factory;
use Tzunghaor\SettingsBundle\Test\Helper\TzunghaorObjectComparator;

require_once (__DIR__ . '/../vendor/autoload.php');

Factory::getInstance()->register(new TzunghaorObjectComparator());

// clear test project's cache to force a container rebuild
$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/minimal');
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/test');
