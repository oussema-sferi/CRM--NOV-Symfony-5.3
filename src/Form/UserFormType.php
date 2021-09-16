<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            /*->add('password')*/
            ->add('firstName')
            ->add('lastName')
            ->add('roles', CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'entry_options' => [
                    'label' => false,
                    'choices' => [
                        'Super Admin' => 'ROLE_SUPERADMIN',
                        'Admin' => 'ROLE_ADMIN',
                        'Commercial' => 'ROLE_COMMERCIAL',
                        'Téléprospecteur' => 'ROLE_TELEPRO'
                    ],
                    /*'data' => 'ROLE_COMMERCIAL'*/
                ]
            ])
            /*->add('teleprospector')*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
