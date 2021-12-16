<?php

namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\PaymentScheduleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BillingController extends AbstractController
{
    /**
     * @Route("/dashboard/billing/paymentschedulelist", name="payment_schedule_list")
     */
    public function paymentScheduleList(Request $request, PaginatorInterface $paginator, EquipmentRepository $equipmentRepository, PaymentScheduleRepository $paymentScheduleRepository): Response
    {
        $session = $request->getSession();
        $equipments = $equipmentRepository->findAll();
        $data = $paymentScheduleRepository->findAll();
        if($session->get('pagination_value')) {
            $paymentsSchedules = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $paymentsSchedules = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('billing/payments_schedules_list.html.twig', [
            'equipments' => $equipments,
            'payments_schedules' => $paymentsSchedules,
            'all_payments_schedules' => $data,
        ]);
    }

    /**
     * @Route("/dashboard/billing/paymentschedulelist/{id}/paymentsperschedule", name="payments_per_schedule")
     */
    public function paymentsPerSchedule(Request $request, $id, PaginatorInterface $paginator, PaymentRepository $paymentRepository): Response
    {
        $session = $request->getSession();
        $data = $paymentRepository->getPaymentsOfPaymentSchedule($id);
        if($session->get('pagination_value')) {
            $payments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $payments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('billing/payments_per_schedule_list.html.twig', [
            'payments' => $payments,
            'all_payments' => $data,
        ]);
    }
}
