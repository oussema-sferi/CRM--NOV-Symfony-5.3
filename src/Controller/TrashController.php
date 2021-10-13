<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Call;
use App\Entity\Client;
use App\Entity\User;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrashController extends AbstractController
{
    /**
     * @Route("/dashboard/trash/contacts", name="trash_contacts")
     */
    public function deletedContactsList(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(Client::class)->getDeletedClients();
        if($session->get('pagination_value')) {
            $deletedContacts = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $deletedContacts = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('trash/contacts.html.twig', [
            'all_deleted_contacts' => $data,
            'deleted_contacts' => $deletedContacts
        ]);
    }

    /**
     * @Route("/dashboard/trash/contacts/pagination", name="deleted_contacts_pagination")
     */
    public function deletedContactsPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
        }
        /*return new Response('use Ajax');*/
    }

    /**
     * @Route("/dashboard/trash/users", name="trash_users")
     */
    public function deletedUsersList(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(User::class)->getDeletedUsers();
        if($session->get('pagination_value')) {
            $deletedUsers = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $deletedUsers = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('trash/users.html.twig', [
            'all_deleted_users' => $data,
            'deleted_users' => $deletedUsers
        ]);
    }

    /**
     * @Route("/dashboard/trash/users/pagination", name="deleted_users_pagination")
     */
    public function deletedUsersPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
        }
        /*return new Response('use Ajax');*/
    }

    /**
     * @Route("/dashboard/trash/calls", name="trash_calls")
     */
    public function deletedCallsList(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(Call::class)->getDeletedCalls();
        if($session->get('pagination_value')) {
            $deletedCalls = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $deletedCalls = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('trash/calls.html.twig', [
            'all_deleted_calls' => $data,
            'deleted_calls' => $deletedCalls
        ]);
    }

    /**
     * @Route("/dashboard/trash/calls/pagination", name="deleted_calls_pagination")
     */
    public function deletedCallsPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
        }
        /*return new Response('use Ajax');*/
    }

    /**
     * @Route("/dashboard/trash/appointments", name="trash_appointments")
     */
    public function deletedAppointmentsList(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        $data = $this->getDoctrine()->getRepository(Appointment::class)->getDeletedAppointments();
        if($session->get('pagination_value')) {
            $deletedAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $deletedAppointments = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            );
        }

        return $this->render('trash/appointments.html.twig', [
            'all_deleted_appointments' => $data,
            'deleted_appointments' => $deletedAppointments
        ]);
    }

    /**
     * @Route("/dashboard/trash/appointments/pagination", name="deleted_appointments_pagination")
     */
    public function deletedAppointmentsPagination(Request $request): Response
    {
        $session = $request->getSession();
        if($request->isXmlHttpRequest()) {
            $paginationValue = $request->get('paginationValue');
            $session->set('pagination_value',
                $paginationValue
            );
            return new JsonResponse(['message'=> 'Task Success!']);
        } else {
            return new JsonResponse(['message'=> 'Task Fails!']);
        }
        /*return new Response('use Ajax');*/
    }
}
