<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\GeographicArea;
use App\Form\ClientFormType;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AllContactsController extends AbstractController
{
    /**
     * @Route("/dashboard/allcontacts", name="all_contacts")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        $data = $this->getDoctrine()->getRepository(Client::class)->findAll();
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
            'geographic_areas'=> $geographicAreas
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/add", name="new_contact")
     */
    public function add(Request $request): Response
    {
        $newClient = new Client();
        $clientForm = $this->createForm(ClientFormType::class, $newClient);
        $clientForm->handleRequest($request);
        $manager = $this->getDoctrine()->getManager();
        if($clientForm->isSubmitted()) {
            $newClient->setStatus(0);
            $newClient->setCreatedAt(new \DateTime());
            $newClient->setUpdatedAt(new \DateTime());
            $manager->persist($newClient);
            $manager->flush();
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
            $clientToUpdate->setFirstName($newClient->getFirstName());
            $clientToUpdate->setLastName($newClient->getLastName());
            $clientToUpdate->setCompanyName($newClient->getCompanyName());
            $clientToUpdate->setEmail($newClient->getEmail());
            $clientToUpdate->setAddress($newClient->getAddress());
            $clientToUpdate->setPostalCode($newClient->getPostalCode());
            $clientToUpdate->setCountry($newClient->getCountry());
            $clientToUpdate->setPhoneNumber($newClient->getPhoneNumber());
            $clientToUpdate->setMobileNumber($newClient->getMobileNumber());
            $clientToUpdate->setCategory($newClient->getCategory());
            $clientToUpdate->setIsUnderContract($newClient->getIsUnderContract());
            $clientToUpdate->setProvidedEquipment($newClient->getProvidedEquipment());
            $clientToUpdate->setGeographicArea($newClient->getGeographicArea());
            $clientToUpdate->setUpdatedAt(new \DateTime());
            $manager->persist($clientToUpdate);
            $manager->flush();
            return $this->redirectToRoute('all_contacts');
        }
        return $this->render('/all_contacts/update.html.twig', [
            'client_form' => $clientForm->createView(),
            'client_to_update' => $clientToUpdate
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/show/{id}", name="show_contact")
     */
    public function show(Request $request, $id): Response
    {
        $clientToShow = $this->getDoctrine()->getRepository(Client::class)->find($id);
        return $this->render('/all_contacts/show.html.twig', [
            'client_to_show' => $clientToShow
        ]);
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
        $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
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
            $reader->setInputEncoding('CP1252');

            /*dd($reader);*/
            /**  Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($fileFolder . $filePathName);
            /*dd($spreadsheet);*/
            //Check if the template of the uploaded excel file is correct
            $activeSheet = $spreadsheet->getActiveSheet();
            if(!($activeSheet->getCellByColumnAndRow(1,1)->getValue() === "Numéro de téléphone" &&
                $activeSheet->getCellByColumnAndRow(2,1)->getValue() === "Nom du professionnel" &&
                $activeSheet->getCellByColumnAndRow(3,1)->getValue() === "Adresse" &&
                $activeSheet->getCellByColumnAndRow(4,1)->getValue() === "Commune" &&
                $activeSheet->getCellByColumnAndRow(5,1)->getValue() === "Code Postal")
            ) {
                /*dd("yes");*/
                $this->addFlash(
                    'import_file_template_error',
                    "Désolé! Ce fichier ne suit pas les normes du modèle!"
                );
                return $this->redirectToRoute('all_contacts');
            }

            /*$spreadsheet = IOFactory::load($fileFolder . $filePathName);*/ // Here we are able to read from the excel file
            $row = $spreadsheet->getActiveSheet()->removeRow(1); // I added this to be able to remove the first file line

            $sheetData = $spreadsheet->getActiveSheet()-> toArray(null, true, true, true, true); // here, the read data is turned into an array
            /*dd($sheetData);*/

            //Save imported contacts in the database

            $entityManager = $this->getDoctrine()->getManager();

            $counterOfAdded = 0;
            $counterOfNonAdded = 0;
            foreach ($sheetData as $Row)
            {

                /*$firstName = $Row['A']; // store the first_name on each iteration*/
                $lastName = $Row['B']; // store the last_name on each iteration
                /*$email= $Row['C'];*/     // store the email on each iteration
                /*$companyName= $Row['D'];*/
                $address= $Row['C'];
                $postalCode= $Row['E'];
                $city = $Row['D'];
                /*$country= $Row['G'];*/
                $phoneNumber= $Row['A'];
                /*  $mobileNumber= $Row['I'];
                  $category= $Row['J'];*/

                /*if(strtolower($Row['K']) === 'non') {
                    $isUnderContract = false;
                } else {
                    $isUnderContract = true;
                }*/

                //

                /*$providedEquipment = $this->getDoctrine()->getRepository(Equipment::class)->find($Row['L']);*/


                /*$geographicArea = $this->getDoctrine()->getRepository(GeographicArea::class)->findOneBy(array('code' => $Row['M']));*/
                $geographicArea = $this->getDoctrine()->getRepository(GeographicArea::class)->findOneBy(array('code' => (int)substr('59621',0,2)));

                /*dd($geographicArea);*/


                $existingContact = $entityManager->getRepository(Client::class)->findOneBy(array('lastName' => $lastName));

                // make sure that the user does not already exists in your db
                if (!$existingContact)
                {

                    $contact = new Client();
                    /*$contact->setFirstName($firstName);*/
                    $contact->setLastName($lastName);
                    /* $contact->setEmail($email);
                     $contact->setCompanyName($companyName);*/
                    $contact->setAddress($address);
                    $contact->setPostalCode($postalCode);
                    $contact->setCity($city);
                    $contact->setCountry('France');
                    $contact->setPhoneNumber($phoneNumber);
                    /* $contact->setMobileNumber($mobileNumber);
                     $contact->setCategory($category);*/
                    $contact->setIsUnderContract(false);
                    $contact->setStatus(0);
                    /*$contact->setProvidedEquipment($providedEquipment);*/
                    $contact->setGeographicArea($geographicArea);
                    $contact->setCreatedAt(new \DateTime());
                    $contact->setUpdatedAt(new \DateTime());
                    $entityManager->persist($contact);
                    $entityManager->flush();
                    // here Doctrine checks all the fields of all fetched data and make a transaction to the database.
                    $counterOfAdded++;
                } else {
                    $counterOfNonAdded++;
                }
            }
            /*dd($counter);*/

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




        } else {
            $this->addFlash(
                'import_file_type_error',
                "Désolé! Ce type de fichier n'est pas autorisé!"
            );

        }

        return $this->redirectToRoute('all_contacts');
    }

}
