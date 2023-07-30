<?php


namespace TestApp\UnitSettings;


use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * Single Setting Section
 *
 * My test description.
 * In multiple lines.
 */
class TestDateTimeSetting
{
    #[Setting(dataType: 'DateTime')]
    public $foo;
}