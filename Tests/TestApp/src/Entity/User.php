<?php

namespace TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: "string")]
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}