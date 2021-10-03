<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Form\CallFormType;
use App\Form\ClientFormType;
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
        if (in_array("ROLE_TELEPRO",$loggedUserRolesArray)) {
            $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByTeleproDepartments($teleproGeographicAreasIdsArray, $loggedTelepro->getId());
            /*dd($loggedTelepro->getId());*/
        } else {
            $data = $this->getDoctrine()->getRepository(Client::class)->getNotFixedAppointmentsClients();
        }

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
     * @Route("/dashboard/teleprospecting/call/{id}", name="call_handle")
     */
    public function callHandle(Request $request, $id): Response
    {
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
                /*$client->setStatusDetail($statusDetailsQ);*/
                $newCall->setCreatedAt(new  \DateTime());
                $newCall->setUser($loggedUser);
                $newCall->setClient($client);
                $newCall->setGeneralStatus($status);
                $newCall->setCallNotes($newCall->getCallNotes());
                $client->setStatus($status);
                if($statusDetailsQ) {
                    $newCall->setStatusDetails($statusDetailsQ);
                    $client->setStatusDetail($statusDetailsQ);
                } elseif ($statusDetailsNQ) {
                    $newCall->setStatusDetails($statusDetailsNQ);
                    $client->setStatusDetail($statusDetailsNQ);
                }
                $manager->persist($newCall);
                $manager->flush();
                $this->flashy->success("Fiche contact traitée avec succès !");
                return $this->redirectToRoute('teleprospecting');
            }

            /*if(!$request->request->get('detailsnq')) {
            $newCall->setCallNotes(null);
            } else {
                $newCall->setCallNotes($request->request->get('detailsnq'));
            }*/
        }
        $loggedUserId = $this->getUser()->getId();
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();
        if($appointmentForm->isSubmitted()) {
            $validationStartTime = $directAppointment->getStart();
            $validationEndTime = $directAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);

            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                /*$this->addFlash(
                    'appointment_duration_warning',
                    "Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin!"
                );*/
                return $this->render('/teleprospecting/direct_appointment.html.twig', [
                    'appointment_form' => $appointmentForm->createView(),
                ]);
            }

            if((($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h <= 2)) ||
                (($validationEndTime > $validationStartTime) && ($appointmentDuration->days === 0) && ($appointmentDuration->h === 3)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0))
            ) {
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
                return $this->render('/appointment/free_commercials_check.html.twig', [
                    /*'free_appointments' => $freeAppointmentsTime*/
                    'free_commercials' => $freeCommercials,
                    'clients' => $client,
                    'start' => $startTime,
                    'end' => $endTime
                ]);
            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    /*dd($appointmentDuration);*/
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
                    /*$this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV doit pas être nulle!"
                    );*/
                } else {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures !");
                   /* $this->addFlash(
                        'appointment_duration_warning',
                        "Veuillez revérifier vos entrées! La durée du RDV ne doit pas dépasser trois heures!"
                    );*/
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

        $allTelepros = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
        $allClients = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        $processedClients = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
        $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->getNotProcessedClients();
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();

        return $this->render('teleprospecting/telepro_stats.html.twig', [
            'count_total_telepros' => count($allTelepros),
            'all_telepros' => $allTelepros,
            'total_clients' => count($allClients),
            'processed_clients' => $processedClients,
            'processed_clients_count' => count($processedClients),
            'not_processed_clients' => count($notProcessedClients),
            'total_appointments' => $allAppointments,
            'total_appointments_count' => count($allAppointments)
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
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
        }
    }

    /**
     * @Route("/dashboard/teleprospecting/stats/filters/Initialization", name="teleprospecting_stats_filters_initialization")
     */
    public function teleprospectingStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('teleprospecting_stats');
    }

    /**
     * @Route("/dashboard/teleprospecting/stats/filters/notifications", name="teleprospecting_stats_filters_notifications")
     */
    public function teleprospectingStatsFiltersNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('teleprospecting_stats');
    }

}
