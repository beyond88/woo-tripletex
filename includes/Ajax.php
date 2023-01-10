<?php

namespace Woo_Tripletex;
use Woo_Tripletex\API\Handler\Order;
use Woo_Tripletex\API\Handler\Customer;
use Woo_Tripletex\Helpers;
use Woo_Tripletex\Admin\Resources\OrderResource;
use Woo_Tripletex\API\Handler\Voucher;

use Woo_Tripletex\Admin\Cron\WooCommerceToTripletexOrder;

/**
 * Ajax handler class
 */
class Ajax
{
    /**
     * Class constructor
     */
    function __construct() {
        //        WP TO TRIPLETEX
        add_action( 'wp_ajax_sync_order', array( $this, 'sync_order') );
        add_action( 'wp_ajax_nopriv_sync_order', array( $this, 'sync_order') );

        //        TRIPLETEX TO WP
        add_action( 'wp_ajax_sync_tt_to_wp_products', array( $this, 'sync_tt_to_wp_products') );
        add_action( 'wp_ajax_nopriv_sync_tt_to_wp_products', array( $this, 'sync_tt_to_wp_products') );

        //        SYNC STATUS
        add_action( 'wp_ajax_sync_status', array( $this, 'sync_status') );
        add_action( 'wp_ajax_nopriv_sync_status', array( $this, 'sync_status') );

        add_action( 'wp_ajax_stop_sync_order', array( $this, 'stop_sync_order') );
        add_action( 'wp_ajax_nopriv_stop_sync_order', array( $this, 'stop_sync_order') );

        add_action( 'wp_ajax_stop_sync_product', array( $this, 'stop_sync_product') );
        add_action( 'wp_ajax_nopriv_stop_sync_product', array( $this, 'stop_sync_product') );

        add_action( 'wp_ajax_monthly_sales_report', array( $this, 'monthly_sales_report') );
        add_action( 'wp_ajax_nopriv_monthly_sales_report', array( $this, 'monthly_sales_report') );
        
    }

    public function sync_tt_to_wp_products()
    {
        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');

        if( ! empty( $_POST )){
            if( !wp_next_scheduled( 'woo_tripletex_sync_tt_to_wp_products_schedule' ) ) {
                wp_schedule_event( time(), 'woo_tripletex_sync_1_min', 'woo_tripletex_sync_tt_to_wp_products_schedule' );
            }

            wp_send_json_success(
                "<p class='wt_success'>" . __('Products sync in progress', 'woo-tripletex') . "</p>",
                200
            );
        } else {
            wp_send_json_error( array(
                __('Something went wrong', 'woo-tripletex') ) ,
                200
            );
        }

        wp_die();
    }

    public function sync_order()
    {
        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');

        if( ! empty( $_POST )){
            if( !wp_next_scheduled( 'woo_tripletex_sync_orders_schedule' ) ) {
                wp_schedule_event( time(), 'woo_tripletex_sync_1_min', 'woo_tripletex_sync_orders_schedule' );
            }

            WooCommerceToTripletexOrder::instance()->sync();

            wp_send_json_success(
                "<p class='wt_success'>" . __('Order sync in progress', 'woo-tripletex') . "</p>",
                200
            );
        } else {
            wp_send_json_error( array(
                __('Something went wrong', 'woo-tripletex') ) ,
                200
            );
        }

        wp_die();
    }

    public function sync_status()
    {
        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');

        if( ! empty( $_POST )){
            $sync_status = get_option('woo_tripletex_orders_sync_pagination');
            $schedule = wp_get_schedule('woo_tripletex_sync_orders_schedule');
            if ($sync_status || $schedule) {
                wp_send_json_success(
                    [ 'data' => $sync_status, 'selector' => '#sync_order_message' ],
                    200
                );
            }

            $sync_status = get_option('woo_tripletex_tt_to_wp_products_sync_pagination');
            $schedule = wp_get_schedule('woo_tripletex_sync_tt_to_wp_products_schedule');
            if ($sync_status || $schedule) {
                wp_send_json_success(
                    [ 'data' => $sync_status, 'selector' => '#sync_tt_to_wp_products_message' ],
                    200
                );
            }

            wp_send_json_success([ 'data' => false ], 200);
        } else {
            wp_send_json_error( array(
                __('Something went wrong', 'woo-tripletex') ) ,
                402
            );
        }

        wp_die();
    }


