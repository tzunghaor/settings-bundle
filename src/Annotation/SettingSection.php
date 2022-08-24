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
     * @var string Label in editor form
     *             If phpdocumentor/reflection-docblock is installed, then the first line of the docblock can be used
     *             instead.
     */
    public $label;

    /**
     * @var string Help text in editor form
     *             If phpdocumentor/reflection-docblock is installed, then the not-first line of the docblock can be
     *             used instead.
     */
    public $help;

    /**
     * @var array Extra data that you can use in your templates / extensions.
     */
    public $extra;
}