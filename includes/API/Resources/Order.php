<?php

namespace Woo_Tripletex\API\Handler;
use Woo_Tripletex\API\TripletexAPI;
use Woo_Tripletex\Helpers;

class Order
{
    private $api;

    public $customer;

    public $orderLines;

    public $currency;

    function __construct()
    {
        $this->api = new TripletexAPI();
    }

    function create()
    {
        if (!$this->customer) {
            throw new \Exception("Customer is required");
        }

        if (!$this->orderLines) {
            throw new \Exception("Order Lines is required");
        }

        $this->setCurrency();

        $formData = [
            'receiverEmail' => $this->customer['email'],
            'deliveryDate' => date("Y-m-d", strtotime('tomorrow')),
            'customer' => $this->customer,
            'orderDate' => date('Y-m-d'),
            'orderLines' => $this->orderLines,
            'currency'   => $this->currency
        ];
        return $this->api->post('order', json_encode($formData) );
    }

    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

    public function setOrderLines($orderLines)
    {
        $this->orderLines = $orderLines;
        return $this;
    }

    private function setCurrency() 
    {
        $helper = new Helpers();
        $this->currency = [
            'id' => $helper->findCurrencyIdByCode( get_woocommerce_currency() ),
            'version' => 0,
            'code' => get_woocommerce_currency(),
            'displayName' => get_woocommerce_currency(),
            'factor' => 1
        ];
    }

    public function getOrders($fromDate, $toDate )
    {
        $initialDate = $fromDate;
        $finalDate = $toDate;
        $orderList = wc_get_orders(array(
            'limit' => -1,
            'type'=> 'shop_order',
            'date_created'=> $initialDate .'...'. $finalDate 
            )
        );
       
        return $orderList;
    }

    function createInvoice($customer = NULL, $orderLines = NULL ){

        if(is_array($customer) && !empty($customer) ){
            $this->customer = $customer;
        }

        if(is_array($orderLines) && !empty($orderLines) ){
            $this->orderLines = $orderLines;
        }

        $invoiceDate    = date("Y-m-d");
        $modifyInvoiceDate = strtotime($invoiceDate."+ 13 days");
        $invoiceDueDate = date("Y-m-d",$modifyInvoiceDate);

        $invoice = [
            "invoiceDate"=> $invoiceDate,
            "invoiceDueDate"=> $invoiceDueDate,
            'customer' => $this->customer,
            'orders' => [
                [
                "customer"      => $this->customer,
                "orderDate"     => $invoiceDate,
                "deliveryDate"  => $invoiceDate,
                "orderLines"    => [
                        [
                            'product' => $this->orderLines[0]['product']
                        ]                             
                    ]
                ]
            ]
        ];

        return $this->api->post('invoice?sendToCustomer=true', json_encode($invoice) );
    }
}