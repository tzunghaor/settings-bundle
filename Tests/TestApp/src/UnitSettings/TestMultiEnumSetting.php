<?php


namespace TestApp\UnitSettings;


use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * Single Setting Section
 *
 * My test description.
 * In multiple lines.
 */
class TestMultiEnumSetting
{
    #[Setting(dataType: 'string[]', enum: ['yay', 'nay'])]
    public $foo;
}