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
     * @var string[][] [$scopeName => [$topAncestor, ...], ...]
     */
    private $scopePathLookup;

    /**
     * @var Scope
     */
    private $defaultScope;

    /**
     * @param Scope[] $scopeHierarchy
     * @param string $defaultScopeName
     */
    public function __construct(array $scopeHierarchy, string $defaultScopeName)
    {
        // create scope lookup from config and pass it to the settings service
        $scopeLookup = [];
        $scopePathLookup = [];
        $this->scopeHierarchy = $this->addToScopeLookup($scopeLookup, $scopePathLookup, $scopeHierarchy, []);
        if (!array_key_exists($defaultScopeName, $scopeLookup)) {
            throw new \LogicException(sprintf('Default scope "%s" is not found in available scopes', $defaultScopeName));
        }

        $this->scopeLookup = $scopeLookup;
        $this->defaultScope = $scopeLookup[$defaultScopeName];
        $this->scopePathLookup = $scopePathLookup;
    }

    /**
     * @inheritdoc
     */
    public function getScope($subject = null): Scope
    {
        if ($subject === null) {
            return $this->defaultScope;
        }

        if (!array_key_exists($subject, $this->scopeLookup)) {
            throw new \DomainException(sprintf('Unknown scope "%s"', $subject));
        }

        return $this->scopeLookup[$subject];
    }

    /**
     * @inheritdoc
     */
    public function getScopePath($subject = null): array
    {
        return $this->scopePathLookup[$subject ?? $this->defaultScope->getName()];
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
            $isMatching = strpos($scope->getName(), $searchString) !== false;

            // if neither this scope name, nor any of the children names match, then skip this scope
            if (empty($matchingChildren) && !$isMatching) {
                continue;
            }
            $passive = $scope->isPassive() || !$isMatching;

            $matchingScopes[] =
                new Scope($scope->getName(), $scope->getTitle(), $matchingChildren, $passive, $scope->getExtra());
        }

        return $matchingScopes;
    }


    /**
     * Turns the hierarchical scope definition into flat lookup
     *
     * @param array $lookup
     * @param array $pathLookup
     * @param array $scopeDefinitions
     * @param array $scopePath name of ancestor scopes
     *
     * @return Scope[] $scopeDefinitions tree turned into Scope object tree
     */
    private function addToScopeLookup(array& $lookup, array& $pathLookup, array $scopeDefinitions, $scopePath): array
    {
        // Symfony configuration doesn't fully support recursive structures,
        // so we have to take care of defaults ourselves
        $scopes = [];

        foreach ($scopeDefinitions as $scopeDefinition) {
            $scopeName = $scopeDefinition[Configuration::SCOPE_NAME];
            $childrenDef = $scopeDefinition[Configuration::SCOPE_CHILDREN] ?? null;
            $isPassive = $scopeDefinition[Configuration::SCOPE_PASSIVE] ?? false;
            $title = $scopeDefinition[Configuration::SCOPE_TITLE] ?? null;
            $handledDefinitionKeys = [
                Configuration::SCOPE_NAME, Configuration::SCOPE_TITLE, Configuration::SCOPE_CHILDREN,
                Configuration::SCOPE_PASSIVE
            ];

            if ($childrenDef !== null) {
                $childrenPath = $scopePath;
                if (!$isPassive) {
                    array_push($childrenPath, $scopeName);
                }

                $children = $this->addToScopeLookup($lookup, $pathLookup, $childrenDef, $childrenPath);
            } else {
                $children = [];
            }

            $extra = array_diff_key($scopeDefinition, array_flip($handledDefinitionKeys));
            $scope = new Scope(
                $scopeName,
                $title,
                $children,
                $isPassive,
                $extra
            );

            if(array_key_exists($scopeName, $lookup)) {
                throw new InvalidConfigurationException('Scope name used multiple times: ' . $scopeName);
            }

            $scopes[] = $scope;
            $lookup[$scopeName] = $scope;
            $pathLookup[$scopeName] = $scopePath;
        }

        return $scopes;
    }
}