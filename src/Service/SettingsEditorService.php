<?php


namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Form\SettingsEditorType;
use Tzunghaor\SettingsBundle\Model\EditorUrlParameters;
use Tzunghaor\SettingsBundle\Helper\FormEditorHelper;
use Tzunghaor\SettingsBundle\Model\Item;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;
use Tzunghaor\SettingsBundle\Model\ViewItem;

/**
 * This service helps to create a page where settings can be edited
 */
class SettingsEditorService
{
    private ServiceLocator $settingsServiceLocator;

    private ServiceLocator $settingsMetaServiceLocator;

    private FormFactoryInterface $formFactory;

    private RouterInterface $router;

    private string $defaultCollectionName;

    /**
     * @var object|null
     * Optional Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        ServiceLocator $settingsServiceLocator,
        ServiceLocator $settingsMetaServiceLocator,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        string $defaultCollectionName
    ) {
        $this->settingsServiceLocator = $settingsServiceLocator;
        $this->settingsMetaServiceLocator = $settingsMetaServiceLocator;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->defaultCollectionName = $defaultCollectionName;
    }

    /**
     * Instance of AuthorizationCheckerInterface can be passed.
     * Not using that interface as type-hint to avoid hard dependency on symfony/security-core.
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
     * @param string|null $scopeName
     * @param string|null $collectionName
     *
     * @return SettingSectionAddress
     *
     * @throws Throwable
     */
    public function createSectionAddress(
        ?string $sectionName,
        ?string $scopeName = null,
        ?string $collectionName = null
    ): SettingSectionAddress {
        $collectionName = $collectionName ?? $this->defaultCollectionName;

        /** @var SettingsMetaService $settingsMetaService */
        $settingsMetaService = $this->settingsMetaServiceLocator->get($collectionName);
        $scopeName = $scopeName ?? $settingsMetaService->getScope(null)->getName();

        // if section name is not given, but there is only one section, then use it as default.
        if ($sectionName === null) {
            $sectionMetaDataArray = $settingsMetaService->getSectionMetaDataArray();
            if (count($sectionMetaDataArray) === 1) {
                $sectionName = reset($sectionMetaDataArray)->getName();
            }
        }

        return new SettingSectionAddress($collectionName, $scopeName, $sectionName);
    }

    /**
     * Creates a form to edit the given setting section in given scope - pre-fills with current settings
     *
     * @param SettingSectionAddress $sectionAddress
     * @return FormInterface|null returns null if $sectionAddress is not sufficient to identify a setting section
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function createForm(SettingSectionAddress $sectionAddress): ?FormInterface
    {
        if (!$sectionAddress->isComplete()) {
            return null;
        }

        $this->throwExceptionIfNotAuthorized($sectionAddress);

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($sectionAddress->getCollectionName());
        /** @var SettingsMetaService $settingsMetaService */
        $settingsMetaService = $this->settingsMetaServiceLocator->get($sectionAddress->getCollectionName());
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

