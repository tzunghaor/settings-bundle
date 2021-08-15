<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Stores info about a setting scope
 */
class Scope
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var Scope[]
     */
    private $children;
    /**
     * @var bool
     */
    private $isPassive;
    /**
     * @var string[] scope names
     */
    private $path;

    public function __construct(string $name, array $children = [], bool $isPassive = false, array $path = [])
    {
        $this->name = $name;
        $this->children = $children;
        $this->isPassive = $isPassive;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Scope[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function isPassive(): bool
    {
        return $this->isPassive;
    }

    /**
     * @return string[]
     */
    public function getPath(): array
    {
        return $this->path;
    }
}