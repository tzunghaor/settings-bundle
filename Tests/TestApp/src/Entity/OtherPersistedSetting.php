<?php

namespace TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;

#[ORM\Entity]
class OtherPersistedSetting extends AbstractPersistedSetting
{
    #[ORM\Column(type: "string")]
    private string $extra;

    public function getExtra(): string
    {
        return $this->extra;
    }


    public function setExtra(string $extra): void
    {
        $this->extra = $extra;
    }
}