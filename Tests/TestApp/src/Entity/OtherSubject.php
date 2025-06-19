<?php

namespace TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OtherScopeProvider can determine the scope of these entities:
 * the scope "name-" . name, their parent scope is "group-" . group
 */
#[ORM\Entity]
class OtherSubject
{
    #[ORM\Id]
    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\Column(name: "mygroup", type: "string")]
    private string $group;


    public function getName(): string
    {
        return $this->name;
    }


    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function getGroup(): string
    {
        return $this->group;
    }


    public function setGroup(string $group): void
    {
        $this->group = $group;
    }
}