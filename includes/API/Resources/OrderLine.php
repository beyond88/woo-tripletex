<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class OrderLine
{
    private $api;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function create( $request )
    {
        return $this->api->post('orderline', json_encode($request) );
    }
}