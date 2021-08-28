<?php

namespace Tzunghaor\SettingsBundle\Tests\TestProject\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OtherScopeProvider can determine the scope of these entities:
 * the scope "name-" . name, their parent scope is "group-" . group
 *
 * @ORM\Entity
 */
class OtherSubject
{
    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="mygroup")
     */
    private $group;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group): void
    {
        $this->group = $group;
    }
}