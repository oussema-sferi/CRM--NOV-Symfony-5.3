<?php

namespace App\Controller;

use App\Entity\Call;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CallController extends AbstractController
{
    /**
     * @Route("/dashboard/calls", name="call")
     */
    public function index(): Response
    {
        $calls = $this->getDoctrine()->getRepository(Call::class)->findAll();
        /*dd($calls);*/
        return $this->render('call/index.html.twig', [
            'calls' => $calls
        ]);
    }
}
