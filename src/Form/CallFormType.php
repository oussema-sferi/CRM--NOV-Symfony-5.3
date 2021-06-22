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
            ->add('generalStatus', ChoiceType::class, [
                'choices'  => [
                    'Appel non Qualifié' => 1,
                    'Appel Qualifié' => 2
                ],
            ])
            ->add('statusDetails', ChoiceType::class, [
                'choices'  => [
                    'Ne répond pas' => 0,
                    'Répondeur' => 1,
                    'Barrage Secrétaire' => 2
                ],
            ])
            ->add('callNotes', TextareaType::class, [
                    'attr' => [
                        'rows' => 6,
                        'resize' => 'none'
                        ]
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
