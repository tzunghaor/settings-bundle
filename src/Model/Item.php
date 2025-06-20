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
     * Optional. Value of this extra data should be true|false.
     * If you set this in Items' extra returned by your ScopeProviderInterface::getScopeDisplayHierarchy(), then this
     * will be used to determine if a link to that scope is necessary in the scope list (and isGranted will not be
     * called for each Item). isGranted
     */
    public const EXTRA_EDITABLE = 'ts_editable';

    private string $name;

    private string $title;

    /**
     * @var Item[]
     */
    private array $children;

    /**
     * @var array Extra data that you can use in your templates / extensions. Array keys defined as Item::EXTRA_* const
     *            are read by the bundle, but you don't need to set them.
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