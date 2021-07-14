<?php

namespace App\Controller;

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

}
