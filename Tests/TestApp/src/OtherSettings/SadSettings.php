<?php

namespace TestApp\OtherSettings;

use TestApp\Model\FooBar;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Attribute\SettingSection;
use Tzunghaor\SettingsBundle\Form\NullableType;

/**
 * Ignored Section Label
 *
 * Ignored help: label and help in SettingSection takes precedence
 */
#[SettingSection(label: "Sadness", help: "Sadness gives no help", extra: ['foo' => 'bar'])]
class SadSettings extends AbstractBaseSettings
{
    public string $reason = 'nothing';

    /**
     * Foo Bar
     */
    #[Setting(
        dataType: '?' . FooBar::class,
        formOptions: [
            NullableType::OPTION_WRAPPED_OPTIONS => ['row_attr' => ['class' => 'foo-bar']]
        ]
    )]
    public ?FooBar $fooBar = null;
}