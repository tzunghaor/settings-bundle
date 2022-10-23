<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;
use Tzunghaor\SettingsBundle\DependencyInjection\Configuration;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Helper\ObjectHydrator;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\SettingsCacheEntry;

/**
 * This service retrieves and saves settings persisted in DB
 */
class SettingsService
{
    /**
     * @var SettingsMetaService
     */
    private $settingsMetaService;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SettingsStoreInterface
     */
    private $store;

    /**
     * @var SettingConverterInterface[]
     */
    private $dataConverters;

    public function __construct(
        SettingsMetaService $settingsMetaService,
        SettingsStoreInterface $store,
        iterable $dataConverters,
        CacheInterface $cache
    ) {
        $this->settingsMetaService = $settingsMetaService;
        $this->store = $store;
        $this->cache = $cache;
        $this->dataConverters = iterator_to_array($dataConverters);
    }

    /**
     * Retrieves the setting section object filled with values for the given scope
     *
     * @param string $sectionClass
     * @param mixed|null $subject Can be scope name or an object or anything your ScopeProvider supports.
     *                            If null, default scope is used.
     *
     * @return object
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getSection(string $sectionClass, $subject = null)
    {
        $scopeName = $this->settingsMetaService->getScope($subject)->getName();

        return $this->getCacheEntry($sectionClass, $scopeName)->getObject();
    }

    /**
     * Tells in which section are defined the setting values returned by self::getSection($sectionClass, $scope).
     * If a setting is not in the returned array, then that uses the default value defined in the section class.
     *
     * @param string $sectionClass
     * @param string $scope
     *
     * @return array [$settingName => $scopeName, ... ]
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getValueScopes(string $sectionClass, string $scope = ''): array
    {
        return $this->getCacheEntry($sectionClass, $scope)->getValueScopes();
    }

    /**
     * Saves settings to DB
     *
     * @param string $sectionClass
     * @param string $scope
     * @param array $values [$settingName => $value, ...] type of values should be what is defined in the section class
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function save(string $sectionClass, string $scope, array $values): void
    {
        $metaData = $this->settingsMetaService->getSectionMetaData($sectionClass);
        $sectionName = $metaData->getName();
        $settingMetaDataArray = $metaData->getSettingMetaDataArray();

        $valuesToPersist = $this->convertToPersistedValues($values, $settingMetaDataArray);
        $this->store->saveValues($sectionName, $scope, $valuesToPersist);

        $this->invalidateCache($sectionClass, $scope);
    }

    /**
     * Returns a SettingsCacheEntry, loads from DB if it is not loaded yet.
     *
     * @param string $sectionClass
     * @param string $scope
     *
     * @return SettingsCacheEntry
     *
     * @throws SettingsException
     * @throws Throwable
     */
    private function getCacheEntry(string $sectionClass, string $scope): SettingsCacheEntry
    {
        if (!$this->settingsMetaService->hasSectionClass($sectionClass)) {
            throw new SettingsException(sprintf('Unknown settings class "%s"', $sectionClass));
        }

        $cacheKey = $this->getCacheKey($sectionClass, $scope);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($sectionClass, $scope) {
                $scopePath = $this->settingsMetaService->getScopePath($scope);
                $cacheKeys = [];
                $parentEntry = null;

                if (!empty($scopePath) && !($this->cache instanceof TagAwareCacheInterface)) {
                    throw new SettingsException(
                        sprintf('Nested scopes require tag aware cache configured as %s.%s',
                                Configuration::CONFIG_ROOT, Configuration::CACHE)
                    );
                }

                foreach ($scopePath as $scopePathItem) {
                    $ancestorCacheKey = $this->getCacheKey($sectionClass, $scopePathItem);
                    $cacheKeys[] = $ancestorCacheKey;
                    $parentEntry = $this->getCacheEntry2($cacheKeys, $sectionClass, $scopePathItem, $parentEntry);
                }

                if (count($cacheKeys) > 0) {
                    // cached items are tagged with ancestor keys too, so that if an ancestor is updated, all
                    // descendants can be easily invalidated
                    $item->tag($cacheKeys);
                }

                return $this->loadCacheEntry($sectionClass, $scope, $parentEntry);
            }
        );
    }

    /**
     * Gets a cache entry - loads from DB if not yet cached.
     * To call this method, you already need to have the SettingsCacheEntry of the parent scope.
     * This method is used only in case of nested scopes alongside of getCacheEntry() to avoid recursion
     *
     * @param array $cacheKeys
     * @param string $sectionClass
     * @param string $scope
     * @param SettingsCacheEntry|null $parentEntry null for top-level scopes
     *
     * @return SettingsCacheEntry
     *
     * @throws Throwable
     */
    private function getCacheEntry2(
        array $cacheKeys,
        string $sectionClass,
        string $scope,
        ?SettingsCacheEntry $parentEntry
    ): SettingsCacheEntry {
        $cacheKey = end($cacheKeys);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($cacheKeys, $sectionClass, $scope, $parentEntry) {
                $item->tag($cacheKeys);

                return $this->loadCacheEntry($sectionClass, $scope, $parentEntry);
            }
        );
    }

    /**
     * Invalidates cached values for the given $sectionClass in the given $scope and all descendant scopes
     *
     * @param string $sectionClass
     * @param string $scope
     *
     * @throws Throwable
     */
    private function invalidateCache(string $sectionClass, string $scope): void
    {
        $cacheKey = $this->getCacheKey($sectionClass, $scope);
        $this->cache->delete($cacheKey);

        if ($this->cache instanceof TagAwareCacheInterface) {
            $this->cache->invalidateTags([$cacheKey]);
        }
    }

    /**
     * Loads settings for a section + scope pair from DB
     *
     * @param string $sectionClass
     * @param string $scope
     * @param SettingsCacheEntry|null $parentEntry values not saved for current scope should be inherited from this entry
     *
     * @return SettingsCacheEntry
     *
     * @throws \ReflectionException
     * @throws SettingsException
     * @throws Throwable
     */
    private function loadCacheEntry(string $sectionClass, string $scope, ?SettingsCacheEntry $parentEntry): SettingsCacheEntry
    {
        $sectionName = $this->settingsMetaService->getSectionMetaData($sectionClass)->getName();
        $metaDataArray = $this->settingsMetaService->getSectionMetaData($sectionClass)->getSettingMetaDataArray();

        $inheritedValues = $parentEntry ? $parentEntry->getValues() : [];
        $valueScopes = $parentEntry ? $parentEntry->getValueScopes() : [];

        $valuesInScope = $this->store->getValues($sectionName, $scope);
        $valuesInScope = $this->convertFromPersistedValue($valuesInScope, $metaDataArray);

        $values = array_merge($inheritedValues, $valuesInScope);
        foreach (array_keys($valuesInScope) as $settingName) {
            $valueScopes[$settingName] = $scope;
        }

        $settingsObject = ObjectHydrator::hydrate($sectionClass, $values);

        return new SettingsCacheEntry($values, $valueScopes, $settingsObject);
    }

    /**
     * Generates a cache key
     *
     * @param string $sectionClass
     * @param string $scope
     *
     * @return string
     */
    private function getCacheKey(string $sectionClass, string $scope): string
    {
        return 'tzunghaor_settings_section.' . str_replace('\\', '.', $sectionClass) . '..' . $scope;
    }


    /**
     * Converts the DB persisted values to the type defined in the section class
     *
     * @param array $persistedValues [$settingName => $value, ...]
     * @param SettingMetaData[] $settingMetaArray
     *
     * @return array
     *
     * @throws SettingsException
     */
    private function convertFromPersistedValue(array $persistedValues, array $settingMetaArray): array
    {
        $convertedValues = [];

        foreach ($persistedValues as $settingName => $persistedValue) {
            if (!array_key_exists($settingName, $settingMetaArray)) {
                // maybe an old setting still in DB
                continue;
            }

            $type = $settingMetaArray[$settingName]->getDataType();
            foreach ($this->dataConverters as $dataConverter) {
                if ($dataConverter->supports($type)) {
                    $convertedValues[$settingName] = $dataConverter->convertFromString($type, $persistedValue);

                    break;
                }
            }

            if (!isset($convertedValues[$settingName])) {
                throw new SettingsException(sprintf('Could not find converter for setting %s', $settingName));
            }
        }

        return $convertedValues;
    }

    /**
     * Converts the values of types defined in the setting section class to values that can be persisted in DB
     *
     * @param array $values [$settingName => $value, ...]
     * @param SettingMetaData[] $settingMetaArray
     *
     * @return array
     *
     * @throws SettingsException
     */
    private function convertToPersistedValues(array $values, array $settingMetaArray): array
    {
        $convertedValues = [];

        foreach ($values as $settingName => $value) {
            $type = $settingMetaArray[$settingName]->getDataType();
            foreach ($this->dataConverters as $dataConverter) {
                if ($dataConverter->supports($type)) {
                    $convertedValues[$settingName] = $dataConverter->convertToString($type, $value);

                    break;
                }
            }

            if (!isset($convertedValues[$settingName])) {
                throw new SettingsException(sprintf('Could not find converter for setting %s', $settingName));
            }
        }

        return $convertedValues;
    }
}