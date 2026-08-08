<?php

namespace Tzunghaor\SettingsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tzunghaor\SettingsBundle\Helper\SettingTypeTrait;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;

/**
 * Form defined by an array of metadata
 * @see SettingMetaData
 */
class SettingClassType extends AbstractType implements DataMapperInterface
{
    use SettingTypeTrait;

    /**
     * Name of setting section
     */
    public const OPTION_META_ARRAY = 'meta_array';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SettingMetaData[] $metaDataArray */
        $metaDataArray = $options[self::OPTION_META_ARRAY];

        foreach ($metaDataArray as $settingMeta) {
            $settingName = $settingMeta->getName();

            $builder->add($settingName, $settingMeta->getFormType(), $settingMeta->getFormOptions());
        }

        $builder->setDataMapper($this);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([self::OPTION_META_ARRAY]);
        $resolver->setAllowedTypes(self::OPTION_META_ARRAY, 'array');
    }

    public function onPreSubmit(FormEvent $event): void
    {
        // empty form inputs (e.g. not checked checkbox) are not submitted, especially since we are using PATCH,
        // so we need to set them to empty here programmatically - otherwise their old value would be kept
        $data = $event->getData();
        /** @var SectionMetaData $metaData */
        $metaDataArray = $event->getForm()->getConfig()->getOption(self::OPTION_META_ARRAY);

        foreach ($metaDataArray as $settingMeta) {
            $settingName = $settingMeta->getName();
            // if there is already settings in data, then nothing to do
            if (array_key_exists($settingName, $data)) {
                continue;
            }

            // set empty input data based on setting type
            $settingType = $settingMeta->getDataType();
            $data[$settingName] = $this->getEmptyData($settingType);
        }

        $event->setData($data);
    }
}