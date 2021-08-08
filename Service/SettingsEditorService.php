<?php


namespace Tzunghaor\SettingsBundle\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tzunghaor\SettingsBundle\Form\SettingsEditorType;

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
     * Creates a form to edit the given setting section in given scope - pre-fills with current settings
     *
     * @param string $sectionName
     * @param string $scope
     * @param string|null $collectionName if null then default collection is used
     *
     * @return FormInterface|null returns null if $sectionName + $scope is not sufficient
     *
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function createForm(string $sectionName, string $scope, ?string $collectionName = null): ?FormInterface
    {
        if ($sectionName === '') {
            return null;
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($collectionName ?? $this->defaultCollectionName);
        $settingsMetaService = $settingsService->getSettingsMetaService();

        $sectionMeta = $settingsMetaService->getSectionMetaDataByName($sectionName);
        $sectionClass = $sectionMeta->getDataClass();
        $sectionSettings = $settingsService->getSection($sectionClass, $scope);
        $valueScopes = $settingsService->getValueScopes($sectionClass, $scope);
        $inCurrentScope = [];
        foreach ($sectionMeta->getSettingMetaDataArray() as $settingMeta) {
            $settingName = $settingMeta->getName();
            $inCurrentScope[$settingName] = ($valueScopes[$settingName] ?? '') === $scope ? 1 : 0;
        }

        $formData = [
            SettingsEditorType::DATA_SETTINGS => $sectionSettings,
            SettingsEditorType::DATA_IN_SCOPE => $inCurrentScope
        ];

        return $this->formFactory->create(SettingsEditorType::class, $formData, [
            SettingsEditorType::OPTION_SECTION_META => $sectionMeta,
            'label' => $sectionMeta->getTitle(),
            'help' => $sectionMeta->getDescription(),
        ]);
    }

    /**
     * Returns an array that contains the expected variables of editor.html.twig
     *
     * @param string $sectionName
     * @param string $scope passe empty string to use default scope
     * @param FormInterface $form
     * @param string|null $collectionName if null then default collection is used
     * @param string|null $route name of editor route
     * @param array $fixedParameters these route parameters cannot be changed for this route
     *
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     */
    public function getTwigContext(
        string $sectionName,
        string $scope,
        ?FormInterface $form,
        ?string $collectionName = null,
        ?string $route = '',
        array $fixedParameters = []
    ): array {
        $currentCollection = $collectionName ?? $this->defaultCollectionName;
        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($currentCollection);
        $settingsMetaService = $settingsService->getSettingsMetaService();

        $sections = in_array('section', $fixedParameters, true) ?
            [] : $settingsMetaService->getSectionMetaDataArray();
        $scopes = in_array('scope', $fixedParameters, true) ?
            [] : $settingsMetaService->getScopeHierarchy();
        $sectionMeta = $sectionName === '' ? null : $settingsMetaService->getSectionMetaDataByName($sectionName);
        $currentScope = $scope === '' ? $settingsMetaService->getScope() : $scope;
        $collections = in_array('collection', $fixedParameters, true) ?
            [] : array_keys($this->settingsServiceLocator->getProvidedServices());

        return [
            'collections' => $collections,
            'currentCollection' => $currentCollection,
            'scopes' => $scopes,
            'currentScope' => $currentScope,
            'sections' => $sections,
            'currentSection' => $sectionMeta ? $sectionMeta->getName() : '',
            'form' => $form === null ? null : $form->createView(),
            'linkRoute' => $route,
        ];
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
        /** @var SettingsService $settingsService */
        $settingsService = $this->settingsServiceLocator->get($collectionName ?? $this->defaultCollectionName);
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