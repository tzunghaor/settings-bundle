<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * A fully filled instance of this class can identify a stored setting section
 */
class SettingSectionAddress
{
    /**
     * @var string
     */
    private $collectionName;

    /**
     * @var string|null
     */
    private $scope;

    /**
     * @var string|null
     */
    private $sectionName;

    public function __construct(string $collectionName, ?string $scope, ?string $sectionName)
    {
        $this->collectionName = $collectionName;
        $this->scope = $scope;
        $this->sectionName = $sectionName;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return string|null
     */
    public function getSectionName(): ?string
    {
        return $this->sectionName;
    }

    /**
     * @return bool true if all parts of the address are filled
     */
    public function isComplete(): bool
    {
        return $this->collectionName !== null && $this->scope !== null && $this->sectionName !== null;
    }
}