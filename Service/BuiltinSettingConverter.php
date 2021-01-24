<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\PropertyInfo\Type;

/**
 * Converts simple types, arrays and DateTime to/from string that can be stored in DB
 */
class BuiltinSettingConverter implements SettingConverterInterface
{
    /**
     * @param Type $type
     *
     * @return bool true if this converter can convert to-from this type
     */
    public function supports(Type $type): bool
    {
        return in_array($type->getClassName(), [null, \DateTime::class], true);
    }

    /**
     * @param Type $type
     * @param mixed $value value used in setting section object
     *
     * @return string value persisted in DB
     */
    public function convertToString(Type $type, $value): string
    {
        if ($type->getClassName() === \DateTime::class) {
            /** @var \DateTime $value */
            return $value->format(DATE_ATOM);
        }

        if ($type->isCollection()) {
            return json_encode(array_values($value));
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_BOOL:
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
    public function convertFromString(Type $type, string $value)
    {
        if ($type->getClassName() === \DateTime::class) {
            return new \DateTime($value);
        }

        if ($type->isCollection()) {
            return json_decode($value, true);
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_BOOL:
                return in_array($value, ['true', '1'], true);

            case Type::BUILTIN_TYPE_INT:
                return (int) $value;

            case Type::BUILTIN_TYPE_FLOAT:
                return (float) $value;

            default:
                return $value;
        }
    }
}