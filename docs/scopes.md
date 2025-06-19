Advanced scope setup
====================

If you want to have dynamically changing set of scopes 
(e.g. scopes for users/groups/projects/...), you cannot define them in **tzunghaor_settings.yaml**
(which uses the static scope provider), you need to create your own.

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

Support isGranted for your own entities / models
------------------------------------------------

As mentioned at the [security voters](voter.md), if you want to implement voters for your own
entities / models instead of this bundle's **SettingSectionAddress** class, then you have to implement
a special subclass of the scope provider interface 
**Tzunghaor\SettingsBundle\Service\IsGrantedSupportingScopeProviderInterface**. (A detailed example is
CustomGrantScopeProvider in Tests/TestApp)

E.g. if you want to use something like this in your code `{% if is_granted("edit_tzunghaor_settings", myproject) %}`,
you will need to implement methods that will convert a scope name to your Project object, and return the 
attribute name that you want to use in isGranted:

```php
use Tzunghaor\SettingsBundle\Service\IsGrantedSupportingScopeProviderInterface;

class ProjectScopeProvider implements IsGrantedSupportingScopeProviderInterface 
{
    // ...

    public function getSubject(string $scope): ?object
    {
        return $this->em->find(Project::class, $scope);
    }

    public function getIsGrantedAttribute(): string
    {
        // this can be any string, just make sure it doesn't collide with any other
        // attribute used in your app with isGranted()
        return 'edit_tzunghaor_settings';
    }
}
```