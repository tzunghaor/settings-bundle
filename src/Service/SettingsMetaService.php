<?php


namespace Tzunghaor\SettingsBundle\Service;


use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;

/**
 * Keeps track of setting metadata and scopes
 *
 * @internal it is not meant to be used in code outside TzunghaorSettingsBundle
 */
class SettingsMetaService implements CacheWarmerInterface
{
    private const SECTIONS_CACHE_PREFIX = 'tzunghaor_settings_sections_metadata';

    private Item $collectionItem;

    /**
     * @var SectionMetaData[] [$sectionClass => $metaData, ...]
     */
    private ?array $sectionMetaDataArray = null;

    private ?TranslatorInterface $translator = null;

    /**
     * false means no translation - in that case $this->translator should not be set
     * null means use default domain
     */
    private string|null|false $translationDomain = false;

    /**
     * @param array $sectionClasses [$sectionName => $sectionClass, ...]
     */
    public function __construct(
        private CacheInterface $cache,
        private MetaDataExtractor $metaDataExtractor,
        private ScopeProviderInterface $scopeProvider,
        private string $collectionName,
        private array $sectionClasses,
        // I would like to pass simply an Item instead three arguments, but DependencyInjection cannot do that
        ?string $collectionTitle = null,
        array $collectionExtra = []
    ) {
        $this->collectionItem = new Item($collectionName, $collectionTitle, [], $collectionExtra);
    }

    /**
     * If translation is configured for a setting collection, then Dependency Injection will call this method.
     */
    public function setUpTranslation(TranslatorInterface $translator, string|null $domain): void
    {
        $this->translator = $translator;
        $this->translationDomain = $domain;

        $this->collectionItem = new Item(
            $this->collectionItem->getName(),
            $translator->trans($this->collectionItem->getTitle(), domain: $domain),
            $this->collectionItem->getChildren(),
            $this->collectionItem->getExtra(),
        );
    }


    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * Returns metadata about all the setting sections in the current collection.
     *
     * @return SectionMetaData[] [$sectionClass => $metaData, ...]
     *
     * @throws Throwable
     */
    public function getSectionMetaDataArray(): array
    {
        if ($this->sectionMetaDataArray === null) {

            if ($this->translationDomain === false) {
                $this->sectionMetaDataArray = $this->getUntranslatedSectionMetaDataArray();
            } else {
                $locale = $this->translator?->getLocale();
                $translatedCacheKey = self::SECTIONS_CACHE_PREFIX . '.' . $locale . '.' . $this->collectionName;
                $this->sectionMetaDataArray = $this->cache->get(
                    $translatedCacheKey,
                    function (ItemInterface $item) {
                        $untranslatedSections = $this->getUntranslatedSectionMetaDataArray();
                        $sections = $this->translateSectionMetaDataArray($untranslatedSections);

                        uasort($sections, static function(SectionMetaData $a, SectionMetaData $b) {
                            return strcasecmp($a->getTitle(), $b->getTitle());
                        });

                        return $sections;
                    }
                );
            }
        }

        return $this->sectionMetaDataArray;
    }

    public function getTranslationDomain(): bool|string|null
    {
        return $this->translationDomain;
    }

    /**
     * @see self::getSectionMetaDataArray() this method is similar, but returns everything untranslated even if
     * translation is set up for this collection
     *
     * @return SectionMetaData[] [$sectionClass => $metaData, ...]
     *
     * @throws Throwable
     */
    private function getUntranslatedSectionMetaDataArray(): array
    {
        $cacheKey = self::SECTIONS_CACHE_PREFIX . '.' . $this->collectionName;

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) {
                $sections = [];
                foreach ($this->sectionClasses as $sectionName => $sectionClass) {
                    $sections[$sectionClass] = $this->metaDataExtractor
                        ->createSectionMetaData($sectionName, $sectionClass);
                }

                uasort($sections, static function(SectionMetaData $a, SectionMetaData $b) {
                    return strcasecmp($a->getTitle(), $b->getTitle());
                });

                return $sections;
            }
        );
    }

    /**
     * Returns a copy of $sections with translatable elements translated
     *
     * @param SectionMetaData[] $sections
     *
     * @return SectionMetaData[]
     */
    private function translateSectionMetaDataArray(array $sections): array
    {
        $translatedSections = [];
        foreach ($sections as $sectionName => $sectionMetaData) {
            $translatedSections[$sectionName] = new SectionMetaData(
                $sectionMetaData->getName(),
                $this->translator->trans($sectionMetaData->getTitle(), domain: $this->translationDomain),
                $sectionMetaData->getDataClass(),
                $sectionMetaData->getDescription(),
                $sectionMetaData->getSettingMetaDataArray(),
                $sectionMetaData->getExtra(),
            );
        }

        return $translatedSections;
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
     * @return Item of subject
     */
    public function getScope($subject = null): Item
    {
        return $this->scopeProvider->getScope($subject);
    }

    /**
     * Returns the setting metadata of a setting section class
     *
     * @param string $sectionClass
     *
     * @return SectionMetaData
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function getSectionMetaData(string $sectionClass): SectionMetaData
    {
        $sectionsMetaData = $this->getSectionMetaDataArray();
        if (!isset($sectionsMetaData[$sectionClass])) {
            $message = sprintf('Unknown setting section class "%s" in collection "%s"',
                               $sectionClass, $this->collectionName);

            throw new SettingsException($message);
        }

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
     * @throws Throwable
     */
    public function getSectionMetaDataByName(string $sectionName): SectionMetaData
    {
        if (!isset($this->sectionClasses[$sectionName])) {
            $message = sprintf('Unknown setting section name "%s" in collection "%s"',
                               $sectionName, $this->collectionName);

            throw new SettingsException($message);
        }

        $sectionClass = $this->sectionClasses[$sectionName];

        return $this->getSectionMetaData($sectionClass);
    }

    /**
     * @return Item basic info about collection
     */
    public function getCollectionItem(): Item
    {
        return $this->collectionItem;
    }

    // cache warmup functions:

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->getSectionMetaDataArray();

        return [];
    }

    /**
     * Returns arguments to check whether current user has right to edit settings pointed by $sectionAddress
     *
     * @return array [$attribute, $subject] to be used in Symfony AuthorizationCheckerInterface::isGranted($attribute, $subject)
     */
    public function getIsGrantedArguments(SettingSectionAddress $sectionAddress): array
    {
        if ($this->scopeProvider instanceof IsGrantedSupportingScopeProviderInterface) {
            if ($sectionAddress->getScope() === null) {
                $subject = null;
            } else {
                $subject =  $this->scopeProvider->getSubject($sectionAddress->getScope());
            }

            $attribute = $this->scopeProvider->getIsGrantedAttribute();
        } else {
            $subject = $sectionAddress;
            $attribute = 'edit';
        }

        return [$attribute, $subject];
    }
}