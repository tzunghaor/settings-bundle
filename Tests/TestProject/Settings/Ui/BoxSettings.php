<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui;

use Tzunghaor\SettingsBundle\Annotation\Setting;

/**
 * UI Box Settings
 */
class BoxSettings
{
    /**
     * @var int
     */
    private $padding;

    /**
     * @var int
     */
    private $margin;

    /**
     * @var string[]
     * @Setting(enum={"bottom", "top", "left", "right"})
     */
    private $borders;

    /**
     * @return int
     */
    public function getPadding(): int
    {
        return $this->padding;
    }

    /**
     * @return int
     */
    public function getMargin(): int
    {
        return $this->margin;
    }

    /**
     * @return string[]
     */
    public function getBorders(): array
    {
        return $this->borders;
    }

    public function __construct($padding = 0, $margin = 0, $borders = [])
    {
        $this->padding = $padding;
        $this->margin = $margin;
        $this->borders = $borders;
    }
}