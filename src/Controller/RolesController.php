<?php

namespace App\Controller;

use App\Entity\GeographicArea;
use App\Entity\User;
use App\Form\UserFormType;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RolesController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/users", name="roles")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(User::class)->findAll();
        if($session->get('pagination_value')) {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }


        /*$users = $this->getDoctrine()->getRepository(User::class)->findAll();*/
        /*dd($users);*/
        return $this->render('roles/index.html.twig', [
            'allusers' => $data,
            'users' => $users
        ]);
    }

    /**
     * @Route("/dashboard/users/show/{id}", name="show_user")
     */
    public function show(Request $request, $id): Response
    {
        $userToShow = $this->getDoctrine()->getRepository(User::class)->find($id);
        /*dd($userToShow);*/
        return $this->render('/roles/show.html.twig', [
            'user_to_show' => $userToShow
        ]);
    }

    /**
     * @Route("/dashboard/users/update/{id}", name="update_user")
     */
    public function update(Request $request, $id): Response
    {
        $newUser = new User();
        $userForm = $this->createForm(UserFormType::class, $newUser);
        $userForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $userToUpdate = $this->getDoctrine()->getRepository(User::class)->find($id);
        /*dd($userToUpdate->getRoles());*/
        $newArrayRoles = $newUser->getRoles();
        if($userForm->isSubmitted()) {
            array_unshift($newArrayRoles,$request->request->get("role"));
            $userToUpdate->setFirstName($newUser->getFirstName());
            $userToUpdate->setLastName($newUser->getLastName());
            $userToUpdate->setRoles($newArrayRoles);
            $manager->persist($userToUpdate);
            $manager->flush();
            $this->flashy->success("Utilisateur mis à jour avec succès !");
            return $this->redirectToRoute('roles');
        }
        return $this->render('/roles/update.html.twig', [
            'user_form' => $userForm->createView(),
            'user_to_update' => $userToUpdate
        ]);
    }

    /**
     * @Route("/dashboard/users/commercials", name="commercials_listing")
     */
    public function commercialsListing(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        if($session->get('pagination_value')) {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        /*dd($users);*/
        return $this->render('roles/users_management/commercials/index.html.twig', [
            'allusers' => $data,
            'users' => $users,
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro", name="telepro_listing")
     */
    public function teleproListing(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
        if($session->get('pagination_value')) {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $users = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('roles/users_management/telepro/index.html.twig', [
            'allusers' => $data,
            'users' => $users,
        ]);
    }

    /**
     * @Route("/dashboard/users/commercials/{id}/departments", name="commercials_departments")
     */
    public function commercialsDepartments($id): Response
    {
        $commercial = $this->getDoctrine()->getRepository(User::class)->find($id);
        $departments = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        /*dd($users);*/
        return $this->render('roles/users_management/commercials/departments_assignment.html.twig', [
            'commercial' => $commercial,
            'departments' => $departments
        ]);
    }

    /**
     * @Route("/dashboard/users/commercials/departments/assignment", name="commercials_departments_assignment")
     */
    public function commercialsDepartmentsAssignment(Request $request): Response
    {
        $departments = [];
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {

            $commercialId = $request->request->get('commercialid');
            $myString = $request->request->get('departments');
            $departmentsArray = explode(',', $myString);
            /*dd($departmentsArray);*/
            $commercial = $this->getDoctrine()->getRepository(User::class)->find($commercialId);
            $existingDepartments = $commercial->getGeographicAreas();
            if($existingDepartments) {
                foreach ($existingDepartments as $existingDepartment) {
                    $commercial->removeGeographicArea($existingDepartment);
                }
            }
            if($departmentsArray) {
                foreach ($departmentsArray as $department) {
                    if($department) {
                        $commercial->addGeographicArea($this->getDoctrine()->getRepository(GeographicArea::class)->find($department));
                    }

                }
            }

            $manager->persist($commercial);
            $manager->flush();

            $this->flashy->success('Modifications enregistrées avec succès !');

            /*$this->addFlash(
                'notice_departments',
                "Modifications enregistrées avec succès!"
            );*/
        }
        return $this->redirectToRoute('commercials_departments', [
            "id" => $commercialId
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro/{id}/departments", name="telepro_departments")
     */
    public function teleproDepartments($id): Response
    {
        $telepro = $this->getDoctrine()->getRepository(User::class)->find($id);
        $departments = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        /*dd($telepro->getGeographicAreas()[2]);*/
        return $this->render('roles/users_management/telepro/departments_assignment.html.twig', [
            'telepro' => $telepro,
            'departments' => $departments
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro/departments/assignment", name="telepro_departments_assignment")
     */
    public function teleproDepartmentsAssignment(Request $request): Response
    {
        $departments = [];
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {

            $teleproId = $request->request->get('teleproid');
            $myString = $request->request->get('departments');
            $departmentsArray = explode(',', $myString);
            $telepro = $this->getDoctrine()->getRepository(User::class)->find($teleproId);
            $existingDepartments = $telepro->getGeographicAreas();
            if($existingDepartments) {
                foreach ($existingDepartments as $existingDepartment) {
                    $telepro->removeGeographicArea($existingDepartment);
                }
            }
            if($departmentsArray) {
                foreach ($departmentsArray as $department) {
                    if($department) {
                        $geographicArea = $this->getDoctrine()->getRepository(GeographicArea::class)->find($department);
                        $telepro->addGeographicArea($this->getDoctrine()->getRepository(GeographicArea::class)->find($department));
                        if($geographicArea->getUsers()) {
                            foreach ($geographicArea->getUsers() as $user) {
                                if($user !== $telepro) {
                                    $commercial = $this->getDoctrine()->getRepository(User::class)->find($user->getId());
                                    $commercial->setTeleprospector($telepro);
                                }

                            }

                        }
                    }

                }
            }
            $manager->persist($telepro);
            $manager->flush();
            $this->flashy->success('Modifications enregistrées avec succès !');
            /*$this->addFlash(
                'notice_departments',
                "Modifications enregistrées avec succès!"
            );*/

        }
        return $this->redirectToRoute('telepro_departments', [
            "id" => $teleproId
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro/{id}/commercials", name="telepro_commercials")
     */
    public function teleproCommercials($id): Response
    {
        $telepro = $this->getDoctrine()->getRepository(User::class)->find($id);
        $allCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
        $assignedCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($id, "ROLE_COMMERCIAL");
        /*dd($users);*/
        return $this->render('roles/users_management/telepro/commercials_assignment.html.twig', [
            'telepro' => $telepro,
            'assigned_commercials' => $assignedCommercials,
            'all_commercials' => $allCommercials
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro/commercials/assignment", name="telepro_commercials_assignment")
     */
    public function teleproCommercialsAssignment(Request $request): Response
    {
        $commercials = [];
        $manager = $this->getDoctrine()->getManager();
        if($request->isMethod('Post')) {

            $teleproId = $request->request->get('teleproid');
            $myString = $request->request->get('commercials');
            $commercialsArray = explode(',', $myString);
            $telepro = $this->getDoctrine()->getRepository(User::class)->find($teleproId);
            $existingCommercials = $telepro->getCommercials();

            if($existingCommercials) {
                foreach ($existingCommercials as $existingCommercial) {
                    $existingCommercial->setTeleprospector(null);
                }
            }
            /*dd($existingCommercials);*/
            if($commercialsArray) {
                foreach ($commercialsArray as $commercial) {
                    if($commercial) {
                        $commercialToAssign = $this->getDoctrine()->getRepository(User::class)->find($commercial);
                        $commercialToAssign->setTeleprospector($telepro);
                    }

                }
            }
            $manager->persist($telepro);
            $manager->flush();
            $this->flashy->success('Modifications enregistrées avec succès !');
            /*$this->addFlash(
                'notice_commercials_assignment',
                "Modifications enregistrées avec succès!"
            );*/
        }
        return $this->redirectToRoute('telepro_commercials', [
            "id" => $teleproId
        ]);
    }

}
