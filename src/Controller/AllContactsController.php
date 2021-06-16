<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientFormType;
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
    public function index(): Response
    {
        return $this->render('all_contacts/index.html.twig', [
            'clients' => $this->getDoctrine()->getRepository(Client::class)->findAll(),
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

        /*dd($file);*/
        $fileFolder = __DIR__ . '/../../public/excel_contacts_uploads/';  //choose the folder in which the uploaded file will be stored

        $filePathName = md5(uniqid()) . $file->getClientOriginalName();
        // apply md5 function to generate an unique identifier for the file and concat it with the file extension
        try {
            $file->move($fileFolder, $filePathName);
        } catch (FileException $e) {
            dd($e);
        }
        $spreadsheet = IOFactory::load($fileFolder . $filePathName); // Here we are able to read from the excel file
        $row = $spreadsheet->getActiveSheet()->removeRow(1); // I added this to be able to remove the first file line
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true); // here, the read data is turned into an array
        /*dd($sheetData);*/

        //Save imported contacts in the database

        $entityManager = $this->getDoctrine()->getManager();
        foreach ($sheetData as $Row)
        {

            $firstName = strtolower($Row['A']); // store the first_name on each iteration
            $lastName = strtolower($Row['B']); // store the last_name on each iteration
            $email= strtolower($Row['C']);     // store the email on each iteration
            $companyName= strtolower($Row['D']);
            $address= strtolower($Row['E']);
            $postalCode= strtolower($Row['F']);
            $country= strtolower($Row['G']);
            $phoneNumber= strtolower($Row['H']);
            $mobileNumber= strtolower($Row['I']);
            $category= strtolower($Row['J']);

            if(strtolower($Row['K']) === 'non') {
                $isUnderContract = false;
            } else {
                $isUnderContract = true;
            }
            


            $existingContact = $entityManager->getRepository(Client::class)->findOneBy(array('email' => $email));
                // make sure that the user does not already exists in your db
            if (!$existingContact)
            {
                $contact = new Client();
                $contact->setFirstName($firstName);
                $contact->setLastName($lastName);
                $contact->setEmail($email);
                $contact->setCompanyName($companyName);
                $contact->setAddress($address);
                $contact->setPostalCode($postalCode);
                $contact->setCountry($country);
                $contact->setPhoneNumber($phoneNumber);
                $contact->setMobileNumber($mobileNumber);
                $contact->setCategory($category);
                $contact->setIsUnderContract($isUnderContract);
                $contact->setStatus(0);
                $contact->setCreatedAt(new \DateTime());
                $contact->setUpdatedAt(new \DateTime());
                $entityManager->persist($contact);
                $entityManager->flush();
                // here Doctrine checks all the fields of all fetched data and make a transaction to the database.
            }
        }
        return $this->json('contacts saved', 200);

    }

}
