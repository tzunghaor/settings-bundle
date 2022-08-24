<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\PropertyInfo\Type;

/**
 * Converts php values to/from string that can be stored in DB.
 *
 * Implement this interface, and tag the service with "tzunghaor_settings.setting_converter"
 */
interface SettingConverterInterface
{
    /**
     * @param Type $type
     *
     * @return bool true if this converter can convert to-from this type
     */
    public function supports(Type $type): bool;

    /**
     * @param Type $type
     * @param mixed $value value used in setting section object
     *
     * @return string value persisted in DB
     */
    public function convertToString(Type $type, $value): string;

    /**
     * @param Type $type
     * @param string $value value persisted in DB
     *
     * @return mixed value used in setting section object
     */
    public function convertFromString(Type $type, string $value);
}