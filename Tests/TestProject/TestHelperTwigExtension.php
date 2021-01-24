<?php
namespace Tzunghaor\SettingsBundle\Tests\TestProject;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * This bundle doesn't require symfony/asset, so we fake the asset() twig function for the tests
 */
class TestHelperTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('asset', function($src) { return ''; }),
        ];
    }
}