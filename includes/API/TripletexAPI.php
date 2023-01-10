<?php

namespace Woo_Tripletex\API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\TransferStats;

class TripletexAPI
{
    public $isEnable;

    // Test API: https://api.tripletex.io/v2-docs
    public $sessionToken;
    public $baseUrl = 'https://api.tripletex.io'; // Default test api url

    public function __construct()
    {
//        To read from env: $_ENV['TRIPLETEX_BASE_URL']
        $this->baseUrl = get_option('wc_settings_tab_woo_tripletex_base_url');
        $this->sessionToken = get_option('wc_settings_tab_woo_tripletex_session_token');
        $this->isEnable = get_option('wc_settings_tab_woo_tripletex_is_enable');

        if ($this->isEnable && !$this->sessionToken) {
            $this->generateSessionToken();
        }
    }

    private function client()
    {
        // Use that client by requesting path without '/' at start. Otherwise, the url will be example.com/request_url
        // For more: https://github.com/guzzle/guzzle/issues/148#issuecomment-425149779

        return new Client([
            'base_uri' => $this->baseUrl . '/v2/',
            'timeout'  => 100,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function generateSessionToken()
    {
        $token = null;

        try {
            $response = $this->client()->put('token/session/:create', [
                'query' => [
                    'consumerToken' =>  get_option("wc_settings_tab_woo_tripletex_consumer_token"),
                    'employeeToken' =>  get_option("wc_settings_tab_woo_tripletex_employee_token"),
                    'expirationDate' => date('Y-m-d', strtotime('+1 year')),
                ]
            ]);

            if( $response->getStatusCode() == 200 ){
                $res= json_decode($response->getBody(), true);
                if (isset($res['value']['token'])) {
                    $token = $res['value']['token'];
                }

                update_option('wc_settings_tab_woo_tripletex_session_token', $token);
            }

            $this->sessionToken = $token;

            return $token;
        } catch (BadResponseException $ex) {
            return false;
        }
    }

    /*
     * Basic AUTH where username is company/customer id for proxy use (or 0 for default), password should be set to the session token
     */
    public function get($url, array $params = [])
    {
        try {
            $response = $this->client()->get($url, [
                'auth'      => [ 0, $this->sessionToken ],
                'query'     => $params,
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);

            return $response->getBody();
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            error_log('====================API ERROR==================');
            error_log('URL: ' . $url);
            error_log((string) $response->getBody());
            die();
            var_dump((string) $response->getBody());
        }
    }

    public function post($url, $formData)
    {
        try {
            $response = $this->client()->post($url, [
                'auth'      => [ 0, $this->sessionToken ],
                'body'     => $formData,
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);

            return $response->getBody();
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            error_log('====================API ERROR==================');
            error_log('URL: ' . $url);
            error_log((string) $response->getBody());
            die();
            var_dump((string) $response->getBody());
        }
    }

    public function put($url, $formData)
    {

        try {
            $response = $this->client()->put($url, [
                'auth'      => [ 0, $this->sessionToken ],
                // 'json'     => $formData,
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);

            return $response->getBody();
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            error_log('====================API ERROR==================');
            error_log('URL: ' . $url);
            error_log((string) $response->getBody());
            var_dump((string) $response->getBody());
            die();

        }
    }
}