<?php


namespace Tzunghaor\SettingsBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * bool as a radio button - unlike a checkbox, this ensures that a value is submitted when 'no' is selected
 *
 * Symfony currently has a bug in handling expanded ChoiceType with PATCH form method, therefore we have a
 * custom data mapper.
 */
class BoolType extends AbstractType implements DataMapperInterface
{
    public function getParent()
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => ['yes' => true, 'no' => false],
            'expanded' => true,
            'multiple' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        /** @var FormInterface $radio */
        foreach ($forms as $radio) {
            $value = $radio->getConfig()->getOption('value');
            $radio->setData($viewData === $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        /** @var FormInterface $radio */
        foreach ($forms as $radio) {
            if ($radio->isSubmitted() && $radio->getData()) {
                $viewData = $radio->getConfig()->getOption('value');

                return;
            }
        }

        $viewData = false;
    }
}