<?php


namespace Tzunghaor\SettingsBundle\Model;

/**
 * Used by SettingsService to cache loaded setting values
 */
class SettingsCacheEntry
{
    private array $values;

    private array $valueScopes;

    /**
     * @var object|null
     */
    private $object;

    /**
     * @param array  $values [$attributeName => $value, ...]
     * @param array  $valueScopes [$attributeName => $scope, ...] which scope defines the value - can be used to
     *                            determine whether the value is inherited
     * @param object $object the settings object
     */
    public function __construct(array $values, array $valueScopes, $object)
    {
        $this->values = $values;
        $this->valueScopes = $valueScopes;
        $this->object = $object;
    }


    public function getValues(): array
    {
        return $this->values;
    }


    public function getValueScopes(): array
    {
        return $this->valueScopes;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }
}