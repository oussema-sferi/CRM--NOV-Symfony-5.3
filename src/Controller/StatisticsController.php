<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\Process;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\CallRepository;
use App\Repository\ClientRepository;
use App\Repository\ProcessRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/allstats", name="all_statistics")
     */
    public function index(): Response
    {
        /*dd('hi');*/
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExistCommercialStats();
        $allContacts = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        $allUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
        $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->getNotProcessedClients();
        $doneAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDoneAppointments();
        $deletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDeletedAppointments();
        $processedClientsArray = [];
        foreach ($allUsers as $user) {
            foreach ($user->getProcessedClients() as $client) {
                $processedClientsArray[] = $client->getId();
            }
        }


        //CONTACTS PROCESSED
        /*$allProcesses = $this->getDoctrine()->getRepository(Process::class)->findAll();
        $allClientsIdsArray = [];
        foreach ($allProcesses as $process) {
            $allClientsIdsArray[] = $process->getClient()->getId();
        }
        $uniqueClientsIdsArray = array_unique($allClientsIdsArray);
        $uniqueClientsArray = [];
        foreach ($uniqueClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $uniqueClientsArray[$clientId][] = $process->getCreatedAt();
                }
            }
        }*/

        $allProcesses = $this->getDoctrine()->getRepository(Process::class)->findAllSortedDate();
        /*dd($allProcesses);*/
        $allClientsIdsArray = [];
        foreach ($allProcesses as $process) {
            $allClientsIdsArray[] = $process->getClient()->getId();
        }
        $uniqueClientsIdsArray = array_unique($allClientsIdsArray);

        $uniqueClientsArray = [];
        $clientsProcesses = [];
        foreach ($uniqueClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $uniqueClientsArray[$clientId][] = $process->getCreatedAt();
                    $clientsProcesses[$clientId][$process->getStatus()][] = $process->getCreatedAt();
                }
            }
        }
        /*dd($clientsProcesses);*/
        // Qualified and not Qualified clients count
        $qualifiedClientsIdsForGraph = [];
        $notQualifiedClientsIdsForGraph = [];
        $qualifiedClientsCounter = 0;
        $notQualifiedClientsCounter = 0;
        foreach ($clientsProcesses as $processedClientId => $processedClient) {
            if (count($processedClient) === 2) {
                $NQdate = $processedClient[1][0];
                $Qdate = $processedClient[2][0];

                if($NQdate >$Qdate) {
                    $notQualifiedClientsCounter += 1;
                    $notQualifiedClientsIdsForGraph[] = $processedClientId;
                } else {
                    $qualifiedClientsCounter += 1;
                    $qualifiedClientsIdsForGraph[] = $processedClientId;
                }
            } elseif (count($processedClient) === 1) {
                if(!array_key_exists("1", $processedClient)) {
                    $qualifiedClientsCounter += 1;
                    $qualifiedClientsIdsForGraph[] = $processedClientId;
                } elseif (!array_key_exists("2", $processedClient)) {
                    $notQualifiedClientsCounter += 1;
                    $notQualifiedClientsIdsForGraph[] = $processedClientId;
                }
            }
        }

        /*dd($qualifiedClientsIdsForGraph);*/
        //

        //CONTACTS QUALIFIES
        /*$allQualifiedProcesses = $this->getDoctrine()->getRepository(Process::class)->getAllQualifiedProcesses();
        $allQualifiedClientsIdsArray = [];
        foreach ($allQualifiedProcesses as $qualifiedProcess) {
            $allQualifiedClientsIdsArray[] = $qualifiedProcess->getClient()->getId();
        }
        $uniqueQualifiedClientsIdsArray = array_unique($allQualifiedClientsIdsArray);
        $uniqueQualifiedClientsArray = [];
        foreach ($uniqueQualifiedClientsIdsArray as $id) {
            foreach ($allQualifiedProcesses as $qualifiedProcess) {
                if ($qualifiedProcess->getClient()->getId() == $id) {
                    $uniqueQualifiedClientsArray[$id][] = $qualifiedProcess->getCreatedAt();
                }
            }
        }*/

        //CONTACTS NON QUALIFIES
        /*$allNotQualifiedProcesses = $this->getDoctrine()->getRepository(Process::class)->getAllNotQualifiedProcesses();
        $allNotQualifiedClientsIdsArray = [];
        foreach ($allNotQualifiedProcesses as $notQualifiedProcess) {
            $allNotQualifiedClientsIdsArray[] = $notQualifiedProcess->getClient()->getId();
        }
        $uniqueNotQualifiedClientsIdsArray = array_unique($allNotQualifiedClientsIdsArray);
        $uniqueNotQualifiedClientsArray = [];
        foreach ($uniqueNotQualifiedClientsIdsArray as $id) {
            foreach ($allNotQualifiedProcesses as $notQualifiedProcess) {
                if ($notQualifiedProcess->getClient()->getId() == $id) {
                    $uniqueNotQualifiedClientsArray[$id][] = $notQualifiedProcess->getCreatedAt();
                }
            }
        }*/

        // PERFORMANCE FICHES DE CONTACTS TRAITES
        if(count($allContacts) !== 0) {
            $contactsPerformance = number_format(((count($uniqueClientsArray) / count($allContacts)) * 100), 2);
        } else {
            $contactsPerformance = 0;
        }

        // PERFORMANCE FICHES DE CONTACT QUALIFIES
        if(count($uniqueClientsArray) !== 0) {
            $qualifiedContactsPerformance = number_format((($qualifiedClientsCounter / count($uniqueClientsArray)) * 100), 2);
        } else {
            $qualifiedContactsPerformance = 0;
        }


        // PERFORMANCE FICHES DE CONTACT NON QUALIFIES
        if(count($uniqueClientsArray) !== 0) {
            $notQualifiedContactsPerformance = number_format((($notQualifiedClientsCounter / count($uniqueClientsArray)) * 100), 2);
        } else {
            $notQualifiedContactsPerformance = 0;
        }

        // PERFORMANCE RDV
        if(count($uniqueClientsArray) !== 0) {
            $appointmentsPerformance = number_format(((count($allAppointments) / count($uniqueClientsArray)) * 100), 2);
        } else {
            $appointmentsPerformance = 0;
        }

        // PERFORMANCE RDV EFFECTUES
        if(count($allAppointments) !== 0) {
            $doneAppointmentsPerformance = number_format(((count($doneAppointments) / count($allAppointments)) * 100), 2);
        } else {
            $doneAppointmentsPerformance = 0;
        }

        // PERFORMANCE RDV ANNULES
        if(count($allAppointments) !== 0) {
            $deletedAppointmentsPerformance = number_format(((count($deletedAppointments) / count($allAppointments)) * 100), 2);
        } else {
            $deletedAppointmentsPerformance = 0;
        }

        // GET ARRAY OF PROCESSED CLIENTS OBJECTS
        $processedContactsForGraph= [];
        foreach ($uniqueClientsArray as $clientId => $value) {
            $processedContactsForGraph[] = $this->getDoctrine()->getRepository(Client::class)->find((int)$clientId);
        }


        $processedContactsByMonthArray = [];
        for ($i = 1; $i <13; $i++) {
            $contactsCounter = 0;
            foreach ($processedContactsForGraph as $contact) {
                    if (((new \DateTime())->format("Y")) === $contact->getUpdatedAt()->format("Y")) {
                        if (date("F",mktime(0,0,0,(int)($contact->getUpdatedAt()->format("m")),1,(int)($contact->getUpdatedAt())->format("Y"))) === date("F",mktime(0,0,0,$i,1,(int)(new \DateTime())->format("Y")))) {
                            $contactsCounter += 1;
                        }
                    }
            }
            $processedContactsByMonthArray[] = $contactsCounter;
        }

        $appointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $appointmentsCounter = 0;
            foreach ($allAppointments as $appointment) {

                if (((new \DateTime())->format("Y")) === $appointment->getCreatedAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($appointment->getCreatedAt()->format("m")), 1, (int)($appointment->getCreatedAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $appointmentsCounter += 1;
                    }
                }
            }
            $appointmentsByMonthArray[] = $appointmentsCounter;
        }

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


        return $this->render('statistics/index.html.twig', [
            'total_all_contacts' => count($allContacts),
            'not_processed_clients' => count($notProcessedClients),
            'total_appointments' => $allAppointments,
            'done_appointments' => $doneAppointments,
            'done_appointments_count' => count($doneAppointments),
            'deleted_appointments_count' => count($deletedAppointments),
            'count_total_appointments' => count($allAppointments),
            'appointments_performance' => $appointmentsPerformance,
            'processed_contacts_graph' => json_encode($processedContactsByMonthArray),
            'fixed_appointments_graph' => json_encode($appointmentsByMonthArray),
            'done_appointments_graph' => json_encode($doneAppointmentsByMonthArray),
            'deleted_appointments_graph' => json_encode($deletedAppointmentsByMonthArray),
            'actual_year' => (new \DateTime())->format("Y"),
            'single_clients_processes' => $uniqueClientsArray,
            'single_clients_processes_count' => count($uniqueClientsArray),
            /*'single_qualified_clients_processes' => $uniqueQualifiedClientsArray,*/
            'single_qualified_clients_processes_count' => $qualifiedClientsCounter,
            /*'single_not_qualified_clients_processes' => $uniqueNotQualifiedClientsArray,*/
            'single_not_qualified_clients_processes_count' => $notQualifiedClientsCounter,
            'contacts_performance' => $contactsPerformance,
            'qualified_contacts_performance' => $qualifiedContactsPerformance,
            'not_qualified_contacts_performance' => $notQualifiedContactsPerformance,
            'done_appointments_performance' => $doneAppointmentsPerformance,
            'deleted_appointments_performance' => $deletedAppointmentsPerformance,
            'clients_processes' => $clientsProcesses
        ]);
    }

    /**
     * @Route("/dashboard/allstatsnew", name="all_statistics_new")
     */
    public function allStatsNew(Request $request, ProcessRepository $processRepository,CallRepository $callRepository, UserRepository $userRepository, ClientRepository $clientRepository, AppointmentRepository $appointmentRepository): Response
    {
        // Partie TELEPROSPECTION

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
        // PI contacts counter
        $PIcounter = 0;
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
        }
        // RAPPEL contacts counter
        $RAPPELcounter = 0;
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
        }
        // NRP contacts counter
        $NRPcounter = 0;
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
        }
        // RDV contacts counter
        $RDVcounter = 0;
        foreach ($clientsProcesses as $client) {
            $breakRDV = false;
            foreach ($client as $dateTime => $statusDetail) {
                if($breakRDV === false) {
                    if($statusDetail === 7) {
                        $RDVcounter += 1;
                        $breakRDV = true;
                    }
                }
            }
        }
        // QUALIFIED contacts counter
        $processedContactsByStatus = [];
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
        }
        // TX CT contacts
        $processedContactsCount = count($uniqueProcessedClientsIdsArray);
        if($processedContactsCount !== 0) {
            $TXCTPercentage = number_format((($QUALIFIEDcontactscounter / $processedContactsCount) * 100), 2);
        } else {
            $TXCTPercentage = 0;
        }
        // TX TRANSFO
        if($processedContactsCount !== 0) {
            $TXTRANSFORPercentage = number_format((($RDVcounter / $QUALIFIEDcontactscounter) * 100), 2);
        } else {
            $TXTRANSFORPercentage = 0;
        }
        // NOT QUALIFIED contacts counter
        $NOTQUALIFIEDcontactscounter = 0;
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
        }
        //Bloc Statistiques Générales
        $allTelepros = $userRepository->findUsersByCommercialRole("ROLE_TELEPRO");
        $allContacts = $clientRepository->getNotDeletedClients();
        $processedContacts = $clientRepository->getProcessedClients();

        //Bloc Statistiques Par Utilisateur
        $teleprosAndSuperAdmins = $userRepository->findUsersTeleproStats("ROLE_TELEPRO", "ROLE_SUPERADMIN");

        // Partie COMMERCIAL

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
        $commercialsAndSuperAdmins = $userRepository->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");

        //GRAPH

        // GET ARRAY OF PROCESSED CLIENTS OBJECTS
        $processedContactsForGraph= [];
        foreach ($uniqueProcessedClientsArray as $clientId => $value) {
            $processedContactsForGraph[] = $this->getDoctrine()->getRepository(Client::class)->find((int)$clientId);
        }


        $processedContactsByMonthArray = [];
        for ($i = 1; $i <13; $i++) {
            $contactsCounter = 0;
            foreach ($processedContactsForGraph as $contact) {
                if (((new \DateTime())->format("Y")) === $contact->getUpdatedAt()->format("Y")) {
                    if (date("F",mktime(0,0,0,(int)($contact->getUpdatedAt()->format("m")),1,(int)($contact->getUpdatedAt())->format("Y"))) === date("F",mktime(0,0,0,$i,1,(int)(new \DateTime())->format("Y")))) {
                        $contactsCounter += 1;
                    }
                }
            }
            $processedContactsByMonthArray[] = $contactsCounter;
        }

        $appointmentsByMonthArray = [];
        for ($j = 1; $j <13; $j++) {
            $appointmentsCounter = 0;
            foreach ($allAppointments as $appointment) {

                if (((new \DateTime())->format("Y")) === $appointment->getCreatedAt()->format("Y")) {
                    if (date("F", mktime(0, 0, 0, (int)($appointment->getCreatedAt()->format("m")), 1, (int)($appointment->getCreatedAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $appointmentsCounter += 1;
                    }
                }
            }
            $appointmentsByMonthArray[] = $appointmentsCounter;
        }

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

        return $this->render('statistics/index_new.html.twig', [
            // Partie TELEPROSPECTION
            //Bloc Statistiques Générales
            'telepro_count' => count($allTelepros),
            'contacts_count' => count($allContacts),
            //Bloc Statistiques Pour La Période Sélectionnée
            'processed_contacts_count' => count($processedContacts),
            //Bloc Statistiques Par Utilisateur
            'telepros_users' => $teleprosAndSuperAdmins,
            //new
            'clients_processes'=> $clientsProcesses,
            'clients_processes_count'=> count($clientsProcesses),
            'all_PI_contacts_count' => $PIcounter,
            'all_RAPPEL_contacts_count' => $RAPPELcounter,
            'all_NRP_contacts_count' => $NRPcounter,
            'all_RDV_contacts_count' => $RDVcounter,
            'clients_processes_by_status' => $processedContactsByStatus,
            'TX_CT' => $TXCTPercentage,
            'TX_TRANSFOR' => $TXTRANSFORPercentage,
            'NOT_QUALIFIED_contacts_count' => $NOTQUALIFIEDcontactscounter,
            'QUALIFIED_contacts_count' => $QUALIFIEDcontactscounter,
            // Partie COMMERCIAL
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
            //Bloc Statistiques Pour La Période Sélectionnée
            'done_appointments' => $doneAppointments,
            //Bloc Statistiques Par Utilisateur
            'commercials_users' => $commercialsAndSuperAdmins,
            //graph
            'processed_contacts_graph' => json_encode($processedContactsByMonthArray),
            'fixed_appointments_graph' => json_encode($appointmentsByMonthArray),
            'done_appointments_graph' => json_encode($doneAppointmentsByMonthArray),
            'deleted_appointments_graph' => json_encode($deletedAppointmentsByMonthArray),
            'actual_year' => (new \DateTime())->format("Y"),
        ]);
    }

    /**
     * @Route("/dashboard/allstats/filters", name="all_stats_filters")
     */
    public function allStatsFilters(Request $request): Response
    {

        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $dateFilterValueAllStats = $request->get('dateFilterValueAllStats');
            $session->set('date_filter_value_all_stats',
                $dateFilterValueAllStats
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } elseif ($request->isMethod('Post')) {
            /*return new JsonResponse(['message'=> 'Task Fails!']);*/
            $startDate = new \DateTime($request->request->get("start_date"));
            $endDate = new \DateTime($request->request->get("end_date"));
            $dateFilterValue = $request->request->get('dateFilterValueAllStats');
            $session->set('date_filter_value_all_stats',
                $dateFilterValue
            );
            $session->set('date_filter_value_all_stats_start',
                $startDate
            );
            $session->set('date_filter_value_all_stats_end',
                $endDate
            );
            $this->flashy->success('Filtre mis à jour avec succès !');
            return $this->redirectToRoute('all_statistics_new');
        }
    }

    /**
     * @Route("/dashboard/allstats/filters/Initialization", name="all_stats_filters_initialization")
     */
    public function allStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_all_stats');
        if($session->get('date_filter_value_all_stats_start')) $session->remove('date_filter_value_all_stats_start');
        if($session->get('date_filter_value_all_stats_end')) $session->remove('date_filter_value_all_stats_end');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('all_statistics_new');
    }

    /**
     * @Route("/dashboard/allstats/filters/notifications", name="all_stats_filters_notifications")
     */
    public function allStatsFilterssNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('all_statistics_new');
    }

    /**
     * @Route("/dashboard/statisticsperuser/{id}", name="statistics_per_user")
     */
    public function statsPerUser($id): Response
    {
        $userId = $this->getUser()->getId();
        $allContacts = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $myProcessedContacts = $user->getProcessedClients();
        $myQualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getQualifiedCallsByUser($id);
        $myNotQualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotQualifiedCallsByUser($id);
        $myDoneAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDoneAppointmentsByUser($id);
        $myDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getDeletedCallsByUser($id);
        $myFixedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getFixedAppointmentsByUser($id);
        $myAssignedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getMyAssignedAppointmentsByUser($id);
        $myUpcomingAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getUpcomingAppointmentsByUser($id);
        $myDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDeletedAppointmentsByUser($id);

        $allProcesses = $this->getDoctrine()->getRepository(Process::class)->getProcessesByUser($id);

        $allClientsIdsArray = [];
        foreach ($allProcesses as $process) {
            $allClientsIdsArray[] = $process->getClient()->getId();
        }
        $uniqueClientsIdsArray = array_unique($allClientsIdsArray);
        $uniqueClientsArray = [];
        $clientsProcesses = [];
        foreach ($uniqueClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $uniqueClientsArray[$clientId][] = $process->getCreatedAt();
                    $clientsProcesses[$clientId][$process->getStatus()][] = $process->getCreatedAt();
                }
            }
        }
        /*dd($uniqueClientsArray);*/

        //

        /*dd($clientsProcesses);*/
        $qualifiedClientsCounter = 0;
        $notQualifiedClientsCounter = 0;
        foreach ($clientsProcesses as $processedClientId => $processedClient) {
            if (count($processedClient) === 2) {
                $NQdate = $processedClient[1][0];
                $Qdate = $processedClient[2][0];

                if($NQdate >$Qdate) {
                    $notQualifiedClientsCounter += 1;
                    $notQualifiedClientsIdsForGraph[] = $processedClientId;
                } else {
                    $qualifiedClientsCounter += 1;
                    $qualifiedClientsIdsForGraph[] = $processedClientId;
                }
            } elseif (count($processedClient) === 1) {
                if(!array_key_exists("1", $processedClient)) {
                    $qualifiedClientsCounter += 1;
                    $qualifiedClientsIdsForGraph[] = $processedClientId;
                } elseif (!array_key_exists("2", $processedClient)) {
                    $notQualifiedClientsCounter += 1;
                    $notQualifiedClientsIdsForGraph[] = $processedClientId;
                }
            }
        }


        //

        //Qualified Processes
        /*$allQualifiedProcessesByUser = $this->getDoctrine()->getRepository(Process::class)->getAllQualifiedProcessesByUser($id);
        $allQualifiedClientsIdsArray = [];
        foreach ($allQualifiedProcessesByUser as $qualifiedProcessByUser) {
            $allQualifiedClientsIdsArray[] = $qualifiedProcessByUser->getClient()->getId();
        }
        $uniqueQualifiedClientsIdsArray = array_unique($allQualifiedClientsIdsArray);
        $uniqueQualifiedClientsArray = [];
        foreach ($uniqueQualifiedClientsIdsArray as $qClientId) {
            foreach ($allQualifiedProcessesByUser as $qualifiedProcessByUser) {
                if ($qualifiedProcessByUser->getClient()->getId() == $qClientId) {
                    $uniqueQualifiedClientsArray[$qClientId][] = $qualifiedProcessByUser->getCreatedAt();
                }
            }
        }*/

        /*dd($uniqueQualifiedClientsArray);*/

        //Not Qualified Processes

        /*$allNotQualifiedProcessesByUser = $this->getDoctrine()->getRepository(Process::class)->getAllNotQualifiedProcessesByUser($id);
        $allNotQualifiedClientsIdsArray = [];
        foreach ($allNotQualifiedProcessesByUser as $notQualifiedProcessByUser) {
            $allNotQualifiedClientsIdsArray[] = $notQualifiedProcessByUser->getClient()->getId();
        }
        $uniqueNotQualifiedClientsIdsArray = array_unique($allNotQualifiedClientsIdsArray);
        $uniqueNotQualifiedClientsArray = [];
        foreach ($uniqueNotQualifiedClientsIdsArray as $notQClientId) {
            foreach ($allNotQualifiedProcessesByUser as $notQualifiedProcessByUser) {
                if ($notQualifiedProcessByUser->getClient()->getId() == $notQClientId) {
                    $uniqueNotQualifiedClientsArray[$notQClientId][] = $notQualifiedProcessByUser->getCreatedAt();
                }
            }
        }*/

        /*dd($uniqueNotQualifiedClientsArray);*/
        // PERFORMANCE FICHES DE CONTACT TRAITÉS
        if(count($allContacts) !== 0) {
            $contactsPerformance = number_format(((count($uniqueClientsArray) / count($allContacts)) * 100), 2);
        } else {
            $contactsPerformance = 0;
        }

        // PERFORMANCE FICHES DE CONTACT QUALIFIES
        if(count($uniqueClientsArray) !== 0) {
            $qualifiedContactsPerformance = number_format((($qualifiedClientsCounter / count($uniqueClientsArray)) * 100), 2);
        } else {
            $qualifiedContactsPerformance = 0;
        }

        // PERFORMANCE FICHES DE CONTACT NON QUALIFIES
        if(count($uniqueClientsArray) !== 0) {
            $notQualifiedContactsPerformance = number_format((($notQualifiedClientsCounter / count($uniqueClientsArray)) * 100), 2);
        } else {
            $notQualifiedContactsPerformance = 0;
        }

        // PERFORMANCE RDV EFFECTUES

        if(count($myAssignedAppointments) !== 0) {
            $doneAppointmentsPerformance = number_format(((count($myDoneAppointments) / count($myAssignedAppointments)) * 100), 2);
        } else {
            $doneAppointmentsPerformance = 0;
        }

        // PERFORMANCE RDV ANNULES
        if(count($myAssignedAppointments) !== 0) {
            $deletedAppointmentsPerformance = number_format(((count($myDeletedAppointments) / count($myAssignedAppointments)) * 100), 2);
        } else {
            $deletedAppointmentsPerformance = 0;
        }

        return $this->render('statistics/stats_per_user.html.twig', [
            'user' => $user,
            'processed_clients' => $myProcessedContacts,
            'processed_clients_count' => count($myProcessedContacts),
            'qualified_calls' =>$myQualifiedCalls,
            'qualified_calls_count' =>count($myQualifiedCalls),
            'not_qualified_calls' =>$myNotQualifiedCalls,
            'not_qualified_calls_count' =>count($myNotQualifiedCalls),
            'done_appointments' =>$myDoneAppointments,
            'done_appointments_count' =>count($myDoneAppointments),
            'upcoming_appointments' =>$myUpcomingAppointments,
            'upcoming_appointments_count' =>count($myUpcomingAppointments),
            'my_assigned_appointments' =>$myAssignedAppointments,
            'my_assigned_appointments_count' =>count($myAssignedAppointments),
            'deleted_calls' =>$myDeletedCalls,
            'deleted_calls_count' =>count($myDeletedCalls),
            'fixed_appointments' =>$myFixedAppointments,
            'fixed_appointments_count' =>count($myFixedAppointments),
            'deleted_appointments' =>$myDeletedAppointments,
            'deleted_appointments_count' =>count($myDeletedAppointments),
            'single_clients_processes' => $uniqueClientsArray,
            'single_clients_processes_count' => count($uniqueClientsArray),
            /*'single_qualified_clients_processes' => $uniqueQualifiedClientsArray,*/
            'single_qualified_clients_processes_count' => $qualifiedClientsCounter,
            /*'single_not_qualified_clients_processes' => $uniqueNotQualifiedClientsArray,*/
            'single_not_qualified_clients_processes_count' => $notQualifiedClientsCounter,
            'all_contacts' =>$allContacts,
            'all_contacts_count' =>count($allContacts),
            'contacts_performance' => $contactsPerformance,
            'done_appointments_performance' => $doneAppointmentsPerformance,
            'deleted_appointments_performance' => $deletedAppointmentsPerformance,
            'qualified_contacts_performance' => $qualifiedContactsPerformance,
            'not_qualified_contacts_performance' => $notQualifiedContactsPerformance,
            'clients_processes' => $clientsProcesses
        ]);
    }

    /**
     * @Route("/dashboard/statisticsperuser/{id}/filters", name="statistics_per_user_filters")
     */
    public function statsPerUserFilters(Request $request, $id): Response
    {

        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $dateFilterValueStatsPerUser = $request->get('dateFilterValueStatsPerUser');
            /*$id = $request->get('userId');*/
            $session->set('date_filter_value_stats_per_user',
                $dateFilterValueStatsPerUser
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } elseif ($request->isMethod('Post')) {
            /*$id = $request->request->get('user_id');*/
            /*return new JsonResponse(['message'=> 'Task Fails!']);*/
            $startDate = new \DateTime($request->request->get("start_date"));
            $endDate = new \DateTime($request->request->get("end_date"));
            $dateFilterValue = $request->request->get('dateFilterValueStatsPerUser');
            $session->set('date_filter_value_stats_per_user',
                $dateFilterValue
            );
            $session->set('date_filter_value_stats_per_user_start',
                $startDate
            );
            $session->set('date_filter_value_stats_per_user_end',
                $endDate
            );
            $this->flashy->success('Filtre mis à jour avec succès !');
            return $this->redirectToRoute('statistics_per_user', [
                'id' => $id
            ]);
        }
    }

    /**
     * @Route("/dashboard/statisticsperuser/{id}/filters/Initialization", name="statistics_per_user_filters_initialization")
     */
    public function statsPerUserFiltersInitialization(Request $request, $id): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_stats_per_user');
        if($session->get('date_filter_value_stats_per_user_start')) $session->remove('date_filter_value_stats_per_user_start');
        if($session->get('date_filter_value_stats_per_user_end')) $session->remove('date_filter_value_stats_per_user_end');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('statistics_per_user', [
            'id' => $id
        ]);
    }

    /**
     * @Route("/dashboard/statisticsperuser/{id}filters/notifications", name="statistics_per_user_filters_notifications")
     */
    public function statsPerUserFilterssNotifications($id): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('statistics_per_user', [
            'id' => $id
        ]);
    }


}
