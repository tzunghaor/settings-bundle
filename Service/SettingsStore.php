<?php
namespace Tzunghaor\SettingsBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tzunghaor\SettingsBundle\Entity\AbstractPersistedSetting;
use Tzunghaor\SettingsBundle\Exception\SettingsException;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;

/**
 * Stores/retrieves individual setting values in DB
 */
class SettingsStore implements SettingsStoreInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SettingConverterInterface[]
     */
    private $dataConverters;

    /**
     * @var string class name, must be subclass of AbstractPersistedSetting
     */
    private $entityClass;

    public function __construct(EntityManagerInterface $em, iterable $dataConverters, string $entityClass)
    {
        $this->em = $em;
        $this->dataConverters = iterator_to_array($dataConverters);
        $this->entityClass = $entityClass;
    }


    /**
     * {@inheritdoc}
     *
     * @throws SettingsException
     */
    public function getValues(string $sectionName, string $scope, array $metaDataArray): array
    {
        $values = [];
        $persistedSettings = $this->loadPersistedSettings($sectionName, $scope);

        foreach ($persistedSettings as $settingName => $persistedSetting) {
            $values[$settingName] = $persistedSetting->getValue();
        }

        return $this->convertFromPersistedValue($values, $metaDataArray);
    }

    /**
     * {@inheritdoc}
     *
     *
     * @throws SettingsException
     */
    public function saveValues(string $sectionName, string $scope, array $values, array $metaDataArray): void
    {
        $validSettingNames = [];
        foreach ($metaDataArray as $settingMetaData) {
            $validSettingNames[] = $settingMetaData->getName();
        }

        // existing persisted settings that are not in this save will remain in this array, and will be deleted
        $unhandledPersistedSettings = $this->loadPersistedSettings($sectionName, $scope);
        $valuesToPersist = $this->convertToPersistedValues($values, $metaDataArray);

        foreach ($valuesToPersist as $settingName => $value) {
            if (!in_array($settingName, $validSettingNames, true)) {
                throw new SettingsException(
                    sprintf('Cannot save unknown setting %s in section %s.', $settingName, $sectionName)
                );
            }

            if (array_key_exists($settingName, $unhandledPersistedSettings)) {
                $persistedSetting = $unhandledPersistedSettings[$settingName];
                unset($unhandledPersistedSettings[$settingName]);

                // value is the same as in DB -> do nothing
                if ($persistedSetting->getValue() === $value) {
                    continue;
                }

                $persistedSetting->setValue($value);
            } else {
                /** @var AbstractPersistedSetting $persistedSetting */
                $persistedSetting = new $this->entityClass();
                $persistedSetting->setPath($sectionName . '.' . $settingName);
                $persistedSetting->setScope($scope);
                $persistedSetting->setValue($value);

                $this->em->persist($persistedSetting);
            }
        }

        foreach ($unhandledPersistedSettings as $unhandledPersistedSetting) {
            $this->em->remove($unhandledPersistedSetting);
        }

        $this->em->flush();
    }


    /**
     * Loads persisted setting entities from DB
     *
     * @param string $sectionName
     * @param string $scope
     *
     * @return AbstractPersistedSetting[] [$settingName => $persistedSetting, ...]
     */
    private function loadPersistedSettings(string $sectionName, string $scope): array
    {
        $query = $this->em->createQuery(
            sprintf('select p from %s p where p.path LIKE :pathLike AND p.scope = :scope', $this->entityClass)
        );

        $pathLike = $sectionName . '.%';

        /** @var AbstractPersistedSetting[] $persistedSettings */
        $persistedSettings = $query->execute(['pathLike' => $pathLike, 'scope' => $scope]);
        $persistedSettingsWithKey = [];

        foreach ($persistedSettings as $persistedSetting) {
            $settingName = substr(strrchr($persistedSetting->getPath(), '.'), 1);
            $persistedSettingsWithKey[$settingName] = $persistedSetting;
        }

        return $persistedSettingsWithKey;
    }

    /**
     * Converts the DB persisted values to the type defined in the section class
     *
     * @param array $persistedValues [$settingName => $value, ...]
     * @param SettingMetaData[] $settingMetaArray
     *
     * @return array
     *
     * @throws SettingsException
     */
    private function convertFromPersistedValue(array $persistedValues, array $settingMetaArray): array
    {
        $convertedValues = [];

        foreach ($persistedValues as $settingName => $persistedValue) {
            $type = $settingMetaArray[$settingName]->getDataType();
            foreach ($this->dataConverters as $dataConverter) {
                if ($dataConverter->supports($type)) {
                    $convertedValues[$settingName] = $dataConverter->convertFromString($type, $persistedValue);

                    break;
                }
            }

            if (!isset($convertedValues[$settingName])) {
                throw new SettingsException(sprintf('Could not find converter for setting %s', $settingName));
            }
        }

        return $convertedValues;
    }

    /**
     * Converts the values of types defined in the setting section class to values that can be persisted in DB
     *
     * @param array $values [$settingName => $value, ...]
     * @param SettingMetaData[] $settingMetaArray
     *
     * @return array
     *
     * @throws SettingsException
     */
    private function convertToPersistedValues(array $values, array $settingMetaArray): array
    {
        $convertedValues = [];

        foreach ($values as $settingName => $value) {
            $type = $settingMetaArray[$settingName]->getDataType();
            foreach ($this->dataConverters as $dataConverter) {
                if ($dataConverter->supports($type)) {
                    $convertedValues[$settingName] = $dataConverter->convertToString($type, $value);

                    break;
                }
            }

            if (!isset($convertedValues[$settingName])) {
                throw new SettingsException(sprintf('Could not find converter for setting %s', $settingName));
            }
        }

        return $convertedValues;
    }
}