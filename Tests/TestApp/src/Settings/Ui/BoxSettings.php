<?php

namespace TestApp\Settings\Ui;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Tzunghaor\SettingsBundle\Attribute\Setting;

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
     */
    #[Setting(enum: ["bottom", "top", "left", "right"])]
    private $borders;

    /**
     * @var bool
     */
    #[Setting(formType: CheckboxType::class, formOptions: ["required" => false])]
    private $nightMode;

    public function getPadding(): int
    {
        return $this->padding;
    }

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

    public function getNightMode(): bool
    {
        return $this->nightMode;
    }

    public function __construct($padding = 0, $margin = 0, $borders = ['bottom'], $nightMode = true)
    {
        $this->padding = $padding;
        $this->margin = $margin;
        $this->borders = $borders;
        $this->nightMode = $nightMode;
    }
}