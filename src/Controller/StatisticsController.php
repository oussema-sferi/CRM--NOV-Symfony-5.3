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
        $processedContacts = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
        if(count($allContacts) !== 0) {
            $contactsPerformance = number_format(((count($processedContacts) / count($allContacts)) * 100), 2);
        } else {
            $contactsPerformance = 0;
        }
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        if(count($processedContacts) !== 0) {
            $appointmentsPerformance = number_format(((count($allAppointments) / count($processedContacts)) * 100), 2);
        } else {
            $appointmentsPerformance = 0;
        }
        $processedContactsByMonthArray = [];
        for ($i = 1; $i <13; $i++) {
            $contactsCounter = 0;
            foreach ($processedContacts as $contact) {
                foreach ($contact->getCalls() as $call) {
                    if (((new \DateTime())->format("Y")) === $call->getCreatedAt()->format("Y")) {
                        if (date("F",mktime(0,0,0,(int)($call->getCreatedAt()->format("m")),1,(int)($call->getCreatedAt())->format("Y"))) === date("F",mktime(0,0,0,$i,1,(int)(new \DateTime())->format("Y")))) {
                            $contactsCounter += 1;
                            break;
                        }
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
                    if (date("F", mktime(0, 0, 0, (int)($appointment->getCreatedAt()->format("m")), 1, (int)($call->getCreatedAt())->format("Y"))) === date("F", mktime(0, 0, 0, $j, 1, (int)(new \DateTime())->format("Y")))) {
                        $appointmentsCounter += 1;
                    }
                }
            }
            $appointmentsByMonthArray[] = $appointmentsCounter;
        }
        return $this->render('statistics/index.html.twig', [
            'total_all_contacts' => count($allContacts),
            'total_processed_contacts' => $processedContacts,
            'count_total_processed_contacts' => count($processedContacts),
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
     * @Route("/dashboard/myprofile/mystatistics", name="my_statistics")
     */
    public function myStatistics(): Response
    {
        $userId = $this->getUser()->getId();
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
        $myProcessedContacts = $user->getCalledClients();
        $myQualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getQualifiedCallsByUser($userId);
        $myNotQualifiedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotQualifiedCallsByUser($userId);
        $myDoneAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getDoneAppointmentsByUser($userId);
        /*dd($myQualifiedCalls);*/

        /*dd(count($myProcessedContacts));*/
        return $this->render('statistics/my_stats.html.twig', [
            'processed_clients' => $myProcessedContacts,
            'processed_clients_count' => count($myProcessedContacts),
            'qualified_calls' =>$myQualifiedCalls,
            'qualified_calls_count' =>count($myQualifiedCalls),
            'not_qualified_calls' =>$myNotQualifiedCalls,
            'not_qualified_calls_count' =>count($myNotQualifiedCalls),
            'done_appointments' =>$myDoneAppointments,
            'done_appointments_count' =>count($myDoneAppointments),
        ]);
    }

    /**
     * @Route("/dashboard/myprofile/mystatistics/filters", name="my_stats_filters")
     */
    public function myStatsFilters(Request $request): Response
    {

        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $dateFilterValueMyStats = $request->get('dateFilterValueMyStats');
            $session->set('date_filter_value_my_stats',
                $dateFilterValueMyStats
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } elseif ($request->isMethod('Post')) {
            /*return new JsonResponse(['message'=> 'Task Fails!']);*/
            $startDate = new \DateTime($request->request->get("start_date"));
            $endDate = new \DateTime($request->request->get("end_date"));
            $dateFilterValue = $request->request->get('dateFilterValueMyStats');
            $session->set('date_filter_value_my_stats',
                $dateFilterValue
            );
            $session->set('date_filter_value_my_stats_start',
                $startDate
            );
            $session->set('date_filter_value_my_stats_end',
                $endDate
            );
            $this->flashy->success('Filtre mis à jour avec succès !');
            return $this->redirectToRoute('my_statistics');
        }
    }

    /**
     * @Route("/dashboard/myprofile/mystatistics/filters/Initialization", name="my_stats_filters_initialization")
     */
    public function myStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_my_stats');
        if($session->get('date_filter_value_my_stats_start')) $session->remove('date_filter_value_my_stats_start');
        if($session->get('date_filter_value_my_stats_end')) $session->remove('date_filter_value_my_stats_end');
        $this->flashy->success('Filtre réinitialisé avec succès !');
        return $this->redirectToRoute('my_statistics');
    }

    /**
     * @Route("/dashboard/myprofile/mystatistics/filters/notifications", name="my_stats_filters_notifications")
     */
    public function myStatsFilterssNotifications(): Response
    {
        $this->flashy->success('Filtre mis à jour avec succès !');
        return $this->redirectToRoute('my_statistics');
    }


}
