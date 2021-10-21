<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\EventType;
use App\Entity\GeographicArea;
use App\Entity\GeographicZoneEvent;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function mysql_xdevapi\getSession;

class AppointmentController extends AbstractController
{

    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/appointments/commercialslist", name="appointment")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $loggedUserId = $this->getUser()->getId();
        /*dd($this->getUser()->getRoles());*/
        if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
            $clients = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
            $data = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        } elseif (in_array("ROLE_TELEPRO", $this->getUser()->getRoles())) {
            $teleproGeographicAreasArray = $this->getUser()->getGeographicAreas();
            $teleproGeographicAreasIdsArray = [];
            foreach ($teleproGeographicAreasArray as $geographicArea) {
                $teleproGeographicAreasIdsArray[] =  $geographicArea->getId();
            }
            $clients = $this->getDoctrine()->getRepository(Client::class)->findClientsByTeleproDepartments($teleproGeographicAreasIdsArray, $this->getUser()->getId());
            $data = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId, "ROLE_COMMERCIAL");
        }

        //pagination
        if($session->get('pagination_value')) {
            $commercial_agents = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $commercial_agents = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        /*dd($commercial_agents);*/
        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);
        /*$clients = $this->getDoctrine()->getRepository(Client::class)->findAll();*/
        if($appointmentForm->isSubmitted()) {
            // for validation -> appointment duration must be <= 3 hours
            $validationStartTime = $newAppointment->getStart();
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
               /* $this->addFlash(
                    'appointment_duration_warning',
                    "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                );*/
                return $this->render('/appointment/index.html.twig', [
                    'all_commercial_agents' => $data,
                    'commercial_agents' => $commercial_agents,
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
                /*dd($busyAppointmentsTime);*/
                if($busyAppointmentsTime) {
                    $busyCommercialsIdsArray = [];
                    foreach ($busyAppointmentsTime as $busyAppointment) {
                        $busyCommercialsIdsArray[] = $busyAppointment->getUser()->getId();
                    }
                    if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercialsForSuperAdmin($busyCommercialsIdsArray, "ROLE_COMMERCIAL");
                    } else {
                        /*$busyCommercialId = $busyAppointmentsTime[0]->getUser()->getId();*/
                        /*dd($busyCommercialId);*/
                        /*dd($result);*/
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercials($busyCommercialsIdsArray, "ROLE_COMMERCIAL", $loggedUserId);
                        /*dd($freeCommercials);*/
                    }

                } else {
                    if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
                    } else {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId,"ROLE_COMMERCIAL");
                    }
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
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV doit pas être nulle !");
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV doit pas être nulle!"
                    );*/
                } else {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures !");
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures!"
                    );*/
                }
                return $this->render('/appointment/index.html.twig', [
                    'all_commercial_agents' => $data,
                    'commercial_agents' => $commercial_agents,
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }

        }


        return $this->render('appointment/index.html.twig', [
            'all_commercial_agents' => $data,
            'commercial_agents' => $commercial_agents,
            'appointment_form' => $appointmentForm->createView()
        ]);
    }

    /**
     * @Route("/dashboard/mycalendar", name="show_my_calendar")
     */
    public function showMyCalendar(Request $request, AppointmentRepository $appointment): Response
    {
        $manager = $this->getDoctrine()->getManager();
        /*$events = $appointment->findBy(['user' => $this->getUser()->getId()]);*/
        $events = $appointment->getAllAppointmentsOfUser($this->getUser()->getId());
        /*$clients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);*/

        /*dd($events);*/
        $appointments = [];
        foreach ($events as $event) {
            if ($event->getClient()) {
                $appointments[] = [
                    'id' => $event->getId(),
                    'title' => $event->getClient()->getFirstName() . " " . $event->getClient()->getLastName(),
                    'start' => $event->getStart()->format('Y-m-d H:i:s'),
                    'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                    'description' => $event->getAppointmentCall()->getCallIfAppointmentNotes(),
                    'backgroundColor' => $event->getEventType()->getBackgroundColor(),
                    'allDay' => false
                ];
            } else {
                $appointments[] = [
                    'id' => $event->getId(),
                    'title' => $event->getEventType()->getDesignation(),
                    'start' => $event->getStart()->format('Y-m-d H:i:s'),
                    'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                    'description' => $event->getAppointmentNotes(),
                    'backgroundColor' => $event->getEventType()->getBackgroundColor(),
                    'allDay' => false
                ];
            }
        }

        $data = json_encode($appointments);

            $myPersonalEvent = new Appointment();
            $myPersonalEventForm = $this->createForm(AppointmentFormType::class, $myPersonalEvent);
            $myPersonalEventForm->handleRequest($request);



            if($myPersonalEventForm->isSubmitted()) {

                $validationStartTime = $myPersonalEvent->getStart();
                $validationEndTime = $myPersonalEvent->getEnd();
                $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
                if($validationEndTime < $validationStartTime) {
                    $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                    /*$this->addFlash(
                        'event_duration_warning',
                        "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                    );*/
                    return $this->redirectToRoute('show_my_calendar');
                }

                if((($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h <= 2)) ||
                    (($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h === 3)
                        && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0))
                ) {
                    $startTime = $myPersonalEvent->getStart()->format('Y-m-d H:i:s');
                    $endTime = $myPersonalEvent->getEnd()->format('Y-m-d H:i:s');
                    $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                    /*dd($busyAppointmentsTime);*/
                    if($busyAppointmentsTime) {
                        foreach ($busyAppointmentsTime as $appointment) {
                            if($appointment->getUser()->getId() === (int)$this->getUser()->getId()) {
                                $this->flashy->info("Aucune disponibilité à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                                /*$this->addFlash(
                                    'event_busy_warning',
                                    "Aucune disponibilité à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates!"
                                );*/
                                return $this->redirectToRoute('show_my_calendar');
                            }
                        }
                        /* $busyCommercialId = $busyAppointmentsTime[0]->getUser()->getId();*/

                    } else {
                       /* dd($request->request->get('notes'));*/

                        $newEvent = new Appointment();
                        $newEvent->setCreatedAt(new \DateTime());
                        $newEvent->setUser($this->getUser());
                        $newEvent->setStatus(0);
                        $newEvent->setStart($validationStartTime);
                        $newEvent->setEnd($validationEndTime);
                        $newEvent->setIsDone(0);
                        $newEvent->setAppointmentNotes($request->request->get('notes'));
                        $newEvent->setIsDeleted(false);
                        $manager->persist($newEvent);
                        $manager->flush();
                        $this->flashy->success("Evénement fixé avec succès !");

                        /*$this->addFlash(
                            'event_confirmation',
                            "Félicitations! L'événement est fixé avec succès!"
                        );*/
                        return $this->redirectToRoute('show_my_calendar');
                    }

                }
                else {
                    if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                        && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                        /*dd($appointmentDuration);*/
                        $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle !");
                        /*$this->addFlash(
                            'event_duration_warning',
                            "Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle!"
                        );*/
                    } else {
                        $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas dépasser trois heures !");
                        /*$this->addFlash(
                            'event_duration_warning',
                            "Veuillez revérifier vos entrées! La durée de l'événement ne doit pas dépasser trois heures!"
                        );*/
                    }
                    return $this->redirectToRoute('show_my_calendar');
                }

                }




            return $this->render('/appointment/show_my_calendar.html.twig', [
                /*'calendar_to_show' => $calendarToShow,*/
                'data' => compact('data'),
                'my_personal_event_form' => $myPersonalEventForm->createView(),
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
        /*$events = $appointment->findBy(['user' => $id]);*/
        $events = $appointment->getAllAppointmentsOfUser($id);
        /*$clients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);*/
        /*$clients = $this->getDoctrine()->getRepository(Client::class)->findAll();*/
        if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
            $clients = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        } elseif (in_array("ROLE_TELEPRO", $this->getUser()->getRoles())) {
            $teleproGeographicAreasArray = $this->getUser()->getGeographicAreas();
            $teleproGeographicAreasIdsArray = [];
            foreach ($teleproGeographicAreasArray as $geographicArea) {
                $teleproGeographicAreasIdsArray[] =  $geographicArea->getId();
            }
            $clients = $this->getDoctrine()->getRepository(Client::class)->findClientsByTeleproDepartments($teleproGeographicAreasIdsArray, $this->getUser()->getId());
        }
        $geographicZoneEvents = $this->getDoctrine()->getRepository(GeographicZoneEvent::class)->getUserGeographicZoneEvents($id);
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        /*dd($geographicZoneEvents);*/


        /*dd($events);*/
        $appointments = [];
        /*dd($events[0]->getClient());*/
        foreach ($events as $event) {
            /*if ($event->getEventType()->getId() === 7) {

                $appointments[] = [
                    'id' => $event->getId(),
                    'title' => $event->getAppointmentNotes(),
                    'start' => $event->getStart()->format('Y-m-d'),
                    'end' => $event->getEnd()->add(new \DateInterval('P1D'))->format('Y-m-d'),
                    'description' => $event->getAppointmentNotes(),
                    'backgroundColor' => $event->getEventType()->getBackgroundColor(),
                    'allDay' => true,
                ];
            }*/
                if ($event->getClient()) {
                    $appointments[] = [
                        'id' => $event->getId(),
                        'client' => $event->getClient()->getFirstName() . " " . $event->getClient()->getLastName(),
                        'title' => $event->getEventType()->getDesignation(),
                        'start' => $event->getStart()->format('Y-m-d H:i:s'),
                        'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                        'description' => $event->getAppointmentCall()->getCallIfAppointmentNotes(),
                        'backgroundColor' => $event->getEventType()->getBackgroundColor(),
                        'allDay' => false
                    ];
                } else {
                    $appointments[] = [
                        'id' => $event->getId(),
                        'title' => $event->getEventType()->getDesignation(),
                        'start' => $event->getStart()->format('Y-m-d H:i:s'),
                        'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                        'description' => $event->getAppointmentNotes(),
                        'backgroundColor' => $event->getEventType()->getBackgroundColor(),
                        'allDay' => false
                    ];
                }

        }

        foreach ($geographicZoneEvents as $geographicZoneEvent) {
            $title = "";
            $geoAreasIdsArray = [];
            foreach ($geographicZoneEvent->getGeographicAreas() as $geographicArea) {
                $title = $title . " | " . $geographicArea->getDesignation();
                $geoAreasIdsArray[] = $geographicArea->getId();
            }
            $appointments[] = [
                'id' => $geographicZoneEvent->getId(),
                'title' => $title,
                'start' => $geographicZoneEvent->getStart()->format('Y-m-d'),
                'end' => $geographicZoneEvent->getEnd()->add(new \DateInterval('P1D'))->format('Y-m-d'),
                'geoareasid' => $geoAreasIdsArray,
                /*'description' => "geo zone obs test",*/
                'backgroundColor' => "#800000",
                'allDay' => true,
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

            $eventTypeId = (int)($request->request->get("event_type"));
            $validationStartTime = $newAppointment->getStart();
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                /*$this->addFlash(
                    'appointment_duration_warning',
                    "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                );*/
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
                /*dd($request->request->get("event_type"));*/
                $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                /*dd($busyAppointmentsTime);*/
                if($busyAppointmentsTime) {
                    foreach ($busyAppointmentsTime as $appointment) {
                        if($appointment->getUser()->getId() === (int)$id) {
                            $this->flashy->info("Cet utilisateur n'est pas disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                            /*$this->addFlash(
                                'appointment_busy_commercial_warning',
                                "Cet utilisateur n'est pas disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates!"
                            );*/
                            return $this->redirectToRoute('show_calendar', [
                                'id' => $id,
                            ]);
                        }
                    }
                   /* $busyCommercialId = $busyAppointmentsTime[0]->getUser()->getId();*/

                    } else {

                        if($eventTypeId === 4) {
                            return $this->render('/appointment/free_commercial_client_assignment.html.twig', [
                                'commercial_user' => $commercialUser,
                                'clients' => $clients,
                                'start' => $startTime,
                                'end' => $endTime
                            ]);
                        } else {
                            return $this->render('/appointment/event_set_notes.html.twig', [
                                'eventTypeId' =>  $eventTypeId,
                                'commercial_user' => $commercialUser,
                                'start' => $startTime,
                                'end' => $endTime
                            ]);
                        }
                    }

                }

               else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle!"
                    );*/
                } else {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures !");
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures!"
                    );*/
                }
                return $this->redirectToRoute('show_calendar', [
                    'id' => $id,
                ]);
            }

        }

        return $this->render('/appointment/show_calendar.html.twig', [
            /*'calendar_to_show' => $calendarToShow,*/
            'data' => compact('data'),
            'departments' => $geographicAreas,
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
        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $clients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);
        return $this->render('/appointment/index.html.twig', [
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
            /*dd($request->request->get("event_type_id"));*/
            if($request->request->get("event_type_id") !== null) {
                $eventType = $this->getDoctrine()->getRepository(EventType::class)->find($request->request->get("event_type_id"));
                $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));
                $newEvent = new Appointment();
                $newEvent->setCreatedAt(new \DateTime());
                $newEvent->setUser($commercial);
                $newEvent->setEventType($eventType);
                $newEvent->setStatus(0);
                $newEvent->setStart(new \DateTime($request->request->get('start')));
                $newEvent->setEnd(new \DateTime($request->request->get('end')));
                $newEvent->setIsDone(0);
                $newEvent->setAppointmentNotes($request->request->get('event_notes'));
                $newEvent->setIsDeleted(false);
                $manager->persist($newEvent);
                $manager->flush();
                $this->flashy->success("Evénement fixé avec succès !");
                return $this->redirectToRoute('show_calendar', [
                    'id' => $request->request->get('commercial'),
                ]);
            }
            $client = $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('client'));
            /*dd($client->getCalls());*/
            /*dd($client);*/
            /*$value = false;
            foreach ($client->getCalls() as $call) {
                if ($call->getStatusDetails() === 7) {
                    $value = true;
                    break;
                }
            }
            if(in_array("ROLE_TELEPRO", $this->getUser()->getRoles())) {
                if (!$value) {
                    $this->flashy->warning("Désolé! Ce client doit être traité avant l'affectation un RDV !");
                    return $this->redirectToRoute('appointment');
                }
            }*/

            /*dd($call);*/
            /*dd($client->getId());*/
            $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));

            $newAppointment = new Appointment();
            $newAppointment->setStatus(0);
            $newAppointment->setIsDone(0);
            $newAppointment->setStart(new \DateTime($request->request->get('start')));
            $newAppointment->setEnd(new \DateTime($request->request->get('end')));
            $newAppointment->setCreatedAt(new \DateTime());
            $newAppointment->setClient($client);
            $newAppointment->setUser($commercial);
            $newAppointment->setIsDeleted(false);
            $newAppointment->setEventType($this->getDoctrine()->getRepository(EventType::class)->find(4));
            /*$newAppointment->setAppointmentCallNotes($request->request->get('notes'));*/
            /*$call->setCallNotes($request->request->get('notes'));*/
            /*$newAppointment->setAppointmentNotes($request->request->get('notes'));*/
            /*$client->setStatus(2);*/
            $client->setStatus(2);
            $client->setStatusDetail(7);

            /*$value = false;
            foreach ($client->getCalls() as $call) {
                if ($call->getStatus() == 2 && $call->getStatusDetails() == 7) {
                    $value = true;
                    break;
                }
            }*/
            /*if(!$value) {*/
            $aNewCall = new Call();
            $aNewCall->setUser($this->getUser());
            $aNewCall->setClient($client);
            $aNewCall->setGeneralStatus(2);
            $aNewCall->setStatusDetails(7);
            $aNewCall->setCallIfAppointmentNotes($request->request->get('notes'));
            $aNewCall->setCreatedAt(new \DateTime());
            $aNewCall->setIsDeleted(false);
            /*}*/
            $newAppointment->setAppointmentCall($aNewCall);

            $manager->persist($newAppointment);
            $manager->persist($aNewCall);
            $manager->flush();
            $this->flashy->success("RDV fixé avec succès !");
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
            $client = $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('client'));
            /*dd($client->getCalls());*/
            /*dd($client);*/
            /*$value = false;
            foreach ($client->getCalls() as $call) {
                if ($call->getStatusDetails() === 7) {
                    $value = true;
                    break;
                }
            }
            if(in_array("ROLE_TELEPRO", $this->getUser()->getRoles())) {
                if (!$value) {
                    $this->flashy->warning("Désolé! Ce client doit être traité avant l'affectation un RDV !");
                    return $this->redirectToRoute('appointment');
                }
            }*/

            /*dd($call);*/
            /*dd($client->getId());*/
            $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));

            $newAppointment = new Appointment();
            $newAppointment->setStatus(0);
            $newAppointment->setIsDone(0);
            $newAppointment->setStart(new \DateTime($request->request->get('start')));
            $newAppointment->setEnd(new \DateTime($request->request->get('end')));
            $newAppointment->setCreatedAt(new \DateTime());
            $newAppointment->setClient($client);
            $newAppointment->setUser($commercial);
            $newAppointment->setIsDeleted(false);
            $newAppointment->setEventType($this->getDoctrine()->getRepository(EventType::class)->find(4));
            /*$newAppointment->setAppointmentCallNotes($request->request->get('notes'));*/
            /*$call->setCallNotes($request->request->get('notes'));*/
            /*$newAppointment->setAppointmentNotes($request->request->get('notes'));*/
            /*$client->setStatus(2);*/
            $client->setStatus(2);
            $client->setStatusDetail(7);

            /*$value = false;
            foreach ($client->getCalls() as $call) {
                if ($call->getStatus() == 2 && $call->getStatusDetails() == 7) {
                    $value = true;
                    break;
                }
            }*/
            /*if(!$value) {*/
            $aNewCall = new Call();
            $aNewCall->setUser($this->getUser());
            $aNewCall->setClient($client);
            $aNewCall->setGeneralStatus(2);
            $aNewCall->setStatusDetails(7);
            $aNewCall->setCallIfAppointmentNotes($request->request->get('notes'));
            $aNewCall->setCreatedAt(new \DateTime());
            $aNewCall->setIsDeleted(false);
            /*}*/
            $newAppointment->setAppointmentCall($aNewCall);

            $manager->persist($newAppointment);
            $manager->persist($aNewCall);
            $manager->flush();
            $this->flashy->success("RDV fixé avec succès !");
        }
        return $this->redirectToRoute('show_calendar', [
            'id' => $request->request->get('commercial'),
        ]);
    }

    /**
     * @Route("/dashboard/appointments/{id}/show", name="show_appointment")
     */
    public function show(Request $request, $id): Response
    {
        $appointmentToShow = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        return $this->render('/appointment/show.html.twig', [
            'appointment_to_show' => $appointmentToShow
        ]);
    }

    /**
     * @Route("/dashboard/appointments/update/appointment/{id}", name="update_appointment")
     */
    public function fullUpdateAppointment(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $appointmentToUpdate = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $selectedCommercial = $this->getDoctrine()->getRepository(User::class)->find((int)$request->request->get('assigned_commercial_appointment'));
        /*$clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($appointmentToUpdate->getClient()->getId());*/
        $clientId = $appointmentToUpdate->getClient()->getId();
        $appointmentToUpdate->setStart(new \DateTime($request->request->get('start_appointment')));
        $appointmentToUpdate->setEnd(new \DateTime($request->request->get('end_appointment')));
        $appointmentToUpdate->setAppointmentNotes($request->request->get('notes_appointment'));
        $appointmentToUpdate->setUser($selectedCommercial);
        $manager->persist($appointmentToUpdate);
        $manager->flush();
        /*dd(new \DateTime($request->request->get('start_appointment')));
        dd($request->request->all());
        dd($id);*/
        $this->flashy->success('Rendez-Vous mis à jour avec succès !');
        return $this->redirectToRoute('full_update_contact', [
            "id" => $clientId
        ]);
    }

    /**
     * @Route("/dashboard/appointments/delete/appointment/{id}", name="delete_appointment")
     */
    public function deleteCall(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $appointmentToDelete = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $clientId = $appointmentToDelete->getClient()->getId();
        $appointmentToDelete->setIsDeleted(true);
        $appointmentToDelete->setDeletionDate(new \DateTime());
        $manager->persist($appointmentToDelete);
        $manager->flush();
        $this->flashy->success('RDV supprimé avec succès !');
        return $this->redirectToRoute('full_update_contact', [
            "id" => $clientId
        ]);
    }

    /**
     * @Route("/dashboard/appointments/restore/appointment/{id}", name="restore_appointment")
     */
    public function restoreCall(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $appointmentToRestore = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $appointmentToRestore->setIsDeleted(false);
        $appointmentToRestore->setDeletionDate(null);
        $manager->persist($appointmentToRestore);
        $manager->flush();
        $this->flashy->success("RDV restauré avec succès !");
        return $this->redirectToRoute('trash_appointments');
    }

    /**
     * @Route("/dashboard/appointments/showcalendar/{id}/addgeozoneevent", name="add_geo_zone_event")
     */
    public function addGeoZoneEvent(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {
            /*dd($request->request->all());*/
            $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial_id'));
            $myString = $request->request->get('departments');
            $departmentsArray = explode(',', $myString);
            /*dd($departmentsArray);*/
            $newGeoegraphicZoneEvent = new GeographicZoneEvent();
            $newGeoegraphicZoneEvent->setCalendarUser($commercial);
            $newGeoegraphicZoneEvent->setStart(new \DateTime($request->request->get('start')));
            $newGeoegraphicZoneEvent->setEnd(new \DateTime($request->request->get('end')));
            if($departmentsArray) {
                foreach ($departmentsArray as $department) {
                    if($department) {
                        $newGeoegraphicZoneEvent->addGeographicArea($this->getDoctrine()->getRepository(GeographicArea::class)->find($department));
                    }
                }
            }
            $manager->persist($newGeoegraphicZoneEvent);
            $manager->flush();
            $this->flashy->success("Zone Géographique attribuée avec succès !");
        }
        return $this->redirectToRoute('show_calendar', [
            'id' => $request->request->get('commercial_id'),
        ]);
    }

    /**
     * @Route("/dashboard/appointments/showcalendar/deletegeozoneevent/{id}", name="delete_geo_zone_event")
     */
    public function deleteGeoZoneEvent(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $eventToDelete = $this->getDoctrine()->getRepository(GeographicZoneEvent::class)->find($id);
        $calendarUserId = $eventToDelete->getCalendarUser()->getId();
        $manager->remove($eventToDelete);
        $manager->flush();
        $this->flashy->success('Zone Géographique supprimée avec succès !');
        return $this->redirectToRoute('show_calendar', [
            "id" => $calendarUserId
        ]);
    }
}
