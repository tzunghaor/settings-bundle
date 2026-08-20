<?php

namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tzunghaor\SettingsBundle\DependencyInjection\Configuration;
use Tzunghaor\SettingsBundle\Model\Item;

/**
 * Scope provider using a static scope hierarchy - used when scopes are defined in configuration
 */
class StaticScopeProvider implements ScopeProviderInterface
{
    /**
     * we save data from config files and build other structures only on-demand, when the optionally
     * injected $translator is likely to have default locale already set
     *
     * @var array<mixed>
     */
    private array $configScopeHierarchy;

    private string $defaultScopeName;

    /**
     * @var Item[]
     */
    private ?array $scopeHierarchy = null;

    /**
     * @var Item[] [$scopeName => Scope, ...]
     */
    private ?array $scopeLookup = null;

    /**
     * @var string[][] [$scopeName => [$topAncestor, ...], ...]
     */
    private ?array $scopePathLookup = null;

    private ?Item $defaultScope = null;

    private ?TranslatorInterface $translator = null;

    /**
     * false means no translation - in that case $this->translator should not be set
     * null means use default domain
     */
    private string|null|false $translationDomain = false;

    /**
     * @param array<mixed> $scopeHierarchy array of scopes coming from bundle configuration
     */
    public function __construct(array $scopeHierarchy, string $defaultScopeName)
    {
        $this->configScopeHierarchy = $scopeHierarchy;
        $this->defaultScopeName = $defaultScopeName;
    }

    /**
     * If translation is configured for a setting collection, then Dependency Injection will inject these parameters
     */
    public function setUpTranslation(TranslatorInterface $translator, string|null $domain): void
    {
        $this->translator = $translator;
        $this->translationDomain = $domain;
    }

    /**
     * @inheritdoc
     */
    public function getScope($subject = null): Item
    {
        $this->ensureDataStructuresAreReady();

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
        $this->ensureDataStructuresAreReady();

        return $this->scopePathLookup[$subject ?? $this->defaultScope->getName()];
    }

    /**
     * @inheritdoc
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): array
    {
        $this->ensureDataStructuresAreReady();

        if (empty($searchString)) {
            return $this->scopeHierarchy;
        }

        return $this->buildDisplayHierarchy($searchString, $this->scopeHierarchy);
    }

    /**
     * If data structures are not yet build, then it builds them from the saved config data
     */
    private function ensureDataStructuresAreReady(): void
    {
        if ($this->scopeHierarchy !== null) {
            return;
        }

        // create scope lookup from config and pass it to the settings service
        $scopeLookup = [];
        $scopePathLookup = [];
        $this->scopeHierarchy = $this->addToScopeLookup(
            $scopeLookup,
            $scopePathLookup,
            $this->configScopeHierarchy,
            []
        );

        if (!array_key_exists($this->defaultScopeName, $scopeLookup)) {
            throw new \LogicException(sprintf(
                'Default scope "%s" is not found in available scopes',
                $this->defaultScopeName
            ));
        }

        $this->scopeLookup = $scopeLookup;
        $this->defaultScope = $scopeLookup[$this->defaultScopeName];
        $this->scopePathLookup = $scopePathLookup;
    }


    /**
     * Builds scope hierarchy subset that matches $searchString
     *
     * @param string $searchString
     * @param Item[] $scopes
     *
     * @return Item[]
     */
    private function buildDisplayHierarchy(string $searchString, array $scopes): array
    {
        $matchingScopes = [];

        foreach ($scopes as $scope) {
            $matchingChildren = $this->buildDisplayHierarchy($searchString, $scope->getChildren());
            $isMatching = mb_stripos($scope->getTitle(), $searchString) !== false;

            // if neither this scope name, nor any of the children names match, then skip this scope
            if (empty($matchingChildren) && !$isMatching) {
                continue;
            }

            $matchingScopes[] =
                new Item($scope->getName(), $scope->getTitle(), $matchingChildren, $scope->getExtra());
        }

        return $matchingScopes;
    }


    /**
     * Turns the hierarchical scope definition into flat lookup
     *
     * @param Item[] $lookup
     * @param string[][] $pathLookup
     * @param array<array<string, mixed>> $scopeDefinitions
     * @param string[] $scopePath name of ancestor scopes
     *
     * @return Item[] $scopeDefinitions tree turned into Scope object tree
     */
    private function addToScopeLookup(array& $lookup, array& $pathLookup, array $scopeDefinitions, array $scopePath): array
    {
        // Symfony configuration doesn't fully support recursive structures,
        // so we have to take care of defaults ourselves
        $scopes = [];

        foreach ($scopeDefinitions as $scopeDefinition) {
            $scopeName = $scopeDefinition[Configuration::NAME];
            $childrenDef = $scopeDefinition[Configuration::CHILDREN] ?? null;
            $title = $scopeDefinition[Configuration::TITLE] ?? null;
            if ($this->translator) {
                $title = $this->translator->trans($title ?? $scopeName, domain: $this->translationDomain);
            }

            if ($childrenDef !== null) {
                $childrenPath = $scopePath;
                $childrenPath[] = $scopeName;

                $children = $this->addToScopeLookup($lookup, $pathLookup, $childrenDef, $childrenPath);
            } else {
                $children = [];
            }

            $extra = $scopeDefinition[Configuration::EXTRA] ?? [];
            $scope = new Item(
                $scopeName,
                $title,
                $children,
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