<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommercialController extends AbstractController
{
    /**
     * @Route("/dashboard/commercial", name="commercial")
     */
    public function index(AppointmentRepository $appointment): Response
    {
        $commercialAppointments = $appointment->findAll();
        return $this->render('commercial/index.html.twig', [
            'commercial_appointments' => $commercialAppointments,
        ]);
    }

    /**
     * @Route("/dashboard/commercial/mycalendar", name="my_calendar")
     */
    public function showMyCalendar(AppointmentRepository $appointment): Response
    {
        $loggedUser = $this->getUser();
        /*dd($loggedUser->getRoles());*/
        if($loggedUser && in_array("ROLE_COMMERCIAL", $loggedUser->getRoles())) {
            $events = $appointment->findBy(['user' => $loggedUser->getId()]);
            $appointments = [];
            foreach ($events as $event) {
                $appointments[] = [
                    'id' => $event->getId(),
                    'title' => $event->getClient()->getFirstName() . " " . $event->getClient()->getLastName(),
                    'start' => $event->getStart()->format('Y-m-d H:i:s'),
                    'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                ];
            }
            $data = json_encode($appointments);
            return $this->render('/commercial/show_my_calendar.html.twig', [
                'data' => compact('data'),
            ]);
        }
        return $this->redirectToRoute('commercial');
    }

    /**
     * @Route("/dashboard/commercial/mycontacts", name="commercial_my_contacts")
     */
    public function myContacts(AppointmentRepository $appointment): Response
    {
        $commercialContacts = $appointment->findAll();
        return $this->render('commercial/my_contacts.html.twig', [
            'commercial_appointments' => $commercialContacts,
        ]);
    }
}
