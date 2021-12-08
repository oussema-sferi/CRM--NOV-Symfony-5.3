<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\GeographicArea;
use App\Entity\Process;
use App\Entity\User;
use App\Form\AppointmentFormType;
use App\Form\CallFormType;
use App\Form\ClientFormType;
use App\Repository\ClientCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AllContactsController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/allcontacts", name="all_contacts")
     */
    public function index(Request $request, PaginatorInterface $paginator, ClientCategoryRepository $clientCategoryRepository): Response
    {
        $loggedUserRolesArray = $this->getUser()->getRoles();
        /*if (in_array("ROLE_TELEPRO",$loggedUserRolesArray)) {
            return $this->redirectToRoute('teleprospecting');
        } elseif (in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            return $this->redirectToRoute('commercial');
        } elseif (in_array("ROLE_ADMIN",$loggedUserRolesArray)) {
            return $this->redirectToRoute('show_my_calendar');
        }*/
        $session = $request->getSession();
        $loggedUser = $this->getUser();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $clientsCategories = $clientCategoryRepository->findAll();

        $loggedUserGeographicAreasArray = $loggedUser->getGeographicAreas();
        $loggedUserGeographicAreasIdsArray = [];
        foreach ($loggedUserGeographicAreasArray as $geographicArea) {
            $loggedUserGeographicAreasIdsArray[] =  $geographicArea->getId();
        }
        $loggedUserRolesArray = $this->getUser()->getRoles();
        if (in_array("ROLE_TELEPRO",$loggedUserRolesArray) || in_array("ROLE_COMMERCIAL",$loggedUserRolesArray)) {
            if(count($loggedUserGeographicAreasIdsArray) === 0) {
                $data = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
            } else {
                $data = $this->getDoctrine()->getRepository(Client::class)->findAllClientsByUserDepartments($loggedUserGeographicAreasIdsArray, $loggedUser->getId());
            }

        } else {
            $data = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();
        }

        /*$data = $this->getDoctrine()->getRepository(Client::class)->getNotDeletedClients();*/
        $session->set('total_contacts',
            count($data)
        );
        $session->remove('total_contacts_search_results');
        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        /*$clients = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            50
        );*/

        return $this->render('all_contacts/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas,
            'clients_categories' => $clientsCategories
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/add", name="new_contact")
     */
    public function add(Request $request): Response
    {
        $loggedUser = $this->getUser();
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        if($clientForm->isSubmitted()) {
            $newClient->setStatus(0);
            $newClient->setStatusDetail(0);
            $newClient->setCreatedAt(new \DateTime());
            $newClient->setUpdatedAt(new \DateTime());
            $newClient->setCreatorUser($loggedUser);
            $newClient->setIsDeleted(false);
            $newClient->setIsProcessed(false);
            $manager->persist($newClient);
            $manager->flush();
            $this->flashy->success("Contact créé avec succès !");
            return $this->redirectToRoute('all_contacts');
        }
        return $this->render('/all_contacts/add.html.twig', [
            'client_form' => $clientForm->createView()
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/update/{id}", name="update_contact")
     */
    public function update(Request $request, $id): Response
    {
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        $clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($id);
        if($clientForm->isSubmitted()) {
            if ($newClient->getFirstName()) $clientToUpdate->setFirstName($newClient->getFirstName());
            $clientToUpdate->setLastName($newClient->getLastName());
            if ($newClient->getCompanyName()) $clientToUpdate->setCompanyName($newClient->getCompanyName());
            if ($newClient->getEmail()) $clientToUpdate->setEmail($newClient->getEmail());
            $clientToUpdate->setAddress($newClient->getAddress());
            $clientToUpdate->setPostalCode($newClient->getPostalCode());
            $clientToUpdate->setCountry($newClient->getCountry());
            $clientToUpdate->setPhoneNumber($newClient->getPhoneNumber());
            if ($newClient->getMobileNumber()) $clientToUpdate->setMobileNumber($newClient->getMobileNumber());
            if ($newClient->getCategory()) $clientToUpdate->setCategory($newClient->getCategory());
            if ($newClient->getIsUnderContract()) $clientToUpdate->setIsUnderContract($newClient->getIsUnderContract());
            if ($newClient->getProvidedEquipment()) $clientToUpdate->setProvidedEquipment($newClient->getProvidedEquipment());
            $clientToUpdate->setGeographicArea($newClient->getGeographicArea());
            $clientToUpdate->setUpdatedAt(new \DateTime());
            $manager->persist($clientToUpdate);
            $manager->flush();
            $this->flashy->success("Contact mis à jour avec succès !");
            return $this->redirectToRoute('all_contacts');
        }
        return $this->render('/all_contacts/update.html.twig', [
            'client_form' => $clientForm->createView(),
            'client_to_update' => $clientToUpdate
        ]);
    }


    /**
     * @Route("/dashboard/allcontacts/fullupdate/{id}", name="full_update_contact")
     */
    public function fullUpdate(Request $request, $id): Response
    {
        $newClient = new Client();
        $clientToUpdate = $this->getDoctrine()->getRepository(Client::class)->find($id);
        /*$call1 = new Call();
        $call1->setId(1);
        $call1->setCallNotes(null);
        $newClient->getCalls()->add($call1);*/

        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();

        $clientAppointmentsList = $clientToUpdate->getAppointments();

        $newAppointment = new Appointment();
        $appointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $appointmentForm->handleRequest($request);
        $commercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");

        if($clientForm->isSubmitted()) {
            if ($newClient->getFirstName()) $clientToUpdate->setFirstName($newClient->getFirstName());
            $clientToUpdate->setLastName($newClient->getLastName());
            if ($newClient->getCompanyName()) $clientToUpdate->setCompanyName($newClient->getCompanyName());
            if ($newClient->getEmail()) $clientToUpdate->setEmail($newClient->getEmail());
            $clientToUpdate->setAddress($newClient->getAddress());
            $clientToUpdate->setPostalCode($newClient->getPostalCode());
            $clientToUpdate->setCountry($newClient->getCountry());
            $clientToUpdate->setPhoneNumber($newClient->getPhoneNumber());
            if ($newClient->getMobileNumber()) $clientToUpdate->setMobileNumber($newClient->getMobileNumber());
            if ($newClient->getCategory()) $clientToUpdate->setCategory($newClient->getCategory());
            if ($newClient->getIsUnderContract()) $clientToUpdate->setIsUnderContract($newClient->getIsUnderContract());
            if ($newClient->getProvidedEquipment()) $clientToUpdate->setProvidedEquipment($newClient->getProvidedEquipment());
            $clientToUpdate->setGeographicArea($newClient->getGeographicArea());
            $clientToUpdate->setUpdatedAt(new \DateTime());
            $manager->persist($clientToUpdate);
            $manager->flush();
            $this->flashy->success("Informations personnelles mises à jour avec succès !");
            return $this->redirectToRoute('full_update_contact', [
                "id" => $id
            ]);
        }
        return $this->render('/all_contacts/full_update_contact.html.twig', [
            'client_form' => $clientForm->createView(),
            'client_to_update' => $clientToUpdate,
            'client_appointments_list' => $clientAppointmentsList,
            'appointment_form' => $appointmentForm->createView(),
            /*'client_calls' => $calls,*/
            /*'calls' => $clientForm->getData()->getCalls()->createView(),*/
            'commercials' => $commercials
        ]);
    }


    /**
     * @Route("/dashboard/allcontacts/show/{id}", name="show_contact")
     */
    public function show(Request $request, $id): Response
    {
        $loggedUserId = $this->getUser()->getId();
        $allContactsReferer = $request->headers->get('referer');
        $clientToShow = $this->getDoctrine()->getRepository(Client::class)->find($id);
        /*dd($clientToShow->getIsDeleted());*/
        if ($clientToShow->getIsDeleted()) {
            return $this->redirectToRoute('all_contacts');
        }
        $clientAppointmentsList = $clientToShow->getAppointments();

        $newAppointment = new Appointment();
        $addAppointmentForm = $this->createForm(AppointmentFormType::class, $newAppointment);
        $addAppointmentForm->handleRequest($request);
        if($addAppointmentForm->isSubmitted()) {
            $validationStartTime = $newAppointment->getStart();
            $validationEndTime = $newAppointment->getEnd();
            $appointmentDuration = date_diff($validationEndTime,$validationStartTime);

            if($validationEndTime < $validationStartTime) {
                $this->flashy->warning("Veuillez revérifier vos entrées! L'heure de début doit être avant l'heure de fin !");
                return $this->render('/all_contacts/show.html.twig', [
                    'client_to_show' => $clientToShow,
                    'client_appointments_list' => $clientAppointmentsList,
                    'add_appointment_form' => $addAppointmentForm->createView(),
                ]);
            }
            if($validationEndTime > $validationStartTime) {
                $startTime = $newAppointment->getStart()->format('Y-m-d H:i:s');
                $endTime = $newAppointment->getEnd()->format('Y-m-d H:i:s');
                $busyAppointmentsTime = $this->getDoctrine()->getRepository(Appointment::class)->getAppointmentsBetweenByDate($startTime, $endTime);
                /*dd($busyAppointmentsTime);*/
                if($busyAppointmentsTime) {
                    $busyCommercialsIdsArray = [];
                    foreach ($busyAppointmentsTime as $busyAppointment) {
                        $busyCommercialsIdsArray[] = $busyAppointment->getUser()->getId();
                    }
                    if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles()) || in_array("ROLE_COMMERCIAL", $this->getUser()->getRoles())) {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercialsForSuperAdmin($busyCommercialsIdsArray, "ROLE_COMMERCIAL");
                    } else {
                        if(count($this->getUser()->getCommercials()) === 0) {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercialsIfNoneAssigned($busyCommercialsIdsArray, "ROLE_COMMERCIAL");
                        } else {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findFreeCommercials($busyCommercialsIdsArray, "ROLE_COMMERCIAL", $loggedUserId);
                        }
                    }

                } else {

                    if(in_array("ROLE_SUPERADMIN", $this->getUser()->getRoles()) || in_array("ROLE_COMMERCIAL", $this->getUser()->getRoles())) {
                        $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
                    } else {
                        if(count($this->getUser()->getCommercials()) === 0) {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findUsersByCommercialRole("ROLE_COMMERCIAL");
                        } else {
                            $freeCommercials = $this->getDoctrine()->getRepository(User::class)->findAssignedUsersByCommercialRole($loggedUserId,"ROLE_COMMERCIAL");
                        }

                    }
                }
                if((count($freeCommercials) !== 0)) {
                    return $this->render('/all_contacts/appointment_free_commercials_check.html.twig', [
                        /*'free_appointments' => $freeAppointmentsTime*/
                        'free_commercials' => $freeCommercials,
                        'clients' => $clientToShow,
                        'start' => $startTime,
                        'end' => $endTime
                    ]);
                } else {
                    $this->flashy->info("Aucun agent n'est disponible à l'intervalle de temps choisi, Veuillez sélectionner d'autres dates !");
                    return $this->render('/all_contacts/show.html.twig', [
                        'client_to_show' => $clientToShow,
                        'client_appointments_list' => $clientAppointmentsList,
                        'add_appointment_form' => $addAppointmentForm->createView(),
                    ]);
                }

            } else {
                if(($appointmentDuration->days === 0) && ($appointmentDuration->h === 0)
                    && ($appointmentDuration->i === 0) && ($appointmentDuration->s === 0)) {
                    $this->flashy->warning("Veuillez revérifier vos entrées! La durée du RDV ne doit pas être nulle !");
                }
                return $this->render('/all_contacts/show.html.twig', [
                    'client_to_show' => $clientToShow,
                    'client_appointments_list' => $clientAppointmentsList,
                    'add_appointment_form' => $addAppointmentForm->createView(),
                ]);
            }
        }


        return $this->render('/all_contacts/show.html.twig', [
            'client_to_show' => $clientToShow,
            'client_appointments_list' => $clientAppointmentsList,
            'add_appointment_form' => $addAppointmentForm->createView(),
        ]);
    }



    /**
     * @Route("/dashboard/allcontacts/delete/{id}", name="delete_contact")
     */
    public function delete(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $referer = $request->headers->get('referer');
        /*dd($referer);*/
        $loggedUser = $this->getUser();
        $contactToDelete = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $contactToDelete->setIsDeleted(true);
        $contactToDelete->setDeletionDate(new \DateTime());
        $contactToDelete->setWhoDeletedIt($loggedUser);
        $manager->persist($contactToDelete);
        $manager->flush();
        $this->flashy->success("Contact supprimé avec succès !");
        return $this->redirect($referer);
    }

    /**
     * @Route("/dashboard/allcontacts/restore/{id}", name="restore_contact")
     */
    public function restoreContact(Request $request, $id): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $contactToRestore = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $contactToRestore->setIsDeleted(false);
        $contactToRestore->setDeletionDate(null);
        $manager->persist($contactToRestore);
        $manager->flush();
        $this->flashy->success("Contact restauré avec succès !");
        return $this->redirectToRoute('trash_contacts');
    }

    /**
     * @Route("/dashboard/allcontacts/import-contacts", name="import_contacts")
     * @param Request $request
     * @throws \Exception
     */
    public function importContactsExcel(Request $request)
    {
        $file = $request->files->get('excelcontactsfile'); // get the file from the sent request
        // check the type of the uploaded file
        /*$mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');*/
        $mimes = array('application/vnd.oasis.opendocument.spreadsheet','application/vnd.ms-excel','text/plain','text/csv','text/tsv');
        /*dd($_FILES['excelcontactsfile']['type']);*/
        if(in_array($_FILES['excelcontactsfile']['type'],$mimes)){

            /*dd($file);*/
            $fileFolder = __DIR__ . '/../../public/excel_contacts_uploads/';  //choose the folder in which the uploaded file will be stored

            $filePathName = md5(uniqid()) . $file->getClientOriginalName();
            // apply md5 function to generate an unique identifier for the file and concat it with the file extension
            try {
                $file->move($fileFolder, $filePathName);
            } catch (FileException $e) {
                dd($e);
            }
            $inputFileType = IOFactory::identify($fileFolder . $filePathName);
            $reader = IOFactory::createReader($inputFileType);
            /**  Advise the Reader that we only want to load cell data  **/
            $reader->setReadDataOnly(true);
            /*$reader->setInputEncoding('CP1252');*/

            /*dd($reader);*/
            /**  Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($fileFolder . $filePathName);
            /*dd((($spreadsheet->getAllSheets())[2]->toArray(null, true, true, true, true))[4]);*/
            //Check if the template of the uploaded excel file is correct
            $activeSheet = $spreadsheet->getActiveSheet();
            /*if(!($activeSheet->getCellByColumnAndRow(1,1)->getValue() === "Numéro de téléphone" &&
                $activeSheet->getCellByColumnAndRow(2,1)->getValue() === "Nom du professionnel" &&
                $activeSheet->getCellByColumnAndRow(3,1)->getValue() === "Adresse" &&
                $activeSheet->getCellByColumnAndRow(4,1)->getValue() === "Commune" &&
                $activeSheet->getCellByColumnAndRow(5,1)->getValue() === "Code Postal")
            ) {
                $this->flashy->warning("Désolé! Ce fichier ne suit pas les normes du modèle !");
                return $this->redirectToRoute('all_contacts');
            }*/

            /*$spreadsheet = IOFactory::load($fileFolder . $filePathName);*/ // Here we are able to read from the excel file

            /*$row = $spreadsheet->getActiveSheet()->removeRow(1); // I added this to be able to remove the first file line

            $sheetData = $spreadsheet->getActiveSheet()-> toArray(null, true, true, true, true); // here, the read data is turned into an array*/
            /*dd($sheetData);*/

            //Save imported contacts in the database
            $entityManager = $this->getDoctrine()->getManager();
            $counterOfAdded = 0;
            $counterOfNonAdded = 0;
            /*dd($spreadsheet->getAllSheets()[2]->toArray(null, true, true, true, true));*/
            /*set_time_limit(3600);*/
            /*dd(($spreadsheet->getAllSheets())[1]);
            (($spreadsheet->getAllSheets())[1])->removeRow(1);
            dd(($spreadsheet->getAllSheets())[1]);*/
            $allExistingContacts = $this->getDoctrine()->getRepository(Client::class)->findAll();
            $dbAllContactsArray = [];
            foreach ($allExistingContacts as $existingContact) {
                $oneExistingContactArray = ["firstName" => $existingContact->getFirstName(), "lastName" => $existingContact->getLastName(), "phoneNumber" => $existingContact->getPhoneNumber(), "address" =>$existingContact->getAddress(), "city" =>$existingContact->getCity(), "postalCode" =>$existingContact->getPostalCode(), "geographicArea" =>$existingContact->getGeographicArea()];
                $dbAllContactsArray[] = $oneExistingContactArray;
            }
            $excelAllContactsArray = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {

                if(!($sheet->getCellByColumnAndRow(1,1)->getValue() === "Numéro de téléphone" &&
                    $sheet->getCellByColumnAndRow(2,1)->getValue() === "Nom du professionnel" &&
                    $sheet->getCellByColumnAndRow(3,1)->getValue() === "Adresse" &&
                    $sheet->getCellByColumnAndRow(4,1)->getValue() === "Commune" &&
                    $sheet->getCellByColumnAndRow(5,1)->getValue() === "Code Postal")
                ) {
                    $this->flashy->warning("Désolé! Ce fichier ne suit pas les normes du modèle ! Veuillez vérifier la feuille '". $sheet->getTitle() . "'");
                    return $this->redirectToRoute('all_contacts');
                }
                /*dd($sheet);*/
                $row = $sheet->removeRow(1);
                $sheetData = $sheet-> toArray(null, true, true, true, true); // here, the read data is turned into an array*

                /*dd($sheetData);*/

                foreach ($sheetData as $Row)
                {
                    $oneRowContactArray = [];
                    $allTheName = $Row['B'];
                    $SplitedNameArray = explode(" ", $allTheName, 2);
                    $firstName = $SplitedNameArray[0]; // store the first_name on each iteration
                    $lastName = $SplitedNameArray[1]; // store the last_name on each iteration
                    /*$email= $Row['C'];*/     // store the email on each iteration
                    /*$companyName= $Row['D'];*/
                    $address= $Row['C'];
                    if (strlen($Row['E']) === 4) {
                        $postalCode= "0" . $Row['E'];
                    } else {
                        $postalCode= $Row['E'];
                    }
                    $city = $Row['D'];
                    /*$country= $Row['G'];*/
                    if ($Row['A']) {
                        $phoneNumber= $Row['A'];
                    } else {
                        $phoneNumber = "";
                    }

                    if (substr($postalCode,0,2) === "97") {
                        $departmentCode = substr($postalCode,0,3);
                    } else {
                        $departmentCode = substr($postalCode,0,2);
                    }

                    $geographicArea = $this->getDoctrine()->getRepository(GeographicArea::class)->findOneBy(array('code' => $departmentCode));

                    $existingContactByLastName = $entityManager->getRepository(Client::class)->findOneBy(array('lastName' => $lastName));
                    $existingContactByFirstName = $entityManager->getRepository(Client::class)->findOneBy(array('firstName' => $firstName));

                    $oneRowContactArray = ["firstName" => $firstName, "lastName" => $lastName, "phoneNumber" => $phoneNumber, "address" =>$address, "city" =>$city, "postalCode" =>$postalCode, "geographicArea" =>$geographicArea];




                    if(!in_array($oneRowContactArray, $excelAllContactsArray) && !in_array($oneRowContactArray, $dbAllContactsArray)) {
                        $excelAllContactsArray[] = $oneRowContactArray;
                        $counterOfAdded++;
                    } else {
                        $counterOfNonAdded++;
                    }
                }
            }

            /*$dbAllExistingContactsCount = count($dbAllContactsArray);
            dd($dbAllContactsArray);
            dd($excelAllContactsArray);*/
            foreach ($excelAllContactsArray as $contactRow) {
                $contact = new Client();
                $contact->setFirstName($contactRow["firstName"]);
                $contact->setLastName($contactRow["lastName"]);
                $contact->setAddress($contactRow["address"]);
                $contact->setPostalCode($contactRow["postalCode"]);
                $contact->setCity($contactRow["city"]);
                $contact->setCountry('France');
                $contact->setPhoneNumber($contactRow["phoneNumber"]);
                $contact->setStatus(0);
                $contact->setStatusDetail(0);
                $contact->setGeographicArea($contactRow["geographicArea"]);
                $contact->setCreatedAt(new \DateTime());
                $contact->setUpdatedAt(new \DateTime());
                $contact->setIsDeleted(false);
                $contact->setIsProcessed(false);
                $entityManager->persist($contact);
            }
            $entityManager->flush();

            if($counterOfAdded === 0) {
                $this->addFlash(
                    'add_contacts_warning',
                    "Aucun contact n'a été ajouté!"
                );
                $this->addFlash(
                    'add_contacts_confirmation2',
                    $counterOfNonAdded . " Doublons ont été détectés!"
                );
            } else {
                $this->addFlash(
                    'add_contacts_confirmation1',
                    $counterOfAdded . " Contacts ont été ajoutés avec succès!"
                );
                $this->addFlash(
                    'add_contacts_confirmation2',
                    $counterOfNonAdded . " Doublons ont été détectés!"
                );
            }

            $this->flashy->info("Opération d'import des contacts terminée !");


        } else {
            $this->flashy->warning("Désolé! Ce type de fichier n'est pas autorisé !");
        }

        return $this->redirectToRoute('all_contacts');
    }

}
