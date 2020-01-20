<?php

namespace LogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LogFormReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start_date', TextType::class, array(
                'label' => 'Date start',
                'required' => true,
                'mapped' => false,
                'attr'=> array(
                    "class" => "in-date"
                )
            ))
            ->add('end_date', TextType::class, array(
                'label'     => 'Date finish',
                'required'  => true,
                'mapped'    => false,
                'attr'      => array(
                    "class" => "out-date"
                )
            ))
            ->add('save', SubmitType::class, array("label"=>"Submit"));
    }

    public function getBlockPrefix()
    {
        return 'ms_form_report';
    }
}