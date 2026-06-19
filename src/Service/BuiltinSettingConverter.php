<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Converts simple types, arrays and DateTime to/from string that can be stored in DB
 */
class BuiltinSettingConverter implements SettingConverterInterface, SettingValueConverterInterface
{
    /**
     * @return bool true if this converter can convert to-from this type
     */
    public function supports(PropertyInfoType | TypeInfoType $type): bool
    {
        $type = Type::createFromAnyType($type);

        return in_array($type->getClassName(), [null, \DateTime::class], true);
    }

    /**
     * @param mixed $value value used in setting section object
     *
     * @return string value persisted in DB
     */
    public function convertToString(PropertyInfoType | TypeInfoType $type, $value): string
    {
        $type = Type::createFromAnyType($type);

        if ($type->getClassName() === \DateTime::class) {
            /** @var \DateTime $value */
            return $value->format(DATE_ATOM);
        }

        if ($type->isCollection()) {
            return json_encode(array_values($value));
        }

        switch ($type->getTypeIdentifier()) {
            case 'bool':
                return $value ? '1' : '0';

            default:
                return (string) $value;
        }
    }

    /**
     * @param Type $type
     * @param string $value value persisted in DB
     *
     * @return mixed value used in setting section object
     *
     * @throws \Exception
     */
    public function convertFromString(PropertyInfoType | TypeInfoType $type, string $value)
    {
        $type = Type::createFromAnyType($type);

        if ($type->getClassName() === \DateTime::class) {
            return new \DateTime($value);
        }

        if ($type->isCollection()) {
            return json_decode($value, true);
        }

        switch ($type->getTypeIdentifier()) {
            case 'bool':
                return in_array($value, ['true', '1'], true);

            case 'int':
                return (int) $value;

            case 'float':
                return (float) $value;

            default:
                return $value;
        }
    }
}
