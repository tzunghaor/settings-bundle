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

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDataType(): Type
    {
        return $this->dataType;
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help;
    }
}