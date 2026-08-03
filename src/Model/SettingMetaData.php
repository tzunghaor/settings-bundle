<?php


namespace Tzunghaor\SettingsBundle\Model;


/**
 * metadata about a single setting
 */
class SettingMetaData
{
    public function __construct(
        private string $name,
        private Type $dataType,
        private string $formType,
        private array $formOptions,
        private string $label,
        private string $help
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataType(): Type
    {
        return $this->dataType;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getHelp(): string
    {
        return $this->help;
    }
}
