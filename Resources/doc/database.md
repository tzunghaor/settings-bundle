Database
========

Using custom entity
-------------------

You can create your own entity class extending 
**Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting**. You only need
to define those columns that you want different. 


```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="my_persisted_setting")
 */
class MyPersistedSetting extends AbstractPersistedSetting
{
    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="string", name="my_path")
     */
    protected $path;

    /**
     * Doctrine lifecycle events will increase this counter.
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $version = 0;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function incrementVersion()
    {
        $this->version ++;
    }
}
```

Then set this entity in the config:

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  collections:
    default:
      entity: App\Entity\MyPersistedSetting
```

To make sure that doctrine maps only your entity, either make sure that
doctrine's **auto_mapping** is not enabled, or overwrite this bundle's
doctrine mapping:

```yaml
doctrine:
  orm:
    auto_mapping: true
      mappings:
        TzunghaorSettingsBundle:
          is_bundle: false
          type: annotation
          dir: '%kernel.project_dir%/src/Entity'
          prefix: 'App\Entity'
```