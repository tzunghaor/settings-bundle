<?php

namespace TestApp\Settings\Ui;

use Tzunghaor\SettingsBundle\Attribute\SettingSection;

#[SettingSection('UI Font Settings', extra: ['pos' => 1])]
class FontSettings
{
    public string $weight = 'normal';
}