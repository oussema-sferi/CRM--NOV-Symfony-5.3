<?php

namespace App\Controller;

use App\Entity\Client;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class SearchFiltersController extends AbstractController
{
    /**
     * @Route("/search/telepro/filters", name="telepro_search_filters")
     */
    public function teleproSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        $session = $request->getSession();
        if($request->isMethod('POST')) {
            $searchKeyword = $request->request->get('search_keyword');
            $searchFilter = $request->request->get('filters');
            /*dd($searchFilter);*/
            $session->set('filters',
                $searchFilter
            );
            $session->set('search_keyword',
                $searchKeyword
            );

        }
        /*dd($session->get('search_keyword'));*/
        $searchKeywordSession = $session->get('search_keyword');
        $searchFilterSession = $session->get('filters');
        $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByFilterAndKeyword($searchFilterSession,$searchKeywordSession);
        $clients = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );
            /*dd($data);*/
            return $this->render('teleprospecting/index.html.twig', [
                'clients' => $clients,
            ]);
        }


}
