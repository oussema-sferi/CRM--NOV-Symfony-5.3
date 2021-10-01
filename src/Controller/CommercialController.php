<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommercialController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/commercial", name="commercial")
     */
    public function index(AppointmentRepository $appointment, Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $loggedUserId = $this->getUser()->getId();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $commercialUsers = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        //Fetch all the appointments and sort by the most recent date
        /*$commercialAppointments = $appointment->findBy(array(), array('start' => 'DESC'));*/
        /*$test = $appointment->getAppointmentsWhereClientsExist()->f;
        dd($test);*/
        /*dd($loggedUserId);*/
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $appointment->getAppointmentsOfLoggedUser($loggedUserId);
        } else {
            $data = $appointment->getAppointmentsWhereClientsExist();
        }
        $session->remove('total_appointments_search_results');
        if($session->get('pagination_value')) {
            $commercialAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $commercialAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('commercial/index.html.twig', [
            'all_commercial_appointments' => $data,
            'commercial_appointments' => $commercialAppointments,
            'geographic_areas'=> $geographicAreas,
            'commercial_users' => $commercialUsers
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
    public function myContacts(AppointmentRepository $appointment, Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();

        $loggedUserId = $this->getUser()->getId();
        $loggedUserRolesArray = $this->getUser()->getRoles();
        $clientsIdsArray = [];
        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $loggedCommercialAppointments = $appointment->getAppointmentsOfLoggedUser($loggedUserId);
            foreach ($loggedCommercialAppointments as $appointment) {
                $clientsIdsArray[] = $appointment->getClient()->getId();
            }
            /*dd($clientsIdsArray);*/
            $uniqueClientsIdsArray = array_unique($clientsIdsArray);
            $data = [];
            foreach ($uniqueClientsIdsArray as $clientId) {
                foreach ($loggedCommercialAppointments as $appointment) {
                    if($appointment->getClient()->getId() === $clientId) {
                        $data[] = $appointment;
                        break;
                    }
                }
            }
        } else {
            $data = $appointment->getAppointmentsWhereClientsExist();
        }
        if($session->get('pagination_value')) {
            $commercialAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $commercialAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }
        /*$commercialAppointments = $appointment->findAll();*/
        return $this->render('commercial/my_contacts.html.twig', [
            'all_commercial_appointments' => $data,
            'commercial_appointments' => $commercialAppointments
        ]);
    }

    /**
     * @Route("/dashboard/commercial/appointment/{id}/handle", name="appointment_handle")
     */
    public function appointmentHandleIndex(Request $request, $id): Response
    {
        $appointmentToProcess = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        if($request->isMethod('Post')) {
            if($request->request->get('testing') === "on") {
                /*dd($request->request->get('notes'));*/
                $manager = $this->getDoctrine()->getManager();
                $appointmentToProcess->setIsDone(true);
                $appointmentToProcess->setDoneAt(new \DateTime());
                $appointmentToProcess->setAppointmentNotes($request->request->get('notes'));
                $manager->persist($appointmentToProcess);
                $manager->flush();
                $this->flashy->success("RDV traité avec succès !");
            } else {
                $this->flashy->info("Aucun traitement n'a été effectué sur le RDV !");
            }
            return $this->redirectToRoute('commercial');

        }

        return $this->render('/commercial/appointmentHandle.html.twig', [

            'appointment_to_process' => $appointmentToProcess
        ]);
    }


    /**
     * @Route("/dashboard/commercial/stats", name="commercial_stats")
     */
    public function commercialStats(): Response
    {
        $allCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        $doneAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDoneAppointments();
        $upcomingAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getUpcomingAppointments();
        $processedClients = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
        $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 0]);


        /*$commercial = $this->getDoctrine()->getRepository(User::class)->find(4);
        $appointments = $commercial->getAppointments();
        dd($appointments[3]);*/

        return $this->render('commercial/commercial_stats.html.twig', [
            'count_total_commercials' => count($allCommercials),
            'all_commercials' => $allCommercials,
            'total_appointments' => $allAppointments,
            'total_appointments_count' => count($allAppointments),
            'done_appointments' => $doneAppointments,
            'done_appointments_count' => count($doneAppointments),
            'upcoming_appointments' => count($upcomingAppointments),
            'processed_clients' => count($processedClients),
        ]);
    }

    /**
     * @Route("/dashboard/commercial/stats/filters", name="commercial_stats_filters")
     */
    public function commercialStatsFilters(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $dateFilterValueCommercialStats = $request->get('dateFilterValueCommercialStats');
            $session->set('date_filter_value_commercial_stats',
                $dateFilterValueCommercialStats
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);

        }
    }

    /**
     * @Route("/dashboard/commercial/stats/filters/Initialization", name="commercial_stats_filters_initialization")
     */
    public function commercialStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_commercial_stats');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('commercial_stats');
    }

    /**
     * @Route("/dashboard/commercial/stats/filters/notifications", name="commercial_stats_filters_notifications")
     */
    public function commercialStatsFiltersNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('commercial_stats');
    }
}
