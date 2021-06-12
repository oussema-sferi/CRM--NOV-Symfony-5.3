<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommercialController extends AbstractController
{
    /**
     * @Route("/dashboard/commercial", name="commercial")
     */
    public function index(): Response
    {
        return $this->render('commercial/index.html.twig', [
            'controller_name' => 'CommercialController',
        ]);
    }
}
