<?php


namespace TestApp\UnitSettings;


use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * Single Setting Section
 *
 * My test description.
 * In multiple lines.
 */
class TestSingleNumberSetting
{
    #[Setting(label: 'cool number', help: 'help for the number', dataType: 'int')]
    public $foo;
}