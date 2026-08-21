<?php


namespace Tzunghaor\SettingsBundle\Attribute;

use Symfony\Component\Form\FormTypeInterface;

/***
 * Attribute to set custom values for a setting (a property in a setting section class).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Setting
{
    /**
     * Label in editor form
     * If phpdocumentor/reflection-docblock is installed, then the first line of the docblock can be used
     * instead. Otherwise, the property name is used by default.
     */
    public ?string $label = null;

    /**
     * Help text in editor form
     * If phpdocumentor/reflection-docblock is installed, then the non-first line of the docblock can be
     * used instead.
     */
    public ?string $help = null;

    /**
     * By default, symfony/property-info is used to extract the setting's data type, which can determine
     * * from default value
     * * from getter methods return type declaration
     * * from "@var" annotation if phpdocumentor/reflection-docblock is installed
     * If none of these fits your needs, then you can define the data type here.
     *
     * @var class-string|null
     */
    public ?string $dataType = null;

    /**
     * FQCN of a form type (which implements FormTypeInterface) - used in the editor for this setting.
     * By default, the bundle tries to determine it based on the data type.
     *
     * @var class-string<FormTypeInterface>|null
     */
    public ?string $formType = null;

    /**
     * FQCN of a form type (which implements FormTypeInterface)
     * If the setting is a collection, then this will be used in the editor for each setting entry.
     * By default, the bundle tries to determine it based on the data type.
     *
     * @var class-string<FormTypeInterface>|null
     */
    public ?string $formEntryType = null;

    /**
     * Options to be passed to the form element of this setting in the setting editor.
     * The bundle tries to set some reasonable defaults based on the data and form type.
     *
     * @var null|array<string, mixed>
     */
    public ?array $formOptions = null;

    /**
     * Shorthand for formType=ChoiceType::class, formOptions={"choices": {"val1": "val1", ...}}
     * If the setting data type is array, then "multiple" form option is automatically set.
     *
     * @var null|string[]
     */
    public ?array $enum = null;

    /**
     * @param null|string[] $enum
     * @param class-string|null $dataType
     * @param class-string<FormTypeInterface>|null $formType
     * @param class-string<FormTypeInterface>|null $formEntryType
     * @param null|array<string, mixed> $formOptions
     */
    public function __construct(
        ?string $label = null,
        ?array  $enum = null,
        ?string $dataType = null,
        ?string $help = null,
        ?string $formType = null,
        ?string $formEntryType = null,
        ?array  $formOptions = null,
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
