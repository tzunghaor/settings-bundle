<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings;

use Tzunghaor\SettingsBundle\Annotation\Setting;

abstract class AbstractBaseSettings
{
    /**
     * @Setting(label="public name label")
     */
    public $name = 'baba';

    /**
     * @Setting(label="private address label")
     */
    private $address = 'yaga';

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address): void
    {
        $this->address = $address;
    }
}