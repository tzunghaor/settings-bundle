<?php


namespace Tzunghaor\SettingsBundle\Service;


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
     * @var SectionMetaData[] [$sectionClass => $metaData, ...]
     */
    private $sectionMetaDataArray;

    /**
     * @var MetaDataExtractor
     */
    private $metaDataExtractor;
    /**
     * @var ScopeProviderInterface
     */
    private $scopeProvider;
    /**
     * @var string
     */
    private $collectionName;


    public function __construct(
        CacheInterface $cache,
        MetaDataExtractor $metaDataExtractor,
        ScopeProviderInterface $scopeProvider,
        string $collectionName,
        array $sectionClasses
    ) {
        $this->sectionClasses = $sectionClasses;
        $this->cache = $cache;
        $this->metaDataExtractor = $metaDataExtractor;
        $this->scopeProvider = $scopeProvider;
        $this->collectionName = $collectionName;
    }

    /**
     * @return SectionMetaData[] [$sectionClass => $metaData, ...]
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSectionMetaDataArray(): array
    {
        if ($this->sectionMetaDataArray === null) {
            $cacheKey = 'tzunghaor_settings_sections_metadata.' . $this->collectionName;
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
     * Returns the scope hierarchy to be displayed to the user
     *
     * @param string|null $searchString entered by user
     *
     * @return array nested array of scopes
     */
    public function getScopeDisplayHierarchy(?string $searchString = null): array
    {
        return $this->scopeProvider->getScopeDisplayHierarchy($searchString);
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
     * @param mixed $scope
     *
     * @return array inheritance path of the scope [$topScope, ... , $parentScope]
     */
    public function getScopePath($scope): array
    {
        return $this->scopeProvider->getScopePath($scope);
    }

    /**
     * @param mixed|null $subject Can be scope name or an object or anything you support.
     *                            If null, default scope name is returned.
     *
     * @return string scope name of subject
     */
    public function getScopeName($subject = null): string
    {
        return $this->scopeProvider->getScopeName($subject);
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
     * @throws
     */
    public function warmUp(string $cacheDir)
    {
        $this->getSectionMetaDataArray();
    }
}