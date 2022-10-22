<?php

namespace TestApp\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Tzunghaor\SettingsBundle\Service\SettingsMetaService;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use TestApp\OtherSettings\FunSettings;
use TestApp\Settings\Ui\BoxSettings;

class TestService
{
    /**
     * @var SettingsService
     */
    private $fooSettings;

    /**
     * @var SettingsService
     */
    private $otherSettings;
    /**
     * @var ServiceLocator
     */
    private $settingsMetaServiceLocator;

    /**
     * @param SettingsService $fooSettings autowiring injects the service configured under the "default" section in
     *                                     config/packages/tzunghaor_settings.yaml by default
     * @param SettingsService $otherSettings autowiring injects service configured under "other" if argument name is
     *                                       $other or $otherSettings
     * @param ServiceLocator $settingsMetaServiceLocator helps to get SettingsMetaService in tests
     */
    public function __construct(
        SettingsService $fooSettings,
        SettingsService $otherSettings,
        ServiceLocator $settingsMetaServiceLocator
    ) {
        $this->fooSettings = $fooSettings;
        $this->otherSettings = $otherSettings;
        $this->settingsMetaServiceLocator = $settingsMetaServiceLocator;
    }

    public function getBoxSettings($subject = null): BoxSettings
    {
        return $this->fooSettings->getSection(BoxSettings::class, $subject);
    }

    public function getFunSettings($subject = null): FunSettings
    {
        return $this->otherSettings->getSection(FunSettings::class, $subject);
    }

    public function getSettingsMetaService(string $collectionName): SettingsMetaService
    {
        return $this->settingsMetaServiceLocator->get($collectionName);
    }
}