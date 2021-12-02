<?php

namespace App\Controller;

use App\Entity\ClientCategory;
use App\Entity\Equipment;
use App\Repository\ClientCategoryRepository;
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

    /**
     * @Route("/dashboard/settings/clientscategories/list", name="clients_categories_list")
     */
    public function clientsCategoriesList(Request $request, ClientCategoryRepository $clientCategoryRepository, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $clientCategoryRepository->getSortedByDateCategories();
        if($session->get('pagination_value')) {
            $categories = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $categories = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }
        return $this->render('settings/clients_categories_list.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/dashboard/settings/clientscategories/add", name="new_category")
     */
    public function addCategory(Request $request): Response
    {
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            $newCategory = new ClientCategory();
            $newCategoryDesignation = $request->request->get('new_designation');
            $newCategory->setDesignation($newCategoryDesignation);
            $newCategory->setCreatedAt(new \DateTime());
            $newCategory->setUpdatedAt(new \DateTime());
            $manager->persist($newCategory);
            $manager->flush();
            $this->flashy->success("Catégorie créée avec succès !");
        }
        return $this->redirectToRoute('clients_categories_list');
    }

    /**
     * @Route("/dashboard/settings/clientscategories/edit/{id}", name="edit_category")
     */
    public function editCategory(Request $request, $id, ClientCategoryRepository $clientCategoryRepository): Response
    {
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            $categoryToEdit = $clientCategoryRepository->find($id);
            $newCategoryDesignation = $request->request->get('designation');
            $categoryToEdit->setDesignation($newCategoryDesignation);
            $categoryToEdit->setUpdatedAt(new \DateTime());
            $manager->persist($categoryToEdit);
            $manager->flush();
            $this->flashy->success("Catégorie mise à jour avec succès !");
            return $this->redirectToRoute('clients_categories_list');
        }
    }

    /**
     * @Route("/dashboard/settings/clientscategories/delete/{id}", name="delete_category")
     */
    public function deleteCategory(Request $request, $id, ClientCategoryRepository $clientCategoryRepository): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $categoryToDelete = $clientCategoryRepository->find($id);
        try {
            $manager->remove($categoryToDelete);
            $manager->flush();
            $this->flashy->success("Catégorie supprimée avec succès !");
        } catch (DBAL\Exception $e) {
            $this->flashy->warning("Suppression impossible, Catégorie déja affectée à un Client !");
        }
        return $this->redirectToRoute('clients_categories_list');
    }
}
