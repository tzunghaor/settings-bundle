<?php

namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Tzunghaor\SettingsBundle\DependencyInjection\Configuration;

/**
 * Scope provider using a static scope hierarchy - used when scopes are defined in configuration
 */
class StaticScopeProvider implements ScopeProviderInterface
{
    /**
     * @var array
     */
    private $scopeHierarchy;

    /**
     * @var array $scope => inheritance path of the subject [$topScope, ... , $parentScope]
     */
    private $scopeLookup;

    /**
     * @var string
     */
    private $defaultScope;

    public function __construct(array $scopeHierarchy, string $defaultScope)
    {
        $this->scopeHierarchy = $scopeHierarchy;

        // create scope lookup from config and pass it to the settings service
        $scopeLookup = [];
        $this->addToScopeLookup($scopeLookup, $scopeHierarchy, []);
        $this->scopeLookup = $scopeLookup;
        $this->defaultScope = $defaultScope;
    }

    /**
     * @inheritdoc
     */
    public function getScope($subject = null): string
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
        return $this->scopeLookup[$subject ?? $this->defaultScope];
    }

    /**
     * @inheritdoc
     */
    public function getScopeHierarchy(?string $searchString = null): ?array
    {
        if (empty($searchString)) {
            return $this->scopeHierarchy;
        }

        $matchingHierarchy = [];
        $matchingHierarchyLookup = [];

        foreach ($this->scopeLookup as $scopeName => $scopePath) {
            if (strpos($scopeName, $searchString) === false) {
                continue;
            }

            $currentLevel = &$matchingHierarchy;

            foreach ($scopePath as $currentScopeName) {
                if (!array_key_exists($currentScopeName, $matchingHierarchyLookup)) {
                    $matchingHierarchyLookup[$currentScopeName] = [
                        Configuration::SCOPE_NAME => $currentScopeName,
                        Configuration::SCOPE_CHILDREN => [],
                    ];
                    $currentLevel[] = &$matchingHierarchyLookup[$currentScopeName];
                }

                if (!array_key_exists(Configuration::SCOPE_CHILDREN, $matchingHierarchyLookup[$currentScopeName])) {
                    $matchingHierarchyLookup[$currentScopeName][Configuration::SCOPE_CHILDREN] = [];
                }

                // next level becomes current
                $currentLevel = &$matchingHierarchyLookup[$currentScopeName][Configuration::SCOPE_CHILDREN];
            }

            if (!array_key_exists($scopeName, $currentLevel)) {
                $matchingHierarchyLookup[$scopeName] = [
                    Configuration::SCOPE_NAME => $scopeName,
                ];
                $currentLevel[] = &$matchingHierarchyLookup[$scopeName];
            }
        }

        return $matchingHierarchy;
    }


    /**
     * Turns the hierarchical scope definition into flat lookup
     *
     * @param array $lookup
     * @param array $scopeHierarchy
     * @param array $parents
     */
    private function addToScopeLookup(array& $lookup, array $scopeHierarchy, array $parents): void
    {
        foreach ($scopeHierarchy as $scope) {
            $scopeName = $scope[Configuration::SCOPE_NAME];
            if(array_key_exists($scopeName, $lookup)) {
                throw new InvalidConfigurationException('Scope name used multiple times: ' . $scopeName);
            }

            $scopePath = $parents;
            $lookup[$scopeName] = $scopePath;

            if (isset($scope[Configuration::SCOPE_CHILDREN])) {
                array_push($scopePath, $scopeName);
                $this->addToScopeLookup($lookup, $scope[Configuration::SCOPE_CHILDREN], $scopePath);
            }
        }
    }
}