<?php

namespace TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Project
{
    #[ORM\Id]
    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    private User $owner;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }
}