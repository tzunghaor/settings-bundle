<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormTypeInterface;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\BoolType;
use Tzunghaor\SettingsBundle\Form\NullableType;
use Tzunghaor\SettingsBundle\Form\SettingClassType;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Compiles info for a single setting property from all discovered data.
 */
class SettingPropertyInfoCompiler
{
    private Type $dataType;

    /**
     * @var class-string<FormTypeInterface>|null
     */
    private ?string $formType;

    /**
     * @var array<string, mixed>
     */
    private array $formOptions;

    /**
     * @param class-string $sectionClass
     * @param string|null $docBlockLabel first line in docblock - null if it does not exist or it is empty
     * @param string|null $docBlockHelp other lines in docblock - null if it does not exist or it is empty
     * @param Type|null $propertyType type discovered by propertyInfo
     * @param Setting[] $settingAttributes attributes defined on property
     * @param SettingMetaData|null $ancestorMetaData meta data inherited from parent class
     * @param callable $metaArrayExtractor function ($className): SettingMetaData[]
     *
     * @throws SettingsException if setting cannot work, e.g. references non-existent class
     */
    public function __construct(
        string           $sectionClass,
        string           $propertyName,
        ?string          $docBlockLabel,
        ?string          $docBlockHelp,
        ?Type            $propertyType,
        array            $settingAttributes,
        ?SettingMetaData $ancestorMetaData,
        callable         $metaArrayExtractor,
    ) {
        $mergedSettingAttribute = $this->mergeSettingAttributes($settingAttributes);

        $this->dataType =
            $this->dataTypeFromString($mergedSettingAttribute->dataType) ??
            $ancestorMetaData?->getDataType() ??
            $propertyType ??
            new Type('string')
        ;

        if ($this->dataType->isIncomplete()) {
            throw new SettingsException(
                sprintf('Multiple types are not supported for setting %s in %s',
                $propertyName, $sectionClass)
            );
        }

        $ancestorFormOptions = $ancestorMetaData?->getFormOptions();
        $ancestorLabel = $ancestorFormOptions ? ($ancestorFormOptions['label'] ?? null) : null;
        $ancestorHelp = $ancestorFormOptions ? ($ancestorFormOptions['help'] ?? null) : null;

        $this->formOptions = $mergedSettingAttribute->formOptions ?? [];

        $this->formOptions['label'] =
            $mergedSettingAttribute->label ??
            $docBlockLabel ??
            $ancestorLabel ??
            $propertyName
        ;

        $help =
            $mergedSettingAttribute->help ??
            $docBlockHelp ??
            $ancestorHelp ??
            null
        ;

        if (!empty($help)) {
            $this->formOptions['help'] = $help;
        }

        $this->formType = $mergedSettingAttribute->formType;

        if (is_array($mergedSettingAttribute->enum)) {
            $this->formType = $this->formType ?? ChoiceType::class;
            $this->formOptions['choices'] = $this->formOptions['choices'] ??
                array_combine($mergedSettingAttribute->enum, $mergedSettingAttribute->enum);

            // Enum allows multi select if it is saved as an array
            if ($this->dataType->isCollection()) {
                $this->formOptions['multiple'] = $this->formOptions['multiple'] ?? true;
            }
        }

        if (empty($this->formType)) {
            $this->formType =
                $ancestorMetaData?->getFormType() ??
                $this->getFormTypeByDataType($this->dataType)
            ;
        }

        if ($this->formType === CollectionType::class) {
            $collectionFormOptions = $this->getCollectionFormOptions(
                $this->dataType,
                $mergedSettingAttribute->formEntryType ?? $ancestorFormOptions['entryType'] ?? null,
            );
            $this->formOptions = array_merge($collectionFormOptions, $this->formOptions);
        }

        if ($this->formType === NullableType::class) {
            $this->formOptions[NullableType::OPTION_WRAPPED_TYPE] =
                $this->formOptions[NullableType::OPTION_WRAPPED_TYPE] ??
                $this->getBaseFormTypeByDataType($this->dataType)
            ;
            $this->formOptions[NullableType::OPTION_WRAPPED_OPTIONS] =
                $this->formOptions[NullableType::OPTION_WRAPPED_OPTIONS] ??
                []
            ;
        }

        if ($this->formType === CheckboxType::class) {
            $this->formOptions['false_values'] = $this->formOptions['false_values'] ?? [null, false, 0, '0', ''];
        }

        // Symfony normalizes '' to null by default.
        // If we know that the setting accepts only string, then explicitly set empty string instead.
        if ($this->formType === TextType::class &&
            $this->dataType->getTypeIdentifier() === 'string' &&
            !$this->dataType->isCollection()) {
            $this->formOptions['empty_data'] = $this->formOptions['empty_data'] ?? '';
        }

        // if this property is defined in ancestor class too, then inherit form options that are not set in this class
        if ($ancestorMetaData) {
            $this->formOptions = array_merge($ancestorMetaData->getFormOptions(), $this->formOptions);
        }

        // If the form or a wrapped form is SettingClassType and necessary form options are not filled, then do it now
        // Look out, form options arrays are passed as reference
        $typesAndOptions = ['direct' => [$this->formType, &$this->formOptions]];
        $wrappedOptions = [
            'entry_type' => 'entry_options',
            NullableType::OPTION_WRAPPED_TYPE => NullableType::OPTION_WRAPPED_OPTIONS
        ];
        foreach ($wrappedOptions as $typeOption => $wrappedOption) {
            if (isset($this->formOptions[$wrappedOption])) {
                $typesAndOptions[$typeOption] = [$this->formOptions[$typeOption] ?? null, &$this->formOptions[$wrappedOption]];
            }
        }

        foreach ($typesAndOptions as $name => [$formType, &$formOptions]) {
            if ($formType === SettingClassType::class && !isset($formOptions[SettingClassType::OPTION_META_ARRAY])) {
                $dataClass = $this->dataType->getClassName();
                $formOptions[SettingClassType::OPTION_META_ARRAY] = $metaArrayExtractor($dataClass);
                $formOptions['data_class'] = $dataClass;
            }
        }
        // -- here ends SettingClassType form options filling
    }


