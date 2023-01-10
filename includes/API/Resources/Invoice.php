<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class Invoice
{
    private $api;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function index( $request )
    {
        //return $this->api->get('product', $request );
    }

    function create( $request )
    {
       return $this->api->post('invoice?sendToCustomer=true', json_encode($request) );
    }

    function put( $id,  $request )
    {
       return $this->api->put('order/'.$id.'/:invoice?invoiceDate=2022-10-31&sendToCustomer=true&sendType=EMAIL&createOnAccount=NONE', json_encode($request) );
    }
}