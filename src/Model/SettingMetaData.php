<?php


namespace Tzunghaor\SettingsBundle\Model;


use Symfony\Component\Form\FormTypeInterface;

/**
 * metadata about a single setting
 */
class SettingMetaData
{
    /**
     * @param class-string<FormTypeInterface> $formType
     * @param array<string, mixed> $formOptions
     */
    public function __construct(
        private string $name,
        private Type $dataType,
        private string $formType,
        private array $formOptions,
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

    /**
     * @return class-string<FormTypeInterface>
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }
}
