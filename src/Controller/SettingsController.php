<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL;

class SettingsController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/settings/equipments/list", name="equipments_list")
     */
    public function equipmentsList(Request $request, EquipmentRepository $equipmentRepository, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $equipmentRepository->getSortedByDateEquipments();
        if($session->get('pagination_value')) {
            $equipments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $equipments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }
        return $this->render('settings/equipments_list.html.twig', [
            'equipments' => $equipments,
        ]);
    }

    /**
     * @Route("/dashboard/settings/equipments/add", name="new_equipment")
     */
    public function addEquipment(Request $request): Response
    {
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            $newEquipment = new Equipment();
            $newEquipmentDesignation = $request->request->get('new_designation');
            $newEquipment->setDesignation($newEquipmentDesignation);
            $newEquipment->setCreatedAt(new \DateTime());
            $newEquipment->setUpdatedAt(new \DateTime());
            $manager->persist($newEquipment);
            $manager->flush();
            $this->flashy->success("Equipement créé avec succès !");
            return $this->redirectToRoute('equipments_list');
        }
        return $this->redirectToRoute('equipments_list');
    }

    /**
     * @Route("/dashboard/settings/equipments/edit/{id}", name="edit_equipment")
     */
    public function editEquipment(Request $request, $id, EquipmentRepository $equipmentRepository): Response
    {
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            $equipmentToEdit = $equipmentRepository->find($id);
            $newEquipmentDesignation = $request->request->get('designation');
            $equipmentToEdit->setDesignation($newEquipmentDesignation);
            $equipmentToEdit->setUpdatedAt(new \DateTime());
            $manager->persist($equipmentToEdit);
            $manager->flush();
            $this->flashy->success("Equipement mis à jour avec succès !");
            return $this->redirectToRoute('equipments_list');
        }
    }

    /**
     * @Route("/dashboard/settings/equipments/delete/{id}", name="delete_equipment")
     */
    public function deleteEquipment(Request $request, $id, EquipmentRepository $equipmentRepository): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $equipmentToDelete = $equipmentRepository->find($id);
        try {
            $manager->remove($equipmentToDelete);
            $manager->flush();
            $this->flashy->success("Equipement supprimé avec succès !");
        } catch (DBAL\Exception $e) {
            $this->flashy->warning("Suppression impossible, Equipement déja affecté à un Client/Projet !");
        }
        return $this->redirectToRoute('equipments_list');
    }
}