    public function handleRequest(Request $request): FormEditorHelper
    {
        // Make the request handled as PATCH, so that non-submitted values are not cleared,
        // but remain the current inherited values.
        // The form defines PATCH, but it doesn't work without `http_method_override` set to true in the config,
        // and that is false by default, and I don't want to make people enable it only for this form.
        $request->setMethod(Request::METHOD_PATCH);

        $route = $request->attributes->get('_route');
        $fixedParameters = $request->attributes->get('fixedParameters', []);
        $searchRoute = $request->attributes->get('searchRoute', 'tzunghaor_settings_scope_search');
        $searchUrl = empty($searchRoute) ? null : $this->router->generate($searchRoute);
        $template = $request->attributes->get('template', '@TzunghaorSettings/editor_page.html.twig');
        $editorUrlParameters = new EditorUrlParameters($route, $fixedParameters);
        $sectionAddress = $this->createSectionAddress(
            $request->attributes->get('section'),
            $request->attributes->get('scope'),
            $request->attributes->get('collection')
        );
        $isSuccessfulSubmit = false;

        $form = $this->createForm($sectionAddress);

        // $form might be null if $section is not defined
        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->save($form->getData(), $sectionAddress);

                $isSuccessfulSubmit = true;
            }
        }

        return new FormEditorHelper(
            $isSuccessfulSubmit,
            $sectionAddress,
            $editorUrlParameters,
            $form,
            $searchUrl,
            $template,
            $fixedParameters,
        );
    }

    /**
     * Returns an array that contains the expected variables of editor_page.html.twig
     *
     * @param string|null $searchUrl url of scope search ajax call, null if that functionality should be disabled
     * @param array $fixedParameters these route parameters cannot be changed for this route
     *
     * @throws Throwable
     */
    public function getTwigContext(
        SettingSectionAddress $sectionAddress,
        EditorUrlParameters   $editorUrlParameters,
        ?FormInterface        $form,
        ?string               $searchUrl,
        array                 $fixedParameters = []
    ): array {
        $currentCollection = $sectionAddress->getCollectionName();
        $currentScopeName = $sectionAddress->getScope();
        $currentSection = $sectionAddress->getSectionName();

        /** @var SettingsMetaService $settingsMetaService */
        $settingsMetaService = $this->settingsMetaServiceLocator->get($currentCollection);
        $currentScope = $settingsMetaService->getScope($currentScopeName);

        $collections = [];
        if (!in_array('collection', $fixedParameters, true)) {
            $collections = array_keys($this->settingsServiceLocator->getProvidedServices());
            $collections = $this->prepareTwigCollections($collections, $editorUrlParameters);
        }

        $sections = [];
        if (!in_array('section', $fixedParameters, true)) {
            $sectionMetaDataArray = $settingsMetaService->getSectionMetaDataArray();
            $sections = $this->prepareTwigSections($sectionMetaDataArray, $sectionAddress, $editorUrlParameters);
        }

        $scopes = [];
        if (!in_array('scope', $fixedParameters, true)) {
            $scopes = $settingsMetaService->getScopeDisplayHierarchy();
            $scopes = $this->prepareTwigScopes($scopes, $sectionAddress, $editorUrlParameters);
        }

        return [
            'collections' => $collections,
            'currentCollection' => $currentCollection,
            'scopes' => $scopes,
            'currentScope' => $currentScope,
            'sections' => $sections,
            'currentSection' => $currentSection,
            'form' => $form === null ? null : $form->createView(),
            'linkRoute' => $editorUrlParameters->getRoute(),
            'searchUrl' => $searchUrl,
        ];
    }

    /**
     * Searches scopes matching $searchString and returns an array that contains the expected variables of list.html.twig
     */
    public function getSearchScopeTwigContext(
        string $searchString,
        SettingSectionAddress $sectionAddress,
        EditorUrlParameters $editorUrlParameters
    ): array {
        $currentCollection = $sectionAddress->getCollectionName();

        /** @var SettingsMetaService $settingsMetaService */
        $settingsMetaService = $this->settingsMetaServiceLocator->get($currentCollection);

        $scopes = $settingsMetaService->getScopeDisplayHierarchy($searchString);
        return [
            'currentName' => $sectionAddress->getScope(),
            'items' => $this->prepareTwigScopes($scopes, $sectionAddress, $editorUrlParameters),
        ];
    }

    /**
     * Filters the setting collections with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @return ViewItem[]
     */
    private function prepareTwigCollections(array $collectionNames, EditorUrlParameters $editorUrlParameters): array
    {
        $twigList = [];
        $checkIsGranted = $this->authorizationChecker !== null &&
            method_exists($this->authorizationChecker, 'isGranted');
        foreach ($collectionNames as $collectionName) {
            $voterSubject = new SettingSectionAddress($collectionName, null, null);

            $isGranted = !$checkIsGranted || $this->authorizationChecker->isGranted('edit', $voterSubject);
            if (!$isGranted) {
                continue;
            }

            /** @var SettingsMetaService $metaService */
            $metaService = $this->settingsMetaServiceLocator->get($collectionName);

            $routeParameters = [
                'collection' => $collectionName,
            ];
            $url = $this->router->generate($editorUrlParameters->getRoute(), $editorUrlParameters->filterParameters($routeParameters));

            $collectionItem = $metaService->getCollectionItem();
            $twigList[] = new ViewItem($collectionName, $url, $collectionItem->getExtra(), $collectionItem->getTitle());
        }

        return $twigList;
    }

    /**
     * Filters the setting sections with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @param SectionMetaData[] $sectionMetaDataArray
     *
     * @return ViewItem[]
     */
    private function prepareTwigSections(
        array                 $sectionMetaDataArray,
        SettingSectionAddress $sectionAddress,
        EditorUrlParameters $editorUrlParameters
    ): array {
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

            $routeParameters = [
                'collection' => $sectionAddress->getCollectionName(),
                'section' => $sectionName,
                'scope' => $sectionAddress->getScope(),
            ];
            $url = $this->router->generate($editorUrlParameters->getRoute(), $editorUrlParameters->filterParameters($routeParameters));

            $twigList[] = new ViewItem(
                $sectionName, $url, $sectionMetaData->getExtra(), $sectionMetaData->getTitle()
            );
        }

        usort($twigList, static function(ViewItem $a, ViewItem $b) {
            return strcasecmp($a->getTitle(), $b->getTitle());
        });

        return $twigList;
    }

    /**
     * Filters the setting scopes with isGranted if available.
     * Returns an array as expected in the twig templates.
     *
     * @param Item[] $scopes
     *
     * @return ViewItem[]
     */
    private function prepareTwigScopes(
        array                 $scopes,
        SettingSectionAddress $sectionAddress,
        EditorUrlParameters $editorUrlParameters
    ): array {
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
                $children = $this->prepareTwigScopes($children, $sectionAddress, $editorUrlParameters);
            }

            $needsLink =
                    $this->authorizationChecker === null ||
                    $this->authorizationChecker->isGranted('edit', $voterSubject)
            ;
            if ($needsLink) {
                $routeParameters = [
                    'collection' => $sectionAddress->getCollectionName(),
                    'section' => $sectionAddress->getSectionName(),
                    'scope' => $scopeName,
                ];
                $url = $this->router->generate($editorUrlParameters->getRoute(), $editorUrlParameters->filterParameters($routeParameters));
            } else {
                $url = null;

                if (count($children) === 0) {
                    continue;
                }
            }

            $twigList[] = new ViewItem($scopeName, $url, $scope->getExtra(), $scope->getTitle(), $children);
        }

        return $twigList;
    }


    /**
     * Saves the form data to database
     *
     * @param array $formData of SettingsEditorType
     * @param SettingSectionAddress $sectionAddress must be complete address
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function save(array $formData, SettingSectionAddress $sectionAddress): void
    {
        if (!$sectionAddress->isComplete()) {
            throw new \InvalidArgumentException('$sectionAddress must be complete');
        }

        $this->throwExceptionIfNotAuthorized($sectionAddress);

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($sectionAddress->getCollectionName());
        /** @var SettingsMetaService $settingsMetaService */
        $settingsMetaService = $this->settingsMetaServiceLocator->get($sectionAddress->getCollectionName());

        $sectionMeta = $settingsMetaService->getSectionMetaDataByName($sectionAddress->getSectionName());
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

        $settingsService->save($sectionMeta->getDataClass(), $sectionAddress->getScope(), $values);
    }

    /**
     * Throws an exception if authorizationChecker is set, and 'edit' is not granted for $sectionAddress
     *
     * @param SettingSectionAddress $sectionAddress
     */
    private function throwExceptionIfNotAuthorized(SettingSectionAddress $sectionAddress): void
    {
        if ($this->authorizationChecker === null) {
            return;
        }

        if ($this->authorizationChecker->isGranted('edit', $sectionAddress)) {
            return;
        }

        $message = 'Not allowed to edit these settings.';
        // if symfony-security is not installed, then throw an HttpException which semantically might not be correct
        $exceptionClass = 'Symfony\Component\Security\Core\Exception\AccessDeniedException';
        if (class_exists($exceptionClass)) {
            throw new $exceptionClass($message);
        }

        throw new AccessDeniedHttpException($message);
    }
}