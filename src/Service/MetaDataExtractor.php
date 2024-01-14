<?php


namespace Tzunghaor\SettingsBundle\Service;


use ReflectionProperty;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Attribute\SettingSection;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\BoolType;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;

/**
 * Extracts metadata from setting section classes
 *
 * @internal it is not meant to be used outside TzunghaorSettingsBundle
 */
class MetaDataExtractor
{
    private PropertyInfoExtractorInterface $propertyInfo;

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfo
    ) {
        $this->propertyInfo = $propertyInfo;
    }

    /**
     * @throws SettingsException
     * @throws \ReflectionException
     */
    public function createSectionMetaData(string $sectionName, string $sectionClass): SectionMetaData
    {
        $reflectionClass = new \ReflectionClass($sectionClass);

        [$sectionTitle, $sectionDescription, $sectionExtra] = $this->extractSectionInfo($reflectionClass);
        $sectionTitle = empty($sectionTitle) ? $sectionName : $sectionTitle;

        // collect properties, including ancestor classes private properties
        // we will start with ancestors and allow subclasses to override properties
        $reflectionProperties = [];
        $currentReflectionClass = $reflectionClass;
        do {
            $reflectionProperties = array_merge($currentReflectionClass->getProperties(), $reflectionProperties);
        } while ($currentReflectionClass = $currentReflectionClass->getParentClass());

        $settingsMetaArray = $this->extractPropertyInfos($reflectionProperties);

        return new SectionMetaData(
            $sectionName, $sectionTitle, $sectionClass, $sectionDescription, $settingsMetaArray, $sectionExtra
        );
    }

    /**
     * Extracts settings metadata from class properties.
     * If multiple property reflections are passed with the same name, then non-empty extracted data from later ones
     * will override earlier ones.
     *
     * @param ReflectionProperty[] $reflectionProperties
     * @return SettingMetaData[]
     *
     * @throws SettingsException
     */
    private function extractPropertyInfos(array $reflectionProperties): array
    {
        $settingsMetaArray = [];
        $defaultDataType = new Type('string');

        foreach ($reflectionProperties as $reflectionProperty) {
            $sectionClass = $reflectionProperty->class;
            $propertyName = $reflectionProperty->getName();

            $settingLabel = null;
            $settingHelp = null;
            $dataType = null;
            $formType = null;
            $formEntryType = null;
            $formOptions = [];
            $isEnum = false;

            $propertyAttributes = $reflectionProperty->getAttributes(Setting::class);
            foreach ($propertyAttributes as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();
                $formType = $attribute->formType;
                $formEntryType = $attribute->formEntryType;
                $formOptions = $attribute->formOptions ?? $formOptions;
                if (is_array($attribute->enum)) {
                    $formType = $formType ?? ChoiceType::class;
                    $formOptions['choices'] = $formOptions['choices'] ?? array_combine($attribute->enum, $attribute->enum);
                    $isEnum = true;
                }
                $settingLabel = $attribute->label;
                $settingHelp = $attribute->help;
                $dataType = $dataType ?? $this->extractDataType($attribute->dataType);
            }

            if ($dataType === null) {
                $dataTypes = $this->propertyInfo->getTypes($sectionClass, $propertyName);

                if ($dataTypes === null) {
                    $dataType = null;
                } elseif (count($dataTypes) === 1) {
                    $dataType = $dataTypes[0];
                } else {
                    throw new SettingsException(sprintf('Multiple types are not supported for setting %s in %s',
                        $propertyName, $sectionClass));
                }
            }

            $settingLabel = $settingLabel ??
                trim((string) $this->propertyInfo->getShortDescription($sectionClass, $propertyName));
            $settingHelp = $settingHelp ??
                (string) $this->propertyInfo->getLongDescription($sectionClass, $propertyName);

            // --- end of extracting info from property, now applying defaults if something is not defined explicitly
            $ancestorMetaData = $settingsMetaArray[$propertyName] ?? null;

            if ($dataType === null) {
                $dataType = $ancestorMetaData ? $ancestorMetaData->getDataType() : $defaultDataType;
            }

            // by default enum allows multi select
            if ($isEnum && $dataType->isCollection()) {
                $formOptions['multiple'] = $formOptions['multiple'] ?? true;
            }

            if (empty($settingLabel)) {
                $settingLabel = $ancestorMetaData ? $ancestorMetaData->getLabel() : $propertyName;
            }
            if (empty($settingHelp) && $ancestorMetaData) {
                $settingHelp = $ancestorMetaData->getHelp();
            }
            if (empty($formType)) {
                $formType = $ancestorMetaData ? $ancestorMetaData->getFormType() : $this->getFormTypeByDataType($dataType);
            }

            if ($formType === CollectionType::class) {
                $formOptions = $this->getCollectionFormOptions($dataType, $formEntryType, $formOptions);
            }

            if ($ancestorMetaData) {
                $formOptions = array_merge($ancestorMetaData->getFormOptions(), $formOptions);
            }

            $settingsMetaArray[$propertyName] = new SettingMetaData(
                $propertyName,
                $dataType,
                $formType,
                $formOptions,
                $settingLabel,
                $settingHelp
            );
        }

        return $settingsMetaArray;
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
     * Returns the default base form type (entry type in case of collection) to be used for the given data type
     *
     * @return string FQCN of form type
     */
    private function getBaseFormTypeByDataType(Type $dataType): string
    {
        switch ($dataType->getClassName()) {
            case \DateTime::class:
                return DateTimeType::class;
        }

        switch ($dataType->getBuiltinType()) {
            case 'bool':
                return BoolType::class;

            case 'int':
                return IntegerType::class;

            case 'float':
                return NumberType::class;

            default:
                return TextType::class;
        }
    }

    /**
     * Adds form options needed by collection type
     *
     * @param Type $dataType datatype of setting
     * @param string|null $formEntryType explicitly configured form entry type
     * @param array $formOptions form options so far - these values won't be overwritten
     *
     * @return array form options enriched with options for collection type
     */
    private function getCollectionFormOptions(Type $dataType, ?string $formEntryType, array& $formOptions): array
    {
        $formEntryType = $formEntryType ?? $this->getBaseFormTypeByDataType($dataType);

        $collectionFormOptions = [
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => $formEntryType,
            'entry_options' => ['label' => false, 'row_attr' => ['class' => 'tzunghaor_settings_collection_row']],
        ];

        return array_merge($collectionFormOptions, $formOptions);
    }

    /**
     * Simple naive method to extract title and description from a docblock
     */
    private function extractSectionInfo(\ReflectionClass $reflectionClass): array
    {
        $sectionAttributes = $reflectionClass->getAttributes(SettingSection::class);
        if (count($sectionAttributes)) {
            /** @var SettingSection $sectionAttribute */
            $sectionAttribute = $sectionAttributes[0]->newInstance();
            $sectionTitle = $sectionAttribute->label;
            $sectionDescription = $sectionAttribute->help;
            $sectionExtra = $sectionAttribute->extra;
        }

        $docBlock = $reflectionClass->getDocComment();

        if ($docBlock !== false) {
            $docComment = trim($docBlock, "\t /");
            $commentLines = explode("\n", $docComment);
            $isBeginning = true;
            $descriptionLines = [];

            foreach ($commentLines as $commentLine) {
                $commentLine = trim(ltrim($commentLine, "\t *"));
                if (empty($commentLine) || $commentLine[0] === '@') {
                    continue;
                }

                if ($isBeginning) {
                    $sectionTitle = $sectionTitle ?? $commentLine;
                    $isBeginning = false;
                } else {
                    $descriptionLines[] = $commentLine;
                }
            }

            $sectionDescription = $sectionDescription ?? implode("\n", $descriptionLines);
        }

        return [$sectionTitle ?? '', $sectionDescription ?? '', $sectionExtra ?? []];
    }

    /**
     * Simple naive method to extract data type from a type definition string (e.g. "\DateTime[]")
     *
     * @throws SettingsException
     */
    private function extractDataType(?string $dataTypeStringIn): ?Type
    {
        if ($dataTypeStringIn === null || empty($dataTypeString = trim($dataTypeStringIn))) {
            return null;
        }

        $isCollection = false;
        if (substr($dataTypeString, -2) === '[]') {
            $isCollection = true;
            $dataTypeString = substr($dataTypeString, 0, -2);
        }

        if (in_array($dataTypeString, Type::$builtinTypes, true)) {
            return new Type($dataTypeString, false, null, $isCollection);
        }

        if (!class_exists($dataTypeString)) {
            throw new SettingsException(sprintf('unknown #[Setting(dataType: "%s")]', $dataTypeStringIn));
        }

        return new Type(Type::BUILTIN_TYPE_OBJECT, false, $dataTypeString, $isCollection);
    }
}
