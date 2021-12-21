<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\PaymentSchedule;
use App\Repository\ProjectRepository;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentScheduleController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

    /**
     * @Route("/dashboard/project/{id}/paymentschedule", name="payment_schedule")
     */
    public function paymentScheduleGenerate(Request $request, $id, ProjectRepository $projectRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $associatedProject = $projectRepository->find($id);
        $totalHT = $associatedProject->getTotalHT();
        $numberOfMonthlyPayments = $associatedProject->getNumberOfMonthlyPayments();
        /*$paymentPerMonth = $totalHT / $numberOfMonthlyPayments;*/
        $paymentPerMonth = $associatedProject->getMonthlyPayment();
        $paymentSchedule = new PaymentSchedule();
        $paymentSchedule->setProject($associatedProject);
        $paymentSchedule->setWhoGenerateIt($this->getUser());
        $paymentSchedule->setClient($associatedProject->getClient());
        $paymentSchedule->setIsDeleted(false);
        $paymentSchedule->setIsCompleted(false);
        for ($i = 0; $i < $numberOfMonthlyPayments; $i++) {
            $paymentLine = new Payment();
            $paymentLine->setAssociatedPaymentSchedule($paymentSchedule);
            $paymentLine->setValue($paymentPerMonth);
            $paymentDate = date("m/d/y",mktime(0, 0, 0, ((int)(new \DateTime())->format("m") + $i),(new \DateTime())->format("d"),((int)(new \DateTime())->format("Y"))));
            /*dd($paymentDate);*/
            $paymentLine->setPaymentDate(new \DateTime($paymentDate));
            $paymentLine->setIsPaid(false);
            $paymentLine->setPaymentNumber($i + 1);
            $paymentLine->setPaymentMethod(0);
            $em->persist($paymentLine);
        }
        $paymentSchedule->setCreatedAt(new \DateTime());
        $em->persist($paymentSchedule);
        $em->flush();
        $this->flashy->success("Échéancier généré avec succès !");
        return $this->redirectToRoute('payment_schedule_list');
    }
}