    public function stop_sync_order()
    {
        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');

        delete_option( 'woo_tripletex_orders_sync_pagination' );
        wp_clear_scheduled_hook('woo_tripletex_sync_orders_schedule');

        wp_send_json_success(
            "",
            200
        );
    }

    public function stop_sync_product()
    {
        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');

        delete_option( 'woo_tripletex_tt_to_wp_products_sync_pagination' );
        wp_clear_scheduled_hook('woo_tripletex_sync_tt_to_wp_products_schedule');

        wp_send_json_success(
            "",
            200
        );
    }

    public function monthly_sales_report()
    {

        check_ajax_referer( 'woo-tripletex-admin-nonce', 'security');
        $queryDate = $_POST['queryDate'];
        $voucherInfo = [];
        $customerId = '';
        $accountId = '';
        $vatType = '';
        $voucherDate = date("Y-m-d");
        $voucherInfo['date'] = $voucherDate;
        $voucherInfo['description'] = '';
        $voucherInfo['postings'] = [];
        $voucherId = 0;
        
        if( empty( $queryDate ) ) {
            wp_send_json_error( array(
                __('Please select a date', 'woo-tripletex') ) ,
                200
            );
        }

        $date = explode('-', $queryDate);
        $startDate = $date[0];
        $endDate = $date[1];

        //Tripletex date format YYYY-mm-dd
        $startDate = date("Y-m-d", strtotime($date[0]));
        $endDate = date("Y-m-d", strtotime($date[1]));

        $orderHandler = new Order();
        $orderList = $orderHandler->getOrders($startDate, $endDate);
        
        if( ! empty($orderList) ) {

            $customer = new Customer();
            $helper = new Helpers();
            $accountInfo = $helper->findAccountInfoByNumber(3000); // get account id by account number
            $accountId = $accountInfo['id'];
            $vatType = $accountInfo['vatType']['id'];

            $rowNumber = 1;
            $i=0;
            foreach( $orderList as $order ) {
                            
                $orderDate = date("Y-m-d", strtotime($order->get_date_created()));
                $orderDetails = $order->payment_method_title .' - '. $order->get_status();

                $customerEmail = $order->get_billing_email();
                $response = $customer->getByEmail($customerEmail);
                $response = json_decode($response, true);

                if (isset($response['fullResultSize']) && $response['fullResultSize']) {
                    $customerData = $response['values'][0];
                } else {

                    $orderResource = new class extends OrderResource {
                        public function customer($order) {
                          return parent::customer($order);
                        }
                    };
                    
                    $customerInfo = $orderResource->customer($order);
                    $customerData = $customer->create($customerInfo);
                    $customerData = json_decode($customerData, true)['value'];
                }

                if( !empty($customerData) ) {
                    $customerId = $customerData['id']; // customer id if exists;
                }

                $data = [];
                $data['date'] =  $orderDate;
                $data['description'] =  $orderDetails;
                $data['account'] =  [
                    'id' => $accountId,
                    'vatType' => [
                        'id' => $vatType
                    ]
                ];
                $data['customer'] = [
                    'id' => $customerId
                ];
                $data['amountGross'] =  (float)$order->get_total();
                $data['amountGrossCurrency'] =  (float)$order->get_total();
                $data['row'] = $rowNumber;
                $data['vatType'] = [
                    'id' => $vatType
                ];

                array_push($voucherInfo['postings'], $data);
                
                $data['amountGross'] =  (float)-$order->get_total();
                $data['amountGrossCurrency'] =  (float)-$order->get_total();
                $rowCount = $rowNumber+1;
                $rowNumber = $rowCount;
                $data['row'] = $rowCount;   
                $data['vatType'] = [
                    'id' => $vatType
                ];             

                array_push($voucherInfo['postings'], $data);

                $rowNumber++;
                $i++;
                
            }

            if( !empty($voucherInfo) ) {
                $voucher = new Voucher();
                $response = $voucher->create($voucherInfo);
                if( ! empty( $response->__toString() ) ){ 

                    $res = $response->__toString();
                    $res_decoded = json_decode($res);
                    $voucherId = $res_decoded->value->id;             
                    $msg = sprintf( __('The voucher {id: %s} has been generated successfully!', 'woo-tripletex'), $voucherId );
                    wp_send_json([
                        $msg
                    ]);

                } else {
                    wp_send_json_error([
                        __('Something went wrong!', 'woo-tripletex'),
                    ]);
                }

            }
        } else {
            wp_send_json_error([
                __('Order not found on the requested date!', 'woo-tripletex'),
            ]);
        }
    }
}
