<?php

namespace TestApp\OtherSettings;

use Tzunghaor\SettingsBundle\Attribute\SettingSection;

/**
 * Ignored Section Label
 *
 * Ignored help: label and help in SettingSection takes precedence
 */
#[SettingSection(label: "Sadness", help: "Sadness gives no help", extra: ['foo' => 'bar'])]
class SadSettings extends AbstractBaseSettings
{
    public string $reason = 'nothing';
}