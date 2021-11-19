<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\CallRepository;
use App\Repository\ClientRepository;
use App\Repository\ProcessRepository;
use App\Repository\UserRepository;
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
            $data = $appointment->getAppointmentsOfLoggedUserEvenDeleted($loggedUserId);
        } else {
            $data = $appointment->getAppointmentsWhereClientsExistCommercialStats();
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
        $manager = $this->getDoctrine()->getManager();
        $appointmentToProcess = $this->getDoctrine()->getRepository(Appointment::class)->find($id);
        if($request->isMethod('Post')) {
            /*if($request->request->get('testing') === "on") {
                $appointmentToProcess->setIsDone(true);
                $appointmentToProcess->setDoneAt(new \DateTime());
                $appointmentToProcess->setPostAppointmentNotes($request->request->get('notes'));
            } else {
                $appointmentToProcess->setIsDone(false);
                $appointmentToProcess->setDoneAt(null);
                $appointmentToProcess->setPostAppointmentNotes(null);
            }*/
            $isDoneStatus = $request->request->get('appointment_status');
            if($isDoneStatus === "argu") {
                $appointmentToProcess->setIsDone(2);
            } elseif ($isDoneStatus === "vente") {
                $appointmentToProcess->setIsDone(3);
            }
            $appointmentToProcess->setDoneAt(new \DateTime());
            $appointmentToProcess->setPostAppointmentNotes($request->request->get('notes'));
            $manager->persist($appointmentToProcess);
            $manager->flush();
            $this->flashy->success("RDV traité avec succès !");
            return $this->redirectToRoute('commercial');

        }

        return $this->render('/commercial/appointmentHandle.html.twig', [

            'appointment_to_process' => $appointmentToProcess
        ]);
    }


    /**
     * @Route("/dashboard/commercial/statsold", name="commercial_stats")
     */
    public function commercialStats(): Response
    {
        $justCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        $allCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");
        $doneAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDoneAppointments();
        $deletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDeletedAppointments();
        $upcomingAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getUpcomingAppointments();
        $processedClients = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExistCommercialStats();
        /*$allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();*/

        if(count($allAppointments) !== 0) {
            $doneAppointmentsPerformance = number_format(((count($doneAppointments) / count($allAppointments)) * 100), 2);
        } else {
            $doneAppointmentsPerformance = 0;
        }

        if(count($allAppointments) !== 0) {
            $deletedAppointmentsPerformance = number_format(((count($deletedAppointments) / count($allAppointments)) * 100), 2);
        } else {
            $deletedAppointmentsPerformance = 0;
        }

        return $this->render('commercial/commercial_stats.html.twig', [
            'count_total_commercials' => count($justCommercials),
            'all_commercials' => $allCommercials,
            'total_appointments' => $allAppointments,
            'total_appointments_count' => count($allAppointments),
            'done_appointments' => $doneAppointments,
            'done_appointments_count' => count($doneAppointments),
            'deleted_appointments_count' => count($deletedAppointments),
            'upcoming_appointments' => count($upcomingAppointments),
            'processed_clients' => count($processedClients),
            'done_appointments_performance' => $doneAppointmentsPerformance,
            'deleted_appointments_performance' => $deletedAppointmentsPerformance,
        ]);
    }

    /**
     * @Route("/dashboard/commercial/stats", name="commercial_stats_new")
     */
    public function commercialStatsNew(Request $request, ProcessRepository $processRepository,CallRepository $callRepository, UserRepository $userRepository, ClientRepository $clientRepository, AppointmentRepository $appointmentRepository): Response
    {
        // new

        // RDV count
        $allAppointments = $appointmentRepository->getAppointmentsWhereClientsExistCommercialStats();
        $allAppointmentsCount = count($allAppointments);

        // Done RDV count
        $doneAppointments = $appointmentRepository->getDoneAppointments();
        $doneAppointmentsCount = count($doneAppointments);

        // Upcoming RDV count
        $upcomingAppointments = $appointmentRepository->getUpcomingAppointments();
        $upcomingAppointmentsCount = count($upcomingAppointments);

        // Deleted RDV count
        $deletedAppointments = $appointmentRepository->getDeletedAppointments();
        $deletedAppointmentsCount = count($deletedAppointments);

        // Postponed RDV count
        $postponedAppointments = $appointmentRepository->getPostponedAppointments();
        $postponedAppointmentsCount = count($postponedAppointments);

        // Argu RDV count
        $arguAppointments = $appointmentRepository->getArguAppointments();
        $arguAppointmentsCount = count($arguAppointments);

        // Vente RDV count
        $venteAppointments = $appointmentRepository->getVenteAppointments();
        $venteAppointmentsCount = count($venteAppointments);

        //Bloc Statistiques Générales
        $allCommercials = $userRepository->findUsersByCommercialRole("ROLE_COMMERCIAL");

        //Bloc Statistiques Par Utilisateur
        $users = $userRepository->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");

        // TX TRANSFO

        if($doneAppointmentsCount !== 0) {
            $TXTRANSFORPercentage = number_format((($venteAppointmentsCount / $doneAppointmentsCount) * 100), 2);
        } else {
            $TXTRANSFORPercentage = 0;
        }

        // GRAPHIQUE
        // ALL APPOINTMENTS
        $allAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $appointmentsCounter = 0;
            foreach ($allAppointments as $appointment) {

                if (((new \DateTime())->format("Y")) === $appointment->getCreatedAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($appointment->getCreatedAt()->format("m")), 1, (int)($appointment->getCreatedAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $appointmentsCounter += 1;
                    }
                }
            }
            $allAppointmentsByMonthArray[] = $appointmentsCounter;
        }
        // POSTPONED APPOINTMENTS
        $postponedAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $postponedAppointmentsCounter = 0;
            foreach ($postponedAppointments as $postponedAppointment) {
                if (((new \DateTime())->format("Y")) === $postponedAppointment->getPostponedAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($postponedAppointment->getPostponedAt()->format("m")), 1, (int)($postponedAppointment->getPostponedAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $postponedAppointmentsCounter += 1;
                    }
                }
            }
            $postponedAppointmentsByMonthArray[] = $postponedAppointmentsCounter;
        }
        // DELETED APPOINTMENTS
        $deletedAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $deletedAppointmentsCounter = 0;
            foreach ($deletedAppointments as $deletedAppointment) {
                if (((new \DateTime())->format("Y")) === $deletedAppointment->getDeletionDate()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($deletedAppointment->getDeletionDate()->format("m")), 1, (int)($deletedAppointment->getDeletionDate())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $deletedAppointmentsCounter += 1;
                    }
                }
            }
            $deletedAppointmentsByMonthArray[] = $deletedAppointmentsCounter;
        }
        // DONE APPOINTMENTS
        $doneAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $doneAppointmentsCounter = 0;
            foreach ($doneAppointments as $doneAppointment) {

                if (((new \DateTime())->format("Y")) === $doneAppointment->getDoneAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($doneAppointment->getDoneAt()->format("m")), 1, (int)($doneAppointment->getDoneAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $doneAppointmentsCounter += 1;
                    }
                }
            }
            $doneAppointmentsByMonthArray[] = $doneAppointmentsCounter;
        }

        // ARGU APPOINTMENTS
        $arguAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $arguAppointmentsCounter = 0;
            foreach ($arguAppointments as $arguAppointment) {

                if (((new \DateTime())->format("Y")) === $arguAppointment->getDoneAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($arguAppointment->getDoneAt()->format("m")), 1, (int)($arguAppointment->getDoneAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $arguAppointmentsCounter += 1;
                    }
                }
            }
            $arguAppointmentsByMonthArray[] = $arguAppointmentsCounter;
        }

        // VENTE APPOINTMENTS
        $venteAppointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $venteAppointmentsCounter = 0;
            foreach ($venteAppointments as $venteAppointment) {

                if (((new \DateTime())->format("Y")) === $venteAppointment->getDoneAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($venteAppointment->getDoneAt()->format("m")), 1, (int)($venteAppointment->getDoneAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $venteAppointmentsCounter += 1;
                    }
                }
            }
            $venteAppointmentsByMonthArray[] = $venteAppointmentsCounter;
        }


        return $this->render('commercial/commercial_stats_new.html.twig', [
            //Bloc Statistiques Générales
            'commercials_count' => count($allCommercials),
            'all_appointments_count' => $allAppointmentsCount,
            'done_appointments_count' => $doneAppointmentsCount,
            'upcoming_appointments_count' => $upcomingAppointmentsCount,
            'deleted_appointments_count' => $deletedAppointmentsCount,
            //Bloc Résumé Statistiques
            'all_appointments' => $allAppointments,
            'deleted_appointments' => $deletedAppointments,
            'postponed_appointments' => $postponedAppointments,
            'postponed_appointments_count' => $postponedAppointmentsCount,
            'argu_appointments' => $arguAppointments,
            'argu_appointments_count' => $arguAppointmentsCount,
            'vente_appointments' => $venteAppointments,
            'vente_appointments_count' => $venteAppointmentsCount,
            'TX_TRANSFOR' => $TXTRANSFORPercentage,
            //Bloc Statistiques Pour La Période Sélectionnée
            'done_appointments' => $doneAppointments,
            //Bloc Statistiques Par Utilisateur
            'users' => $users,
            // GRAPHIQUE
            'fixed_appointments_graph' => json_encode($allAppointmentsByMonthArray),
            'postponed_appointments_graph' => json_encode($postponedAppointmentsByMonthArray),
            'deleted_appointments_graph' => json_encode($deletedAppointmentsByMonthArray),
            'done_appointments_graph' => json_encode($doneAppointmentsByMonthArray),
            'argu_appointments_graph' => json_encode($arguAppointmentsByMonthArray),
            'vente_appointments_graph' => json_encode($venteAppointmentsByMonthArray),
            'actual_year' => (new \DateTime())->format("Y"),
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
        } elseif ($request->isMethod('Post')) {
            /*dd($request->request->all());*/

            /*return new JsonResponse(['message'=> 'Task Fails!']);*/
            $startDate = new \DateTime($request->request->get("start_date"));
            $endDate = new \DateTime($request->request->get("end_date"));
            /*dd($startDate);
            dd($request->request->get("end_date"));*/
            $dateFilterValue = $request->request->get('dateFilterValueCommercialStats');
            $session->set('date_filter_value_commercial_stats',
                $dateFilterValue
            );
            $session->set('date_filter_commercial_start',
                $startDate
            );
            $session->set('date_filter_commercial_end',
                $endDate
            );
            $this->flashy->success('Filtre mis à jour avec succès !');
            return $this->redirectToRoute('commercial_stats_new');
        }
    }

    /**
     * @Route("/dashboard/commercial/stats/filters/Initialization", name="commercial_stats_filters_initialization")
     */
    public function commercialStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_commercial_stats');
        if($session->get('date_filter_commercial_start')) $session->remove('date_filter_commercial_start');
        if($session->get('date_filter_commercial_end')) $session->remove('date_filter_commercial_end');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('commercial_stats_new');
    }

    /**
     * @Route("/dashboard/commercial/stats/filters/notifications", name="commercial_stats_filters_notifications")
     */
    public function commercialStatsFiltersNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('commercial_stats_new');
    }
}
