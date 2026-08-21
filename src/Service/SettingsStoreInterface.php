<?php


namespace Tzunghaor\SettingsBundle\Service;


interface SettingsStoreInterface
{
    /**
     * Retrieves stored setting values.
     *
     * @param string $sectionName
     * @param string $scope
     *
     * @return array<string, mixed> [$settingName => $value, ... ]
     */
    public function getValues(string $sectionName, string $scope): array;

    /**
     * Saves setting values.
     *
     * @param string $sectionName
     * @param string $scope
     * @param array<string, mixed> $values [$settingName => $value, ...]
     */
    public function saveValues(string $sectionName, string $scope, array $values): void;
}