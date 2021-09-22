<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Repository\ClientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', DateTimeType::class, [
                'attr' => ['class' => 'form-control'],
                'widget' => 'single_text'
            ])
            ->add('end', DateTimeType::class, [
                'attr' => ['class' => 'form-control'],
                'widget' => 'single_text'
            ])
         /*   ->add('client', EntityType::class, [
                'class' => Client::class,
                'query_builder' => function (ClientRepository $client) {
                    return $client->createQueryBuilder('c')
                        ->where("c.status = 0");
                }
            ])*/
            /*->add('end', DateTimeType::class, [
                'date_widget' => 'single_text'
            ])*/
            /*->add('status')
            ->add('appointmentNotes')
            ->add('client')
            ->add('user')*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
