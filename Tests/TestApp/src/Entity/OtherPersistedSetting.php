<?php

namespace TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;

/**
 * @ORM\Entity()
 */
class OtherPersistedSetting extends AbstractPersistedSetting
{
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $extra;

    /**
     * @return string
     */
    public function getExtra(): string
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     */
    public function setExtra(string $extra): void
    {
        $this->extra = $extra;
    }
}