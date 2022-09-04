<?php

namespace Tzunghaor\SettingsBundle\Attribute;

use \Attribute;
#[Attribute(Attribute::TARGET_CLASS)]
/**
 * Attribute to set custom values for a setting section class
 */
class SettingSection
{
    /**
     * Label in editor form
     * If phpdocumentor/reflection-docblock is installed, then the first line of the docblock can be used instead.
     */
    public ?string $label = null;

    /**
     * Help text in editor form
     * If phpdocumentor/reflection-docblock is installed, then the not-first line of the docblock can be used instead.
     */
    public ?string $help = null;

    /**
     * Extra data that you can use in your templates / extensions.
     */
    public ?array $extra = null;

    public function __construct(?string $label = null, ?string $help = null, array $extra = []) {
        $this->label = $label;
        $this->help = $help;
        $this->extra = $extra;
    }
}
