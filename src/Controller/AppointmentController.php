<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentController extends AbstractController
{
    /**
     * @Route("/dashboard/appointments", name="appointment")
     */
    public function index(): Response
    {
        $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        return $this->render('appointment/index.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }
}
