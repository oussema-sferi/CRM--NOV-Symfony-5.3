<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Project;
use App\Form\ProjectFormType;
use App\Repository\EquipmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    /**
     * @Route("/dashboard/project", name="project")
     */
    public function index(): Response
    {
        return $this->render('project/index.html.twig', [
            'controller_name' => 'ProjectController',
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
