<?php

namespace TestApp\Service;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use TestApp\Entity\Project;
use TestApp\Entity\User;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Service\GrantedSupportScopeProviderInterface;

class CustomGrantScopeProvider implements GrantedSupportScopeProviderInterface
{
    private const PREFIX_USER = 'user';
    private const PREFIX_PROJECT = 'proj';

    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
    }

    public function getScope(mixed $subject = null): Item
    {
        // return default scope name if $subject is null
        if ($subject === null) {
            return $this->getDefaultScope();
        }

        // if $subject is string, then it is already a scope name
        if (is_string($subject)) {
            return new Item($subject);
        }

        // we can provide the scope of User and Project
        if ($subject instanceof User) {
            return new Item(self::PREFIX_USER . '-' . $subject->getId());
        } elseif ($subject instanceof Project) {
            return new Item(self::PREFIX_PROJECT . '-' . $subject->getName());
        }

        throw new \InvalidArgumentException('Cannot determine scope');
    }

    public function getScopePath(mixed $subject = null): array
    {
        // get default scope name if $subject is null
        $subject = $subject ?? $this->getDefaultScope()->getName();

        // if $subject is string, then it is already a scope name
        if (is_string($subject)) {
            [$prefix, $identifier] = explode('-', $subject, 2);

            // 'user' scopes don't have parent scope
            if ($prefix === self::PREFIX_USER) {
                return [];
            }

            // if it is not a user, then it is a project: its parent is its owner
            $subject = $this->em->find(Project::class, $identifier);
        }

        // we can provide the scope path of Project instances
        if ($subject instanceof Project) {
            return [self::PREFIX_USER . '-' . $subject->getOwner()->getId()];
        }

        throw new \InvalidArgumentException('Cannot determine scope path');
    }

    public function getScopeDisplayHierarchy(?string $searchString = null): array
    {
        // no scope display hierarchy is not necessary for the tests
        return [];
    }

    // ---------- isGranted supporting functions

    public function getSubject(string $scope): object
    {
        [$prefix, $identifier] = explode('-', $scope, 2);

        return match ($prefix) {
            self::PREFIX_USER => $this->em->find(User::class, $identifier),
            self::PREFIX_PROJECT => $this->em->find(Project::class, $identifier),
        };
    }

    public function getIsGrantedAttribute(): string
    {
        return 'edit_settings';
    }

    /**
     * @throws RuntimeException if there is no "authenticated" user
     */
    private function getDefaultScope(): Item
    {
        // normally we would get the current user from Symfony's security component, but for the test we take it
        // directly from the request, not even checking if the user exists
        $request = $this->requestStack->getCurrentRequest();
        $userId = $request->get('userId');

        if ($userId === null) {
            throw new RuntimeException('There is no "userId" in request.');
        }

        return new Item(self::PREFIX_USER . '-' . $userId);
    }
}