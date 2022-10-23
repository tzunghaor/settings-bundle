Advanced routing
================

The editor controller action uses the following request attributes:

* **collection**: setting collection name - the main keys in your package configuration
* **scope**: settings scope
* **section**: settings section name (derived from php class FQCN, but not the same)
* **fixedParameters**: array of strings telling which of the above three cannot be
  different for this route 
* **searchRoute**: name of custom scope search route 
* **template**: name of custom twig template

These request attributes can be filled using route path parameters, route config, 
with a request listener, etc.

You can define multiple routes to the edit controller action for different use cases.

### Use case 1

For example, you have only one "default" setting collection and don't want it to
be present in the url (as it would be with the route provided by the bundle), you
can configure it like this:

```yaml
# config/routes.yml
# not including default bundle routes.xml
settings_scope_search:
  path: '/settings/scope-search'
  controller: 'tzunghaor_settings.editor_controller::searchScope'

settings_edit:
  path: '/settings/edit/{scope?}/{section?}'
  controller: 'tzunghaor_settings.editor_controller::edit'
  defaults:
    collection: 'default'
    fixedParameters: ['collection']
    searchRoute: 'settings_scope_search'
```

If you don't include the routes.xml of the bundle but want to use the scope search
functionality, then you have to define a route for searchScope. But if you name
this route "tzunghaor_settings_scope_search", then you don't need to pass it to the
editor route.

### Use case 2

You have a "user" collection, and
you set up a [scope provider](scopes.md) to have a separate scope for each user, and
you want an editor where a user can edit their own settings. You need to set up your
scope provider to return the currently logged in user's scope as default. You won't need
the scope search functionality, since on this route the user can edit only their own
scope:

```yaml
# config/routes.yml
user_settings_edit:
  path: '/user-settings/edit/{section?}'
  controller: 'tzunghaor_settings.editor_controller::edit'
  defaults:
    collection: 'user'
    # noo need to explicitly define scope: ~, because it is null by default anyway
    # scope: ~
    fixedParameters: ['collection', 'scope']
    # passing null to hide scope search input 
    searchRoute: ~
    template: 'user-settings/editor_page.html.twig' 
```
