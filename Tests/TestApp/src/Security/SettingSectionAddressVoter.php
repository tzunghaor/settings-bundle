<?php

namespace TestApp\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;

/**
 * This voter denies access to scopes pointed by SettingSectionAddress if the scope name contains "forbidden"
 * and "allow" query parameter is not present in the HTTP request.
 */
class SettingSectionAddressVoter extends Voter
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof SettingSectionAddress && $attribute === 'edit';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->requestStack->getCurrentRequest()->query->get('allow') !== null) {
            return true;
        }

        $scopeName = $subject->getScope();

        return $scopeName === null || !str_contains($scopeName, 'forbidden');
    }
}