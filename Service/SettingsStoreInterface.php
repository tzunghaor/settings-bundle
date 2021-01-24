<?php


namespace Tzunghaor\SettingsBundle\Service;


use Tzunghaor\SettingsBundle\Model\SettingMetaData;

interface SettingsStoreInterface
{
    /**
     * Retrieves stored setting values. The returned values' type are as is defined in the passed $metaDataArray
     *
     * @param string $sectionName
     * @param string $scope
     * @param SettingMetaData[] $metaDataArray [$settingName => $metaData, ...]
     *
     * @return array [$settingName => $value, ... ]
     */
    public function getValues(string $sectionName, string $scope, array $metaDataArray): array;

    /**
     * Saves setting values. Expects the values' type as is defined in $metaDataArray
     *
     * @param string $sectionName
     * @param string $scope
     * @param array $values [$settingName => $value, ...]
     * @param SettingMetaData[] $metaDataArray [$settingName => $metaData, ...]
     */
    public function saveValues(string $sectionName, string $scope, array $values, array $metaDataArray): void;
}