<?php

namespace TestApp\OtherSettings;

use Tzunghaor\SettingsBundle\Attribute\Setting;

abstract class AbstractBaseSettings
{
    #[Setting(label: "assets name label")]
    public string $name = 'baba';

    #[Setting(label: "private address label")]
    private string $address = 'yaga';

    /**
     * The minimum
     *
     * This is the minimum description
     */
    protected int $minimum = 10;

    /**
     * The maximum
     *
     * This is the maximum description
     */
    #[Setting(formOptions: ["attr" => ["class" => "max"]])]
    protected int $maximum = 20;


    public function getName(): string
    {
        return $this->name;
    }


    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function getAddress(): string
    {
        return $this->address;
    }


    public function setAddress(string $address): void
    {
        $this->address = $address;
    }


    public function getMinimum(): int
    {
        return $this->minimum;
    }


    public function setMinimum(int $minimum): void
    {
        $this->minimum = $minimum;
    }


    public function getMaximum(): int
    {
        return $this->maximum;
    }


    public function setMaximum(int $maximum): void
    {
        $this->maximum = $maximum;
    }
}
