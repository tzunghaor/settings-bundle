Advanced scope setup
====================

If you want to have dynamically changing set of scopes 
(e.g. scopes for users/groups/projects/...), you cannot use the default
static scope provider, you need to create your own.

Create a class that implements **Tzunghaor\SettingsBundle\Service\ScopeProviderInterface** 
(see the documentation in the interface) and set it in your configuration.

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  collections:
    user:
      # The service id of your scope provider.
      # It is the fully qualified class name when using default Symfony config.
      scope_provider: 'App\Service\UserScopeProvider'
      # ...
```

For a detailed example, see OtherScopeProvider in the test project.