    public function getDataType(): Type
    {
        return $this->dataType;
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * It doesn't actually make sense to define multiple #[Setting] for a property, but if it happens we 
     * merge them "first defined value wins"
     *
     * @param Setting[] $settingAttributes
     */
    private function mergeSettingAttributes(array $settingAttributes): Setting
    {
        $compiledAttribute = new Setting();
        foreach ($settingAttributes as $setting) {
            $compiledAttribute->label = $compiledAttribute->label ?? $setting->label;
            $compiledAttribute->help = $compiledAttribute->help ?? $setting->help;
            $compiledAttribute->dataType = $compiledAttribute->dataType ?? $setting->dataType;
            $compiledAttribute->formType = $compiledAttribute->formType ?? $setting->formType;
            $compiledAttribute->formEntryType = $compiledAttribute->formEntryType ?? $setting->formEntryType;
            $compiledAttribute->formOptions = $compiledAttribute->formOptions ?? $setting->formOptions;
            $compiledAttribute->enum = $compiledAttribute->enum ?? $setting->enum;
        }

        return $compiledAttribute;
    }

    /**
     * Simple naive method to extract data type from a type definition string (e.g. "\DateTime[]")
     *
     * @throws SettingsException
     */
    private function dataTypeFromString(?string $dataTypeStringIn): ?Type
    {
        if ($dataTypeStringIn === null || empty($dataTypeString = trim($dataTypeStringIn))) {
            return null;
        }

        $isCollection = false;
        if (str_ends_with($dataTypeString, '[]')) {
            $isCollection = true;
            $dataTypeString = substr($dataTypeString, 0, -2);
        }
        $isNullable = false;
        if (str_starts_with($dataTypeString, '?')) {
            $isNullable = true;
            $dataTypeString = substr($dataTypeString, 1);
        }

        if (Type::isBuiltinType($dataTypeString)) {
            return new Type($dataTypeString, $isNullable, null, $isCollection);
        }

        if (!class_exists($dataTypeString)) {
            throw new SettingsException(sprintf('unknown #[Setting(dataType: "%s")]', $dataTypeStringIn));
        }

        return new Type('object', $isNullable, $dataTypeString, $isCollection);
    }


    /**
     * Returns the default form type to be used for the given data type.
     *
     * @return class-string<FormTypeInterface> FQCN of form type
     */
    private function getFormTypeByDataType(Type $dataType): string
    {
        if ($dataType->isCollection()) {
            return CollectionType::class;
        }
        if ($dataType->isNullable()) {
            return NullableType::class;
        }

        return $this->getBaseFormTypeByDataType($dataType);
    }

    /**
     * Adds form options needed by collection type
     *
     * @param Type $dataType datatype of setting
     * @param class-string|null $formEntryType explicitly configured form entry type
     *
     * @return array<string, mixed>
     */
    private function getCollectionFormOptions(Type $dataType, ?string $formEntryType): array
    {
        $formEntryType = $formEntryType ?? $this->getBaseFormTypeByDataType($dataType);

        return [
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => $formEntryType,
            'entry_options' => ['label' => false, 'row_attr' => ['data-tzhs-role' => 'collection-row']],
        ];
    }

    /**
     * Returns the default base form type (entry type in case of collection) to be used for the given data type
     *
     * @return class-string<FormTypeInterface> FQCN of form type
     */
    private function getBaseFormTypeByDataType(Type $dataType): string
    {
        return match ($dataType->getTypeIdentifier()) {
            'object' => match ($dataType->getClassName()) {
                \DateTime::class => DateTimeType::class,
                default => SettingClassType::class,
            },
            'bool' => BoolType::class,
            'int' => IntegerType::class,
            'float' => NumberType::class,
            default => TextType::class,
        };
    }
}