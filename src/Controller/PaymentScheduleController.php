<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\PaymentSchedule;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentScheduleController extends AbstractController
{
    /**
     * @Route("/dashboard/project/{id}/paymentschedule", name="payment_schedule")
     */
    public function paymentScheduleGenerate(Request $request, $id, ProjectRepository $projectRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $associatedProject = $projectRepository->find($id);
        $totalHT = $associatedProject->getTotalHT();
        $numberOfMonthlyPayments = $associatedProject->getNumberOfMonthlyPayments();
        $paymentPerMonth = $totalHT / $numberOfMonthlyPayments;
        $paymentSchedule = new PaymentSchedule();
        $paymentSchedule->setProject($associatedProject);
        $paymentSchedule->setWhoGenerateIt($this->getUser());
        $paymentSchedule->setClient($associatedProject->getClient());
        $paymentSchedule->setIsDeleted(false);
        for ($i = 0; $i < $numberOfMonthlyPayments; $i++) {
            $paymentLine = new Payment();
            $paymentLine->setAssociatedPaymentSchedule($paymentSchedule);
            $paymentLine->setValue($paymentPerMonth);
            $paymentDate = date("m/d/y",mktime(0, 0, 0, ((int)(new \DateTime())->format("m") + $i + 1),18,((int)(new \DateTime())->format("Y"))));
            $paymentLine->setPaymentDate(new \DateTime($paymentDate));
            $paymentLine->setIsPaid(false);
            $em->persist($paymentLine);
        }
        $paymentSchedule->setCreatedAt(new \DateTime());
        $em->persist($paymentSchedule);
        $em->flush();
        return $this->redirectToRoute('payment_schedule_list');
    }
}
