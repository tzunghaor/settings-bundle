<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Helper class for generating urls pointing to the settings editor controller
 */
class EditorUrlParameters
{
    private string $route;

    private array $fixedParametersAsKeys;

    /**
     * @param string $route name of settings editor controller route
     * @param array $fixedParameters zero or more of 'collection', 'scope', 'section' - $route doesn't have these in url
     */
    public function __construct(string $route, array $fixedParameters)
    {
        $this->route = $route;
        $this->fixedParametersAsKeys = array_flip($fixedParameters);
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Returns route parameters usable for this route
     */
    public function filterParameters(array $parameters): array
    {
        return array_diff_key($parameters, $this->fixedParametersAsKeys);
    }
}