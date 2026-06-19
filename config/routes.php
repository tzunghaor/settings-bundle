<?php
namespace Symfony\Component\Routing\Loader\Configurator;

return Routes::config([
    'tzunghaor_settings_edit' => [
        'path' => '/edit/{collection?}/{scope?}/{section?}',
        'controller' => ['tzunghaor_settings.editor_controller', 'edit'],
    ],
    'tzunghaor_settings_scope_search' => [
        'path' => '/scope-search',
        'controller' => ['tzunghaor_settings.editor_controller', 'searchScope'],
    ],
]);
