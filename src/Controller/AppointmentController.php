<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\EventType;
use App\Entity\GeographicArea;
use App\Entity\GeographicZoneEvent;
use App\Entity\Process;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use App\Services\GoogleCalendarService;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use function mysql_xdevapi\getSession;
use Google_Service_Calendar_Event;
use Google_Service_Calendar;

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
            $myAssignedCommercials = $this->getUser()->getCommercials();
            if(count($myAssignedCommercials) === 0) {
                $data = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
            } else {
                $data = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId, "ROLE_COMMERCIAL");
            }

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
            if($validationEndTime > $validationStartTime) {
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

                        /*dd($freeCommercials);*/
                        if(count($myAssignedCommercials) === 0) {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercialsForSuperAdmin($busyCommercialsIdsArray, "ROLE_COMMERCIAL");
                        } else {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercials($busyCommercialsIdsArray, "ROLE_COMMERCIAL", $loggedUserId);
                        }
                    }

                } else {
                    if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles())) {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
                    } else {
                        if(count($myAssignedCommercials) === 0) {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
                        } else {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId,"ROLE_COMMERCIAL");
                        }
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
                } /*else {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures !");
                }*/
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

            if($validationEndTime > $validationStartTime) {
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

            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle !");
                    /*$this->addFlash(
                        'event_duration_warning',
                        "Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle!"
                    );*/
                } /*else {
                        $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas dépasser trois heures !");
                    }*/
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
     * @Route("/dashboard/appointments/showcalendar", name="show_calendar_from_google_oauth")
     */
    public function showCalendarRedirectFromGoogleOauth(Request $request, AppointmentRepository $appointment): Response
    {
        $session = new Session();
        $session->set('authcode', $_GET["code"]);
        return $this->redirectToRoute('show_calendar', [
            'id' => (int)$_GET["state"],
            /*'code' => $_GET["code"]*/
        ]);
    }


    /**
     * @Route("/dashboard/appointments/showcalendar/{id}", name="show_calendar")
     */
    public function showCalendar(Request $request, $id, AppointmentRepository $appointment, GoogleCalendarService $googleCalendarService): Response
    {
        $session = new Session();
        /*$session = new Session();
        $client = new \Google_Client();
        $client->setApplicationName("nov");
        $client->setAuthConfig('client_secret.json ');
        $client->addScope(\Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setState($id);
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . "/dashboard/appointments/showcalendar";
        $dynamicRedirect = $redirect_uri . $id;
        $client->setRedirectUri($redirect_uri);
        $guzzleClient = new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
        $client->setHttpClient($guzzleClient);
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                $authCode = $session->get('authcode');
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);
                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        $service = new \Google_Service_Calendar($client);

        $calendarId = 'primary';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,

        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();
        dd($events);*/


$authLink = "";
        /*dd($authCode);*/
        $code = $session->get('authcode');
       /* if(!empty($code)) {
            $authCode = $_GET["code"];
        }*/
        $googleEvents = [];
        $email = $this->getUser()->getEmail();
        if(!empty($code)) {
            $session->remove('authcode');
            $session->remove('authToken');
            $googleCalendarService->insertGoogleApiToken($email, $id, $code);
        }
        if (!empty($session->get('authToken'))) {
            $googleEvents = $googleCalendarService->getEventsByEmail($email, $id);

            /*$event = new Google_Service_Calendar_Event(array(
                'summary' => 'My Birthday Oussema',
                'location' => '800 Howard St., San Francisco, CA 94103',
                'description' => 'A chance to hear more about Google\'s developer products.',
                'start' => array(
                    'dateTime' => '2022-04-19T09:00:00-07:00',
                    'timeZone' => 'America/Los_Angeles',
                ),
                'end' => array(
                    'dateTime' => '2022-04-19T17:00:00-07:00',
                    'timeZone' => 'America/Los_Angeles',
                ),
                'recurrence' => array(
                    'RRULE:FREQ=DAILY;COUNT=2'
                ),
                'attendees' => array(
                    array('email' => 'lpage@example.com'),
                    array('email' => 'sbrin@example.com'),
                ),
                'reminders' => array(
                    'useDefault' => FALSE,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ));

            $service = new Google_Service_Calendar($googleCalendarService->getClient($email,$id));
            $calendarId = 'primary';

            $event = $service->events->insert($calendarId, $event);*/



        } else {
            $authLink = $googleCalendarService->generateGoogleApiToken($email, $id);
        }


       /* dd($event);
        printf('Event created: %s\n', $event->htmlLink);*/







        /*$events = $googleCalendarService->getEventsByEmail($email, $id);
        dd($events);*/



        //
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
                    'description' => $event->getAppointmentNotes(),
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
                'backgroundColor' => "#008000",
                'allDay' => true,
            ];
        }
        foreach ($googleEvents as $googleEvent) {
            if($googleEvent->getSummary()) { $googleEventTitle = $googleEvent->getSummary(); } else {$googleEventTitle = "Pas de Titre";}

            $appointments[] = [
                'id' => $googleEvent->getId(),
                'title' => $googleEventTitle ,
                'start' => $googleEvent->getStart()->getDatetime(),
                'end' => $googleEvent->getEnd()->getDatetime(),
                'description' => $googleEvent->getDescription(),
                /*'backgroundColor' => $googleEvent->getEventType()->getBackgroundColor(),*/
                'allDay' => false
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
            /*dd($validationStartTime);*/
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                return $this->redirectToRoute('show_calendar', [
                    'id' => $id,
                ]);
            }
            if($validationEndTime > $validationStartTime) {
                $startTime = $newAppointment->getStart()->format('Y-m-d H:i:s');
                $endTime = $newAppointment->getEnd()->format('Y-m-d H:i:s');
                $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                $availibilityCounter = 0;
                if($busyAppointmentsTime) {
                    foreach ($busyAppointmentsTime as $appointment) {
                        if($appointment->getUser()->getId() === (int)$id) {
                            $availibilityCounter++;
                            break;
                        }
                    }
                }
                if($availibilityCounter !== 0) {
                    if($loggedUser->getId() === (int)$id) {
                        $this->flashy->info("L'intervalle de temps choisi n'est pas disponible, Veuillez sélectionner d'autres dates !");
                    } else {
                        $this->flashy->info("Cet utilisateur n'est pas disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                    }
                    return $this->redirectToRoute('show_calendar', [
                        'id' => $id,
                    ]);
                } else {
                    if($eventTypeId === 4) {
                        return $this->render('/appointment/free_commercial_client_assignment.html.twig', [
                            'commercial_user' => $commercialUser,
                            /*'clients' => $clients,*/
                            'start' => $startTime,
                            'end' => $endTime
                        ]);
                    } else {
                        $theEvent = $this->getDoctrine()->getRepository(EventType::class)->find($eventTypeId);
                        return $this->render('/appointment/event_set_notes.html.twig', [
                            'eventTypeId' =>  $eventTypeId,
                            'eventDesignation' =>  $theEvent->getDesignation(),
                            'commercial_user' => $commercialUser,
                            'start' => $startTime,
                            'end' => $endTime
                        ]);
                    }
                }
            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    if($eventTypeId === 4) {
                        $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
                    } else {
                        $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle !");
                    }
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle!"
                    );*/
                } /*else {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures !");
                }*/
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
            'commercial_user' => $commercialUser,
            'authLink' =>$authLink
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
        $loggedUser = $this->getUser();
        if($request->isMethod('Post')) {
            /*dd($request->request->get("event_type_id"));*/
            if($request->request->get("event_type_id") !== null) {
                $eventType = $this->getDoctrine()->getRepository(EventType::class)->find($request->request->get("event_type_id"));
                $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));
                $newEvent = new Appointment();
                $newEvent->setCreatedAt(new \DateTime());
                $newEvent->setUser($commercial);
                $newEvent->setEventType($eventType);
                $newEvent->setStart(new \DateTime($request->request->get('start')));
                $newEvent->setEnd(new \DateTime($request->request->get('end')));
                $newEvent->setIsDone(0);
                $newEvent->setIsPostponed(0);
                $newEvent->setPostponedAt(null);
                $newEvent->setAppointmentNotes($request->request->get('event_notes'));
                $newEvent->setIsDeleted(false);
                $newEvent->setAppointmentFixer($this->getUser());
                $manager->persist($newEvent);
                $manager->flush();
                $this->flashy->success("Evénement fixé avec succès !");
                return $this->redirectToRoute('show_calendar', [
                    'id' => $request->request->get('commercial'),
                ]);
            } else {
                $client = $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('client'));
                $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));
                $newAppointment = new Appointment();
                /*$newAppointment->setStatus(0);*/
                $newAppointment->setIsDone(0);
                $newAppointment->setStart(new \DateTime($request->request->get('start')));
                $newAppointment->setEnd(new \DateTime($request->request->get('end')));
                $newAppointment->setCreatedAt(new \DateTime());
                $newAppointment->setClient($client);
                $newAppointment->setUser($commercial);
                $newAppointment->setIsDeleted(false);
                $newAppointment->setIsPostponed(false);
                $newAppointment->setAppointmentFixer($this->getUser());
                $newAppointment->setAppointmentNotes($request->request->get('notes'));
                $newAppointment->setEventType($this->getDoctrine()->getRepository(EventType::class)->find(4));
                /*$newAppointment->setAppointmentCallNotes($request->request->get('notes'));*/
                /*$call->setCallNotes($request->request->get('notes'));*/
                /*$newAppointment->setAppointmentNotes($request->request->get('notes'));*/
                /*$client->setStatus(2);*/
                $client->setStatus(2);
                $client->setStatusDetail(7);
                $client->setIsProcessed(true);
                $manager->persist($newAppointment);
                $client->setUpdatedAt(new \DateTime());
                /*$manager->persist($aNewCall);*/
                $loggedUser->addProcessedClient($client);

                if($request->request->get("not_direct_appointment") !== null) {
                    /*$newAppointment->setAppointmentNotes($request->request->get('notes'));*/
                    $newCall = new Call();
                    $newCall->setCreatedAt(new  \DateTime());
                    $newCall->setUser($loggedUser);
                    $newCall->setClient($client);
                    $newCall->setGeneralStatus(2);
                    $newCall->setStatusDetails(7);
                    $newCall->setCallNotes($request->request->get('call_notes'));
                    $newCall->setIsDeleted(false);
                    $usersWhoCalled = $client->getCallersUsers();
                    $userCounter = 0;
                    foreach ($usersWhoCalled as $user) {
                        if ($user->getId() == $loggedUser->getId()) {
                            $userCounter += 1;
                            break;
                        }
                    }
                    if ($userCounter === 0) {
                        $client->addCallersUser($loggedUser);
                    }
                    $manager->persist($newCall);
                }

                $newProcess = new Process();
                $newProcess->setClient($client);
                $newProcess->setProcessorUser($loggedUser);
                $newProcess->setStatus(2);
                $newProcess->setStatusDetail(7);
                $newProcess->setCreatedAt(new \DateTime());
                $manager->persist($newProcess);
                $manager->flush();
                $this->flashy->success("RDV fixé avec succès !");
            }
            if($request->request->get("add_appointment_from_contact") !== null) {
                return $this->redirectToRoute('show_contact', [
                    'id' => $client->getId(),
                ]);
            } elseif ($request->request->get("not_direct_appointment") !== null) {
                return $this->redirectToRoute('teleprospecting');
            }
            else {
                return $this->redirectToRoute('show_calendar', [
                    'id' => $request->request->get('commercial'),
                ]);
            }
        }
    }


    /**
     * @Route("/dashboard/appointments/testfixappointment/", name="test_fix_appointment")
     */
    public function testfixAppointment(Request $request): Response
    {
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {
            $client = $this->getDoctrine()->getRepository(Client::class)->find($request->request->get('client'));

            $commercial = $this->getDoctrine()->getRepository(User::class)->find($request->request->get('commercial'));

            $newAppointment = new Appointment();
            /*$newAppointment->setStatus(0);*/
            $newAppointment->setIsDone(0);
            $newAppointment->setStart(new \DateTime($request->request->get('start')));
            $newAppointment->setEnd(new \DateTime($request->request->get('end')));
            $newAppointment->setCreatedAt(new \DateTime());
            $newAppointment->setClient($client);
            $newAppointment->setUser($commercial);
            $newAppointment->setIsDeleted(false);
            $newAppointment->setIsPostponed(false);
            $newAppointment->setAppointmentFixer($this->getUser());
            $newAppointment->setAppointmentNotes($request->request->get('notes'));
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

            /*$aNewCall = new Call();
            $aNewCall->setUser($this->getUser());
            $aNewCall->setClient($client);
            $aNewCall->setGeneralStatus(2);
            $aNewCall->setStatusDetails(7);
            $aNewCall->setCallIfAppointmentNotes($request->request->get('notes'));
            $aNewCall->setCreatedAt(new \DateTime());
            $aNewCall->setIsDeleted(false);

            $newAppointment->setAppointmentCall($aNewCall);*/


            $manager->persist($newAppointment);
            /*$manager->persist($aNewCall);*/
            $client->setUpdatedAt(new \DateTime());
            $this->getUser()->addProcessedClient($client);
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
        $isDoneStatus = (int)$request->request->get('is_done');
        $startDay = (($request->request->get('edit_form'))["start"])["date"];
        $startHour = ((($request->request->get('edit_form'))["start"])["time"])["hour"];
        $startMinute = ((($request->request->get('edit_form'))["start"])["time"])["minute"];
        $endDay = (($request->request->get('edit_form'))["end"])["date"];
        $endHour = ((($request->request->get('edit_form'))["end"])["time"])["hour"];
        $endMinute = ((($request->request->get('edit_form'))["end"])["time"])["minute"];
        if (strlen($startMinute) === 1) {
            $startMinute = "0" . $startMinute;
        }
        if (strlen($endMinute) === 1) {
            $endMinute = "0" . $endMinute;
        }
        $fullStartDate = $startDay . " " . $startHour . ":" . $startMinute;
        $fullStartDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullStartDate);
        $fullEndtDate = $endDay . " " . $endHour . ":" . $endMinute;
        $fullEndDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullEndtDate);
        $appointmentToUpdate = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $selectedCommercial = $this->getDoctrine()->getRepository(User::class)->find((int)$request->request->get('assigned_commercial_appointment'));
        /*$clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($appointmentToUpdate->getClient()->getId());*/
        $clientId = $appointmentToUpdate->getClient()->getId();
        $appointmentToUpdate->setStart($fullStartDateFormatted);
        $appointmentToUpdate->setEnd($fullEndDateFormatted);
        if($isDoneStatus === 1) {
            $appointmentToUpdate->setPostponedAt(new \DateTime());
        } elseif ($isDoneStatus === 0) {
            $appointmentToUpdate->setPostponedAt(null);
        }
        $appointmentToUpdate->setIsDone($isDoneStatus);
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
    public function deleteAppointment(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $referer = $request->headers->get('referer');
        $loggedUser = $this->getUser();
        $appointmentToDelete = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $clientId = $appointmentToDelete->getClient()->getId();
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $appointmentToDelete->setIsDeleted(true);
        $appointmentToDelete->setDeletionDate(new \DateTime());
        $appointmentToDelete->setWhoDeletedIt($loggedUser);
        $manager->persist($appointmentToDelete);
        $manager->flush();
        $allClientNotDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotDeletedCallsByClient($clientId);
        $allClientNotDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getNotDeletedAppointmentsByClient($clientId);
        $allItems = array_merge($allClientNotDeletedCalls,$allClientNotDeletedAppointments);
        function compare($a, $b)
        {
            if ($a->getCreatedAt() < $b->getCreatedAt())
                return 1;
            else if ($a->getCreatedAt() > $b->getCreatedAt())
                return -1;
            else
                return 0;
        }
        usort($allItems, "App\Controller\compare");
        if ($allItems) {
            if ($allItems[0] instanceof Call) {
                $client->setStatus($allItems[0]->getGeneralStatus());
                $client->setStatusDetail($allItems[0]->getStatusDetails());
            } elseif ($allItems[0] instanceof Appointment) {
                $client->setStatus(2);
                $client->setStatusDetail(7);
            }
        }
        if ((count($allClientNotDeletedCalls) === 0) && (count($allClientNotDeletedAppointments) === 0)) {
            $client->setStatus(0);
            $client->setStatusDetail(0);
            $client->setIsProcessed(false);
        }
        $manager->persist($client);
        $manager->flush();
        $this->flashy->success('RDV annulé avec succès !');
        return $this->redirect($referer);
    }

    /**
     * @Route("/dashboard/appointments/restore/appointment/{id}", name="restore_appointment")
     */
    public function restoreCall(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $appointmentToRestore = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $clientId = $appointmentToRestore->getClient()->getId();
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $appointmentToRestore->setIsDeleted(false);
        $appointmentToRestore->setDeletionDate(null);
        $manager->persist($appointmentToRestore);
        $manager->flush();

        $allClientNotDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotDeletedCallsByClient($clientId);
        $allClientNotDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getNotDeletedAppointmentsByClient($clientId);
        $allItems = array_merge($allClientNotDeletedCalls,$allClientNotDeletedAppointments);

        function compare($a, $b)
        {
            if ($a->getCreatedAt() < $b->getCreatedAt())
                return 1;
            else if ($a->getCreatedAt() > $b->getCreatedAt())
                return -1;
            else
                return 0;
        }
        usort($allItems, "App\Controller\compare");

        if ($allItems) {
            if ($allItems[0] instanceof Call) {
                $client->setStatus($allItems[0]->getGeneralStatus());
                $client->setStatusDetail($allItems[0]->getStatusDetails());
                $client->setIsProcessed(true);
            } elseif ($allItems[0] instanceof Appointment) {
                $client->setStatus(2);
                $client->setStatusDetail(7);
                $client->setIsProcessed(true);
            }
        }
        $manager->persist($client);
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

    /**
     * @Route("/dashboard/appointments/showcalendar/deleteevent/{id}", name="delete_event")
     */
    public function deleteEvent(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $eventToDelete = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $calendarUserId = $eventToDelete->getUser()->getId();
        if($eventToDelete->getEventType()->getId() !== 4) {
            $manager->remove($eventToDelete);
            $manager->flush();
            $this->flashy->success('Entrée supprimée avec succès !');
            return $this->redirectToRoute('show_calendar', [
                "id" => $calendarUserId
            ]);
        } else {
            $loggedUser = $this->getUser();
            $appointmentToDelete = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
            $clientId = $appointmentToDelete->getClient()->getId();
            $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
            $appointmentToDelete->setIsDeleted(true);
            $appointmentToDelete->setDeletionDate(new \DateTime());
            $appointmentToDelete->setWhoDeletedIt($loggedUser);
            $manager->persist($appointmentToDelete);
            $manager->flush();
            $allClientNotDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotDeletedCallsByClient($clientId);
            $allClientNotDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getNotDeletedAppointmentsByClient($clientId);
            $allItems = array_merge($allClientNotDeletedCalls,$allClientNotDeletedAppointments);
            function compare($a, $b)
            {
                if ($a->getCreatedAt() < $b->getCreatedAt())
                    return 1;
                else if ($a->getCreatedAt() > $b->getCreatedAt())
                    return -1;
                else
                    return 0;
            }
            usort($allItems, "App\Controller\compare");
            if ($allItems) {
                if ($allItems[0] instanceof Call) {
                    $client->setStatus($allItems[0]->getGeneralStatus());
                    $client->setStatusDetail($allItems[0]->getStatusDetails());
                } elseif ($allItems[0] instanceof Appointment) {
                    $client->setStatus(2);
                    $client->setStatusDetail(7);
                }
            }
            if ((count($allClientNotDeletedCalls) === 0) && (count($allClientNotDeletedAppointments) === 0)) {
                $client->setStatus(0);
                $client->setStatusDetail(0);
                $client->setIsProcessed(false);
            }
            $manager->persist($client);
            $manager->flush();
            $this->flashy->success('RDV supprimé avec succès !');
            return $this->redirectToRoute('show_calendar', [
                "id" => $calendarUserId
            ]);
        }

    }

    /**
     * @Route("/dashboard/appointments/showcalendar/editevent/{id}", name="edit_event")
     */
    public function editEvent(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $eventToEdit = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $calendarUserId = $eventToEdit->getUser()->getId();
        $startDay = (($request->request->get('edit_form'))["start"])["date"];
        $startHour = ((($request->request->get('edit_form'))["start"])["time"])["hour"];
        $startMinute = ((($request->request->get('edit_form'))["start"])["time"])["minute"];
        $endDay = (($request->request->get('edit_form'))["end"])["date"];
        $endHour = ((($request->request->get('edit_form'))["end"])["time"])["hour"];
        $endMinute = ((($request->request->get('edit_form'))["end"])["time"])["minute"];
        if (strlen($startMinute) === 1) {
            $startMinute = "0" . $startMinute;
        }
        if (strlen($endMinute) === 1) {
            $endMinute = "0" . $endMinute;
        }
        $fullStartDate = $startDay . " " . $startHour . ":" . $startMinute;
        $fullStartDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullStartDate);
        $fullEndtDate = $endDay . " " . $endHour . ":" . $endMinute;
        $fullEndDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullEndtDate);
        $editedNotes = $request->request->get('notes');
        //check availability
        $validationStartTime = $fullStartDateFormatted;
        $validationEndTime = $fullEndDateFormatted;
        $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
        if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
            && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
            $this->flashy->warning("Veuillez revérifier vos entrées! La durée de l'événement ne doit pas être nulle !");
            return $this->redirectToRoute('show_calendar', [
                'id' => $calendarUserId,
            ]);
        }
        if($validationEndTime < $validationStartTime) {
            $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
            return $this->redirectToRoute('show_calendar', [
                'id' => $calendarUserId,
            ]);
        } else {
            $startTime = $fullStartDateFormatted->format('Y-m-d H:i:s');
            $endTime = $fullEndDateFormatted->format('Y-m-d H:i:s');
            $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
            $newBusyAppointmentsTime = [];
            foreach ($busyAppointmentsTime as $appointment) {
                if ($appointment->getId() !== $eventToEdit->getId())
                    $newBusyAppointmentsTime[] = $appointment;
            }
            /*if ($newBusyAppointmentsTime) {
                foreach ($newBusyAppointmentsTime as $appointment) {
                    if ($appointment->getUser()->getId() === $calendarUserId) {
                        $this->flashy->info("Aucune disponibilité à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                        return $this->redirectToRoute('show_calendar', [
                            'id' => $calendarUserId,
                        ]);
                    }
                }
            } else {*/

            $eventToEdit->setStart($validationStartTime);
            $eventToEdit->setEnd($validationEndTime);
            $eventToEdit->setAppointmentNotes($editedNotes);
            $manager->persist($eventToEdit);
            $manager->flush();
            $this->flashy->success("Entrée éditée avec succès !");
            return $this->redirectToRoute('show_calendar', [
                'id' => $calendarUserId,
            ]);
            /*}*/
        }
    }


    /**
     * @Route("/dashboard/appointments/showcalendar/postponeappointment/{id}", name="postpone_appointment")
     */
    public function postponeAppointment(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $appointmentToPostpone = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        $calendarUserId = $appointmentToPostpone->getUser()->getId();
        $startDay = (($request->request->get('edit_form'))["start"])["date"];
        $startHour = ((($request->request->get('edit_form'))["start"])["time"])["hour"];
        $startMinute = ((($request->request->get('edit_form'))["start"])["time"])["minute"];
        $endDay = (($request->request->get('edit_form'))["end"])["date"];
        $endHour = ((($request->request->get('edit_form'))["end"])["time"])["hour"];
        $endMinute = ((($request->request->get('edit_form'))["end"])["time"])["minute"];
        if (strlen($startMinute) === 1) {
            $startMinute = "0" . $startMinute;
        }
        if (strlen($endMinute) === 1) {
            $endMinute = "0" . $endMinute;
        }
        $fullStartDate = $startDay . " " . $startHour . ":" . $startMinute;
        $fullStartDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullStartDate);
        $fullEndtDate = $endDay . " " . $endHour . ":" . $endMinute;
        $fullEndDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullEndtDate);
        $editedNotes = $request->request->get('notes');


        //check availability
        $validationStartTime = $fullStartDateFormatted;
        $validationEndTime = $fullEndDateFormatted;
        $appointmentDuration = date_diff($validationEndTime,$validationStartTime);
        if($validationEndTime < $validationStartTime) {
            $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
            return $this->redirectToRoute('show_calendar', [
                'id' => $calendarUserId,
            ]);
        }

        if($validationEndTime > $validationStartTime) {
            $startTime = $fullStartDateFormatted->format('Y-m-d H:i:s');
            $endTime = $fullEndDateFormatted->format('Y-m-d H:i:s');
            $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
            $newBusyAppointmentsTime = [];
            foreach ($busyAppointmentsTime as $appointment) {
                if ($appointment->getId() !== $appointmentToPostpone->getId())
                    $newBusyAppointmentsTime[] = $appointment;
            }
            if ($newBusyAppointmentsTime) {
                foreach ($newBusyAppointmentsTime as $appointment) {
                    if ($appointment->getUser()->getId() === $calendarUserId) {
                        $this->flashy->info("Aucune disponibilité à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                        return $this->redirectToRoute('show_calendar', [
                            'id' => $calendarUserId,
                        ]);
                    }
                }
            } else {
                $appointmentToPostpone->setStart($validationStartTime);
                $appointmentToPostpone->setEnd($validationEndTime);
                $appointmentToPostpone->setAppointmentNotes($editedNotes);
                $appointmentToPostpone->setIsDone(1);
                $appointmentToPostpone->setPostponedAt(new \DateTime());
                $manager->persist($appointmentToPostpone);
                $manager->flush();
                $this->flashy->success("RDV reporté avec succès !");
                return $this->redirectToRoute('show_calendar', [
                    'id' => $calendarUserId,
                ]);
            }

        }
        else {
            if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
            }
            return $this->redirectToRoute('show_calendar', [
                'id' => $calendarUserId,
            ]);
        }
    }


    /**
     * @Route("/dashboard/appointments/showcalendar/editgeozoneevent/{id}", name="edit_geo_zone_event")
     */
    public function editGeoZoneEvent(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $startDay = (($request->request->get('edit_geo_zone_form'))["start"])["date"];
        $fullStartDate = $startDay . "00:00";
        $fullStartDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullStartDate);
        $endDay = (($request->request->get('edit_geo_zone_form'))["end"])["date"];
        $fullEndDate = $endDay . "00:00";
        $fullEndDateFormatted = \DateTime::createFromFormat('Y-m-d H:i',$fullEndDate);
        $geoZoneEventToEdit = $this->getDoctrine()->getRepository(GeographicZoneEvent::class)->find($id);
        $calendarUserId = $geoZoneEventToEdit->getCalendarUser()->getId();
        $myString = $request->request->get('edit-departments-values');
        $departmentsArray = explode(',', $myString);
        $existingDepartments = $geoZoneEventToEdit->getGeographicAreas();
        if($existingDepartments) {
            foreach ($existingDepartments as $existingDepartment) {
                $geoZoneEventToEdit->removeGeographicArea($existingDepartment);
            }
        }
        $geoZoneEventToEdit->setStart($fullStartDateFormatted);
        $geoZoneEventToEdit->setEnd($fullEndDateFormatted);
        if($departmentsArray) {
            foreach ($departmentsArray as $department) {
                if($department) {
                    $geoZoneEventToEdit->addGeographicArea($this->getDoctrine()->getRepository(GeographicArea::class)->find($department));
                }
            }
        }
        $manager->persist($geoZoneEventToEdit);
        $manager->flush();
        $this->flashy->success("Zone Géographique éditée avec succès !");
        return $this->redirectToRoute('show_calendar', [
            "id" => $calendarUserId
        ]);
    }

    /**
     * @Route("/dashboard/appointments/clientsajaxsearch/", name="clients_ajax_search")
     */
    public function clientsAjaxSearch(Request $request): Response
    {
        $manager = $this->getDoctrine()->getManager();
        if($request->isXmlHttpRequest()){
            $searchKeyword = $request->get('searchKeyword');
            /*dd($searchKeyword);*/
            $clients = $this->getDoctrine()->getRepository(Client::class)->ajaxClientsSearch($searchKeyword);
            $clientsArray = [];
            foreach ($clients as $client) {
                $clientsArray[] = ["id" => $client->getId(), "firstName" => $client->getFirstName(), "lastName" => $client->getLastName()];
            }
            /*dd($clients);*/
            $serializer = new \Symfony\Component\Serializer\Serializer([new ObjectNormalizer()]);
            $result = $serializer->normalize($clientsArray, 'json');
            return new JsonResponse($result);
        }
        /*['attributes' => 'id', 'firstName', 'lastName']*/
        return new Response('use AJAX');
    }

}
