<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('companyName')
            ->add('address')
            ->add('postalCode')
            ->add('country')
            ->add('phoneNumber')
            ->add('mobileNumber')
            ->add('category', ChoiceType::class, [
                'choices'  => [
                    'Médecin' => 'Médecin',
                    'Vétérinaire' => 'Vétérinaire',
                    'Chirurgien' => 'Chirurgien'
                ],
                'placeholder' => 'Catégorie'
            ])
            ->add('providedEquipment')
            ->add('geographicArea')
            ->add('isUnderContract', ChoiceType::class, [
                'choices'  => [
                    'Non' => false,
                    'Oui' => true
                ],
                'placeholder' => 'Sous Contrat?'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
