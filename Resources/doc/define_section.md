Defining Setting Classes
========================

The bundle needs to write the settings into objects of your setting_section class,
so the class attributes must be readable and writable. You have multiple 
possibilities:

1. Simply make the class attributes public.
2. Make the class attributes private, and create getter and setter methods.
3. The previous possibilities have the disadvantage, when you receive the section
   object from the service, you can accidentally modify the values, which will
   effect other services. If you want to avoid this, then you can have private 
   attributes, getter methods and pass all attributes with the constructor. 

Let's see an example first, then we break down the possibilities:

```php
namespace App\Settings\Ui;

use Symfony\Component\Validator\Constraints as Assert;
use Tzunghaor\SettingsBundle\Annotation\Setting;
use App\Form\CustomIntType;

/**
 * UI Box Settings
 *
 * Various settings defining the css box layout of some HTML elements.
 */
class BoxSettings
{
    /**
     * @Setting(
     *     label="CSS Padding",
     *     help="This padding is used where it is appropriate.",
     *     formOptions={"attr": {"style": "border: 5px solid green;"}}
     * )
     *
     * @var int
     * @Assert\PositiveOrZero(message="padding should not be negative")
     * @Assert\LessThan(30, message="maximum accepted padding is {{ compared_value }} pixel")
     */
    private $padding;

    /**
     * CSS margin
     *
     * I think you already understand how this help text works.
     *
     * @Setting(
     *     formType=CustomIntType::class,
     *     dataType="int"
     * )
     *
     * @Assert\PositiveOrZero()
     * @Assert\LessThan(30, message="maximum accepted margin is {{ compared_value }} pixel")
     */
    private $margin;

    public function __construct($padding = 0, $margin = 0, $borders = [])
    {
        $this->padding = $padding;
        $this->margin = $margin;
        $this->borders = $borders;
    }

    /**
     * @var string[]
     * @Setting(enum={"bottom", "top", "left", "right"})
     */
    private $borders;

    /**
     * @return int
     */
    public function getPadding(): int
    {
        return $this->padding;
    }

    /**
     * @return int
     */
    public function getMargin(): int
    {
        return $this->margin;
    }

    /**
     * @return string[]
     */
    public function getBorders(): array
    {
        return $this->borders;
    }
}
```



Section names
-------------

The section name is used in urls and in the database. It is 
based on the fully qualified class name, controlled by
the mapping configuration:

1. The **prefix** part of the namespace is removed
1. Namespace separators are converted to dots
1. If the mapping name is not **default**, then the name is prefixed with it.

```yaml
# config/packages/tzunghaor_settings.yaml
tzunghaor_settings:
  collections:
    default:
      mappings:
        default:
          dir: '%kernel.project_dir%/src/Settings'
          prefix: App\Settings\
        custom:
          dir: '%kernel.project_dir%/src/Custom/Settings'
          prefix: App\Custom\Settings\
```

examples:

App\Settings\Project\ExportSettings => Project.ExportSettings  
App\Custom\Settings\Project\ExportSettings => custom.Project.ExportSettings

Setting and section definition
------------------------------

Every class attribute in your setting class will be used as a setting.
You can fine tune the data type and how it is displayed in the editor GUI, but
the bundle also tries it's best to provide sensible defaults for usual cases.

You can add **Tzunghaor\SettingsBundle\Annotation\Setting** to all/any of your
setting attributes, whatever is defined with it takes precedence over any other
method.

I suggest to install phpdocumentor/reflection-docblock, which gives you more, and 
probably easier to read options for the fine-tuning:

```bash
$ composer require phpdocumentor/reflection-docblock
```

### Section label and help text

The first line of your class docblock will be used as label in the editor GUI, and
the remaining lines in the docblock will be shown as help text.

### Setting data type

Since all settings are stored in database in a VARCHAR column, the bundle 
needs to know the data type of the settings to correctly hydrate the
setting section object.

The converting from/to database values are done by classes implementing
**Tzunghaor\SettingsBundle\Service\SettingConverterInterface**. The built-in
converter supports simple types, arrays of simple types and the DateTime class. 
If you need support for other types, you will need to create a service implementing
the above interface, and tag it with **tzunghaor_settings.setting_converter**. You
can use multiple converters supporting different types, the tag priority will
determine in which order they are tried, the built-in converter has -1000 priority. 

1. If you define **dataType** with the **Setting** annotation, it will be used.
2. If you have **phpdocumentor/reflection-docblock** installed, the @var annotation
   will be used.
3. Otherwise **symfony/property-info** will use all its magic - like the type of
   the default value - to find out the data type.
4. If all else fails, **string** is used.

### Setting label and help text

These are used in the editor GUI.

1. You can define **label** and **help** with the **Setting** annotation.
2. Otherwise, if you have **phpdocumentor/reflection-docblock** installed,
   the first and remaining lines of the docblock will be used, similarly as
   for the section class.
   
### Setting form type an options

These are used in the editor GUI.

1. You can define **formType** and **formOptions** with the **Setting** annotation.
2. You can define **enum** with the **Setting** annotation: it is just a convenience
   feature, it will set formType=choice, 
   formOptions={choices: {enum1: enum1, ...}}. If the dataType is array, then it 
   also adds 'multiple' to the formOptions
3. Otherwise the bundle chooses the formType based on the dataType

Validation
----------

Your section class is used in the editor GUI as part of the form data,  
so if you have **symfony/validator** installed, then you can define validation
rules in your class, 
see [symfony validation](https://symfony.com/doc/current/validation.html).
