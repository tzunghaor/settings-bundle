<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Such items are passed to twig templates in **collections**, **scopes** and **sections**
 */
class ViewItem extends Item
{
    private ?string $url;

    public function __construct(
        string $name,
        ?string $url = null,
        array $extra = [],
        ?string $customTitle = null,
        array $children = []
    ) {
        parent::__construct($name, $customTitle, $children, $extra);
        $this->url = $url;
    }


    public function getUrl(): ?string
    {
        return $this->url;
    }
}