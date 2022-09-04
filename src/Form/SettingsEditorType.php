<?php


namespace Tzunghaor\SettingsBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tzunghaor\SettingsBundle\Helper\ObjectHydrator;
use Tzunghaor\SettingsBundle\Model\SectionMetaData;

/**
 * Form Type for editing a settings section
 */
class SettingsEditorType extends AbstractType implements DataMapperInterface
{
    /**
     * I want to support form validation with validation constraint annotations in the setting section class,
     * but I dont't want to have the validation component as a hard dependency. Therefore the data of this form type is
     * an array with keys defined here. (And not an object which would require explicitly propagating validation.)
     */
    // form data key of the settings of edited scope
    public const DATA_SETTINGS = 'settings';
    // form data key of the settings of parent scope - displayed when user selects inherit
    public const DATA_PARENT_SETTINGS = 'parent_settings';
    // form data key of booleans: true - value in DATA_SETTINGS is defined in current scope | false - value is inherited
    public const DATA_IN_SCOPE = 'in_scope';

    /**
     * Name of setting section
     */
    public const OPTION_SECTION_META = 'section_meta';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var SectionMetaData $metaData */
        $metaData = $options[self::OPTION_SECTION_META];

        $builder->add(self::DATA_SETTINGS, FormType::class, [
            'label' => false,
            'data_class' => $metaData->getDataClass(),
        ]);
        $settingsForm = $builder->get(self::DATA_SETTINGS);
        $settingsForm->setDataMapper($this);

        $builder->add(self::DATA_PARENT_SETTINGS, FormType::class, [
            'label' => false,
            'data_class' => $metaData->getDataClass(),
            'disabled' => true,
        ]);
        $parentSettingsForm = $builder->get(self::DATA_PARENT_SETTINGS);
        $parentSettingsForm->setDataMapper($this);

        $builder->add(self::DATA_IN_SCOPE, FormType::class, ['label' => false]);
        $overrideForm = $builder->get(self::DATA_IN_SCOPE);

        foreach ($metaData->getSettingMetaDataArray() as $settingMeta) {
            $settingName = $settingMeta->getName();

            $description = $settingMeta->getHelp();
            $generatedValueOptions = [
                'label' => $settingMeta->getLabel(),
                'help' => !empty($description) ? $description : null,
                // make sure that property accessor handles it as class attribute and not as array index
                'property_path' => $settingName,
                'row_attr' => ['class' => 'tzunghaor_setting_value'],
            ];
            $valueOptions = array_merge($generatedValueOptions, $settingMeta->getFormOptions());

            $settingsForm->add($settingName, $settingMeta->getFormType(), $valueOptions);
            $parentSettingsForm->add($settingName, $settingMeta->getFormType(), $valueOptions);

            $overrideOptions = [
                'required' => true,
                'label' => false,
                'expanded' => true,
                'choices' => ['set' => true, 'inherit' => false],
                'row_attr' => [
                    'title' => 'set value in this scope',
                    'class' => 'tzunghaor_setting_override',
                ],
            ];

            $overrideForm->add($settingName, BoolType::class, $overrideOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([self::OPTION_SECTION_META]);
        $resolver->setAllowedTypes(self::OPTION_SECTION_META, SectionMetaData::class);
        // use PATCH so that non-submitted values are not cleared, but remain the current inherited values
        $resolver->setDefault('method', Request::METHOD_PATCH);
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($viewData, iterable $forms)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($forms as $settingName => $childForm) {
            $childForm->setData($propertyAccessor->getValue($viewData, $settingName));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData(iterable $forms, &$viewData)
    {
        $values = [];

        /** @var FormInterface[] $forms */
        foreach ($forms as $settingName => $form) {
            $values[$settingName] = $form->getData();
        }

        $viewData = ObjectHydrator::hydrate(get_class($viewData), $values);
    }
}