<?php


namespace Tzunghaor\SettingsBundle\Tests\Setting;


/**
 * My test title
 *
 * My test description.
 * In multiple lines.
 */
class TestSetting
{
    public $default = 'default';

    public $number = 42;

    private $singleEnum = '';

    private $multiEnum = [];

    public function getSingleEnum(): string
    {
        return $this->singleEnum;
    }

    public function setSingleEnum(string $singleEnum): void
    {
        $this->singleEnum = $singleEnum;
    }

    public function getMultiEnum(): array
    {
        return $this->multiEnum;
    }

    public function setMultiEnum(array $multiEnum): void
    {
        $this->multiEnum = $multiEnum;
    }
}