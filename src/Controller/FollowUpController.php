<?php

namespace App\Controller;

use App\Entity\FollowUpCall;
use App\Repository\ClientRepository;
use App\Repository\EquipmentRepository;
use App\Repository\FollowUpCallRepository;
use App\Repository\PaymentScheduleRepository;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FollowUpController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }
    /**
     * @Route("/dashboard/followup", name="follow_up_clients_list")
     */
    public function followUpClientsList(Request $request, FollowUpCallRepository $followUpCallRepository, EquipmentRepository $equipmentRepository): Response
    {
        $session = $request->getSession();
        $followUpCalls = $followUpCallRepository->findAll();
        $equipments = $equipmentRepository->findAll();
        $session->set('total_follow_up_calls',
            count($followUpCalls)
        );
        return $this->render('follow_up/index.html.twig', [
            'follow_up_calls' => $followUpCalls,
            'equipments' => $equipments,
        ]);
    }

    /**
     * @Route("/dashboard/followup/addcall", name="follow_up_add_call")
     */
    public function followUpAddCall(Request $request, ClientRepository $clientRepository, EquipmentRepository $equipmentRepository): Response
    {
        if($request->isMethod('Post')) {
            $em = $this->getDoctrine()->getManager();
            $client = $clientRepository->find($request->request->get('client'));
            $equipment = $equipmentRepository->find($request->request->get('equipment'));
            $followUpCall = new FollowUpCall();
            $followUpCall->setClient($client);
            $followUpCall->setAssociatedEquipment($equipment);
            $followUpCall->setType((int)($request->request->get('type')));
            $followUpCall->setNotes($request->request->get('notes'));
            $followUpCall->setCreatedAt(new \DateTime());
            $followUpCall->setUpdatedAt(new \DateTime());
            $followUpCall->setIsDeleted(false);
            $em->persist($followUpCall);
            $em->flush();
            $this->flashy->success("Traitement effectué avec succès !");
        }
        return $this->redirectToRoute('follow_up_clients_list');
    }

    /**
     * @Route("/dashboard/followup/editcall/{callId}", name="follow_up_edit_call")
     */
    public function followUpEditCall(Request $request, $callId,FollowUpCallRepository $followUpCallRepository, ClientRepository $clientRepository, EquipmentRepository $equipmentRepository): Response
    {
        if($request->isMethod('Post')) {
            $em = $this->getDoctrine()->getManager();
            $followUpCallToUpdate = $followUpCallRepository->find($callId);
            $newEquipment = $equipmentRepository->find($request->request->get('equipment_edit'));
            $followUpCallToUpdate->setAssociatedEquipment($newEquipment);
            $followUpCallToUpdate->setType((int)($request->request->get('type_edit')));
            $followUpCallToUpdate->setNotes($request->request->get('notes_edit'));
            $followUpCallToUpdate->setUpdatedAt(new \DateTime());
            $em->persist($followUpCallToUpdate);
            $em->flush();
            $this->flashy->success("Traitement mis à jour avec succès !");
        }
        return $this->redirectToRoute('follow_up_clients_list');
    }

    /**
     * @Route("/dashboard/followup/deletecall/{callId}", name="follow_up_delete_call")
     */
    public function followUpDeleteCall(Request $request, $callId,FollowUpCallRepository $followUpCallRepository, ClientRepository $clientRepository, EquipmentRepository $equipmentRepository): Response
    {
            $em = $this->getDoctrine()->getManager();
            $followUpCallToDelete = $followUpCallRepository->find($callId);
            $em->remove($followUpCallToDelete);
            $em->flush();
            $this->flashy->success("Traitement supprimé avec succès !");
        return $this->redirectToRoute('follow_up_clients_list');
    }

}
