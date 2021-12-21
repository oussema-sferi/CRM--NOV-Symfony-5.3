<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectFormType;
use App\Repository\EquipmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }
    /**
     * @Route("/dashboard/project/list", name="projects_list")
     */
    public function index(Request $request, ProjectRepository $projectRepository, UserRepository $userRepository, EquipmentRepository $equipmentRepository, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $loggedUserId = $this->getUser()->getId();
        $commercialAndSuperadminUsers = $userRepository->findUsersTeleproStats("ROLE_COMMERCIAL", "ROLE_SUPERADMIN");
        $equipments = $equipmentRepository->findAll();
        /*$allProjects = $projectRepository->findAll();*/
       /* dd($allProjects);*/
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            $data = $projectRepository->getProjectsOfLoggedUser($loggedUserId);
        } else {
            $data = $projectRepository->findAll();
        }
        $session->remove('total_projects_search_results');
        if($session->get('pagination_value')) {
            $projects = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $projects = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }
        return $this->render('project/index.html.twig', [
            'all_projects' => $data,
            'projects' => $projects,
            'equipments'=> $equipments,
            'commercial_users' => $commercialAndSuperadminUsers
        ]);
    }

    /**
     * @Route("/dashboard/project/{clientId}/add", name="new_project")
     */
    public function addProject($clientId,Request $request, EquipmentRepository $equipmentRepository): Response
    {
        $loggedUser = $this->getUser();
        $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);
        $equipmentsList = $equipmentRepository->findAll();
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            // Save Files
            $newProject = new Project();
            $attachmentsDirectory = $this->getParameter('attachments_directory');
            //get request files
            $cni = $request->files->get('cni');
            if($cni) {
                $cniFilename = md5(uniqid()) . '.' . $cni->guessExtension();
            }
            $rib = $request->files->get('rib');
            if($rib) {
                $ribFilename = md5(uniqid()) . '.' . $rib->guessExtension();
            }
            $declaration2035 = $request->files->get('declaration2035');
            if($declaration2035) {
                $declaration2035Filename = md5(uniqid()) . '.' . $declaration2035->guessExtension();
            }
            $declaration2042 = $request->files->get('declaration2042');
            if($declaration2042) {
                $declaration2042Filename = md5(uniqid()) . '.' . $declaration2042->guessExtension();
            }
            $bilanComptable = $request->files->get('bilanComptable');
            if($bilanComptable) {
                $bilanComptableFilename = md5(uniqid()) . '.' . $bilanComptable->guessExtension();
            }
            $partenariat = $request->files->get('partenariat');
            if($partenariat) {
                $partenariatFilename = md5(uniqid()) . '.' . $partenariat->guessExtension();
            }
           if($cni) {
               $cni->move(
                   $attachmentsDirectory,
                   $cniFilename
               );
               $newProject->setCni($cniFilename);
           }
            if($rib) {
                $rib->move(
                    $attachmentsDirectory,
                    $ribFilename
                );
                $newProject->setRib($ribFilename);
            }
            if($declaration2035) {
                $declaration2035->move(
                    $attachmentsDirectory,
                    $declaration2035Filename
                );
                $newProject->setDeclaration2035($declaration2035Filename);
            }
            if($declaration2042) {
                $declaration2042->move(
                    $attachmentsDirectory,
                    $declaration2042Filename
                );
                $newProject->setDeclaration2042($declaration2042Filename);
            }
            if($bilanComptable) {
                $bilanComptable->move(
                    $attachmentsDirectory,
                    $bilanComptableFilename
                );
                $newProject->setBilanComptable($bilanComptableFilename);
            }
            if($partenariat) {
                $partenariat->move(
                    $attachmentsDirectory,
                    $partenariatFilename
                );
                $newProject->setPartenariat($partenariatFilename);
            }

            /*foreach ($request->files as $key => $attachment) {
                if($attachment) {
                    $attachment->move(
                        $attachmentsDirectory,
                        md5(uniqid()) . '.' . $attachment->guessExtension()
                    );
                }

            }*/
            $equipment = $equipmentRepository->find((int)($request->request->get('equipment')));
            $newProject->setClient($client);
            $newProject->setProjectMakerUser($loggedUser);
            $newProject->setEquipment($equipment);
            if($request->request->get('monthlyPayment') === "10") {
                $newProject->setMonthlyPayment($request->request->get('monthlyPaymentCustomValue'));
            } else {
                $newProject->setMonthlyPayment($request->request->get('monthlyPayment'));
            }
            $newProject->setNumberOfMonthlyPayments($request->request->get('numberOfMonthlyPayments'));
            $newProject->setTotalHT($request->request->get('totalHT'));
            if($request->request->get('rachat') === "on") {
                $newProject->setRachat(true);
                if($request->request->get('reportMensualite') === "10") {
                    $newProject->setReportMensualite((int)($request->request->get('reportMensualiteCustomValue')));
                } else {
                    $newProject->setReportMensualite((int)($request->request->get('reportMensualite')));
                }
            } else {
                $newProject->setRachat(false);
            }


            $newProject->setProjectNotes($request->request->get('projectNotes'));
            $newProject->setStatus((int)($request->request->get('status')));
            $newProject->setShipmentStatus((int)($request->request->get('shipmentStatus')));
            $newProject->setShipmentStatusDate(new \DateTime($request->request->get('shipmentStatusDate')));
            $newProject->setShipmentNotes($request->request->get('shipmentNotes'));
            $newProject->setCreatedAt(new \DateTime());
            $newProject->setUpdatedAt(new \DateTime());
            $newProject->setIsDeleted(false);
            $manager->persist($newProject);
            $manager->flush();
            $this->flashy->success("Projet créé avec succès !");
            return $this->redirectToRoute('projects_list');
        }
        return $this->render('project/add.html.twig', [
            'client' => $client,
            'equipments_list' => $equipmentsList,
        ]);
    }


    /**
     * @Route("/dashboard/project/{projectId}/edit", name="edit_project")
     */
    public function editProject($projectId,Request $request, EquipmentRepository $equipmentRepository, ProjectRepository $projectRepository): Response
    {
        $loggedUser = $this->getUser();
        /*$referrer = $request->headers->get('referer');*/
        $projectToUpdate = $projectRepository->find($projectId);
        $client = $projectToUpdate->getClient();
        $equipmentsList = $equipmentRepository->findAll();
        if($request->isMethod('Post')) {
            $manager = $this->getDoctrine()->getManager();
            // Save Files
            $attachmentsDirectory = $this->getParameter('attachments_directory');
            //get request files
            $cni = $request->files->get('cni');
            if($cni) {
                $cniFilename = md5(uniqid()) . '.' . $cni->guessExtension();
            }
            $rib = $request->files->get('rib');
            if($rib) {
                $ribFilename = md5(uniqid()) . '.' . $rib->guessExtension();
            }
            $declaration2035 = $request->files->get('declaration2035');
            if($declaration2035) {
                $declaration2035Filename = md5(uniqid()) . '.' . $declaration2035->guessExtension();
            }
            $declaration2042 = $request->files->get('declaration2042');
            if($declaration2042) {
                $declaration2042Filename = md5(uniqid()) . '.' . $declaration2042->guessExtension();
            }
            $bilanComptable = $request->files->get('bilanComptable');
            if($bilanComptable) {
                $bilanComptableFilename = md5(uniqid()) . '.' . $bilanComptable->guessExtension();
            }
            $partenariat = $request->files->get('partenariat');
            if($partenariat) {
                $partenariatFilename = md5(uniqid()) . '.' . $partenariat->guessExtension();
            }
            if($cni) {
                $cni->move(
                    $attachmentsDirectory,
                    $cniFilename
                );
                $projectToUpdate->setCni($cniFilename);
            }
            if($rib) {
                $rib->move(
                    $attachmentsDirectory,
                    $ribFilename
                );
                $projectToUpdate->setRib($ribFilename);
            }
            if($declaration2035) {
                $declaration2035->move(
                    $attachmentsDirectory,
                    $declaration2035Filename
                );
                $projectToUpdate->setDeclaration2035($declaration2035Filename);
            }
            if($declaration2042) {
                $declaration2042->move(
                    $attachmentsDirectory,
                    $declaration2042Filename
                );
                $projectToUpdate->setDeclaration2042($declaration2042Filename);
            }
            if($bilanComptable) {
                $bilanComptable->move(
                    $attachmentsDirectory,
                    $bilanComptableFilename
                );
                $projectToUpdate->setBilanComptable($bilanComptableFilename);
            }
            if($partenariat) {
                $partenariat->move(
                    $attachmentsDirectory,
                    $partenariatFilename
                );
                $projectToUpdate->setPartenariat($partenariatFilename);
            }

            $equipment = $equipmentRepository->find((int)($request->request->get('equipment')));
            $projectToUpdate->setEquipment($equipment);
            if($request->request->get('monthlyPayment') === "10") {
                $projectToUpdate->setMonthlyPayment($request->request->get('monthlyPaymentCustomValue'));
            } else {
                $projectToUpdate->setMonthlyPayment($request->request->get('monthlyPayment'));
            }
            $projectToUpdate->setNumberOfMonthlyPayments($request->request->get('numberOfMonthlyPayments'));
            $projectToUpdate->setTotalHT($request->request->get('totalHT'));
            if($request->request->get('rachat') === "on") {
                $projectToUpdate->setRachat(true);
                if($request->request->get('reportMensualite') === "10") {
                    $projectToUpdate->setReportMensualite((int)($request->request->get('reportMensualiteCustomValue')));
                } else {
                    $projectToUpdate->setReportMensualite((int)($request->request->get('reportMensualite')));
                }
            } else {
                $projectToUpdate->setRachat(false);
                $projectToUpdate->setReportMensualite(null);
            }
            $projectToUpdate->setProjectNotes($request->request->get('projectNotes'));
            $projectToUpdate->setStatus((int)($request->request->get('status')));
            $projectToUpdate->setShipmentStatus((int)($request->request->get('shipmentStatus')));
            $projectToUpdate->setShipmentStatusDate(new \DateTime($request->request->get('shipmentStatusDate')));
            $projectToUpdate->setShipmentNotes($request->request->get('shipmentNotes'));
            $projectToUpdate->setUpdatedAt(new \DateTime());
            $manager->persist($projectToUpdate);
            $manager->flush();
            $this->flashy->success("Projet mis à jour avec succès !");
            return $this->redirect($request->request->get('referer'));
        }
        return $this->render('project/edit.html.twig', [
            'project_to_update' => $projectToUpdate,
            'client' => $client,
            'equipments_list' => $equipmentsList,
        ]);
    }

    /**
     * @Route("/dashboard/calls/delete/project/{id}", name="delete_project")
     */
    public function deleteCall(Request $request, $id, ProjectRepository $projectRepository): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $referrer = $request->headers->get('referer');
        $loggedUser = $this->getUser();
        $projectToDelete = $projectRepository->find($id);
        $projectToDelete->setIsDeleted(true);
        $projectToDelete->setDeletedAt(new \DateTime());
        $projectToDelete->setWhoDeletedIt($loggedUser);
        $manager->persist($projectToDelete);
        $manager->flush();
        $this->flashy->success('Projet supprimé avec succès !');
        return $this->redirect($referrer);
    }

    /**
     * @Route("/dashboard/appointments/restore/project/{id}", name="restore_project")
     */
    public function restoreCall(Request $request, $id, ProjectRepository $projectRepository): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $projectToRestore = $projectRepository->find($id);
        $projectToRestore->setIsDeleted(false);
        $projectToRestore->setDeletedAt(null);
        $projectToRestore->setWhoDeletedIt(null);
        $manager->persist($projectToRestore);
        $manager->flush();
        $this->flashy->success("Projet restauré avec succès !");
        return $this->redirectToRoute('trash_projects');
    }
}
