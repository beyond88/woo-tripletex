<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class Customer
{
    private $api;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function create( $customerData )
    {
        return $this->api->post('customer', json_encode($customerData) );
    }

    function getByEmail( string $email )
    {
        $request = [
            'email' => $email,
            'from' => 0,
            'count' => 1,
        ];

        return $this->api->get('customer', $request );
    }
}