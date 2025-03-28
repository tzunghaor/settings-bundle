Collections and Services
========================

Multiple collections
--------------------

You can configure multiple collections. For example, if you want some 'season' settings that are different
for each season, and 'user' settings that are different for each user, then you can do something like 
([more on defining scopes](scopes.md)):

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  collections:
    season:
      default_scope: summer
      # ...
    user:
      scope_provider: my_scope_provider
      # ...
```

Then you can use dependency injection autowiring by using the collection name,
or collection name + "Settings" as parameter name, for example:

```php
namespace App\Service;

use Tzunghaor\SettingsBundle\Service\SettingsService;

class MyService
{
    public function __construct(SettingsService $systemSettings, SettingsService $userSettings)
    {
       // ...
``` 

Or if you don't use autowiring, the bundle creates services with the id
'tzunghaor_settings.settings_service.' + collection name, so you can manually set it up:

```yaml
# config/services.yaml

App\Service\MyService:
  arguments:
    $systemSettings: tzunghaor_settings.settings_service.system
    $userSettings: tzunghaor_settings.settings_service.user
```

You can omit the "default" collection, but then you have to always specify which collection
to use.

Alternatively you can override the **tzunghaor_settings.default_collection** config parameter
to set a different name for the default collection. 

Multiple mappings
-----------------

You might want to spread your setting classes in multiple independent directories: in that 
case you can define multiple **mappings** instead of a single **mapping**:

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  collections:
    season:
      # ...
      mappings:
        core:
          dir: '%kernel.project_dir%/src/Core/SeasonSettings'
          prefix: App\Core\SeasonSettings\
        sales:
          dir: '%kernel.project_dir%/src/Sales/SeasonSettings'
          prefix: App\Sales\SeasonSettings\
```