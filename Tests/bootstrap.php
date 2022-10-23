<?php
require_once (__DIR__ . '/../vendor/autoload.php');

// clear test project's cache to force a container rebuild
$fileSystem = new \Symfony\Component\Filesystem\Filesystem();
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/minimal');
$fileSystem->remove(__DIR__ . '/TestApp/var/cache/test');
