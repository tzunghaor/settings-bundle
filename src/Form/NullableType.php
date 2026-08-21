<?php

namespace Tzunghaor\SettingsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
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
                'choices' => ['set' => '1', 'unset' => '0'],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'row_attr' => ['data-tzhs-role' => 'nullable-setter'],
            ])
            ->add('value', $options[self::OPTION_WRAPPED_TYPE], $wrappedOptions)
        ;

        $builder->setDataMapper($this);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
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
                $form->setData($viewData === null ? '0' : '1');
            } else {
                $form->setData($viewData);
            }
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        foreach ($forms as $form) {
            if ($form->getName() === 'set') {
                if ($form->getData() === '0') {
                    $viewData = null;
                    return;
                }
            } else {
                $viewData = $form->getData();
            }
        }
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        // if 'unset' is selected, then remove value sub-form to avoid possible validation errors caused by empty values
        if ($data['set'] === '0') {
            $form->remove('value');
        }
    }
}