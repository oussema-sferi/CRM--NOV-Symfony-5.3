<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\EquipmentRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SearchFiltersController extends AbstractController
{
    /**
     * @Route("/dashboard/teleprospecting/search/filters", name="telepro_search_filters")
     */
    public function teleproSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        $loggedTelepro = $this->getUser();
        $teleproGeographicAreasArray = $loggedTelepro->getGeographicAreas();
        $teleproGeographicAreasIdsArray = [];
        foreach ($teleproGeographicAreasArray as $geographicArea) {
            $teleproGeographicAreasIdsArray[] =  $geographicArea->getId();
        }
        /*dd($teleproGeographicAreasIdsArray);*/
        /*$session = $request->getSession();
        if($request->isMethod('POST')) {
            $searchKeyword = $request->request->get('search_keyword');
            $searchFilter = $request->request->get('filters');
            $session->set('filters',
                $searchFilter
            );
            $session->set('search_keyword',
                $searchKeyword
            );

        }
        $searchKeywordSession = $session->get('search_keyword');
        $searchFilterSession = $session->get('filters');
        $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByFilterAndKeyword($searchFilterSession,$searchKeywordSession);
        $clients = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );
            return $this->render('teleprospecting/index.html.twig', [
                'clients' => $clients,
            ]);*/
        //search without ajax
            $session = $request->getSession();
            $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
            if($request->isMethod('POST')) {
                /*dd($request->request->all());*/
                $session->set('criterias',
                    $request->request->all()
                );
            }
            $criterias = $session->get('criterias');

            $loggedUserId = $this->getUser()->getId();
            $loggedUserRolesArray = $this->getUser()->getRoles();
            if (in_array("ROLE_TELEPRO",$loggedUserRolesArray) || in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
                /*dd($teleproGeographicAreasIdsArray);*/
                $payload = $this->getDoctrine()->getRepository(Client::class)->fetchAssignedClientsbyFilters($teleproGeographicAreasIdsArray, $criterias, $loggedUserId);

            } else {
                $payload = $this->getDoctrine()->getRepository(Client::class)->fetchClientsbyFilters($criterias);
            }


            if(count($payload) === 0) {
                $session->set('total_telepro_search_results',
                    'nothing'
                );
            } else {
                $session->set('total_telepro_search_results',
                    count($payload)
                );
            }
            /*dd($payload);*/


        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }

           /* $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );*/

        return $this->render('teleprospecting/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/search/filters", name="all_contacts_search_filters")
     */
    public function allContactsSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        //search without ajax
        $session = $request->getSession();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        if($request->isMethod('POST')) {
            /*dd($request->request->all());*/
            $session->set('criterias',
                $request->request->all()
            );
        }
        $criterias = $session->get('criterias');

        $loggedTelepro = $this->getUser();
        $teleproGeographicAreasArray = $loggedTelepro->getGeographicAreas();
        $teleproGeographicAreasIdsArray = [];
        foreach ($teleproGeographicAreasArray as $geographicArea) {
            $teleproGeographicAreasIdsArray[] =  $geographicArea->getId();
        }
        $loggedUserId = $this->getUser()->getId();
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_TELEPRO",$loggedUserRolesArray) || in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $payload = $this->getDoctrine()->getRepository(Client::class)->fetchAssignedClientsbyFiltersAllContacts($teleproGeographicAreasIdsArray, $criterias, $loggedUserId);
        } else {
            $payload = $this->getDoctrine()->getRepository(Client::class)->fetchClientsbyFiltersAllContacts($criterias);
        }

        /*$payload = $this->getDoctrine()->getRepository(Client::class)->fetchClientsbyFiltersAllContacts($criterias);*/
        if(count($payload) === 0) {
            $session->set('total_contacts_search_results',
                'nothing'
            );
        } else {
            $session->set('total_contacts_search_results',
                count($payload)
            );
        }
        /*dd($payload);*/


        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }

        /* $clients = $paginator->paginate(
             $payload,
             $request->query->getInt('page', 1),
             10
         );*/

        return $this->render('all_contacts/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
        ]);
    }

    /**
     * @Route("/dashboard/commercial/appointments/search/filters", name="appointments_search_filters")
     */
    public function appointmentsSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        //search without ajax
        $session = $request->getSession();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $commercialUsers = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        if($request->isMethod('POST')) {
            /*dd($request->request->all());*/
            $session->set('criterias',
                $request->request->all()
            );
        }
        $criterias = $session->get('criterias');
        $loggedUserId = $this->getUser()->getId();
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAppointmentsbyFiltersForLoggedCommercial($criterias, $loggedUserId);
        } else {
            $payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAppointmentsbyFilters($criterias);
        }
        /*$payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAppointmentsbyFilters($criterias);*/
        if(count($payload) === 0) {
            $session->set('total_appointments_search_results',
                'nothing'
            );
        } else {
            $session->set('total_appointments_search_results',
                count($payload)
            );
        }
        /*dd($payload);*/


        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsOfLoggedUser($loggedUserId);
        } else {
            $data = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        }
        if($session->get('pagination_value')) {
            $appointments = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $appointments = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }
        /*dd($payload);*/

        /* $clients = $paginator->paginate(
             $payload,
             $request->query->getInt('page', 1),
             10
         );*/

        return $this->render('commercial/index.html.twig', [
            'all_commercial_appointments' => $data,
            'commercial_appointments' => $appointments,
            'geographic_areas'=> $geographicAreas,
            'commercial_users' => $commercialUsers
        ]);
    }

    /**
     * @Route("/dashboard/project/search/filters", name="projects_search_filters")
     */
    public function projectsSearchFilters(Request $request, PaginatorInterface $paginator, EquipmentRepository $equipmentRepository, UserRepository $userRepository): Response
    {
        //search without ajax
        $session = $request->getSession();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $commercialUsers = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        if($request->isMethod('POST')) {
            $session->set('criterias',
                $request->request->all()
            );
        }
        $criterias = $session->get('criterias');
        $commercialUsers = $userRepository->findUsersByCommercialRole("ROLE_COMMERCIAL");
        $equipments = $equipmentRepository->findAll();
        /*if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAppointmentsbyFiltersForLoggedCommercial($criterias, $loggedUserId);
        } else {
            $payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAllProjectsbyFilters($criterias);
        }*/
        $payload = $this->getDoctrine()->getRepository(Project::class)->fetchAllProjectsByFilters($criterias);
        /*$payload = $this->getDoctrine()->getRepository(Appointment::class)->fetchAppointmentsbyFilters($criterias);*/
        if(count($payload) === 0) {
            $session->set('total_projects_search_results',
                'nothing'
            );
        } else {
            $session->set('total_projects_search_results',
                count($payload)
            );
        }
        /*dd($payload);*/


       /* if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsOfLoggedUser($loggedUserId);
        } else {
            $data = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        }*/
        $data = $this->getDoctrine()->getRepository(Project::class)->findAll();
        if($session->get('pagination_value')) {
            $projects = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $projects = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('project/index.html.twig', [
            'all_projects' => $data,
            'projects' => $projects,
            'equipments'=> $equipments,
            'commercial_users' => $commercialUsers
        ]);
    }


}
