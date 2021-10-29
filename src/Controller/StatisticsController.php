<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\User;
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


        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        $allContacts = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        $allUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
        $processedClientsArray = [];
        foreach ($allUsers as $user) {
            foreach ($user->getProcessedClients() as $client) {
                $processedClientsArray[] = $client->getId();
            }
        }
        /*dd($clientsIdsArray);*/
        $uniqueProcessedClientsArray = array_unique($processedClientsArray);
        $processedClients = [];
        foreach ($uniqueProcessedClientsArray as $clientId) {
            foreach ($allContacts as $clientObject) {
                if($clientObject->getId() === $clientId) {
                    $processedClients[] = $clientObject;
                    break;
                }
            }
        }

        /*$processedContacts = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();*/
        if(count($allContacts) !== 0) {
            $contactsPerformance = number_format(((count($processedClients) / count($allContacts)) * 100), 2);
        } else {
            $contactsPerformance = 0;
        }
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        if(count($processedClients) !== 0) {
            $appointmentsPerformance = number_format(((count($allAppointments) / count($processedClients)) * 100), 2);
        } else {
            $appointmentsPerformance = 0;
        }
        $processedContactsByMonthArray = [];
        for ($i = 1; $i <13; $i++) {
            $contactsCounter = 0;
            foreach ($processedClients as $contact) {
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


        return $this->render('statistics/index.html.twig', [
            'total_all_contacts' => count($allContacts),
            'total_processed_contacts' => $processedClients,
            'count_total_processed_contacts' => count($processedClients),
            'contacts_performance' => $contactsPerformance,
            'total_appointments' => $allAppointments,
            'count_total_appointments' => count($allAppointments),
            'appointments_performance' => $appointmentsPerformance,
            'processed_contacts_graph' => json_encode($processedContactsByMonthArray),
            'fixed_appointments_graph' => json_encode($appointmentsByMonthArray),
            'actual_year' => (new \DateTime())->format("Y")
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
            return $this->redirectToRoute('all_statistics');
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
        return $this->redirectToRoute('all_statistics');
    }

    /**
     * @Route("/dashboard/allstats/filters/notifications", name="all_stats_filters_notifications")
     */
    public function allStatsFilterssNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('all_statistics');
    }

    /**
     * @Route("/dashboard/statisticsperuser/{id}", name="statistics_per_user")
     */
    public function statsPerUser($id): Response
    {
        $userId = $this->getUser()->getId();
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
        /*dd($myDeletedAppointments);*/

        /*dd(count($myProcessedContacts));*/
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
