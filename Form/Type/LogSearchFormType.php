<?php

namespace LogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LogSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('keyword', TextType::class, array('label' => 'Search for:', 'mapped' => false));
    }

    public function getBlockPrefix()
    {
        return 'ms_logger_search';
    }
}