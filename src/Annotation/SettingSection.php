<?php

namespace Tzunghaor\SettingsBundle\Annotation;

/**
 * @Annotation
 *
 * Annotation to set custom values for a setting section class
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
}