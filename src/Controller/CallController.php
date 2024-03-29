<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use MercurySeries\FlashyBundle\FlashyNotifier;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CallController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

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

    /**
     * @Route("/dashboard/calls/update/call/{id}", name="update_call")
     */
    public function updateCall(Request $request, $id): Response
    {
        /*dd($request->request->all());
        dd($request->request->get('status'));*/

        $manager = $this->getDoctrine()->getManager();
        $callToUpdate = $this->getDoctrine()->getRepository(Call::class)->find($id);
        $clientId = $callToUpdate->getClient()->getId();
        $callToUpdate->setGeneralStatus($request->request->get('status'));
        $callToUpdate->setStatusDetails($request->request->get('status_details'));
        if($request->request->get('status') === "2" && $request->request->get('status_details') === "7") {
            /*$clientAppointments = $callToUpdate->getClient()->getAppointments();
            dd($clientAppointments[count($clientAppointments) - 1]);
            dd($request->request->all());
            $clientAppointments[count($clientAppointments) - 1]->setAppointmentCallNotes($request->request->get('notes_call'));*/
            $callToUpdate->setCallIfAppointmentNotes($request->request->get('notes_call'));
        } else {
            /*$test = $callToUpdate->getClient()->getAppointments();*/
            $callToUpdate->setCallNotes($request->request->get('notes_call'));
        }
        $callToUpdate->setUpdatedAt(new \DateTime());
        $manager->persist($callToUpdate);
        $manager->flush();
        /*dd(new \DateTime($request->request->get('start_appointment')));
        dd($request->request->all());
        dd($id);*/
        $this->flashy->success('Appel mis à jour avec succès !');
        return $this->redirectToRoute('full_update_contact', [
            "id" => $clientId
        ]);
    }

    /**
     * @Route("/dashboard/calls/delete/call/{id}", name="delete_call")
     */
    public function deleteCall(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $callToDelete = $this->getDoctrine()->getRepository(Call::class)->find($id);
        $clientId = $callToDelete->getClient()->getId();
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $callToDelete->setIsDeleted(true);
        $callToDelete->setDeletionDate(new \DateTime());
        $callToDelete->setWhoDeletedIt($loggedUser);
        $manager->persist($callToDelete);
        $manager->flush();

        $allClientNotDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotDeletedCallsByClient($clientId);
        $allClientNotDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getNotDeletedAppointmentsByClient($clientId);
        $allItems = array_merge($allClientNotDeletedCalls,$allClientNotDeletedAppointments);
        function compare($a, $b)
        {
            if ($a->getCreatedAt() < $b->getCreatedAt())
                return 1;
            else if ($a->getCreatedAt() > $b->getCreatedAt())
                return -1;
            else
                return 0;
        }
        usort($allItems, "App\Controller\compare");
        if ($allItems) {
            if ($allItems[0] instanceof Call) {
                $client->setStatus($allItems[0]->getGeneralStatus());
                $client->setStatusDetail($allItems[0]->getStatusDetails());
            } elseif ($allItems[0] instanceof Appointment) {
                $client->setStatus(2);
                $client->setStatusDetail(7);
            }
        }
        if ((count($allClientNotDeletedCalls) === 0) && (count($allClientNotDeletedAppointments) === 0)) {
            $client->setStatus(0);
            $client->setStatusDetail(0);
            $client->setIsProcessed(false);
        }
        $manager->persist($client);
        $manager->flush();
        $this->flashy->success('Appel supprimé avec succès !');
        return $this->redirectToRoute('full_update_contact', [
            "id" => $clientId
        ]);
    }

    /**
     * @Route("/dashboard/calls/restore/call/{id}", name="restore_call")
     */
    public function restoreCall(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $callToRestore = $this->getDoctrine()->getRepository(Call::class)->find($id);
        $clientId = $callToRestore->getClient()->getId();
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $callToRestore->setIsDeleted(false);
        $callToRestore->setDeletionDate(null);
        $manager->persist($callToRestore);
        $manager->flush();

        $allClientNotDeletedCalls = $this->getDoctrine()->getRepository(Call::class)->getNotDeletedCallsByClient($clientId);
        $allClientNotDeletedAppointments = $this->getDoctrine()->getRepository(Appointment::class)->getNotDeletedAppointmentsByClient($clientId);
        $allItems = array_merge($allClientNotDeletedCalls,$allClientNotDeletedAppointments);
        function compare($a, $b)
        {
            if ($a->getCreatedAt() < $b->getCreatedAt())
                return 1;
            else if ($a->getCreatedAt() > $b->getCreatedAt())
                return -1;
            else
                return 0;
        }
        usort($allItems, "App\Controller\compare");
        if ($allItems) {
            if ($allItems[0] instanceof Call) {
                $client->setStatus($allItems[0]->getGeneralStatus());
                $client->setStatusDetail($allItems[0]->getStatusDetails());
                $client->setIsProcessed(true);
            } elseif ($allItems[0] instanceof Appointment) {
                $client->setStatus(2);
                $client->setStatusDetail(7);
                $client->setIsProcessed(true);
            }
        }
        /*if ((count($allClientNotDeletedCalls) === 0) && (count($allClientNotDeletedAppointments) === 0)) {
            $client->setStatus(0);
            $client->setStatusDetail(0);
        }*/
        $manager->persist($client);
        $manager->flush();

        $this->flashy->success("Appel restauré avec succès !");
        return $this->redirectToRoute('trash_calls');
    }
}
