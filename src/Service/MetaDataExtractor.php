<?php


namespace Tzunghaor\SettingsBundle\Service;


use ReflectionProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Attribute\SettingSection;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Helper\SettingPropertyInfoCompiler;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Extracts metadata from setting section classes
 *
 * @internal it is not meant to be used outside TzunghaorSettingsBundle
 */
class MetaDataExtractor
{
    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfo
    ) {
    }

    /**
     * Analyses the $sectionClass and its properties and creates a SectionMetaData based on it.
     *
     * @throws SettingsException
     * @throws \ReflectionException
     */
    public function createSectionMetaData(string $sectionName, string $sectionClass): SectionMetaData
    {
        $reflectionClass = new \ReflectionClass($sectionClass);

        [$sectionTitle, $sectionDescription, $sectionExtra] = $this->extractSectionInfo($reflectionClass);
        $sectionTitle = empty($sectionTitle) ? $sectionName : $sectionTitle;

        $settingsMetaArray = $this->extractSettingMetaArray($sectionClass);

        return new SectionMetaData(
            $sectionName, $sectionTitle, $sectionClass, $sectionDescription, $settingsMetaArray, $sectionExtra
        );
    }


    /**
     * Collect metadata for class properties, including inherited properties defined in ancestor classes
     *
     * @return SettingMetaData[]
     *
     * @throws SettingsException
     * @throws \ReflectionException
     */
    public function extractSettingMetaArray(string $className): array
    {
        $reflectionClass = new \ReflectionClass($className);

        // we will start with ancestors and allow subclasses to override properties
        // @see how extractPropertyInfos() handles the array passed to it
        $reflectionProperties = [];
        $currentReflectionClass = $reflectionClass;
        do {
            $reflectionProperties = array_merge($currentReflectionClass->getProperties(), $reflectionProperties);
        } while ($currentReflectionClass = $currentReflectionClass->getParentClass());

        return $this->extractPropertyInfos($reflectionProperties);
    }

    /**
     * Extracts settings metadata from class properties.
     * If multiple property reflections are passed with the same name, then non-empty extracted data from later ones
     * will override earlier ones: so if you call this method for a class that extend other(s), then parent properties
     * must come before child properties in $reflectionProperties array.
     *
     * @param ReflectionProperty[] $reflectionProperties
     * @return SettingMetaData[]
     *
     * @throws SettingsException
     */
    private function extractPropertyInfos(array $reflectionProperties): array
    {
        $settingsMetaArray = [];

        foreach ($reflectionProperties as $reflectionProperty) {
            $sectionClass = $reflectionProperty->class;
            $propertyName = $reflectionProperty->getName();

            // This function expects parent class properties come in $reflectionProperties before child class
            // properties, and we fill $settingsMetaArray in that order, so it is possible that there is already parent
            // class metadata for this property name in it
            /** @var ?SettingMetaData $ancestorMetaData */
            $ancestorMetaData = $settingsMetaArray[$propertyName] ?? null;

            $propertyAttributes = $reflectionProperty->getAttributes(Setting::class);
            /** @var Setting[] $settingAttributes */
            $settingAttributes = [];
            foreach ($propertyAttributes as $reflectionAttribute) {
                $settingAttributes[] = $reflectionAttribute->newInstance();
            }

            $commentLabel = trim((string) $this->propertyInfo->getShortDescription($sectionClass, $propertyName));
            $docBlockHelp = trim((string) $this->propertyInfo->getLongDescription($sectionClass, $propertyName));

            $compiler = new SettingPropertyInfoCompiler(
                $sectionClass,
                $propertyName,
                !empty($commentLabel) ? $commentLabel : null,
                !empty($docBlockHelp) ? $docBlockHelp : null,
                $this->extractPropertyDataType($sectionClass, $propertyName),
                $settingAttributes,
                $ancestorMetaData,
                [$this, 'extractSettingMetaArray'],
            );

            $settingsMetaArray[$propertyName] = new SettingMetaData(
                $propertyName,
                $compiler->getDataType(),
                $compiler->getFormType(),
                $compiler->getFormOptions(),
            );
        }

        return $settingsMetaArray;
    }

    /**
     * Attempts to extract data type of the given class property
     */
    private function extractPropertyDataType(string $sectionClass, string $propertyName): ?Type
    {
        // backwards compatibility with older property-info method
        if (method_exists($this->propertyInfo, 'getTypes')) {
            $origDataTypes = $this->propertyInfo->getTypes($sectionClass, $propertyName);
            $dataType = is_array($origDataTypes) ? Type::createFromPropertyInfoArray($origDataTypes) : null;
        } else {
            $origDataType = $this->propertyInfo->getType($sectionClass, $propertyName);
            $dataType = $origDataType === null ? null : Type::createFromTypeInfo($origDataType);
        }

        return $dataType;
    }

    /**
     * Simple naive method to extract title, description and extra data from a docblock of a class
     *
     * @return array<mixed> [$title, $description, $extraArray]
     */
    // @phpstan-ignore missingType.generics (by design we don't care about which class it is)
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
                // skip empty lines and annotations
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
}
