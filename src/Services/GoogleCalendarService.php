<?php

namespace App\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GoogleCalendarService
{
    private $session;
    private $container;

    public function __construct(SessionInterface $session, ContainerInterface $container) {
        $this->session = $session;
        $this->container = $container;
    }

    public function generateGoogleApiToken($email, $id) {
        $root_dir = $this->container->getParameter('kernel.project_dir') . '/public';
        $client = new \Google_Client();
        $client->setApplicationName("Novuus CRM");
        $client->addScope(\Google_Service_Calendar::CALENDAR);
        $client->setAuthConfig('client_secret.json ');
        $client->setAccessType('offline');
        $client->setState($id);
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . "/dashboard/appointments/showcalendar";
        $client->setRedirectUri($redirect_uri);
        $guzzleClient = new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
        $client->setHttpClient($guzzleClient);
        $authUrl = '';

        $tokenPath = $root_dir . '/tokens/' . $email . 'token.json';
        $authToken = $this->session->get('authToken');
        if (!empty($authToken)) {
            $client->setAccessToken($authToken);
        }
        /*if(file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }*/


        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $this->session->set('authToken', $client->getRefreshToken());
            } else {
                $this->session->remove('authcode');
                $this->session->remove('authToken');
                $authUrl = $client->createAuthUrl();
            }
        }
        return $authUrl;
    }

    public function insertGoogleApiToken($email, $id, $authCode ="") {
        /*dd("t");*/
        $root_dir = $this->container->getParameter('kernel.project_dir') . '/public';
        if(empty($authCode)) return false;

        $tokenPath = $root_dir . '/tokens/' . $email . 'token.json';

        $client = new \Google_Client();
        $client->setApplicationName("Novuus CRM");
        $client->addScope(\Google_Service_Calendar::CALENDAR);
        $client->setAuthConfig('client_secret.json ');
        $client->setAccessType('offline');
        $client->setState($id);
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . "/dashboard/appointments/showcalendar";
        $client->setRedirectUri($redirect_uri);
        $guzzleClient = new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
        $client->setHttpClient($guzzleClient);
        $authUrl = '';


        /*dd($client->fetchAccessTokenWithAuthCode($authCode));*/
        /*$authToken = $this->session->get('authToken');*/
        $authToken = $client->fetchAccessTokenWithAuthCode($authCode);
        /*dd($accessToken);*/
        $client->setAccessToken($authToken);
        $this->session->set('authToken', $client->getAccessToken());

        if (array_key_exists('error', $authToken)) {
            return false;
        }


        /*if (empty($authToken)) {
            $this->session->set('authToken', $client->getAccessToken());
        }*/
        /*if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));*/
        return true;
    }

    public function getEventsByEmail($email, $id): array
    {
        $client = $this->getClient($email, $id);
        if(!$client) return [];

        $service = new \Google_Service_Calendar($client);
        // Print the next 10 events on the user's calendar.
        $calendarId = 'primary';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            /*'timeMin' => date('c')*/
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();
        return $events;
    }

    public function getClient($email, $id) {
        $client = new \Google_Client();
        $client->setApplicationName("Novuus CRM");
        $client->addScope(\Google_Service_Calendar::CALENDAR);
        $client->setAuthConfig('client_secret.json ');
        $client->setAccessType('offline');
        $client->setState($id);
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . "/dashboard/appointments/showcalendar";
        $client->setRedirectUri($redirect_uri);
        $guzzleClient = new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => false]]);
        $client->setHttpClient($guzzleClient);

        $tokenPath = '/tokens/' . $email . 'token.json';
        $authToken = $this->session->get('authToken');
        if (!empty($authToken)) {
            $client->setAccessToken($authToken);
        }
        /*if(file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }*/
        return $client;
    }

}