<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Such items are passed to twig templates in **collections**, **scopes** and **sections**
 */
class ViewItem extends Item
{
    /**
     * @var string|null url
     */
    private $url;

    public function __construct(
        string $name,
        string $url = null,
        array $extra = [],
        string $customTitle = null,
        array $children = []
    ) {
        parent::__construct($name, $customTitle, $children, $extra);
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}