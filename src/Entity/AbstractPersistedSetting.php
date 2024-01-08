<?php
namespace Tzunghaor\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tzunghaor\SettingsBundle\Model\PersistedSettingInterface;

/**
 * Unmapped superclass of the persisted setting entity.
 * You can create your own subclass of it, and set it in the bundle config.
 */
#[ORM\MappedSuperclass]
abstract class AbstractPersistedSetting implements PersistedSettingInterface
{
    /**
     * Limiting column size to avoid MySQL 767 byte key limit with utf8mb4 default encoding.
     * If you have other needs see Resources/doc/database.md
     */
    #[ORM\Id]
    #[ORM\Column(type: "string", length: 50)]
    protected string $scope;

    #[ORM\Id]
    #[ORM\Column(type: "string", length: 140)]
    protected string $path;

    #[ORM\Column(type: "string", length: 10000)]
    protected string $value;


    public function getScope(): string
    {
        return $this->scope;
    }


    public function setScope(string $scope): AbstractPersistedSetting
    {
        $this->scope = $scope;

        return $this;
    }


    public function getPath(): string
    {
        return $this->path;
    }


    public function setPath(string $path): AbstractPersistedSetting
    {
        $this->path = $path;

        return $this;
    }


    public function getValue(): string
    {
        return $this->value;
    }


    public function setValue(string $value): AbstractPersistedSetting
    {
        $this->value = $value;

        return $this;
    }
}