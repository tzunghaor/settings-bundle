<?php

namespace Tzunghaor\SettingsBundle\Service;

use Tzunghaor\SettingsBundle\Model\Scope;

interface ScopeProviderInterface
{
    /**
     * @param mixed|null $subject Can be scope name or an object or anything you support.
     *                            If null, default scope name is returned.
     *
     * @return string scope name of subject
     */
    public function getScopeName($subject = null): string;

    /**
     * @param mixed|null $subject Can be scope name or an object or anything you support.
     *                            If null, default scope path is returned.
     *
     * @return string[] inheritance path of the subject [$topScope, ... , $parentScope]
     */
    public function getScopePath($subject = null): array;

    /**
     * Scope hierarchy to be displayed to user, filtered by the optional $searchString entered by the user.
     * The scope provider is free to decide what scopes it returns, the path of the returned Scope objects
     * don't need to be filled.
     * If you are using a Voter, then the editor will filter out the not editable items from the returned hierarchy,
     * and if no editable scope remain, then the scope selector will not be displayed at all. So if you are returning
     * only a subset of matching scopes, then pay attention to return a subset that is editable according to your Voter.
     *
     * @param string|null $searchString
     *
     * @return Scope[]|null
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): ?array;
}