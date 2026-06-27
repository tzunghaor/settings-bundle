<?php


namespace TestApp\Service;


use TestApp\Model\StringableInterface;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Tzunghaor\SettingsBundle\Model\Type;
use Tzunghaor\SettingsBundle\Service\SettingConverterInterface;
use Tzunghaor\SettingsBundle\Service\SettingValueConverterInterface;

/**
 * This converter is tagged with 'tzunghaor_settings.setting_converter', so
 * tzunghaor/settings-bundle will try this to convert values between DB and PHP.
 * This one can convert any class implementing TestApp\Model\StringableInterface
 *
 * I implemented both the deprecated SettingConverterInterface and the new SettingValueConverterInterface to be
 * able to test older and newer Symfony versions.
 */
class CustomSettingConverter implements SettingConverterInterface, SettingValueConverterInterface
{
    // this type should be supported
    private ?Type $acceptedType = null;

    // convert functions should return these
    private string $toStringResult = '';
    private mixed $fromStringResult = null;

    // collects $value-s that were passed to converter functions
    private array $toStringValues = [];
    private array $fromStringValues = [];

    public function supports(PropertyInfoType | TypeInfoType $type): bool
    {
        if ($this->acceptedType === null) {
            return false;
        }
        $type = Type::createFromAnyType($type);

        return $type->equals($this->acceptedType);
    }


    public function convertToString(PropertyInfoType | TypeInfoType $type, $value): string
    {
        $this->toStringValues[] = $value;

        return $this->toStringResult;
    }


    public function convertFromString(PropertyInfoType | TypeInfoType $type, string $value)
    {
        $this->fromStringValues[] = $value;

        return $this->fromStringResult;
    }


    public function setAcceptedType(?Type $acceptedType): void
    {
        $this->acceptedType = $acceptedType;
    }

    public function setToStringResult(string $toStringResult): void
    {
        $this->toStringResult = $toStringResult;
    }

    public function setFromStringResult(mixed $fromStringResult): void
    {
        $this->fromStringResult = $fromStringResult;
    }

    public function getToStringValues(): array
    {
        return $this->toStringValues;
    }

    public function getFromStringValues(): array
    {
        return $this->fromStringValues;
    }
}