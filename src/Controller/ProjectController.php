<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectFormType;
use App\Repository\EquipmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    /**
     * @Route("/dashboard/project/list", name="projects_list")
     */
    public function index(Request $request, ProjectRepository $projectRepository, UserRepository $userRepository, EquipmentRepository $equipmentRepository, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $loggedUserId = $this->getUser()->getId();
        $commercialUsers = $userRepository->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");
        $equipments = $equipmentRepository->findAll();
        $allProjects = $projectRepository->findAll();
       /* dd($allProjects);*/
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $projectRepository->getProjectsOfLoggedUser($loggedUserId);
        } else {
            $data = $projectRepository->findAll();
        }
        $session->remove('total_projects_search_results');
        if($session->get('pagination_value')) {
            $projects = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $projects = $paginator->paginate(
                $data,
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

    /**
     * @Route("/dashboard/project/{clientId}/add", name="new_project")
     */
    public function addProject($clientId,Request $request, EquipmentRepository $equipmentRepository): Response
    {
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $equipmentsList = $equipmentRepository->findAll();
        return $this->render('project/add.html.twig', [
            'client' => $client,
            'equipments_list' => $equipmentsList,
        ]);
    }
}
