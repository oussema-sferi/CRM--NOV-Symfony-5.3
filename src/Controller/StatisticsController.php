<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    /**
     * @Route("/dashboard/allstats", name="all_statistics")
     */
    public function index(): Response
    {

        return $this->render('statistics/index.html.twig', [
            'controller_name' => 'StatisticsController',
        ]);
    }

}
