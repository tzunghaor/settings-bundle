<?php


namespace Tzunghaor\SettingsBundle\Model;

/**
 * You can create your own entity that implements this interface and set that in the bundle configuration file.
 */
interface PersistedSettingInterface
{
    public function getScope(): string;

    // @phpstan-ignore missingType.return (backward compatibility)
    public function setScope(string $scope);

    public function getPath(): string;

    // @phpstan-ignore missingType.return (backward compatibility)
    public function setPath(string $path);

    public function getValue(): string;

    // @phpstan-ignore missingType.return (backward compatibility)
    public function setValue(string $value);
}