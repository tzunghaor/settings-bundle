<?php


namespace TestApp\UnitSettings;


use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * Single Setting Section
 *
 * My test description.
 * In multiple lines.
 */
class TestBoolSetting
{
    #[Setting(dataType: '', formOptions: ['attr' => 'yay'])]
    public $foo;
}