<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\GeographicArea;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                'placeholder' => 'Choisir la catégorie'
            ])
            ->add('providedEquipment', EntityType::class, [
                'class' => Equipment::class,
                'placeholder' => "Choisir l'équipement..."
            ])
            ->add('geographicArea', EntityType::class, [
                'class' => GeographicArea::class,
                'placeholder' => 'Choisir le département...'
            ])
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
