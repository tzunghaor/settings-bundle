<?php

namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Tzunghaor\SettingsBundle\DependencyInjection\Configuration;
use Tzunghaor\SettingsBundle\Model\Scope;

/**
 * Scope provider using a static scope hierarchy - used when scopes are defined in configuration
 */
class StaticScopeProvider implements ScopeProviderInterface
{
    /**
     * @var Scope[]
     */
    private $scopeHierarchy;

    /**
     * @var Scope[] [$scopeName => Scope, ...]
     */
    private $scopeLookup;

    /**
     * @var string
     */
    private $defaultScope;

    /**
     * @param Scope[] $scopeHierarchy
     * @param string $defaultScope
     */
    public function __construct(array $scopeHierarchy, string $defaultScope)
    {
        // create scope lookup from config and pass it to the settings service
        $scopeLookup = [];
        $this->scopeHierarchy = $this->addToScopeLookup($scopeLookup, $scopeHierarchy, []);
        if (!array_key_exists($defaultScope, $scopeLookup)) {
            throw new \LogicException(sprintf('Default scope "%s" is not found in available scopes', $defaultScope));
        }

        $this->scopeLookup = $scopeLookup;
        $this->defaultScope = $defaultScope;
    }

    /**
     * @inheritdoc
     */
    public function getScopeName($subject = null): string
    {
        if ($subject === null) {
            return $this->defaultScope;
        }

        if (!array_key_exists($subject, $this->scopeLookup)) {
            throw new \DomainException(sprintf('Unknown scope "%s"', $subject));
        }

        return $subject;
    }

    /**
     * @inheritdoc
     */
    public function getScopePath($subject = null): array
    {
        return $this->scopeLookup[$subject ?? $this->defaultScope]->getPath();
    }

    /**
     * @inheritdoc
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): ?array
    {
        if (empty($searchString)) {
            return $this->scopeHierarchy;
        }

        return $this->buildDisplayHierarchy($searchString, $this->scopeHierarchy);
    }

    /**
     * Builds scope hierarchy subset that matches $searchString
     *
     * @param string $searchString
     * @param Scope[] $scopes
     *
     * @return array
     */
    private function buildDisplayHierarchy(string $searchString, array $scopes): array
    {
        $matchingScopes = [];

        foreach ($scopes as $scope) {
            $matchingChildren = $this->buildDisplayHierarchy($searchString, $scope->getChildren());

            // if neither this scope name, nor any of the children names match, then skip this scope
            if (empty($matchingChildren) && strpos($scope->getName(), $searchString) === false) {
                continue;
            }

            $matchingScopes[] = new Scope($scope->getName(), $matchingChildren, $scope->isPassive());
        }

        return $matchingScopes;
    }


    /**
     * Turns the hierarchical scope definition into flat lookup
     *
     * @param array $lookup
     * @param array $scopeDefinitions
     * @param array $scopePath name of ancestor scopes
     *
     * @return Scope[]
     */
    private function addToScopeLookup(array& $lookup, array $scopeDefinitions, $scopePath): array
    {
        // Symfony configuration doesn't fully support recursive structures, so we have to handle
        // default handling here
        $scopes = [];

        foreach ($scopeDefinitions as $scopeDefinition) {
            $scopeName = $scopeDefinition[Configuration::SCOPE_NAME];

            $childrenDef = $scopeDefinition[Configuration::SCOPE_CHILDREN] ?? null;
            $isPassive = $scopeDefinition[Configuration::SCOPE_PASSIVE] ?? false;

            if ($childrenDef !== null) {
                $childrenPath = $scopePath;
                if (!$isPassive) {
                    array_push($childrenPath, $scopeName);
                }

                $children = $this->addToScopeLookup($lookup, $childrenDef, $childrenPath);
            } else {
                $children = [];
            }

            $scope = new Scope(
                $scopeName,
                $children,
                $isPassive,
                $scopePath
            );

            if(array_key_exists($scopeName, $lookup)) {
                throw new InvalidConfigurationException('Scope name used multiple times: ' . $scopeName);
            }

            $scopes[] = $scope;
            $lookup[$scopeName] = $scope;
        }

        return $scopes;
    }
}