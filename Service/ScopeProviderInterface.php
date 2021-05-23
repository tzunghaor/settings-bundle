<?php


namespace Tzunghaor\SettingsBundle\Service;


interface ScopeProviderInterface
{
    /**
     * @param mixed|null $subject can be scope name or an object or anything you support
     *                            if null, default scope path is returned
     *
     * @return string[] inheritance path of the subject [$topScope, ... , $parentScope]
     */
    public function getScopePath($subject = null): array;

    /**
     * @param string|null $searchString optional search string entered by user
     *
     * @return array|null nested array of all scopes or null if returning full scope hierarchy is not supported
     */
    public function getScopeHierarchy(?string $searchString = null): ?array;
}