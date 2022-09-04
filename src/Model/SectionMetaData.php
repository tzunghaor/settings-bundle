<?php


namespace Tzunghaor\SettingsBundle\Model;

/**
 * metadata about a setting section
 */
class SectionMetaData
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $dataClass;

    /**
     * @var string
     */
    private $description;

    /**
     * @var SettingMetaData[]
     */
    private $settingMetaDataArray;

    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $extra;

    /**
     * @param string $name used as identifier in DB and url
     * @param string $title section title used in settings editor
     * @param string $dataClass php class that defines/stores this section
     * @param string $description description used in settings editor
     * @param SettingMetaData[] $settingMetaDataArray metadata array of the settings in this section
     * @param array $extra Extra data that you can use in your templates / extensions
     */
    public function __construct(
        string $name,
        string $title,
        string $dataClass,
        string $description,
        array $settingMetaDataArray,
        array $extra = []
    ) {
        $this->name = $name;
        $this->dataClass = $dataClass;
        $this->description = $description;
        $this->settingMetaDataArray = $settingMetaDataArray;
        $this->title = $title;
        $this->extra = $extra;
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
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDataClass(): string
    {
        return $this->dataClass;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return SettingMetaData[]
     */
    public function getSettingMetaDataArray(): array
    {
        return $this->settingMetaDataArray;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}