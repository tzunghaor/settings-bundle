<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\Routing\RouterInterface;

/**
 * Helper class to generate urls pointing to the settings editor controller
 */
class UrlGenerator
{
    private RouterInterface $router;

    private string $route;

    private array $fixedParametersFlipped;

    /**
     * @param string $route name of settings editor controller route
     * @param array $fixedParameters zero or more of 'collection', 'scope', 'section' - $route doesn't have these in url
     */
    public function __construct(RouterInterface $router, string $route, array $fixedParameters)
    {
        $this->router = $router;
        $this->route = $route;
        $this->fixedParametersFlipped = array_flip($fixedParameters);
    }

    public function generateUrl(array $parameters)
    {
        $filteredParameters = array_diff_key($parameters, $this->fixedParametersFlipped);

        return $this->router->generate($this->route, $filteredParameters);
    }
}