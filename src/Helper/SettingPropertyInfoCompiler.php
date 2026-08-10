<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\BoolType;
use Tzunghaor\SettingsBundle\Form\SettingClassType;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Compiles info for a single setting property from all discovered data.
 */
class SettingPropertyInfoCompiler
{
    private ?Type $dataType;

    private ?string $formType;

    private array $formOptions;

    /**
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

        // form is for a single object using SettingClassType and meta_array is not yet set
        if ($this->formType === SettingClassType::class && !isset($this->formOptions[SettingClassType::OPTION_META_ARRAY])) {
            $dataClass = $this->dataType->getClassName();
            $this->formOptions[SettingClassType::OPTION_META_ARRAY] = $metaArrayExtractor($dataClass);
            $this->formOptions['data_class'] = $dataClass;
        }

        // form is for an array of objects using SettingClassType and meta_array is not yet set
        $entryType = $this->formOptions['entry_type'] ?? null;
        if ($entryType === SettingClassType::class && !isset($this->formOptions['entry_options'][SettingClassType::OPTION_META_ARRAY])) {
            $dataClass = $this->dataType->getClassName();
            $this->formOptions['entry_options'][SettingClassType::OPTION_META_ARRAY] = $metaArrayExtractor($dataClass);
            $this->formOptions['entry_options']['data_class'] = $dataClass;
        }
    }


    public function getDataType(): Type
    {
        return $this->dataType;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * It doesn't actually make sense to define multiple #[Setting] for a property, but if it happens we 
     * merge them "first defined value wins"
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

        if (Type::isBuiltinType($dataTypeString)) {
            return new Type($dataTypeString, false, null, $isCollection);
        }

        if (!class_exists($dataTypeString)) {
            throw new SettingsException(sprintf('unknown #[Setting(dataType: "%s")]', $dataTypeStringIn));
        }

        return new Type('object', false, $dataTypeString, $isCollection);
    }


    /**
     * Returns the default form type to be used for the given data type.
     *
     * @return string FQCN of form type
     */
    private function getFormTypeByDataType(Type $dataType): string
    {
        return $dataType->isCollection() ? CollectionType::class : $this->getBaseFormTypeByDataType($dataType);
    }

    /**
     * Adds form options needed by collection type
     *
     * @param Type $dataType datatype of setting
     * @param string|null $formEntryType explicitly configured form entry type
     *
     * @return array
     */
    private function getCollectionFormOptions(Type $dataType, ?string $formEntryType): array
    {
        $formEntryType = $formEntryType ?? $this->getBaseFormTypeByDataType($dataType);

        return [
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => $formEntryType,
            'entry_options' => ['label' => false],
        ];
    }

    /**
     * Returns the default base form type (entry type in case of collection) to be used for the given data type
     *
     * @return string FQCN of form type
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