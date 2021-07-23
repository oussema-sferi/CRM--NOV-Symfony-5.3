<?php

namespace App\Controller;

use App\Entity\GeographicArea;
use App\Entity\User;
use App\Form\UserFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RolesController extends AbstractController
{
    /**
     * @Route("/dashboard/users", name="roles")
     */
    public function index(): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        /*dd($users);*/
        return $this->render('roles/index.html.twig', [
            'users' => $users,
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
        if($userForm->isSubmitted()) {
            /*dd($newUser->getRoles());*/
            $userToUpdate->setFirstName($newUser->getFirstName());
            $userToUpdate->setLastName($newUser->getLastName());
            $userToUpdate->setRoles($newUser->getRoles());
            $manager->persist($userToUpdate);
            $manager->flush();
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
    public function commercialsListing(): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");

        /*dd($users);*/
        return $this->render('roles/users_management/commercials/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/dashboard/users/telepro", name="telepro_listing")
     */
    public function teleproListing(): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_TELEPRO");
        /*dd($users);*/
        return $this->render('roles/users_management/telepro/index.html.twig', [
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

            $this->addFlash(
                'notice_departments',
                "Modifications enregistrées avec succès!"
            );
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
        /*dd($users);*/
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
            $this->addFlash(
                'notice_departments',
                "Modifications enregistrées avec succès!"
            );

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
            $this->addFlash(
                'notice_commercials_assignment',
                "Modifications enregistrées avec succès!"
            );
        }
        return $this->redirectToRoute('telepro_commercials', [
            "id" => $teleproId
        ]);
    }

}
