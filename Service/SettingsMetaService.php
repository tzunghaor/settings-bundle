<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;

/**
 * Keeps track of setting metadata and scopes
 *
 * @internal it is not meant to be used outside of TzunghaorSettingsBundle
 */
class SettingsMetaService implements CacheWarmerInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array [$sectionName => $sectionClass, ...]
     */
    private $sectionClasses;

    /**
     * @var array
     */
    private $scopeLookup;

    /**
     * @var SectionMetaData[] [$sectionClass => $metaData, ...]
     */
    private $sectionMetaDataArray;

    /**
     * @var MetaDataExtractor
     */
    private $metaDataExtractor;

    /**
     * @var array
     */
    private $scopeHierarchy;


    public function __construct(
        CacheInterface $cache,
        MetaDataExtractor $metaDataExtractor,
        array $sectionClasses,
        array $scopeHierarchy
    ) {
        $this->sectionClasses = $sectionClasses;
        $this->cache = $cache;
        $this->metaDataExtractor = $metaDataExtractor;
        $this->scopeHierarchy = $scopeHierarchy;

        // create scope lookup from config and pass it to the settings service
        $scopeLookup = [];
        $this->addToScopeLookup($scopeLookup, $scopeHierarchy, []);
        $this->scopeLookup = $scopeLookup;
    }

    /**
     * @return SectionMetaData[] [$sectionClass => $metaData, ...]
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSectionMetaDataArray(): array
    {
        if ($this->sectionMetaDataArray === null) {
            $cacheKey = 'tzunghaor_settings_sections_metadata';
            $sectionClasses = $this->sectionClasses;

            $this->sectionMetaDataArray = $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($sectionClasses) {
                    $sections = [];
                    foreach ($sectionClasses as $sectionName => $sectionClass) {
                        $sections[$sectionClass] = $this->metaDataExtractor
                            ->createSectionMetaData($sectionName, $sectionClass);
                    }

                    return $sections;
                }
            );

        }

        return $this->sectionMetaDataArray;
    }

    /**
     * @return array nested array of scopes
     */
    public function getScopeHierarchy(): array
    {
        return $this->scopeHierarchy;
    }

    /**
     * @param string $scope
     *
     * @return bool true if scope with the given name exists
     */
    public function hasScope(string $scope): bool
    {
        return array_key_exists($scope, $this->scopeLookup);
    }

    /**
     * @param string $sectionClass
     *
     * @return bool true if the given string is the class name of a setting section
     */
    public function hasSectionClass(string $sectionClass): bool
    {
        return in_array($sectionClass, $this->sectionClasses, true);
    }

    /**
     * @param string $scope
     *
     * @return array inheritance path of the scope [$topScope, ... , $parentScope]
     */
    public function getScopePath(string $scope): array
    {
        return $this->scopeLookup[$scope];
    }

    /**
     * Returns the setting metadata of a setting section class
     *
     * @param string $sectionClass
     *
     * @return SectionMetaData
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSectionMetaData(string $sectionClass): SectionMetaData
    {
        $sectionsMetaData = $this->getSectionMetaDataArray();

        return $sectionsMetaData[$sectionClass];
    }

    /**
     * Returns the setting metadata of a setting section given by name
     *
     * @param string $sectionName
     *
     * @return SectionMetaData
     *
     * @throws SettingsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSectionMetaDataByName(string $sectionName): SectionMetaData
    {
        if (!isset($this->sectionClasses[$sectionName])) {
            throw new SettingsException(sprintf('Unknown setting section name "%s"', $sectionName));
        }

        $sectionClass = $this->sectionClasses[$sectionName];

        return $this->getSectionMetaData($sectionClass);
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
            $scopeName = $scope['name'];
            if(array_key_exists($scopeName, $lookup)) {
                throw new InvalidConfigurationException('Scope name used multiple times: ' . $scopeName);
            }

            $scopePath = $parents;
            $lookup[$scopeName] = $scopePath;

            if (isset($scope['children'])) {
                array_push($scopePath, $scopeName);
                $this->addToScopeLookup($lookup, $scope['children'], $scopePath);
            }
        }
    }

    // cache warmup functions:

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir)
    {
        $this->getSectionMetaDataArray();
    }
}