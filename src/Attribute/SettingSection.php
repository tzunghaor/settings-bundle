<?php

namespace Tzunghaor\SettingsBundle\Attribute;


/**
 * Attribute to set custom values for a setting section class
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SettingSection
{
    /**
     * @param string|null $label Label in editor form
     *                           If phpdocumentor/reflection-docblock is installed, then the first line of the docblock
     *                           can be used instead.
     * @param string|null $help  Help text in editor form
     *                           If phpdocumentor/reflection-docblock is installed, then the not-first line of the
     *                           docblock can be used instead.
     * @param mixed[] $extra       Extra data that you can use in your templates / extensions.
     */
    public function __construct(
        public ?string $label = null,
        public ?string $help = null,
        public array $extra = [],
    ) {

    }
}
