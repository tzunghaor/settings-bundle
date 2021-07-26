<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\Service;

use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\FunSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\Settings\Ui\BoxSettings;

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
     * @param SettingsService $fooSettings autowiring injects the service configured under the "default" section in
     *                                     config/packages/tzunghaor_settings.yaml by default
     * @param SettingsService $otherSettings autowiring injects service configured under "other" if argument name is
     *                                       $other or $otherSettings
     */
    public function __construct(SettingsService $fooSettings, SettingsService $otherSettings)
    {
        $this->fooSettings = $fooSettings;
        $this->otherSettings = $otherSettings;
    }

    public function getBoxSettings($subject = null): BoxSettings
    {
        return $this->fooSettings->getSection(BoxSettings::class, $subject);
    }

    public function getFunSettings($subject = null): FunSettings
    {
        return $this->otherSettings->getSection(FunSettings::class, $subject);
    }
}