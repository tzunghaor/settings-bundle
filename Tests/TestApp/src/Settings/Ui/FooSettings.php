<?php

namespace TestApp\Settings\Ui;

use TestApp\Model\Message;
use Tzunghaor\SettingsBundle\Attribute\Setting;
use Tzunghaor\SettingsBundle\Attribute\SettingSection;

#[SettingSection(extra: ['pos' => 10])]
class FooSettings
{
    private int $number;

    // The type in the constructor is nullable and type discovery might use that,
    // so we have to explicitly declare dataType in Setting attribute
    #[Setting(dataType: Message::class)]
    private Message $mandatoryMessage;

    #[Setting(dataType: Message::class . '[]')]
    private array $messages;

    private ?\DateTime $date = null;

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getMandatoryMessage(): Message
    {
        return $this->mandatoryMessage;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function __construct(int $number = 0, ?Message $mandatoryMessage = null, array $messages = [], ?\DateTime $date = null)
    {
        $this->number = $number;
        $this->mandatoryMessage = $mandatoryMessage ?? new Message('', '');
        $this->messages = $messages;
        $this->date = $date;
    }
}