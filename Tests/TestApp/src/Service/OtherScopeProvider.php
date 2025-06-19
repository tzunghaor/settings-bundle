<?php

namespace TestApp\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Service\ScopeProviderInterface;
use TestApp\Entity\OtherSubject;

class OtherScopeProvider implements ScopeProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getScope($subject = null): Item
    {
        // return default scope name if $subject is null
        if ($subject === null) {
            return $this->getDefaultScope();
        }

        // if $subject is string, then it is already a scope name
        if (is_string($subject)) {
            return new Item($subject);
        }

        // we can provide the scope of OtherObject instances too
        if ($subject instanceof OtherSubject) {
            return new Item('name-' . $subject->getName());
        }

        throw new \InvalidArgumentException('Cannot determine scope');
    }

    /**
     * @inheritdoc
     */
    public function getScopePath($subject = null): array
    {
        // get default scope name if $subject is null
        $subject = $subject ?? $this->getDefaultScope()->getName();

        // if $subject is string, then it is already a scope name
        if (is_string($subject)) {
            [$type, $name] = explode('-', $subject);

            // 'group' scopes are root scopes
            if ($type === 'group') {
                return [];
            }

            // 'name' scope belongs to an OtherSubject entity, whose parent scope is its group
            $subject = $this->em->find(OtherSubject::class, $name);
        }

        // we can provide the scope path of OtherObject instances too
        if ($subject instanceof OtherSubject) {
            return ['group-' . $subject->getGroup()];
        }

        throw new \InvalidArgumentException('Cannot determine scope path');
    }

    /**
     * @inheritdoc
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): array
    {
        $hierarchy = [];

        // if $searchString is null, then display all group scopes
        if ($searchString === null) {
            $dql = sprintf('select distinct s.group from %s s', OtherSubject::class);
            $results = $this->em->createQuery($dql)->execute();
            foreach ($results as $result) {
                $hierarchy[] = new Item('group-' . $result['group']);
            }
        }

        return $hierarchy;
    }

    /**
     * In this example default scope is the 'scopeSubject' param of the request, or 'group-foo' if request doesn't specify it.
     * You could also use e.g. the current user from TokenStorage.
     *
     * @return Item
     */
    private function getDefaultScope(): Item
    {
        $defaultScopeName = 'group-foo';

        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest === null) {
            $scopeName = $defaultScopeName;
        } else {
            $scopeName = $currentRequest->get('scopeSubject', $defaultScopeName);
        }

        return new Item($scopeName);
    }
}