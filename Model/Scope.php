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
    private $extra;

    public function __construct(string $name, array $children = [], bool $isPassive = false, array $extra = [])
    {
        $this->name = $name;
        $this->children = $children;
        $this->isPassive = $isPassive;
        $this->extra = $extra;
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
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}