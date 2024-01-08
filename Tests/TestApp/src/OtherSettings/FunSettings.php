<?php

namespace TestApp\OtherSettings;

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

    protected int $minimum = 100;

    /**
     * Fun maximum
     *
     * Higher than normal maximum
     */
    protected int $maximum = 200;
}