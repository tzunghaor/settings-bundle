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
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $title;
    /**
     * @var Item[]
     */
    private $children;
    /**
     * @var array extra data that you can use in your templates / extensions
     */
    private $extra;

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

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
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

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}