<?php


namespace Tzunghaor\SettingsBundle\Model;

/**
 * metadata about a setting section
 */
class SectionMetaData
{
    /**
     * @param string $name used as identifier in DB and url
     * @param string $title section title used in settings editor
     * @param string $dataClass php class that defines/stores this section
     * @param string $description description used in settings editor
     * @param SettingMetaData[] $settingMetaDataArray metadata array of the settings in this section
     * @param array<mixed> $extra Extra data that you can use in your templates / extensions
     */
    public function __construct(
        private string $name,
        private string $title,
        private string $dataClass,
        private string $description,
        private array $settingMetaDataArray,
        private array $extra = []
    ) {
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function getTitle(): string
    {
        return $this->title;
    }


    public function getDataClass(): string
    {
        return $this->dataClass;
    }


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
     * @return array<mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}