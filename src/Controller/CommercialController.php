<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommercialController extends AbstractController
{
    /**
     * @Route("/dashboard/commercial", name="commercial")
     */
    public function index(AppointmentRepository $appointment): Response
    {
        $commercialAppointments = $appointment->findAll();
        return $this->render('commercial/index.html.twig', [
            'commercial_appointments' => $commercialAppointments,
        ]);
    }
}
