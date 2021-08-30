Advanced scope setup
====================

If you want to have dynamically changing set of scopes 
(e.g. scopes for users/groups/projects/...), you cannot use the default
static scope provider, you need to create your own.

Create a class that implements **Tzunghaor\SettingsBundle\Service\ScopeProviderInterface** 
(see the documentation in the interface) and set it in your configuration.

```yaml
# config/services.yaml

services:
  app.user_scope_provider:
    class: App\Service\UserScopeProvider
```

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  collections:
    user:
      scope_provider: app.user_scope_provider
```

For a detailed example, see OtherScopeProvider in the test project.