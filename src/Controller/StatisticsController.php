<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    /**
     * @Route("/dashboard/allstats", name="all_statistics")
     */
    public function index(): Response
    {
        $allContacts = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $processedContacts = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
        $contactsPerformance = number_format(((count($processedContacts) / count($allContacts)) * 100), 2);
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        if(count($processedContacts) !== 0) {
            $appointmentsPerformance = number_format(((count($allAppointments) / count($processedContacts)) * 100), 2);
        } else {
            $appointmentsPerformance = 0;
        }


        return $this->render('statistics/index.html.twig', [
            'total_all_contacts' => count($allContacts),
            'total_processed_contacts' => count($processedContacts),
            'contacts_performance' => $contactsPerformance,
            'total_appointments' => count($allAppointments),
            'appointments_performance' => $appointmentsPerformance,
        ]);
    }

}
