<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentController extends AbstractController
{
    /**
     * @Route("/dashboard/appointments", name="appointment")
     */
    public function index(): Response
    {
        $loggedUserId = $this->getUser()->getId();

        /*$commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");*/
        $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId);
        /*dd($commercial_agents);*/
        return $this->render('appointment/index.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

    /**
     * @Route("/dashboard/appointments/showcalendar/{id}", name="show_calendar")
     */
    public function show(Request $request, $id): Response
    {
        $calendarToShow = $this->getDoctrine()->getRepository(User::class)->find($id);
        return $this->render('/appointment/show_calendar.html.twig', [
            'calendar_to_show' => $calendarToShow
        ]);
    }

}
