<?php

namespace App\Controller;

use App\Services\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google_Client;
use Google_Service_Calendar;
use http\Exception;
use GuzzleHttp\Client;

class GoogleCalendarController extends AbstractController
{
    /**
     * @Route("/dashboard/google/calendar", name="google_calendar")
     */

    public function index(Request $request, GoogleCalendarService $googleCalendarService): Response
    {
        $authCode = $request->get('authcode');
        $email = "oussema.sferi@gmail.com";
        $authLink = $googleCalendarService->generateGoogleApiToken($email);
        $events = $googleCalendarService->getEventsByEmail($email);
        return $this->render('google_calendar/index.html.twig', [
            'service' => $service,
            'client' => $client,
        ]);
    }
}
