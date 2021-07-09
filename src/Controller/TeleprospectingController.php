<?php

namespace App\Controller;

use App\Entity\Call;
use App\Entity\Client;
use App\Entity\User;
use App\Form\CallFormType;
use App\Form\ClientFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeleprospectingController extends AbstractController
{
    /**
     * @Route("/dashboard/teleprospecting", name="teleprospecting")
     */
    public function index(): Response
    {
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();
        /*dd($clients[0]->getCalls());*/
        return $this->render('teleprospecting/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/add", name="new_client")
     */
    public function add(Request $request): Response
    {
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        if($clientForm->isSubmitted()) {
            $manager->persist($newClient);
            $manager->flush();
            return $this->redirectToRoute('teleprospecting');
        }
        return $this->render('/teleprospecting/add.html.twig', [
            'client_form' => $clientForm->createView()
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/update/{id}", name="update_client")
     */
    public function update(Request $request, $id): Response
    {
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($id);
        if($clientForm->isSubmitted()) {
            $clientToUpdate->setFirstName($newClient->getFirstName());
            $clientToUpdate->setLastName($newClient->getLastName());
            $clientToUpdate->setCompanyName($newClient->getCompanyName());
            $clientToUpdate->setEmail($newClient->getEmail());
            $clientToUpdate->setAddress($newClient->getAddress());
            $clientToUpdate->setPostalCode($newClient->getPostalCode());
            $clientToUpdate->setCountry($newClient->getCountry());
            $clientToUpdate->setPhoneNumber($newClient->getPhoneNumber());
            $clientToUpdate->setMobileNumber($newClient->getMobileNumber());
            $clientToUpdate->setCategory($newClient->getCategory());
            $clientToUpdate->setIsUnderContract($newClient->getIsUnderContract());
            $manager->persist($clientToUpdate);
            $manager->flush();
            return $this->redirectToRoute('teleprospecting');
        }
        return $this->render('/teleprospecting/update.html.twig', [
            'client_form' => $clientForm->createView(),
            'client_to_update' => $clientToUpdate
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/show/{id}", name="show_client")
     */
    public function show(Request $request, $id): Response
    {
        $clientToShow = $this->getDoctrine()->getRepository(Client::class)->find($id);
        return $this->render('/teleprospecting/show.html.twig', [
            'client_to_show' => $clientToShow
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/call/{id}", name="call_handle")
     */
    public function callHandle(Request $request, $id): Response
    {
        $loggedUser = $this->getUser();
        /*$loggedUserId = $this->getUser()->getUserIdentifier();
        $usr = $this->getDoctrine()->getRepository(User::class)->findBy(['email' => $loggedUserId]);*/
        /*dd($usr);*/
        $newCall = new Call();
        $callForm = $this->createForm(CallFormType::class, $newCall);
        $callForm->handleRequest($request);
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $manager = $this->getDoctrine()->getManager();

        if($callForm->isSubmitted()) {
            $newCall->setCreatedAt(new  \DateTime());
            $newCall->setUser($loggedUser);
            $newCall->setClient($client);
            $client->setStatus(1);
            $manager->persist($newCall);
            $manager->flush();
            return $this->redirectToRoute('teleprospecting');
        }
        return $this->render('/teleprospecting/callHandle.html.twig', [
            'call_form' => $callForm->createView(),
            'client' => $client
        ]);
    }

    /**
     * @Route("/dashboard/teleprospecting/commercialslist", name="commercials_list")
     */
    public function showCommercialsList(): Response
    {
        $loggedUserId = $this->getUser()->getId();

        /*$commercial_agents = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");*/
        $commercial_agents = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId);
        /*dd($commercial_agents);*/
        return $this->render('teleprospecting/commercials_list_show.html.twig', [
            'commercial_agents' => $commercial_agents,
        ]);
    }

}
