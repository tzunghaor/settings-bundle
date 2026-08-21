<?php

namespace Tzunghaor\SettingsBundle\Model;

/**
 * Helper class for generating urls pointing to the settings editor controller
 */
class EditorUrlParameters
{
    private string $route;

    /**
     * @var array<string, string>
     */
    private array $fixedParametersAsKeys;

    /**
     * @param string $route name of settings editor controller route
     * @param array<string, string> $fixedParameters zero or more of 'collection', 'scope', 'section' - $route doesn't have these in url
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
     *
     * @param array<string, string> $parameters
     *
     * @return array<string, string>
     */
    public function filterParameters(array $parameters): array
    {
        return array_diff_key($parameters, $this->fixedParametersAsKeys);
    }
}