<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class Currency
{
    private $api;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function index()
    {
        return $this->api->get('currency');
    }
}