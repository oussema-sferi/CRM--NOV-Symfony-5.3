<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\GeographicArea;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', null,[
                'required' => true
            ])
            ->add('lastName', null,[
                'required' => true
            ])
            ->add('email', EmailType::class,[
                'required' => true
            ])
            ->add('companyName')
            ->add('address', null,[
                'required' => true
            ])
            ->add('postalCode', )
            ->add('city', null,[
                'required' => true
            ])
            ->add('country', null,[
                'required' => true
            ])
            ->add('phoneNumber', null,[
                'required' => true
            ])
            ->add('mobileNumber')
            ->add('category', ChoiceType::class, [
                'choices'  => [
                    'Médecin' => 'Médecin',
                    'Vétérinaire' => 'Vétérinaire',
                    'Chirurgien' => 'Chirurgien'
                ],
                'placeholder' => 'Choisir la catégorie...',
                'required' => false
            ])
            ->add('providedEquipment', EntityType::class, [
                'class' => Equipment::class,
                'placeholder' => "Choisir l'équipement...",
                'required' => false
            ])
            ->add('geographicArea', EntityType::class, [
                'class' => GeographicArea::class,
                'placeholder' => 'Choisir le département...',
                'required' => true
            ])
            ->add('isUnderContract', ChoiceType::class, [
                'choices'  => [
                    'Non' => false,
                    'Oui' => true
                ],
                'placeholder' => 'Sous contrat ?',
                'required' => true
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
