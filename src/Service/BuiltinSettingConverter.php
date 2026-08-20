<?php


namespace Tzunghaor\SettingsBundle\Service;


use DateTime;
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

        if ($type->isNullable() && $type->isCollection()) {
            return false;
        }

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

        if ($type->getTypeIdentifier() === 'string' && $type->isNullable()) {
            return json_encode($value);
        }

        if ($value === null && $type->isNullable()) {
            return '';
        }

        if ($type->isCollection()) {
            if ($type->getClassName() === DateTime::class) {
                $value = array_map(fn (DateTime $date) => $date->format(DATE_ATOM), $value);
            }

            return json_encode(array_values($value));
        }

        if ($type->getClassName() === DateTime::class) {
            /** @var DateTime $value */
            return $value->format(DATE_ATOM);
        }

        if ($type->getTypeIdentifier() === 'bool') {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @param string $value value persisted in DB
     *
     * @return mixed value used in setting section object
     *
     * @throws \Exception
     */
    public function convertFromString(PropertyInfoType | TypeInfoType $type, string $value)
    {
        $type = Type::createFromAnyType($type);

        if ($type->getTypeIdentifier() === 'string' && $type->isNullable()) {
            return json_decode($value);
        }

        if ($type->isCollection()) {
            if ($type->getClassName() === DateTime::class) {
                return array_map(
                    fn (string $dateString) => new DateTime($dateString),
                    json_decode($value, true)
                );
            }

            return json_decode($value, true);
        }

        if ($value === '' && $type->isNullable()) {
            return null;
        }

        if ($type->getClassName() === DateTime::class) {
            return new DateTime($value);
        }

        return match ($type->getTypeIdentifier()) {
            'bool' => in_array($value, ['true', '1'], true),
            'int' => (int)$value,
            'float' => (float)$value,
            default => $value,
        };
    }
}
