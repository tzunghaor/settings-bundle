<?php

namespace TestApp\Settings\Ui;

use Tzunghaor\SettingsBundle\Attribute\SettingSection;

#[SettingSection(extra: ['pos' => 10])]
class FooSettings
{
    public int $bar = 0;
}