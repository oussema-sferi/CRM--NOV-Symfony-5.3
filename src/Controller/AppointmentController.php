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
use function mysql_xdevapi\getSession;

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
        } /*elseif (in_array("ROLE_COMMERCIAL", $this->getUser()->getRoles())) {
            return $this->redirectToRoute("show_calendar", [
                "id" => $loggedUserId
            ]);
        }*/
        /*$commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");*/

        /*dd($commercial_agents);*/
        return $this->render('appointment/index.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

    /**
     * @Route("/dashboard/mycalendar", name="show_my_calendar")
     */
    public function showMyCalendar(): Response
    {
        $loggedUserId = $this->getUser()->getId();
        /*dd($this->getUser()->getRoles());*/
            return $this->redirectToRoute("show_calendar", [
                "id" => $loggedUserId
            ]);
    }


    /**
     * @Route("/dashboard/appointments/showcalendar/{id}", name="show_calendar")
     */
    public function showCalendar(Request $request, $id, AppointmentRepository $appointment): Response
    {
        $commercialUser = $this->getDoctrine()->getRepository(User::class)->find($id);
        // Check if the commercial exists
        if(!$commercialUser) {
            return $this->redirectToRoute('appointment');
        }
        $events = $appointment->findBy(['user' => $id]);
        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);
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

        if($appointmentForm->isSubmitted()) {

            $validationStartTime = $newAppointment->getStart();
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
            if($validationEndTime < $validationStartTime) {
                $this->addFlash(
                    'appointment_duration_warning',
                    "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                );
                return $this->redirectToRoute('show_calendar', [
                    'id' => $id,
                ]);
            }

            if((($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h <= 2)) ||
                (($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h === 3)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0))
            ) {
                $startTime = $newAppointment->getStart()->format('Y-m-d H:i:s');
                $endTime = $newAppointment->getEnd()->format('Y-m-d H:i:s');
                $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                if($busyAppointmentsTime && ($busyAppointmentsTime[0]->getUser()->getId() === (int)$id)) {
                   /* $busyCommercialId = $busyAppointmentsTime[0]->getUser()->getId();*/
                    $this->addFlash(
                        'appointment_busy_commercial_warning',
                        "Cet utilisateur n'est pas disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates!"
                    );
                    return $this->redirectToRoute('show_calendar', [
                        'id' => $id,
                    ]);
                    } else {
                        return $this->render('/appointment/free_commercial_client_assignment.html.twig', [
                            'commercial_user' => $commercialUser,
                            'clients' => $clients,
                            'start' => $startTime,
                            'end' => $endTime
                        ]);
                    }

                }

               else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    $this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV doit pas être nulle!"
                    );
                } else {
                    $this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures!"
                    );
                }
                return $this->redirectToRoute('show_calendar', [
                    'id' => $id,
                ]);
            }


        }
        /*dd($id);*/
        if($this->getUser()->getId() === (int)$id) {
            return $this->render('/appointment/show_my_calendar.html.twig', [
                /*'calendar_to_show' => $calendarToShow,*/
                'data' => compact('data'),
                'appointment_form' => $appointmentForm->createView(),
                'commercial_user' => $commercialUser
            ]);
        }

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
            // for validation -> appointment duration must be <= 3 hours
            $validationStartTime = $newAppointment->getStart();
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
            if($validationEndTime < $validationStartTime) {
                $this->addFlash(
                    'appointment_duration_warning',
                    "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                );
                return $this->render('/appointment/fix_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }
            if((($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h <= 2)) ||
                (($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h === 3)
                && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0))
            ) {
                $startTime = $newAppointment->getStart()->format('Y-m-d H:i:s');
                $endTime = $newAppointment->getEnd()->format('Y-m-d H:i:s');
                $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                if($busyAppointmentsTime) {
                    $busyCommercial = $busyAppointmentsTime[0]->getUser()->getId();
                    $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercials($busyCommercial, "ROLE_COMMERCIAL", $loggedUserId);
                } else {
                    $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId,"ROLE_COMMERCIAL");
                }
                return $this->render('/appointment/free_commercials_check.html.twig', [
                    /*'free_appointments' => $freeAppointmentsTime*/
                    'free_commercials' => $freeCommercials,
                    'clients' => $clients,
                    'start' => $startTime,
                    'end' => $endTime
                ]);
            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    $this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV doit pas être nulle!"
                    );
                } else {
                    $this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures!"
                    );
                }
                return $this->render('/appointment/fix_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }

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

        }
        return $this->redirectToRoute('show_calendar', [
            'id' => $request->request->get('commercial'),
        ]);
    }


    /**
     * @Route("/dashboard/appointments/testfixappointment/", name="test_fix_appointment")
     */
    public function testfixAppointment(Request $request): Response
    {
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {
            /*dd($request->request->all());*/
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

            $this->addFlash(
                'appointment_confirmation',
                "Félicitations! Le RDV est fixé avec succès!"
            );
        }
        return $this->redirectToRoute('show_calendar', [
            'id' => $request->request->get('commercial'),
        ]);
    }



}
