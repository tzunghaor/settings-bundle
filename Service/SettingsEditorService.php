<?php


namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tzunghaor\SettingsBundle\Form\SettingsEditorType;
use Tzunghaor\SettingsBundle\Model\Scope;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;

/**
 * This service helps to create a page where settings can be edited
 */
class SettingsEditorService
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var ServiceLocator
     */
    private $settingsServiceLocator;
    /**
     * @var string
     */
    private $defaultCollectionName;
    /**
     * @var AuthorizationChecker|null
     */
    private $authorizationChecker;

    public function __construct(
        ServiceLocator $settingsServiceLocator,
        FormFactoryInterface $formFactory,
        string $defaultCollectionName
    ) {
        $this->formFactory = $formFactory;
        $this->settingsServiceLocator = $settingsServiceLocator;
        $this->defaultCollectionName = $defaultCollectionName;
    }

    /**
     * Instance of AuthorizationCheckerInterface can be passed. Not using it as type-hint to avoid hard dependency
     * on symfony/security-core
     *
     * @param object $authorizationChecker
     */
    public function setAuthorizationChecker($authorizationChecker): void
    {
        if (!method_exists($authorizationChecker, 'isGranted')) {
            throw new \InvalidArgumentException('$authorizationChecker must have isGranted() method');
        }

        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Factory method to create a SettingSectionAddress, fills in null values with default values if possible
     *
     * @param string|null $sectionName
     * @param string|null $scope
     * @param string|null $collectionName
     *
     * @return SettingSectionAddress
     */
    public function createSectionAddress(
        ?string $sectionName,
        ?string $scope = null,
        ?string $collectionName = null
    ): SettingSectionAddress {
        $collectionName = $collectionName ?? $this->defaultCollectionName;
        if ($scope === null) {
            // get default scope
            /** @var SettingsService $settingsService */
            $settingsService = $this->settingsServiceLocator->get($collectionName);
            $scope = $settingsService->getSettingsMetaService()->getScopeName(null);
        }

        return new SettingSectionAddress($collectionName, $scope, $sectionName);
    }

    /**
     * Creates a form to edit the given setting section in given scope - pre-fills with current settings
     *
     * @param SettingSectionAddress $sectionAddress
     * @return FormInterface|null returns null if $sectionAddress is not sufficient to identify a setting section
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     */
    public function createForm(SettingSectionAddress $sectionAddress): ?FormInterface
    {
        if ($sectionAddress->getSectionName() === null) {
            return null;
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($sectionAddress->getCollectionName());
        $settingsMetaService = $settingsService->getSettingsMetaService();
        $scope = $sectionAddress->getScope();

        $sectionMeta = $settingsMetaService->getSectionMetaDataByName($sectionAddress->getSectionName());
        $sectionClass = $sectionMeta->getDataClass();
        $sectionSettings = $settingsService->getSection($sectionClass, $scope);
        $valueScopes = $settingsService->getValueScopes($sectionClass, $scope);
        $scopePath = $settingsMetaService->getScopePath($scope);
        $parentScope = end($scopePath);
        $parentSettings = $parentScope === false ?
            new $sectionClass() : $settingsService->getSection($sectionClass, $parentScope);

        $inCurrentScope = [];
        foreach ($sectionMeta->getSettingMetaDataArray() as $settingMeta) {
            $settingName = $settingMeta->getName();
            $inCurrentScope[$settingName] = ($valueScopes[$settingName] ?? '') === $scope ? 1 : 0;
        }

        $formData = [
            SettingsEditorType::DATA_SETTINGS => $sectionSettings,
            SettingsEditorType::DATA_PARENT_SETTINGS => $parentSettings,
            SettingsEditorType::DATA_IN_SCOPE => $inCurrentScope,
        ];

        return $this->formFactory->create(SettingsEditorType::class, $formData, [
            SettingsEditorType::OPTION_SECTION_META => $sectionMeta,
            'label' => $sectionMeta->getTitle(),
            'help' => $sectionMeta->getDescription(),
        ]);
    }

    /**
     * Returns an array that contains the expected variables of editor_page.html.twig
     *
     * @param SettingSectionAddress $sectionAddress
     * @param callable $urlGenerator function([$routeParam1, ...]) => $url
     * @param FormInterface $form
     * @param string|null $route name of editor route
     * @param array $fixedParameters these route parameters cannot be changed for this route
     *
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getTwigContext(
        SettingSectionAddress $sectionAddress,
        callable $urlGenerator,
        ?FormInterface $form,
        ?string $route = '',
        array $fixedParameters = []
    ): array {
        $currentCollection = $sectionAddress->getCollectionName();
        $currentScope = $sectionAddress->getScope();
        $currentSection = $sectionAddress->getSectionName();

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($currentCollection);
        $settingsMetaService = $settingsService->getSettingsMetaService();

        $collections = [];
        if (!in_array('collection', $fixedParameters, true)) {
            $collections = array_keys($this->settingsServiceLocator->getProvidedServices());
            $collections = $this->prepareTwigCollections($collections, $urlGenerator);
        }

        $sections = [];
        if (!in_array('section', $fixedParameters, true)) {
            $sectionMetaDataArray = $settingsMetaService->getSectionMetaDataArray();
            $sections = $this->prepareTwigSections($sectionMetaDataArray, $sectionAddress, $urlGenerator);
        }

        $scopes = [];
        if (!in_array('scope', $fixedParameters, true)) {
            $scopes = $settingsMetaService->getScopeDisplayHierarchy();
            $scopes = $this->prepareTwigScopes($scopes, $sectionAddress, $urlGenerator);
        }

        return [
            'collections' => $collections,
            'currentCollection' => $currentCollection,
            'scopes' => $scopes,
            'currentScope' => $currentScope,
            'sections' => $sections,
            'currentSection' => $currentSection,
            'form' => $form === null ? null : $form->createView(),
            'linkRoute' => $route,
        ];
    }

    /**
     * Searches scopes matching $searchString and returns an array that contains the expected variables of
     * scope_list.html.twig
     *
     * @param string $searchString
     * @param SettingSectionAddress $sectionAddress
     * @param callable $urlGenerator
     *
     * @return array
     */
    public function getSearchScopeTwigContext(
        string $searchString,
        SettingSectionAddress $sectionAddress,
        callable $urlGenerator
    ): array {
        $currentCollection = $sectionAddress->getCollectionName();

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($currentCollection);
        $settingsMetaService = $settingsService->getSettingsMetaService();

        $scopes = $settingsMetaService->getScopeDisplayHierarchy($searchString);
        return [
            'currentScope' => $sectionAddress->getScope(),
            'scopes' => $this->prepareTwigScopes($scopes, $sectionAddress, $urlGenerator),
        ];
    }

    /**
     * Filters the setting collections with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @param array $collectionNames
     * @param callable $urlGenerator
     *
     * @return array
     */
    private function prepareTwigCollections(array $collectionNames, callable $urlGenerator): array
    {
        $twigList = [];
        foreach ($collectionNames as $collectionName) {
            $voterSubject = new SettingSectionAddress($collectionName, null, null);

            $isGranted = $this->authorizationChecker === null ||
                $this->authorizationChecker->isGranted('edit', $voterSubject);
            if (!$isGranted) {
                continue;
            }

            $url = $urlGenerator([
                'collection' => $collectionName,
            ]);

            $twigList[] = [
                'name' => $collectionName,
                'title' => $collectionName,
                'url' => $url,
            ];
        }

        return $twigList;
    }

    /**
     * Filters the setting sections with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @param SectionMetaData[] $sectionMetaDataArray
     * @param SettingSectionAddress $sectionAddress
     * @param callable $urlGenerator
     *
     * @return array
     */
    private function prepareTwigSections(array $sectionMetaDataArray, SettingSectionAddress $sectionAddress, callable $urlGenerator): array
    {
        $twigList = [];
        foreach ($sectionMetaDataArray as $sectionMetaData) {
            $sectionName = $sectionMetaData->getName();
            $voterSubject = new SettingSectionAddress(
                $sectionAddress->getCollectionName(),
                $sectionAddress->getScope(),
                $sectionName
            );

            $isGranted = $this->authorizationChecker === null ||
                $this->authorizationChecker->isGranted('edit', $voterSubject);
            if (!$isGranted) {
                continue;
            }

            $url = $urlGenerator([
                'collection' => $sectionAddress->getCollectionName(),
                'section' => $sectionName,
                'scope' => $sectionAddress->getScope(),
            ]);

            $twigList[] = [
                'name' => $sectionName,
                'title' => $sectionMetaData->getTitle(),
                'url' => $url,
            ];
        }

        return $twigList;
    }

    /**
     * Filters the setting scopes with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @param Scope[] $scopes
     * @param SettingSectionAddress $sectionAddress
     * @param callable $urlGenerator
     *
     * @return array
     */
    private function prepareTwigScopes(array $scopes, SettingSectionAddress $sectionAddress, callable $urlGenerator): array
    {
        $twigList = [];
        foreach ($scopes as $scope) {
            $scopeName = $scope->getName();
            $voterSubject = new SettingSectionAddress(
                $sectionAddress->getCollectionName(),
                $scopeName,
                $sectionAddress->getSectionName()
            );

            $children = $scope->getChildren();
            if (!empty($children)) {
                $children = $this->prepareTwigScopes($children, $sectionAddress, $urlGenerator);
            }

            $isGranted = $this->authorizationChecker === null ||
                $this->authorizationChecker->isGranted('edit', $voterSubject);
            if ($isGranted) {
                $url = $urlGenerator([
                    'collection' => $sectionAddress->getCollectionName(),
                    'section' => $sectionAddress->getSectionName(),
                    'scope' => $scopeName,
                ]);
            } else {
                $url = null;

                if (count($children) === 0) {
                    continue;
                }
            }

            $twigList[] = [
                'name' => $scopeName,
                'title' => $scopeName,
                'children' => $children,
                'url' => $url,
            ];
        }

        return $twigList;
    }


    /**
     * Saves the form data to database
     *
     * @param array $formData of SettingsEditorType
     * @param string $sectionName
     * @param string $scope
     * @param string|null $collectionName if null then default collection is used
     *
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save(array $formData, string $sectionName, string $scope, ?string $collectionName = null): void
    {
        $collectionName = $collectionName ?? $this->defaultCollectionName;
        if ($this->authorizationChecker !== null) {
            $subject = new SettingSectionAddress($collectionName, $scope, $sectionName);
            if (!$this->authorizationChecker->isGranted('edit', $subject)) {
                throw new \RuntimeException('Not allowed to edit these settings.');
            }
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($collectionName);
        $settingsMetaService = $settingsService->getSettingsMetaService();

        $sectionMeta = $settingsMetaService->getSectionMetaDataByName($sectionName);
        $settingMetaArray = $sectionMeta->getSettingMetaDataArray();

        $sectionObject = $formData[SettingsEditorType::DATA_SETTINGS];
        $inCurrentScope = $formData[SettingsEditorType::DATA_IN_SCOPE];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $values = [];

        foreach ($settingMetaArray as $settingMeta) {
            $settingName = $settingMeta->getName();
            if (!isset($inCurrentScope[$settingName]) || ($inCurrentScope[$settingName] !== true)) {
                continue;
            }

            $values[$settingName] = $propertyAccessor->getValue($sectionObject, $settingName);
        }

        $settingsService->save($sectionMeta->getDataClass(), $scope, $values);
    }
}