<?php


namespace Tzunghaor\SettingsBundle\Service;


interface ScopeProviderInterface
{
    /**
     * @param mixed|null $subject Can be scope name or an object or anything you support.
     *                            If null, default scope name is returned.
     *
     * @return string scope name of subject
     */
    public function getScope($subject = null): string;

    /**
     * @param mixed|null $subject Can be scope name or an object or anything you support.
     *                            If null, default scope path is returned.
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