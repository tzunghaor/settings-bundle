<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings;

/**
 * FunYeah
 *
 * Description
 * in two lines
 */
class FunSettings extends AbstractBaseSettings
{
    /**
     * @var string
     */
    public $foo = 'fff';

    /**
     * @var string
     */
    public $bar = 'bbb';

    protected $minimum = 100;

    /**
     * Fun maximum
     *
     * Higher than normal maximum
     */
    protected $maximum = 200;
}