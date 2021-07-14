<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
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
        /*dd($this->getUser()->getRoles());*/
        if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
            $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        } elseif (in_array("ROLE_TELEPRO", $this->getUser()->getRoles())) {
            $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId, "ROLE_COMMERCIAL");
        } elseif (in_array("ROLE_COMMERCIAL", $this->getUser()->getRoles())) {
            return $this->redirectToRoute("show_calendar", [
                "id" => $loggedUserId
            ]);
        }
        /*$commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");*/

        /*dd($commercial_agents);*/
        return $this->render('appointment/index.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

    /**
     * @Route("/dashboard/appointments/showcalendar/{id}", name="show_calendar")
     */
    public function showCalendar(Request $request, $id, AppointmentRepository $appointment): Response
    {
        /*$client = $this->getDoctrine()->getRepository(Client::class)->find(3);*/
        $commercialUser = $this->getDoctrine()->getRepository(User::class)->find($id);
        /*dd($client);*/
        $events = $appointment->findBy(['user' => $id]);
        /*dd($events);*/
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
        /*dd(compact('data'));*/
        $loggedUser = $this->getUser();

        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();

        /*if($appointmentForm->isSubmitted()) {
            $newAppointment->setUser($commercialUser); //commercial

            $newAppointment->setStatus(0);
            $newAppointment->setAppointmentNotes('test');
            ($newAppointment->getClient())->setStatus(1);
            $manager->persist($newAppointment);
            $manager->flush();
            return $this->redirectToRoute('show_calendar', [
                'id' => $id
            ]);
        }*/

        return $this->render('/appointment/show_calendar.html.twig', [
            /*'calendar_to_show' => $calendarToShow,*/
            'data' => compact('data'),
            'appointment_form' => $appointmentForm->createView(),
            'commercial_user' => $commercialUser
        ]);
    }

    /**
     * @Route("/dashboard/appointments/availibilitycheck/", name="availibility_check")
     */
    public function availabilityCheck(Request $request, AppointmentRepository $appointment): Response
    {
        $loggedUserId = $this->getUser()->getId();
        /*dd($loggedUserId);*/
        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);


        if($appointmentForm->isSubmitted()) {
            $startTime = $newAppointment->getStart()->format('Y-m-d H:i:s');
            $endTime = $newAppointment->getEnd()->format('Y-m-d H:i:s');
            /*dd($startTime);*/
            $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
            /*dd($busyAppointmentsTime);*/
            if($busyAppointmentsTime) {
                $busyCommercial = $busyAppointmentsTime[0]->getUser()->getId();
                $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercials($busyCommercial, "ROLE_COMMERCIAL", $loggedUserId);
            } else {
                $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId,"ROLE_COMMERCIAL");

            }

            /*dd($freeCommercials);*/
            /*dd($clients);*/
            return $this->render('/appointment/free_commercials_check.html.twig', [
                /*'free_appointments' => $freeAppointmentsTime*/
                'free_commercials' => $freeCommercials,
                'clients' => $clients,
                'start' => $startTime,
                'end' => $endTime
            ]);
        }

        return $this->render('/appointment/fix_appointment.html.twig', [
            'appointment_form' => $appointmentForm->createView(),
        ]);
    }

    /**
     * @Route("/dashboard/appointments/fixappointment/", name="fix_appointment")
     */
    public function fixAppointment(Request $request): Response
    {
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {
            $client = $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('client'));
            $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));

            $newAppointment = new Appointment();
            $newAppointment->setStatus(0);
            $newAppointment->setStart(new \DateTime($request->request->get('start')));
            $newAppointment->setEnd(new \DateTime($request->request->get('end')));
            $newAppointment->setClient($client);
            $newAppointment->setUser($commercial);
            $newAppointment->setAppointmentNotes($request->request->get('notes'));

            $manager->persist($newAppointment);
            $manager->flush();
            return $this->redirectToRoute('show_calendar', [
                'id' => $request->request->get('commercial'),
            ]);
        }

    }


}
