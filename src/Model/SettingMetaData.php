<?php


namespace Tzunghaor\SettingsBundle\Model;


use Symfony\Component\PropertyInfo\Type;

/**
 * metadata about a single setting
 */
class SettingMetaData
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Type
     */
    private $dataType;

    /**
     * @var string
     */
    private $formType;

    /**
     * @var array
     */
    private $formOptions;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $help;

    public function __construct(
        string $name,
        Type $dataType,
        string $formType,
        array $formOptions,
        string $label,
        string $help
    ) {
        $this->name = $name;
        $this->dataType = $dataType;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
        $this->label = $label;
        $this->help = $help;
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