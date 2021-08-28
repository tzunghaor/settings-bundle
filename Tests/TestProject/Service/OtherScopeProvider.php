<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tzunghaor\SettingsBundle\Model\Scope;
use Tzunghaor\SettingsBundle\Service\ScopeProviderInterface;
use Tzunghaor\SettingsBundle\Tests\TestProject\Entity\OtherSubject;

class OtherScopeProvider implements ScopeProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritdoc
     */
    public function getScopeName($subject = null): string
    {
        // return default scope name if $subject is null
        if ($subject === null) {
            return $this->getDefaultScope();
        }

        // if $subject is string, then it is already a scope name
        if (is_string($subject)) {
            return $subject;
        }

        // we can provide the scope of OtherObject instances too
        if ($subject instanceof OtherSubject) {
            return 'name-' . $subject->getName();
        }

        throw new \InvalidArgumentException('Cannot determine scope');
    }

    /**
     * @inheritdoc
     */
    public function getScopePath($subject = null): array
    {
        // get default scope name if $subject is null
        $subject = $subject ?? $this->getDefaultScope();

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
    public function getScopeDisplayHierarchy(?string $searchString = null): ?array
    {
        $hierarchy = [];

        // if $searchString is null, then display all group scopes
        if ($searchString === null) {
            $dql = sprintf('select distinct s.group from %s s', OtherSubject::class);
            $results = $this->em->createQuery($dql)->execute();
            foreach ($results as $result) {
                $hierarchy[] = new Scope('group-' . $result['group']);
            }
        }

        return $hierarchy;
    }

    /**
     * In this example default scope is the 'scopeSubject' param of the request, or 'group-foo' if request doesn't specify it.
     * You could also use e.g. the current user from TokenStorage.
     *
     * @return string
     */
    private function getDefaultScope(): string
    {
        $defaultScope = 'group-foo';

        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest === null) {
            return $defaultScope;
        }

        return $currentRequest->get('scopeSubject', $defaultScope);
    }
}