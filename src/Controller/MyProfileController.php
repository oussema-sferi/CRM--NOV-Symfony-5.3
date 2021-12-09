<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyProfileController extends AbstractController
{
    /**
     * @Route("/dashboard/myprofile", name="my_profile")
     */
    public function index(Request $request): Response
    {
        return $this->render('my_profile/index.html.twig');
    }

    /**
     * @Route("/dashboard/myprofile/{id}/edit", name="edit_my_profile")
     */
    public function editMyProfile(Request $request, $id, UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $userRepository->find($id);
        if($request->isMethod('Post')) {
            $loggedUser->setFirstName($request->request->get("firstName"));
            $loggedUser->setLastName($request->request->get("lastName"));
            $em->persist($loggedUser);
            $em->flush();
        }
        return $this->redirectToRoute('my_profile');
    }
}
