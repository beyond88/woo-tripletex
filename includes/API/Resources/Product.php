<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class Product
{
    private $api;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function index( $request )
    {
        return $this->api->get('product', $request );
    }

    function create( $request )
    {
        return $this->api->post('product', json_encode($request) );
    }

    function getByName( string $name )
    {
        $request = [
            'name' => $name,
            'from' => '0',
            'count' => '1',
        ];

        return $this->api->get('product', $request );
    }
}