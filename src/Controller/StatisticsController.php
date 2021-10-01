<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    /**
     * @Route("/dashboard/allstats", name="all_statistics")
     */
    public function index(): Response
    {
        $allContacts = $this->getDoctrine()->getRepository(Client::class)->findAll();
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


        return $this->render('statistics/index.html.twig', [
            'total_all_contacts' => count($allContacts),
            'total_processed_contacts' => $processedContacts,
            'count_total_processed_contacts' => count($processedContacts),
            'contacts_performance' => $contactsPerformance,
            'total_appointments' => $allAppointments,
            'count_total_appointments' => count($allAppointments),
            'appointments_performance' => $appointmentsPerformance,
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
            /*dd($dateFilterValue);*/
            /*if($dateFilterValue) {*/
            /*$session->remove('start_date');
            $session->remove('end_date');*/
            $session->set('date_filter_value_all_stats',
                $dateFilterValueAllStats
            );
            /*$session->remove('invalid_date');*/
            /*dd($dateFilterValue);*/
            /*$serializer = new Serializer([new ObjectNormalizer()]);
            $result = $serializer->normalize($products,'json',['attributes' => ['id','name','price', 'quantityInStock']]);*/
            return new JsonResponse(['message'=> 'Task Success!']);
            /*}*/

        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
            /*if($request->isMethod('Post')) {
                $startDate = new \DateTime($request->request->get('start_date'));
                $endDate = new \DateTime($request->request->get('end_date'));
                if($startDate > $endDate) {
                    $this->flashy->warning("Une erreur est survenue, veuillez sélectioonner une période valide !");

                }
                $session->remove('date_filter_value');
                $session->set('start_date',
                    $startDate
                );
                $session->set('end_date',
                    $endDate
                );

                return $this->redirectToRoute("teleprospecting_stats");
            }*/
        }
        /* return new Response('use Ajax');
         $allTelepros = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
         $allClients = $this->getDoctrine()->getRepository(Client::class)->findAll();
         $processedClients = $this->getDoctrine()->getRepository(Client::class)->getProcessedClients();
         $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 0]);
         $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();

         return $this->render('teleprospecting/telepro_stats.html.twig', [
             'count_total_telepros' => count($allTelepros),
             'all_telepros' => $allTelepros,
             'total_clients' => count($allClients),
             'processed_clients' => count($processedClients),
             'not_processed_clients' => count($notProcessedClients),
             'total_appointments' => count($allAppointments)
         ]);*/
    }

    /**
     * @Route("/dashboard/allstats/filters/Initialization", name="all_stats_filters_initialization")
     */
    public function allStatsFiltersInitialization(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('date_filter_value_all_stats');
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

}
