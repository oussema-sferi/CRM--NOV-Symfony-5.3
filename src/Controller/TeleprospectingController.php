<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\Process;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Form\CallFormType;
use App\Form\ClientFormType;
use App\Repository\AppointmentRepository;
use App\Repository\CallRepository;
use App\Repository\ClientRepository;
use App\Repository\ProcessRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class TeleprospectingController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/teleprospecting", name="teleprospecting")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $loggedTelepro = $this->getUser();
        $teleproGeographicAreasArray = $loggedTelepro->getGeographicAreas();
        $teleproGeographicAreasIdsArray = [];
        foreach ($teleproGeographicAreasArray as $geographicArea) {
            $teleproGeographicAreasIdsArray[] =  $geographicArea->getId();
        }
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_TELEPRO",$loggedUserRolesArray) || in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByTeleproDepartments($teleproGeographicAreasIdsArray, $loggedTelepro->getId());
            /*dd($data);*/
            /*dd($loggedTelepro->getId());*/
        } else {
            $data = $this->getDoctrine()->getRepository(Client::class)->getNotFixedAppointmentsClients();
        }
        /*dd(count($data));*/

        $session = $request->getSession();
        /*$data = $this->getDoctrine()->getRepository(Client::class)->findAll();*/
        /*$data = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 0]);*/
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $session->set('total_telepro',
            count($data)
        );
        $session->remove('total_telepro_search_results');
        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        /*dd($clients[0]->getCalls());*/
        /*dd($clients);*/
        return $this->render('teleprospecting/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/add", name="new_client")
     */
    public function add(Request $request): Response
    {
        $loggedUser = $this->getUser();
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        if($clientForm->isSubmitted()) {
            $newClient->setStatus(0);
            $newClient->setStatusDetail(0);
            $newClient->setCreatedAt(new \DateTime());
            $newClient->setUpdatedAt(new \DateTime());
            $newClient->setCreatorUser($loggedUser);
            $newClient->setIsDeleted(false);
            $newClient->setDeletionDate(null);
            $manager->persist($newClient);
            $manager->flush();
            $this->flashy->success("Contact créé avec succès !");
            return $this->redirectToRoute('teleprospecting');
        }
        return $this->render('/teleprospecting/add.html.twig', [
            'client_form' => $clientForm->createView()
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/update/{id}", name="update_client")
     */
    public function update(Request $request, $id): Response
    {
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($id);
        if($clientForm->isSubmitted()) {
            $clientToUpdate->setFirstName($newClient->getFirstName());
            $clientToUpdate->setLastName($newClient->getLastName());
            $clientToUpdate->setCompanyName($newClient->getCompanyName());
            $clientToUpdate->setEmail($newClient->getEmail());
            $clientToUpdate->setAddress($newClient->getAddress());
            $clientToUpdate->setPostalCode($newClient->getPostalCode());
            $clientToUpdate->setCountry($newClient->getCountry());
            $clientToUpdate->setPhoneNumber($newClient->getPhoneNumber());
            $clientToUpdate->setMobileNumber($newClient->getMobileNumber());
            $clientToUpdate->setCategory($newClient->getCategory());
            $clientToUpdate->setIsUnderContract($newClient->getIsUnderContract());
            $manager->persist($clientToUpdate);
            $manager->flush();
            $this->flashy->success("Contact mis à jour avec succès !");
            return $this->redirectToRoute('teleprospecting');
        }
        return $this->render('/teleprospecting/update.html.twig', [
            'client_form' => $clientForm->createView(),
            'client_to_update' => $clientToUpdate
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/show/{id}", name="show_client")
     */
    public function show(Request $request, $id): Response
    {
        $clientToShow = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $clientAppointmentsList = $clientToShow->getAppointments();
        return $this->render('/all_contacts/show.html.twig', [
            'client_to_show' => $clientToShow,
            'client_appointments_list' => $clientAppointmentsList
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/delete/{id}", name="delete_telepro")
     */
    public function delete(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $contactToDelete = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $contactToDelete->setIsDeleted(true);
        $contactToDelete->setDeletionDate(new \DateTime());
        $contactToDelete->setWhoDeletedIt($loggedUser);
        $manager->persist($contactToDelete);
        $manager->flush();
        $this->flashy->success("Contact supprimé avec succès !");
        return $this->redirectToRoute('teleprospecting');
    }

    /**
     * @Route("/dashboard/teleprospecting/call/{id}", name="call_handle")
     */
    public function callHandle(Request $request, $id): Response
    {
        /*dd($request->headers->get('referer'));*/
        $loggedUser = $this->getUser();
        /*$loggedUserId = $this->getUser()->getUserIdentifier();
        $usr = $this->getDoctrine()->getRepository(User::class)->findBy(['email' => $loggedUserId]);*/
        /*dd($usr);*/
        $directAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $directAppointment);
        $appointmentForm->handleRequest($request);

        $newCall = new Call();
        $callForm = $this->createForm(CallFormType::class, $newCall);
        $callForm->handleRequest($request);
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $manager = $this->getDoctrine()->getManager();

        if($callForm->isSubmitted()) {
            $previousReferer = $request->request->get('call_referer');

            /*dd($newCall->getCallNotes());*/
            /*dd($request->request->get('call_form[callNotes]'));*/
            $status = (int)$request->request->get('status');
            $statusDetailsNQ = (int)$request->request->get('detailsnq');
            $statusDetailsQ = (int)$request->request->get('detailsq');
            if($status === 2 && $statusDetailsQ === 7) {
                return $this->render('/teleprospecting/direct_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);

            } else {
                /*dd($loggedUser->getProcessedClients());*/
                /*$client->setStatusDetail($statusDetailsQ);*/
                $newCall->setCreatedAt(new  \DateTime());
                $newCall->setUser($loggedUser);
                $newCall->setClient($client);
                $newCall->setGeneralStatus($status);
                $newCall->setCallNotes($newCall->getCallNotes());
                $newCall->setIsDeleted(false);
                $usersWhoCalled = $client->getCallersUsers();
                $userCounter = 0;
                foreach ($usersWhoCalled as $user) {
                    if ($user->getId() == $this->getUser()->getId()) {
                        $userCounter += 1;
                        break;
                    }
                }
                if ($userCounter === 0) {
                    $client->addCallersUser($loggedUser);
                }

                $client->setStatus($status);
                if($statusDetailsQ) {
                    $newCall->setStatusDetails($statusDetailsQ);
                    $client->setStatusDetail($statusDetailsQ);
                } elseif ($statusDetailsNQ) {
                    $newCall->setStatusDetails($statusDetailsNQ);
                    $client->setStatusDetail($statusDetailsNQ);
                }
                $client->setUpdatedAt(new \DateTime());
                $client->setIsProcessed(true);
                $loggedUser->addProcessedClient($client);
                $manager->persist($newCall);
                $newProcess = new Process();
                $newProcess->setClient($client);
                $newProcess->setProcessorUser($loggedUser);
                $newProcess->setStatus($status);
                if($statusDetailsQ) {
                    $newProcess->setStatusDetail($statusDetailsQ);
                } elseif ($statusDetailsNQ) {
                    $newProcess->setStatusDetail($statusDetailsNQ);
                }
                $newProcess->setCreatedAt(new \DateTime());
                $manager->persist($newProcess);
                /*$test = $this->getDoctrine()->getRepository(UniqueClientProcess::class)->findAll();*/
                $manager->flush();
                $this->flashy->success("Fiche contact traitée avec succès !");
                return $this->redirect($previousReferer);
            }

        }
        $loggedUserId = $this->getUser()->getId();
        $clients = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        if($appointmentForm->isSubmitted()) {
            $previousReferer = $request->request->get('call_referer');
            $validationStartTime = $directAppointment->getStart();
            $validationEndTime = $directAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);

            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                return $this->render('/teleprospecting/direct_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }
            if($validationEndTime > $validationStartTime) {
                $startTime = $directAppointment->getStart()->format('Y-m-d H:i:s');
                $endTime = $directAppointment->getEnd()->format('Y-m-d H:i:s');
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

                if((count($freeCommercials) !== 0)) {
                    return $this->render('/appointment/free_commercials_check.html.twig', [
                        'free_commercials' => $freeCommercials,
                        'clients' => $client,
                        'start' => $startTime,
                        'end' => $endTime,
                        'appointment_referer_teleprospecting' => $previousReferer
                    ]);
                } else {
                    $this->flashy->info("Aucun agent n'est disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                    return $this->render('/teleprospecting/direct_appointment.html.twig', [
                        'appointment_form' => $appointmentForm->createView(),
                    ]);
                }

            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
                }
                return $this->render('/teleprospecting/direct_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }

        }


        return $this->render('/teleprospecting/callHandle.html.twig', [
            'call_form' => $callForm->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/commercialslist", name="commercials_list")
     */
    public function showCommercialsList(): Response
    {
        $loggedUserId = $this->getUser()->getId();
        $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId);
        return $this->render('teleprospecting/commercials_list_show.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/pagination", name="teleprospecting_pagination")
     */
    public function teleprospectingPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
                return new JsonResponse(['message'=> 'Task Success!']);
            } else {
                return new JsonResponse(['message'=> 'Task Fails!']);
            }
        return new Response('use Ajax');
    }

    /**
     * @Route("/dashboard/teleprospecting/stats", name="teleprospecting_stats")
     */
    public function teleprospectingStats(Request $request): Response
    {

        $justTelepros = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
        $allTelepros = $this->getDoctrine()->getRepository(User::class)->findUsersTeleproStats("ROLE_TELEPRO", "ROLE_SUPERADMIN");
        $allClients = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();

        //CONTACTS PROCESSED
        $allProcesses = $this->getDoctrine()->getRepository(Process::class)->findAllSortedDate();
        $allClientsIdsArray = [];
        $clientsProcesses = [];
        foreach ($allProcesses as $process) {
            $allClientsIdsArray[] = $process->getClient()->getId();
        }
        $uniqueClientsIdsArray = array_unique($allClientsIdsArray);
        $uniqueClientsArray = [];
        foreach ($uniqueClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $uniqueClientsArray[$clientId][] = $process->getCreatedAt();
                    $clientsProcesses[$clientId][$process->getStatus()][] = $process->getCreatedAt();
                }
            }
        }

        // Qualified and not Qualified clients count
        $qualifiedClientsCounter = 0;
        $notQualifiedClientsCounter = 0;
        foreach ($clientsProcesses as $processedClient) {
            if (count($processedClient) === 2) {
                $NQdate = $processedClient[1][0];
                $Qdate = $processedClient[2][0];

                if($NQdate >$Qdate) {
                    $notQualifiedClientsCounter += 1;
                } else {
                    $qualifiedClientsCounter += 1;
                }
            } elseif (count($processedClient) === 1) {
                if(!array_key_exists("1", $processedClient)) {
                    $qualifiedClientsCounter += 1;
                } elseif (!array_key_exists("2", $processedClient)) {
                    $notQualifiedClientsCounter += 1;
                }
            }
        }
        //

        /*dd($notQualifiedClientsCounter);*/
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


        $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->getNotProcessedClients();
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExistCommercialStats();
        /*$allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();*/
        $allCalls = $this->getDoctrine()->getRepository(Call::class)->getAllNotDeletedCalls();
        $qualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getQualifiedCalls();
        $notQualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotQualifiedCalls();
        $deletedCalls = $this->getDoctrine()->getRepository(Call::class)->findBy(["isDeleted" => 1]);
        /*dd($processedCalls);*/
        /*dd($allTelepros);*/

        // PERFORMANCE FICHES DE CONTACT TRAITÉS
        if(count($allClients) !== 0) {
            $contactsPerformance = number_format(((count($uniqueClientsArray) / count($allClients)) * 100), 2);
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
       /* if(count($uniqueClientsArray) !== 0) {
            $appointmentsPerformance = number_format(((count($allAppointments) / count($uniqueClientsArray)) * 100), 2);
        } else {
            $appointmentsPerformance = 0;
        }*/

        return $this->render('teleprospecting/telepro_stats.html.twig', [
            'count_total_telepros' => count($justTelepros),
            'all_telepros' => $allTelepros,
            'total_clients' => count($allClients),
            'not_processed_clients' => count($notProcessedClients),
            'total_appointments' => $allAppointments,
            'total_appointments_count' => count($allAppointments),
            'total_calls' => $allCalls,
            'total_calls_count' => count($allCalls),
            'qualified_calls_count' => count($qualifiedCalls),
            'not_qualified_calls_count' => count($notQualifiedCalls),
            'deleted_calls_count' => count($deletedCalls),
            'single_clients_processes' => $uniqueClientsArray,
            'single_clients_processes_count' => count($uniqueClientsArray),
           /* 'single_qualified_clients_processes' => $uniqueQualifiedClientsArray,*/
            'single_qualified_clients_processes_count' => $qualifiedClientsCounter,
            /*'single_not_qualified_clients_processes' => $uniqueNotQualifiedClientsArray,*/
            'single_not_qualified_clients_processes_count' => $notQualifiedClientsCounter,
            'contacts_performance' => $contactsPerformance,
            /*'appointments_performance' => $appointmentsPerformance,*/
            'qualified_contacts_performance' => $qualifiedContactsPerformance,
            'not_qualified_contacts_performance' => $notQualifiedContactsPerformance,
            'clients_processes' => $clientsProcesses
        ]);
    }


    /**
     * @Route("/dashboard/teleprospecting/statsnew", name="teleprospecting_stats_new")
     */
    public function teleprospectingStatsNew(Request $request, ProcessRepository $processRepository,CallRepository $callRepository, UserRepository $userRepository, ClientRepository $clientRepository, AppointmentRepository $appointmentRepository): Response
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
        $qualifiedContactsArray = [];
        foreach ($processedClientsIdsArray as $clientId) {
            foreach ($allProcesses as $process) {
                if ($process->getClient()->getId() == $clientId) {
                    $qualifiedContactsArray[$clientId][$process->getCreatedAt()->format('Y-m-d H:i:s')] = $process->getStatus();
                }
            }
        }
        $QUALIFIEDcontactscounter = 0;
        foreach ($qualifiedContactsArray as $client) {
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
        //Bloc Résumé Statistiques
        /*$allCalls = $callRepository->findAll();
        $PICalls = $callRepository->findBy(["statusDetails" => 5]);
        $RAPPELCalls = $callRepository->findBy(["statusDetails" => 6]);
        $NRPCalls = $callRepository->findBy(["statusDetails" => 1]);
        $RDVCalls = $callRepository->findBy(["statusDetails" => 7]);
        $QualifiedCalls = $callRepository->findBy(["generalStatus" => 2]);
        $NOTQualifiedCalls = $callRepository->findBy(["generalStatus" => 1]);
        $RDVAppointments = $appointmentRepository->getAppointmentsWhereClientsExistCommercialStats();
        if(count($allCalls) !== 0) {
            $TXCTPercentage = number_format(((count($QualifiedCalls) / count($allCalls)) * 100), 2);
        } else {
            $TXCTPercentage = 0;
        }
        if(count($QualifiedCalls) !== 0) {
            $TXTRANSFOPercentage = number_format(((count($RDVAppointments) / count($QualifiedCalls)) * 100), 2);
        } else {
            $TXTRANSFOPercentage = 0;
        }*/


        //Bloc Statistiques Générales
        /*$allTelepros = $userRepository->findUsersByCommercialRole("ROLE_TELEPRO");
        $allContacts = $clientRepository->getNotDeletedClients();
        $processedContacts = $clientRepository->getProcessedClients();*/

        //Bloc Statistiques Par Utilisateur
        $users = $userRepository->findUsersTeleproStats("ROLE_TELEPRO", "ROLE_SUPERADMIN");

        return $this->render('teleprospecting/telepro_stats_new.html.twig', [
            //Bloc Résumé Statistiques
            /*'all_calls' => $allCalls,
            'all_calls_count' => count($allCalls),
            'PI_calls' => $PICalls,
            'PI_calls_count' => count($PICalls),
            'RAPPEL_calls' => $RAPPELCalls,
            'RAPPEL_calls_count' => count($RAPPELCalls),
            'NRP_calls' => $NRPCalls,
            'NRP_calls_count' => count($NRPCalls),
            'RDV' => $RDVAppointments,
            'RDV_count' => count($RDVAppointments),
            'TX_CT' => $TXCTPercentage,
            'TX_TRANSFO' => $TXTRANSFOPercentage,
            //Bloc Statistiques Générales
            'telepro_count' => count($allTelepros),
            'contacts_count' => count($allContacts),*/
            //Bloc Statistiques Pour La Période Sélectionnée
            /*'processed_contacts_count' => count($processedContacts),
            'QUALIFIED_calls' => $QualifiedCalls,
            'QUALIFIED_calls_count' => count($QualifiedCalls),
            'NOT_QUALIFIED_calls' => $NOTQualifiedCalls,
            'NOT_QUALIFIED_calls_count' => count($NOTQualifiedCalls),*/
            //Bloc Statistiques Par Utilisateur
            'users' => $users,
            //new
            'clients_processes'=> $clientsProcesses,
            'all_PI_contacts_count' => $PIcounter,
            'all_RAPPEL_contacts_count' => $RAPPELcounter,
            'all_NRP_contacts_count' => $NRPcounter,
            'all_RDV_contacts_count' => $RDVcounter,
            'clients_processes_by_status' => $qualifiedContactsArray,
            'TX_CT' => $TXCTPercentage,
            'TX_TRANSFOR' => $TXTRANSFORPercentage,
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/stats/filters", name="teleprospecting_stats_filters")
     */
    public function teleprospectingStatsFilters(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $dateFilterValue = $request->get('dateFilterValue');
                $session->set('date_filter_value',
                    $dateFilterValue
                );
                return new JsonResponse(['message'=> 'Task Success!']);
        } elseif ($request->isMethod('Post')) {
            /*dd($request->request->all());*/

            /*return new JsonResponse(['message'=> 'Task Fails!']);*/
            $startDate = new \DateTime($request->request->get("start_date"));
            $endDate = new \DateTime($request->request->get("end_date"));
            /*dd($startDate);
            dd($request->request->get("end_date"));*/
            $dateFilterValue = $request->request->get('dateFilterValue');
            $session->set('date_filter_value',
                $dateFilterValue
            );
            $session->set('date_filter_start',
                $startDate
            );
            $session->set('date_filter_end',
                $endDate
            );
            $this->flashy->success('Filtre mis à jour avec succès !');
            return $this->redirectToRoute('teleprospecting_stats_new');
        }
    }

    /**
     * @Route("/dashboard/teleprospecting/stats/filters/Initialization", name="teleprospecting_stats_filters_initialization")
     */
    public function teleprospectingStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value');
        if($session->get('date_filter_start')) $session->remove('date_filter_start');
        if($session->get('date_filter_end')) $session->remove('date_filter_end');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('teleprospecting_stats_new');
    }

    /**
     * @Route("/dashboard/teleprospecting/stats/filters/notifications", name="teleprospecting_stats_filters_notifications")
     */
    public function teleprospectingStatsFiltersNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('teleprospecting_stats_new');
    }

}
