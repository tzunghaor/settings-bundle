<?php


namespace Tzunghaor\SettingsBundle\Model;

/**
 * You can create your own entity that implements this interface and set that in the bundle configuration file.
 */
interface PersistedSettingInterface
{
    public function getScope(): string;

    public function setScope(string $scope);

    public function getPath(): string;

    public function setPath(string $path);

    public function getValue(): string;

    public function setValue(string $value);
}