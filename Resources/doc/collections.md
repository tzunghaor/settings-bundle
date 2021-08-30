Collections and Services
========================

You can configure multiple collections, for example:

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  system:
    default_scope: foo
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