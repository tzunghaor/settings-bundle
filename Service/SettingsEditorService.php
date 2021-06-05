<?php


namespace Tzunghaor\SettingsBundle\Service;

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
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var SettingsMetaService
     */
    private $settingsMetaService;

    public function __construct(
        SettingsService $settingsService,
        SettingsMetaService $settingsMetaService,
        FormFactoryInterface $formFactory
    ) {
        $this->formFactory = $formFactory;
        $this->settingsService = $settingsService;
        $this->settingsMetaService = $settingsMetaService;
    }

    /**
     * Creates a form to edit the given setting section in given scope - pre-fills with current settings
     *
     * @param string $sectionName
     * @param string $scope
     *
     * @return FormInterface|null returns null if $sectionName + $scope is not sufficient
     *
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function createForm(string $sectionName, string $scope): ?FormInterface
    {
        if ($sectionName === '') {
            return null;
        }

        $sectionMeta = $this->settingsMetaService->getSectionMetaDataByName($sectionName);
        $sectionClass = $sectionMeta->getDataClass();
        $sectionSettings = $this->settingsService->getSection($sectionClass, $scope);
        $valueScopes = $this->settingsService->getValueScopes($sectionClass, $scope);
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
            SettingsEditorType::OPTION_SECTION => $sectionName,
            'label' => $sectionMeta->getTitle(),
            'help' => $sectionMeta->getDescription(),
        ]);
    }

    /**
     * Returns an array that contains the expected variables of editor.html.twig
     *
     * @param string $sectionName
     * @param string $scope
     * @param FormInterface $form
     *
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     */
    public function getTwigContext(string $sectionName, string $scope, ?FormInterface $form): array
    {
        $sections = $this->settingsMetaService->getSectionMetaDataArray();
        $scopes = $this->settingsMetaService->getScopeHierarchy();
        $sectionMeta = $sectionName === '' ? null : $this->settingsMetaService->getSectionMetaDataByName($sectionName);
        $currentScope = $scope === '' ? $this->settingsMetaService->getScope() : $scope;

        return [
            'scopes' => $scopes,
            'currentScope' => $currentScope,
            'sections' => $sections,
            'currentSection' => $sectionMeta ? $sectionMeta->getName() : '',
            'form' => $form === null ? null : $form->createView(),
        ];
    }

    /**
     * Saves the form data to database
     *
     * @param array $formData of SettingsEditorType
     * @param string $sectionName
     * @param string $scope
     *
     * @throws \Tzunghaor\SettingsBundle\Exception\SettingsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save(array $formData, string $sectionName, string $scope): void
    {
        $sectionMeta = $this->settingsMetaService->getSectionMetaDataByName($sectionName);
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

        $this->settingsService->save($sectionMeta->getDataClass(), $scope, $values);
    }
}