<?php

namespace App\Form;

use App\Entity\Call;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CallFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*->add('createdAt')*/
           /* ->add('generalStatus', ChoiceType::class, [
                'placeholder' => "Choisir le statut de l'appel",
                'choices'  => [
                    'Appel non Qualifié' => 1,
                    'Appel Qualifié' => 2
                ],
            ])
            ->add('statusDetails', ChoiceType::class, [
                'placeholder' => "Choisir les détails de l'appel",
                'choices'  => [
                    'Ne répond pas' => 1,
                    'Répondeur' => 2,
                    'Barrage Secrétaire' => 3,
                    'Pas Intéressé' => 4,
                    'A Rappeler' => 5,
                    'RDV à prendre' => 6,
                    'RDV fixé' => 7
                ],
            ])*/
            ->add('callNotes', TextareaType::class, [
                    'attr' => [
                        'rows' => 6,
                        'resize' => 'none'
                        ],
                'required' => false
            ])
            /*->add('client')*/
            /*->add('user')*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Call::class,
        ]);
    }
}
