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
     * The minimum
     *
     * This is the minimum description
     */
    protected $minimum = 10;

    /**
     * The maximum
     *
     * This is the maximum description
     * @Setting(formOptions={"attr": {"class": "max"}})
     */
    protected $maximum = 20;

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

    /**
     * @return int
     */
    public function getMinimum(): int
    {
        return $this->minimum;
    }

    /**
     * @param int $minimum
     */
    public function setMinimum(int $minimum): void
    {
        $this->minimum = $minimum;
    }

    /**
     * @return int
     */
    public function getMaximum(): int
    {
        return $this->maximum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum(int $maximum): void
    {
        $this->maximum = $maximum;
    }
}