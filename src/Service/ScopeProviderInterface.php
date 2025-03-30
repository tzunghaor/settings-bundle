<?php

namespace Tzunghaor\SettingsBundle\Service;

use Tzunghaor\SettingsBundle\Model\Item;

/**
 * In getScope() and getScopePath() you have to handle the following possibilities as $subject:
 *
 * * null: handle as if the default scope would have been passed. Your provider decides what the default
 *         scope is, but it must remain the same during a request. Your provider might throw an exception if it cannot
 *         return a sensible default: then build your application in a way that neither the setting editor controller
 *         nor any methods of the bundle are called without an explicitly passed subject/scope.
 * * scope name as a string
 * * anything else you want to use as $subject for SettingsService::getSection()
 */
interface ScopeProviderInterface
{
    /**
     * @param mixed|null $subject - see info in class docblock
     *
     * @return Item of the subject.
     *         Wherever the bundle calls this method, it won't use the item's children, so it is not necessary to fill.
     */
    public function getScope($subject = null): Item;

    /**
     * Pay attention that the returned path shouldn't contain the scope of the $subject itself.
     *
     * @param mixed|null $subject - see info in class docblock
     *
     * @return string[] inheritance path of the subject [$topScope, ... , $parentScope]
     *                  e.g. returning ['foo', 'bar'] means:
     *                  * if $subject doesn't have value for a setting, it should inherit it from 'bar'
     *                  * if 'bar' doesn't have value for that setting either, then inherit it from 'foo'
     *                  * if 'foo' doesn't have it either, then use the default of the setting section class
     */
    public function getScopePath($subject = null): array;

    /**
     * Scope hierarchy to be displayed to user on settings edit page, filtered by the optional $searchString entered
     * by the user.
     *
     * If you return an empty list when $searchString === null, then the default settings editor page will not
     * show a scope selector at all.
     *
     * You are free to decide what scopes you return, or whether you return anything at all.
     *
     * If you are using a Voter, then the editor will filter out the not editable items from the returned hierarchy,
     * and if no editable scope remain, then the scope selector will not be displayed at all. So if you are returning
     * only a subset of matching scopes, then pay attention to return a subset that is editable according to your Voter.
     *
     * @param string|null $searchString The user typed in the scope search input, or null on initial page render.
     *
     * @return Item[] - returning null causes error, in the next relase the return type declaration will be **array**
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): ?array;
}