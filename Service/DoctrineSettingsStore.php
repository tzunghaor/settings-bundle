<?php
namespace Tzunghaor\SettingsBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tzunghaor\SettingsBundle\Model\PersistedSettingInterface;

/**
 * Stores/retrieves individual setting values in DB
 */
class DoctrineSettingsStore implements SettingsStoreInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string class name, must be an entity implementing PersistedSettingInterface
     */
    private $entityClass;

    public function __construct(EntityManagerInterface $em, string $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
    }


    /**
     * {@inheritdoc}
     */
    public function getValues(string $sectionName, string $scope): array
    {
        $values = [];
        $persistedSettings = $this->loadPersistedSettings($sectionName, $scope);

        foreach ($persistedSettings as $settingName => $persistedSetting) {
            $values[$settingName] = $persistedSetting->getValue();
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function saveValues(string $sectionName, string $scope, array $values): void
    {
        // existing persisted settings that are not in this save will remain in this array, and will be deleted
        $unhandledPersistedSettings = $this->loadPersistedSettings($sectionName, $scope);

        foreach ($values as $settingName => $value) {
            if (array_key_exists($settingName, $unhandledPersistedSettings)) {
                $persistedSetting = $unhandledPersistedSettings[$settingName];
                unset($unhandledPersistedSettings[$settingName]);

                // value is the same as in DB -> do nothing
                if ($persistedSetting->getValue() === $value) {
                    continue;
                }

                $persistedSetting->setValue($value);
            } else {
                /** @var PersistedSettingInterface $persistedSetting */
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
     * @return PersistedSettingInterface[] [$settingName => $persistedSetting, ...]
     */
    private function loadPersistedSettings(string $sectionName, string $scope): array
    {
        $query = $this->em->createQuery(
            sprintf('select p from %s p where p.path LIKE :pathLike AND p.scope = :scope', $this->entityClass)
        );

        $pathLike = $sectionName . '.%';

        /** @var PersistedSettingInterface[] $persistedSettings */
        $persistedSettings = $query->execute(['pathLike' => $pathLike, 'scope' => $scope]);
        $persistedSettingsWithKey = [];

        foreach ($persistedSettings as $persistedSetting) {
            $settingName = substr(strrchr($persistedSetting->getPath(), '.'), 1);
            $persistedSettingsWithKey[$settingName] = $persistedSetting;
        }

        return $persistedSettingsWithKey;
    }
}