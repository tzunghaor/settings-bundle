<?php

namespace TestApp\Service;

use Tzunghaor\SettingsBundle\Service\SettingsService;
use TestApp\Settings\Ui\BoxSettings;

/**
 * Service to test the minimal configuration
 */
class MinimalTestService
{
    /**
     * @var SettingsService
     */
    private $minimalSettings;

    /**
     * @param SettingsService $minimalSettings autowiring injects the service configured under the "default" section in
     *                                     config/packages/tzunghaor_settings.yaml by default
     */
    public function __construct(
        SettingsService $minimalSettings
    ) {
        $this->minimalSettings = $minimalSettings;
    }

    public function getBoxSettings($subject = null): BoxSettings
    {
        return $this->minimalSettings->getSection(BoxSettings::class, $subject);
    }
}