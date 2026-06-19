<?php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routesConfig = [
        'tzunghaor_settings_edit' => [
            'path' => '/edit/{collection?}/{scope?}/{section?}',
            'controller' => ['tzunghaor_settings.editor_controller', 'edit'],
        ],
        'tzunghaor_settings_scope_search' => [
            'path' => '/scope-search',
            'controller' => ['tzunghaor_settings.editor_controller', 'searchScope'],
        ],
    ];

    foreach ($routesConfig as $name => $config) {
        $routes
            ->add($name, $config['path'])
            ->controller($config['controller'])
        ;
    }
};

