<?php

namespace TestApp\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use TestApp\Entity\Project;
use TestApp\Entity\User;
use TestApp\Service\CustomGrantScopeProvider;

/**
 * Allows "edit_settings" (@see CustomGrantScopeProvider::getIsGrantedAttribute()) for the user set in request query
 * parameter, and that user's projects
 */
class UserProjectVoter extends Voter
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'edit_settings' && ($subject instanceof User || $subject instanceof Project);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // normally we would get the authenticated user from $token, but for this test we just take it from query param
        $authenticatedUserId = $this->requestStack->getCurrentRequest()->query->get('user');

        if ($subject instanceof User) {
            return $subject->getId() === $authenticatedUserId;
        } elseif ($subject instanceof Project) {
            return $subject->getOwner()->getId() === $authenticatedUserId;
        }

        return false;
    }
}