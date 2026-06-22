<?php

namespace TestApp\Model;

/**
 * This class is used in a form collection as entry data.
 */
class Message
{
    public function __construct(
        private string $type,
        private string $text
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

}