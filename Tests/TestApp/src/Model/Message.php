<?php

namespace TestApp\Model;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Tzunghaor\SettingsBundle\Attribute\Setting;

/**
 * This class is used in a form collection as entry data.
 */
class Message
{
    public function __construct(
        private string $type,
        private string $text,
        #[Setting(formType: CheckboxType::class, formOptions: ['required' => false])]
        private bool $important = false,
        #[Setting(enum: ['one', 'two', 'three'],formOptions: ['required' => false])]
        private array $tags = [],
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

    public function isImportant(): bool
    {
        return $this->important;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    // type and text are used with MessageType form which does not have custom mapper, so these two fields need setters
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}