Using security voters
=====================

For finer control over who can use the editor to modify which settings, you can
define your security voter(s). 

Make sure that security-core component is installed:

```sh
$ composer require symfony/security-core
```

Turn on security for the bundle:

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  security: true
  collections:
    # ...
```

Create a [security voter](https://symfony.com/doc/current/security/voters.html) 
that supports **Tzunghaor\SettingsBundle\Model\SettingSectionAddress** as subject
and "edit" as attribute.

Any or all of the three fields of a SettingSectionAddress might be null, then the
question is: "Can we fill the nulls in a way that the user has right to edit?"

If you are using your own [scope provider](scopes.md), then make sure that the
scope provider's getScopeDisplayHierarchy() method takes the security voter in 
account. (Optimally it should return only scopes that your voter approves. At the
minimum it should return at least one scope that your voter approves - if there are
any.)