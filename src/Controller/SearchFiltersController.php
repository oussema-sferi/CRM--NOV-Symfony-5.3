<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\GeographicArea;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SearchFiltersController extends AbstractController
{
    /**
     * @Route("/dashboard/teleprospecting/search/filters", name="telepro_search_filters")
     */
    public function teleproSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        /*$session = $request->getSession();
        if($request->isMethod('POST')) {
            $searchKeyword = $request->request->get('search_keyword');
            $searchFilter = $request->request->get('filters');
            $session->set('filters',
                $searchFilter
            );
            $session->set('search_keyword',
                $searchKeyword
            );

        }
        $searchKeywordSession = $session->get('search_keyword');
        $searchFilterSession = $session->get('filters');
        $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByFilterAndKeyword($searchFilterSession,$searchKeywordSession);
        $clients = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );
            return $this->render('teleprospecting/index.html.twig', [
                'clients' => $clients,
            ]);*/
        //search without ajax
            $session = $request->getSession();
            $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
            if($request->isMethod('POST')) {
                /*dd($request->request->all());*/
                $session->set('criterias',
                    $request->request->all()
                );
            }
            $criterias = $session->get('criterias');
            $payload = $this->getDoctrine()->getRepository(Client::class)->fetchClientsbyFilters($criterias);
            if(count($payload) === 0) {
                $session->set('total_telepro_search_results',
                    'nothing'
                );
            } else {
                $session->set('total_telepro_search_results',
                    count($payload)
                );
            }
            /*dd($payload);*/


        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }

           /* $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );*/

        return $this->render('teleprospecting/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
        ]);
    }

    /**
     * @Route("/dashboard/allcontacts/search/filters", name="all_contacts_search_filters")
     */
    public function allContactsSearchFilters(Request $request, PaginatorInterface $paginator): Response
    {
        /*$session = $request->getSession();
        if($request->isMethod('POST')) {
            $searchKeyword = $request->request->get('search_keyword');
            $searchFilter = $request->request->get('filters');
            $session->set('filters',
                $searchFilter
            );
            $session->set('search_keyword',
                $searchKeyword
            );

        }
        $searchKeywordSession = $session->get('search_keyword');
        $searchFilterSession = $session->get('filters');
        $data = $this->getDoctrine()->getRepository(Client::class)->findClientsByFilterAndKeyword($searchFilterSession,$searchKeywordSession);
        $clients = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );
            return $this->render('teleprospecting/index.html.twig', [
                'clients' => $clients,
            ]);*/
        //search without ajax
        $session = $request->getSession();
        $geographicAreas = $this->getDoctrine()->getRepository(GeographicArea::class)->findAll();
        if($request->isMethod('POST')) {
            /*dd($request->request->all());*/
            $session->set('criterias',
                $request->request->all()
            );
        }
        $criterias = $session->get('criterias');
        $payload = $this->getDoctrine()->getRepository(Client::class)->fetchClientsbyFilters($criterias);
        if(count($payload) === 0) {
            $session->set('total_contacts_search_results',
                'nothing'
            );
        } else {
            $session->set('total_contacts_search_results',
                count($payload)
            );
        }
        /*dd($payload);*/


        if($session->get('pagination_value')) {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                $session->get('pagination_value')
            );
        } else {
            $clients = $paginator->paginate(
                $payload,
                $request->query->getInt('page', 1),
                10
            );
        }

        /* $clients = $paginator->paginate(
             $payload,
             $request->query->getInt('page', 1),
             10
         );*/

        return $this->render('all_contacts/index.html.twig', [
            'clients' => $clients,
            'geographic_areas'=> $geographicAreas
        ]);
    }

    // the ajax search is not used anymore
    /**
     * @Route("/search/telepro/searchajax", name="telepro_search_ajax")
     */
    public function teleproSearchAjax(Request $request): Response
    {
        if($request->isXmlHttpRequest()) {
            $keyword = $request->get('keyword');
            $filter = $request->get('filter');
            $telepros = $this->getDoctrine()->getRepository(Client::class)->findClientsByFilterAndKeyword($filter, $keyword);

                $serializer = new Serializer([new ObjectNormalizer()]);
                $result = $serializer->normalize($telepros,'json',['attributes' => ['id','lastName','postalCode']]);
                return new JsonResponse($result);

        }
        return new Response('use Ajax');

    }


}
