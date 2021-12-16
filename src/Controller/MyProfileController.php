<?php

namespace App\Controller;

use App\Repository\UserRepository;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyProfileController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

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
            $this->flashy->success("Profil mis à jour avec succès !");
        }
        return $this->redirectToRoute('my_profile');
    }

    /**
     * @Route("/dashboard/myprofile/{id}/pictureupload", name="my_profile_pic_upload")
     */
    public function myProfilePictureUpload(Request $request, $id, UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $userRepository->find($id);
        if($request->isMethod('Post')) {
            $profilePicturesDirectory = $this->getParameter('profile_pictures_directory');
            $profilePicture = $request->files->get('profilePicture');
            if($profilePicture) {
                if (($profilePicture->guessExtension() === "jpg") || ($profilePicture->guessExtension() === "png")) {
                    $profilePictureFilename = md5(uniqid()) . '.' . $profilePicture->guessExtension();
                    $profilePicture->move(
                        $profilePicturesDirectory,
                        $profilePictureFilename
                    );
                    $loggedUser->setProfilePicture($profilePictureFilename);
                    $em->flush();
                    $this->flashy->success("Photo de profil mise à jour avec succès !");
                } else {
                    $this->flashy->warning("Désolé ! L'extension du fichier est invalide, veuillez choisir un fichier 'jpg' ou bien 'png' !");
                }
            }
        }
        return $this->redirectToRoute('my_profile');
    }
}
