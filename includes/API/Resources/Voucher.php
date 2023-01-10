<?php

namespace Woo_Tripletex\API\Handler;

use Woo_Tripletex\API\TripletexAPI;

class Voucher
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
       return $this->api->post('ledger/voucher/?sendToLedger=true', json_encode($request) );
    }
}