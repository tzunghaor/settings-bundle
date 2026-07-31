Translations
============

You can set different `translation_domain` for each of your collections.
The values can be:
* false: no translation
* null: use the default "messages" translation domain
* a string: the name of your custom translation domain

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  # This is used for the strings that are the same for every collection.
  translation_domain: null
  collections:
    default:
      # This is used for the form elements in the default collection
      translation_domain: tzunghaor
      ...
    other_collection:
      # other_collection form elements are not translated
      translation_domain: false
      ...
```

Validations
-----------

If you define validation attributes on your setting class variables, their
translation will be handled by the Validator component, and it will use
the `validators` translation domain - see [the chapter about it](https://symfony.com/doc/current/validation/translations.html).

The bundle's strings
--------------------

The following is a list of the translatable strings defined in this bundle.

```yaml
# translations/%translation_domain%.%locale%.yaml

# These strings are in PHP code
'set': ''
'inherit': ''
'Settings saved': ''

# These strings are in the Twig templates
'Collections': ''
'Scopes': ''
'Search scopes': ''
'Sections': ''
'Section "%section%" for scope "%scope%"': ''
'Add': ''
'Save': ''
```