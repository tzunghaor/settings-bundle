<?php

namespace TestApp\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;

/**
 * This fake "AuthorizationChecker" simulates that a security voter denies access to scopes containing "forbidden",
 * unless "allow" query parameter is present in the HTTP request.
 * In real projects you should implement your security logic in a voter, see Resources/doc/voter.md
 */
class ForbiddenAuthorizationChecker
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $what
     * @param SettingSectionAddress $subject
     *
     * @return bool
     */
    public function isGranted($what, $subject): bool
    {
        if ($this->requestStack->getCurrentRequest()->query->get('allow') !== null) {
            return true;
        }

        $scopeName = $subject->getScope();

        return $scopeName === null || strpos($scopeName, 'forbidden') === false;
    }
}