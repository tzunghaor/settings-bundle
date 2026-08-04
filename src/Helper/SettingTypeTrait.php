<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tzunghaor\SettingsBundle\Model\SettingMetaData;
use Tzunghaor\SettingsBundle\Model\Type;

/**
 * Common methods for setting editor form types
 */
trait SettingTypeTrait
{
    protected function makeFormOptions(SettingMetaData $settingMeta): array {
        $settingName = $settingMeta->getName();

        $description = $settingMeta->getHelp();
        $generatedValueOptions = [
            'label' => $settingMeta->getLabel(),
            'help' => !empty($description) ? $description : null,
            // ensure that property accessor handles it as class attribute and not as array index
            'property_path' => $settingName,
            'row_attr' => ['class' => 'tzunghaor_setting_labeled_widget'],
        ];
        return array_merge($generatedValueOptions, $settingMeta->getFormOptions());
    }

    protected function getEmptyData(Type $settingType): mixed
    {
        if ($settingType->isNullable()) {
            $emptyValue = null;
        } elseif ($settingType->isCollection()) {
            $emptyValue = [];
        } elseif ($settingType->getTypeIdentifier() === 'bool') {
            $emptyValue = false;
        } elseif ($settingType->getTypeIdentifier() === 'string') {
            $emptyValue = '';
        }

        return $emptyValue ?? null;
    }

    // two methods that implement DataMapperInterface
    /**
     * @see DataMapperInterface::mapDataToForms()
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        /** @var FormInterface[] $formsArray */
        $formsArray = iterator_to_array($forms);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($formsArray as $settingName => $childForm) {
            $childForm->setData($propertyAccessor->getValue($viewData, $settingName));
        }
    }

    /**
     * @see DataMapperInterface::mapFormsToData()
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $values = [];

        /** @var \Traversable<FormInterface> $forms */
        foreach ($forms as $settingName => $form) {
            $values[$settingName] = $form->getData();
        }

        $viewData = ObjectHydrator::hydrate(get_class($viewData), $values);
    }
}