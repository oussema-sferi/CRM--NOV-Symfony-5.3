<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BillingController extends AbstractController
{
    /**
     * @Route("/billing", name="billing")
     */
    public function index(): Response
    {
        return $this->render('billing/index.html.twig', [
            'controller_name' => 'BillingController',
        ]);
    }
}
