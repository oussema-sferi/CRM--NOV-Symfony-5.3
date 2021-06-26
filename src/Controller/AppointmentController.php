<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentController extends AbstractController
{
    /**
     * @Route("/dashboard/appointments", name="appointment")
     */
    public function index(): Response
    {
        $loggedUserId = $this->getUser()->getId();

        /*$commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");*/
        $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId);
        /*dd($commercial_agents);*/
        return $this->render('appointment/index.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

    /**
     * @Route("/dashboard/appointments/showcalendar/{id}", name="show_calendar")
     */
    public function show(Request $request, $id, AppointmentRepository $appointment): Response
    {
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        /*dd($client);*/
        $events = $appointment->findAll();
        /*dd($events);*/
        $appointments = [];
        foreach ($events as $event) {
            $appointments[] = [
                'id' => $event->getId(),
                'title' => $client->getFirstName(),
                'start' => $event->getStart()->format('Y-m-d H:i:s'),
                'end' => $event->getEnd()->format('Y-m-d H:i:s'),

            ];
        }

        $data = json_encode($appointments);
        /*dd(compact('data'));*/
        $loggedUser = $this->getUser();
        $calendarToShow = $this->getDoctrine()->getRepository(User::class)->find($id);
        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();

        if($appointmentForm->isSubmitted()) {
            $newAppointment->setUser($loggedUser);
            $newAppointment->setClient($client);
            $newAppointment->setStatus(0);
            $newAppointment->setAppointmentNotes('test');
            $manager->persist($newAppointment);
            $manager->flush();
            return $this->redirectToRoute('teleprospecting');
        }

        return $this->render('/appointment/show_calendar.html.twig', [
            'calendar_to_show' => $calendarToShow,
            'data' => compact('data'),
            'appointment_form' => $appointmentForm->createView(),
        ]);
    }


}
