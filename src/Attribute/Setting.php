<?php


namespace Tzunghaor\SettingsBundle\Attribute;

/***
 * Attribute to set custom values for a setting (a property in a setting section class)
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Setting
{
    /**
     * Label in editor form
     * If phpdocumentor/reflection-docblock is installed, then the first line of the docblock can be used
     * instead.
     */
    public ?string $label = null;

    /**
     * Help text in editor form
     * If phpdocumentor/reflection-docblock is installed, then the not-first line of the docblock can be
     * used instead.
     */
    public ?string $help = null;

    /**
     * By default symfony/property-info is used to extract the setting's data type, which can determine
     * * from default value
     * * from getter methods return type declaration
     * * from "@var" annotation if phpdocumentor/reflection-docblock is installed
     * If none of these fits your needs, then you can define the data type here, though this might not be as
     * smart extractor as property-info.
     */
    public ?string $dataType = null;

    /**
     * FQCN of a form type (which implements FormTypeInterface) - used in the editor for this setting
     */
    public ?string $formType = null;

    /**
     * FQCN of a form type (which implements FormTypeInterface)
     * If the setting is a collection, then this will be used in the editor for each setting entry
     */
    public ?string $formEntryType = null;

    /**
     * Options to be passed to the form element of this setting in the setting editor
     */
    public ?array $formOptions = null;

    /**
     * Shorthand for formType=ChoiceType::class, formOptions={"choices": {"val1": "val1", ...}}
     * If the setting data type is array, then "multiple" form option is automatically set
     */
    public ?array $enum = null;

    public function __construct(
        ?string $label = null,
        ?array  $enum = null,
        ?string $dataType = null,
        ?string $help = null,
        ?string $formType = null,
        ?string $formEntryType = null,
        array   $formOptions = []
    ) {
        $this->enum = $enum;
        $this->dataType = $dataType;
        $this->label = $label;
        $this->help = $help;
        $this->formOptions = $formOptions;
        if ($formType) {
            assert(class_exists($formType), "Class $formType does not exist.");
            $this->formType = $formType;
        }
        if ($formEntryType) {
            assert(class_exists($formEntryType), "Class $formEntryType does not exist.");
            $this->formEntryType = $formEntryType;
        }
    }
}
