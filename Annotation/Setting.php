<?php


namespace Tzunghaor\SettingsBundle\Annotation;

/**
 * @Annotation
 *
 * Annotation to set custom values for a setting
 */
class Setting
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
     * @var string By default symfony/property-info is used to extract the setting's data type, which can determine
     *              * from default value
     *              * from getter methods return type declaration
     *              * from "@var" annotation if phpdocumentor/reflection-docblock is installed
     *             If none of these fits your needs, then you can define the data type here, though this might not be as
     *             smart extractor as property-info.
     */
    public $dataType;

    /**
     * @var string FQCN of a form type (which implements FormTypeInterface) - used in the editor for this setting
     */
    public $formType;

    /**
     * @var string FQCN of a form type (which implements FormTypeInterface)
     *             If the setting is a collection, then this will be used in the editor for each setting entry
     */
    public $formEntryType;

    /**
     * @var array options to be passed to the form element of this setting in the setting editor
     */
    public $formOptions;

    /**
     * @var array Shorthand for formType=ChoiceType::class, formOptions={"choices": {"val1": "val1", ...}}
     *            If the setting data type is array, then "multiple" form option is automatically set
     */
    public $enum;
}