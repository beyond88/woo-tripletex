<?php

namespace Woo_Tripletex\Admin;

use Woo_Tripletex\Traits\Singleton;
use Woo_Tripletex\API\TripletexAPI;

class SyncRecordManager
{
    use Singleton;

    public $table;
    public $api; 

    public function __construct()
    {
        global $wpdb;
        $this->api = new TripletexAPI();
        $this->table = $wpdb->prefix . "woo_tripletex";
    }

    public function getSyncRecord( $content_type = null, $column = '*' )
    {
        global $wpdb;
        $condition = "";

        if ($content_type) {
            $condition = "WHERE content_type = '$content_type'";
        }

        return $wpdb->get_results( "SELECT $column FROM $this->table $condition", ARRAY_A);
    }

    /**
     * @param $content_type
     * @param $column
     * @return array
     */
    public function getSyncedIds($content_type, $column)
    {
        $records = $this->getSyncRecord($content_type, $column);

        return array_column($records, $column);
    }

    public function find($wp_id, $content_type = null)
    {
        global $wpdb;

        $condition = "WHERE wp_id = $wp_id";

        if ($content_type) {
            $condition .= " AND content_type = '$content_type'";
        }

        return $wpdb->get_row( "SELECT * FROM $this->table $condition", ARRAY_A);
    }

    public function addSyncRecord( $wp_id, $tripletex_id, $content_type = 'order')
    {
        global $wpdb;

        $existingRecord = $this->find($wp_id, $content_type);

        if ( $existingRecord ) {
            return $existingRecord;
        }

        $wpdb->insert( $this->table, array(
            'wp_id' => $wp_id,
            'tripletex_id' => $tripletex_id,
            'content_type' => $content_type
        ));

        $order = wc_get_order( $wp_id );
        $order_date = date('Y-m-d', strtotime($order->get_date_created()));
        //Generate invoice
        $this->api->put('order/'.$tripletex_id.'/:invoice?invoiceDate='.$order_date.'&sendToCustomer=true&sendType=EMAIL&createOnAccount=NONE&amountOnAccount=0&createBackorder=false&invoiceIdIfIsCreditNote=0', []);
    }
}