<?php


namespace Tzunghaor\SettingsBundle\Service;


use Tzunghaor\SettingsBundle\DependencyInjection\Configuration;

interface ScopeProviderInterface
{
    public const SCOPE_NAME = Configuration::SCOPE_NAME;
    public const SCOPE_CHILDREN = Configuration::SCOPE_CHILDREN;

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
     *                    the hierarchy has the same structure as in the configuration
     *                    SCOPE_* constants are keys in this array
     */
    public function getScopeHierarchy(?string $searchString = null): ?array;
}