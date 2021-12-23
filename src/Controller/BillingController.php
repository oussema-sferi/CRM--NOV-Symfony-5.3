<?php

namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\PaymentScheduleRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;
use MercurySeries\FlashyBundle\FlashyNotifier;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BillingController extends AbstractController
{
    public function __construct(FlashyNotifier $flashy)
    {
        $this->flashy = $flashy;
    }

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
            'payment_schedule_id' => $id,
            'payments' => $payments,
            'all_payments' => $data,
        ]);
    }

    /**
     * @Route("/dashboard/billing/paymentschedulelist/{id}/paymentsperschedule/pdfexport", name="schedule_pdf_export")
     */
    public function paymentsPerSchedulePdfExport(Request $request, $id, PaymentRepository $paymentRepository, PaymentScheduleRepository $paymentScheduleRepository): Response
    {
        /*dd($this->getParameter('kernel.project_dir') . '\public\assets\css');*/
        $payments = $paymentRepository->getPaymentsOfPaymentSchedule($id);
        $paymentSchedule = $paymentScheduleRepository->find($id);
        $client = $paymentSchedule->getProject()->getClient();
        /*return $this->render('billing/payments_per_schedule_export_PDF.html.twig', [
            'payments' => $payments,
            'payment_schedule' => $paymentSchedule,
            'client' => $client,
            'today' => new \DateTime()
        ]);*/
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Cambria');
        $pdfOptions->setIsRemoteEnabled(true);
        $domPdf = new Dompdf($pdfOptions);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $domPdf->setHttpContext($context);
        /*$domPdf->setBasePath($this->getParameter('kernel.project_dir') . '/public/assets/css/');*/
        $html = $this->renderView('billing/payments_per_schedule_export_PDF.html.twig', [
            'payments' => $payments,
            'payment_schedule' => $paymentSchedule,
            'client' => $client,
            'today' => new \DateTime()
        ]);
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'landscape');
        $domPdf->render();
        $pdfFile = 'échéancier' . $id . '.pdf';
        $domPdf->stream($pdfFile, [
            'Attachment' => true
        ]);
        return new Response();

        /*return $this->render('billing/payments_per_schedule_export_PDF.html.twig', [
            'payments' => $payments
        ]);*/
    }

    /**
     * @Route("/dashboard/billing/paymentschedulelist/{paymentScheduleId}/paymentsperschedule/{paymentId}/onepaymentpdfexport", name="one_payment_pdf_export")
     */
    public function onePaymentPdfExport(Request $request, $paymentScheduleId, $paymentId, PaymentRepository $paymentRepository, PaymentScheduleRepository $paymentScheduleRepository): Response
    {
        $payment = $paymentRepository->find($paymentId);
        $paymentSchedule = $paymentScheduleRepository->find($paymentScheduleId);
        $client = $paymentSchedule->getProject()->getClient();
        /*return $this->render('billing/one_payment_export_PDF.html.twig', [
            'payment' => $payment,
            'payment_schedule' => $paymentSchedule,
            'client' => $client,
            'today' => new \DateTime()
        ]);*/
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $domPdf = new Dompdf($pdfOptions);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $domPdf->setHttpContext($context);
        $html = $this->renderView('billing/one_payment_export_PDF.html.twig', [
            'payment' => $payment,
            'payment_schedule' => $paymentSchedule,
            'client' => $client,
            'today' => new \DateTime()
        ]);
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'landscape');
        $domPdf->render();
        $pdfFile = 'réglement' . $payment->getPaymentNumber() . '-' . $paymentScheduleId . '.pdf';
        $domPdf->stream($pdfFile, [
            'Attachment' => true
        ]);
        return new Response();
        /*return $this->render('billing/payments_per_schedule_export_PDF.html.twig', [
            'payments' => $payments
        ]);*/
    }

    /**
     * @Route("/dashboard/billing/paymentschedulelist/{paymentScheduleId}/paymentsperschedule/{paymentRowId}", name="payments_row_show")
     */
    public function paymentsRowShow(Request $request, $paymentScheduleId, $paymentRowId, PaginatorInterface $paginator, PaymentRepository $paymentRepository): Response
    {
        $paymentRow = $paymentRepository->find($paymentRowId);
        if($paymentRow->getIsPaid() === true) {
            return $this->render('billing/payment_row_form_paid.html.twig', [
                'payment_schedule_id' => $paymentScheduleId,
                'payment_row' => $paymentRow,
            ]);
        } else {
            return $this->render('billing/payment_row_form_unpaid.html.twig', [
                'payment_schedule_id' => $paymentScheduleId,
                'payment_row' => $paymentRow,
            ]);
        }
    }

    /**
     * @Route("/dashboard/billing/paymentschedulelist/{paymentScheduleId}/paymentsperschedule/{paymentRowId}/edit", name="payments_row_edit")
     */
    public function paymentsRowEdit(Request $request, $paymentScheduleId, $paymentRowId, PaginatorInterface $paginator, PaymentRepository $paymentRepository, PaymentScheduleRepository $paymentScheduleRepository): Response
    {
        $paymentRow = $paymentRepository->find($paymentRowId);
        $paymentSchedule = $paymentScheduleRepository->find($paymentScheduleId);
        if ($request->isMethod('Post')) {
            if($request->request->get('payment_status') === 'paid') {
                $em = $this->getDoctrine()->getManager();
                $paymentRow->setIsPaid(true);
                $paymentRow->setPaymentReceiptDate(new \DateTime($request->request->get('payment_receipt_date')));
                $paymentRow->setPaymentMethod((int)($request->request->get('paymentMethod')));
                $em->persist($paymentRow);
                $em->flush();
                $allPayments = $paymentSchedule->getPayments();
                $counter = 0;
                foreach ($allPayments as $payment) {
                    if ($payment->getIsPaid() === false) {
                        $counter ++;
                    }
                }
                if ($counter === 0) {
                    $paymentSchedule->setIsCompleted(true);
                }
                $em->persist($paymentSchedule);
                $em->flush();
                $this->flashy->success("Réglement traité avec succès !");
            } else {
                $this->flashy->info("Aucun traitement n'a été effectué !");
            }
        }
        return $this->redirectToRoute('payments_row_show', [
            'paymentScheduleId' => $paymentScheduleId,
            'paymentRowId' => $paymentRowId,
        ]);
    }
}
