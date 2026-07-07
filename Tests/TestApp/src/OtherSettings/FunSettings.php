<?php

namespace TestApp\OtherSettings;

use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * FunYeah
 *
 * Description
 * in two lines
 */
class FunSettings extends AbstractBaseSettings
{
    public string $foo = 'fff';

    public string $bar = 'bbb';

    #[Setting(label: 'Fun minimum', help: 'Higher than normal minimum')]
    protected int $minimum = 100;

    /**
     * Fun maximum
     *
     * Higher than normal maximum
     */
    protected int $maximum = 200;
}