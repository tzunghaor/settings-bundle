<?php

namespace Tzunghaor\SettingsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NullableType extends AbstractType implements DataMapperInterface
{
    public const OPTION_WRAPPED_TYPE = 'wrapped_type';
    public const OPTION_WRAPPED_OPTIONS = 'wrapped_options';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $wrappedOptions = array_merge(['label' => false], $options[self::OPTION_WRAPPED_OPTIONS]);
        $wrappedOptions['row_attr']['data-tzhs-role'] = 'nullable-value';
        $builder->add('set', ChoiceType::class, [
                'choices' => ['set' => true, 'unset' => false],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'row_attr' => ['data-tzhs-role' => 'nullable-setter'],
            ])
            ->add('value', $options[self::OPTION_WRAPPED_TYPE], $wrappedOptions)
        ;

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPTION_WRAPPED_TYPE => TextType::class,
            self::OPTION_WRAPPED_OPTIONS => [],
            'row_attr' => ['data-tzhs-role' => 'nullable-container'],
        ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        foreach ($forms as $form) {
            if ($form->getName() === 'set') {
                $form->setData($viewData !== null);
            } else {
                $form->setData($viewData);
            }
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        foreach ($forms as $form) {
            if ($form->getName() === 'set') {
                if ($form->getData() === false) {
                    $viewData = null;
                    return;
                }
            } else {
                $viewData = $form->getData();
            }
        }
    }
}