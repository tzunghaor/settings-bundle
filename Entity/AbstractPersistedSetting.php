<?php
namespace Tzunghaor\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Unmapped superclass of the persisted setting entity.
 * You can create your own subclass of it, and set it in the bundle config.
 */
abstract class AbstractPersistedSetting
{
    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    protected $scope;

    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    protected $path;

    /**
     * @var string
     * @ORM\Column(type="string", length=10000)
     */
    protected $value;

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     * @return AbstractPersistedSetting
     */
    public function setScope(string $scope): AbstractPersistedSetting
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return AbstractPersistedSetting
     */
    public function setPath(string $path): AbstractPersistedSetting
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return AbstractPersistedSetting
     */
    public function setValue(string $value): AbstractPersistedSetting
    {
        $this->value = $value;

        return $this;
    }
}