<?php

namespace TestApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Tzunghaor\SettingsBundle\Service\SettingsService;
use TestApp\OtherSettings\FunSettings;
use TestApp\OtherSettings\SadSettings;

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