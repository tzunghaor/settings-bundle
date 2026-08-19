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

        if ($type->isNullable() && $value === null) {
            return '';
        }

        if ($type->isCollection()) {
            if ($type->getClassName() === DateTime::class) {
                $stringifiedDates = [];
                foreach ($value as $date) {
                    /** @var \DateTime $date */
                    $stringifiedDates[] = $date->format(DATE_ATOM);
                }
                $value = $stringifiedDates;
            }

            return json_encode(array_values($value));
        }

        if ($type->getClassName() === \DateTime::class) {
            /** @var \DateTime $value */
            return $value->format(DATE_ATOM);
        }

        switch ($type->getTypeIdentifier()) {
            case 'bool':
                return $value ? '1' : '0';

            default:
                return (string) $value;
        }
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
                $dates = [];
                foreach (json_decode($value, true) as $dateString) {
                    $dates[] = new DateTime($dateString);
                }

                return $dates;
            }

            return json_decode($value, true);
        }

        if ($type->getClassName() === \DateTime::class) {
            if ($type->isNullable() && $value === '') {
                return null;
            }

            return new \DateTime($value);
        }

        if ($type->isNullable() && $value === '') {
            return null;
        }

        return match ($type->getTypeIdentifier()) {
            'bool' => in_array($value, ['true', '1'], true),
            'int' => (int)$value,
            'float' => (float)$value,
            default => $value,
        };
    }
}
