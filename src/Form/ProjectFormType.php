<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('monthlyPayment', ChoiceType::class, [
                'choices'  => [
                    '79€' => 1,
                    '99€' => 2,
                    '119€' => 3,
                    '129€' => 4,
                    '149€' => 5,
                    '169€' => 6,
                    '189€' => 7,
                    '269,21€' => 8,
                    '291,84€' => 9,
                ],
                'placeholder' => 'Choisir la mensualité...',
            ])
            ->add('numberOfMonthlyPayments', ChoiceType::class, [
                'choices'  => [
                    '12' => 1,
                    '24' => 2,
                    '36' => 3,
                    '48' => 4,
                    '60' => 5,
                ],
                'placeholder' => 'Choisir le nombre de mensualités...',
            ])
            ->add('rachat')
            ->add('projectNotes')
            ->add('status')
           /* ->add('createdAt')
            ->add('updatedAt')
            ->add('client')
            ->add('projectMakerUser')*/
            ->add('equipment', EntityType::class, [
                'class' => Equipment::class,
               'placeholder' => 'Choisir la technologie...',
               'required' => true,
           ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
