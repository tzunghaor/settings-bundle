<?php


namespace TestApp\UnitSettings;


use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * Single Setting Section
 *
 * My test description.
 * In multiple lines.
 */
class TestSingleEnumSetting
{
    #[Setting(label: 'simple choice', dataType: 'string', enum: ['one', 'two'])]
    public $foo;
}