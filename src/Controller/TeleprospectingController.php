<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\GeographicArea;
use App\Entity\User;
use App\Form\CallFormType;
use App\Form\ClientFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class TeleprospectingController extends AbstractController
{
    /**
     * @Route("/dashboard/teleprospecting", name="teleprospecting")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {

        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $session->set('total_telepro',
            count($data)
        );
        $session->remove('total_telepro_search_results');
        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        /*dd($clients[0]->getCalls());*/
        /*dd($clients);*/
        return $this->render('teleprospecting/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
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

    /**
     * @Route("/dashboard/teleprospecting/pagination", name="teleprospecting_pagination")
     */
    public function teleprospectingPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
                /*$serializer = new Serializer([new ObjectNormalizer()]);
                $result = $serializer->normalize($products,'json',['attributes' => ['id','name','price', 'quantityInStock']]);*/
                return new JsonResponse(['message'=> 'Task Success!']);
            } else {
                return new JsonResponse(['message'=> 'Task Fails!']);
            }
        return new Response('use Ajax');
    }

    /**
     * @Route("/dashboard/teleprospecting/stats", name="teleprospecting_stats")
     */
    public function teleprospectingStats(): Response
    {
        $allTelepros = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
        $allClients = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $processedClients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 1]);
        $notProcessedClients = $this->getDoctrine()->getRepository(Client::class)->findBy(["status" => 0]);
        $allAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsWhereClientsExist();
        /*dd($allProcessedClients);*/
        return $this->render('teleprospecting/telepro_stats.html.twig', [
            'total_telepros' => count($allTelepros),
            'total_clients' => count($allClients),
            'processed_clients' => count($processedClients),
            'not_processed_clients' => count($notProcessedClients),
            'total_appointments' => count($allAppointments)
        ]);
    }

}
