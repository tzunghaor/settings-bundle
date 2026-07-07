<?php

namespace TestApp\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TestApp\Model\Message;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $types = ['info', 'error'];
        $builder
            ->add('type', ChoiceType::class, [
                // choice labels and values are the same
                'choices' => array_combine($types, $types),
            ])
            ->add('text', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Message::class);
    }
}