<?php

namespace Tzunghaor\SettingsBundle\Service;

/**
 * Implement this interface if instead of `isGranted($settingSectionAddress, 'edit')` you want to use
 * something like `isGranted($myObject, 'edit_settings')`.
 */
interface IsGrantedSupportingScopeProviderInterface extends ScopeProviderInterface
{
    /**
     * @param string $scope the scope name (the Item::$name returned by this ScopeProvider::getScope())
     *
     * @return object referenced by $scope - this will be the $subject passed to Voters
     */
    public function getSubject(string $scope): object;

    /**
     * @return string e.g. 'edit_settings' - this will be the $attribute passed to Voters
     */
    public function getIsGrantedAttribute(): string;
}