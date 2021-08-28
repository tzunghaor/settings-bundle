<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\Controller;

use Symfony\Component\HttpFoundation\Response;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\FunSettings;
use Tzunghaor\SettingsBundle\Tests\TestProject\OtherSettings\SadSettings;

class TestController
{
    public function other(SettingsService $otherSettings)
    {
        // get the settings of the default scope
        /** @var FunSettings $funSettings */
        $funSettings = $otherSettings->getSection(FunSettings::class);
        /** @var SadSettings $sadSettings */
        $sadSettings = $otherSettings->getSection(SadSettings::class);

        return new Response($funSettings->getName() . ' --- ' . $sadSettings->getName());
    }
}