<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Stores info about an item, which can be:
 * - a scope
 * - a setting section
 * - a collection
 */
class Item
{
    private string $name;

    private string $title;

    /**
     * @var Item[]
     */
    private array $children;

    /**
     * @var array extra data that you can use in your templates / extensions - the bundle itself doesn't need it
     */
    private array $extra;

    public function __construct(
        string $name,
        ?string $customTitle = null,
        array $children = [],
        array $extra = []
    ) {
        $this->name = $name;
        $this->title = $customTitle ?? $name;
        $this->children = $children;
        $this->extra = $extra;
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Item[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }


    public function getExtra(): array
    {
        return $this->extra;
    }
}