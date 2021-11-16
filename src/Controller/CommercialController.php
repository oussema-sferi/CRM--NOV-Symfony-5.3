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
     * @Route("/dashboard/commercial/stats", name="commercial_stats")
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
     * @Route("/dashboard/commercial/statsnew", name="commercial_stats_new")
     */
    public function commercialStatsNew(Request $request, ProcessRepository $processRepository,CallRepository $callRepository, UserRepository $userRepository, ClientRepository $clientRepository, AppointmentRepository $appointmentRepository): Response
    {
        // new
        //CONTACTS PROCESSED
        $allProcesses = $processRepository->findAllSortedDate();
        $processedClientsIdsArray = [];
        $clientsProcesses = [];
        foreach ($allProcesses as $process) {
            $processedClientsIdsArray[] = $process->getClient()->getId();
        }
        $uniqueProcessedClientsIdsArray = array_unique($processedClientsIdsArray);
        $uniqueProcessedClientsArray = [];
        foreach ($processedClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $uniqueProcessedClientsArray[$clientId][] = $process->getCreatedAt();
                    $clientsProcesses[$clientId][$process->getCreatedAt()->format('Y-m-d H:i:s')] = $process->getStatusDetail();
                }
            }
        }
        /*dd($clientsProcesses);*/
        // PI contacts counter
        /*$PIcounter = 0;
        foreach ($clientsProcesses as $client) {
            $breakPI = false;
            foreach ($client as $dateTime => $statusDetail) {
                if($breakPI === false) {
                    if($statusDetail === 5) {
                        $PIcounter += 1;
                        $breakPI = true;
                    }
                }
            }
        }*/

        // RAPPEL contacts counter
        /*$RAPPELcounter = 0;
        foreach ($clientsProcesses as $client) {
            $breakRAPPEL = false;
            foreach ($client as $dateTime => $statusDetail) {
                if($breakRAPPEL === false) {
                    if($statusDetail === 6) {
                        $RAPPELcounter += 1;
                        $breakRAPPEL = true;
                    }
                }
            }
        }*/

        // NRP contacts counter
        /*$NRPcounter = 0;
        foreach ($clientsProcesses as $client) {
            $breakNRP = false;
            foreach ($client as $dateTime => $statusDetail) {
                if($breakNRP === false) {
                    if($statusDetail === 1) {
                        $NRPcounter += 1;
                        $breakNRP = true;
                    }
                }
            }
        }*/

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


        /*dd($reportedAppointments);*/
        // QUALIFIED contacts counter
        /*$processedContactsByStatus = [];
        foreach ($processedClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $processedContactsByStatus[$clientId][$process->getCreatedAt()->format('Y-m-d H:i:s')] = $process->getStatus();
                }
            }
        }
        $QUALIFIEDcontactscounter = 0;
        foreach ($processedContactsByStatus as $client) {
            $breakQUALIFIED = false;
            foreach ($client as $dateTime => $status) {
                if($breakQUALIFIED === false) {
                    if($status === 2) {
                        $QUALIFIEDcontactscounter += 1;
                        $breakQUALIFIED = true;
                    }
                }
            }
        }*/

        // TX CT contacts

        /*$processedContactsCount = count($uniqueProcessedClientsIdsArray);
        if($processedContactsCount !== 0) {
            $TXCTPercentage = number_format((($QUALIFIEDcontactscounter / $processedContactsCount) * 100), 2);
        } else {
            $TXCTPercentage = 0;
        }*/

        // TX TRANSFO

        /*if($processedContactsCount !== 0) {
            $TXTRANSFORPercentage = number_format((($RDVcounter / $QUALIFIEDcontactscounter) * 100), 2);
        } else {
            $TXTRANSFORPercentage = 0;
        }*/

        // NOT QUALIFIED contacts counter
        /*$NOTQUALIFIEDcontactscounter = 0;
        foreach ($processedContactsByStatus as $client) {
            $breakNOTQUALIFIED = false;
            foreach ($client as $dateTime => $status) {
                if($breakNOTQUALIFIED === false) {
                    if($status === 1) {
                        $NOTQUALIFIEDcontactscounter += 1;
                        $breakNOTQUALIFIED = true;
                    }
                }
            }
        }*/

        //Bloc Statistiques Générales
        $allCommercials = $userRepository->findUsersByCommercialRole("ROLE_COMMERCIAL");
        /*$allContacts = $clientRepository->getNotDeletedClients();
        $processedContacts = $clientRepository->getProcessedClients();*/

        //Bloc Statistiques Par Utilisateur
        $users = $userRepository->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");


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
            /*'contacts_count' => count($allContacts),*/
            //Bloc Statistiques Pour La Période Sélectionnée
            /*'processed_contacts_count' => count($processedContacts),*/
            //Bloc Statistiques Par Utilisateur
            'users' => $users,
            //new
            /*'clients_processes'=> $clientsProcesses,
            'clients_processes_count'=> count($clientsProcesses),
            'all_PI_contacts_count' => $PIcounter,
            'all_RAPPEL_contacts_count' => $RAPPELcounter,
            'all_NRP_contacts_count' => $NRPcounter,
            'all_RDV_contacts_count' => $RDVcounter,
            'clients_processes_by_status' => $processedContactsByStatus,
            'TX_CT' => $TXCTPercentage,
            'TX_TRANSFOR' => $TXTRANSFORPercentage,
            'NOT_QUALIFIED_contacts_count' => $NOTQUALIFIEDcontactscounter,
            'QUALIFIED_contacts_count' => $QUALIFIEDcontactscounter*/

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
