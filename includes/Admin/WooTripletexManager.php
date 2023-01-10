<?php

namespace Woo_Tripletex\Admin;

use Woo_Tripletex\API\Handler\Customer;
use Woo_Tripletex\API\Handler\Order;
use Woo_Tripletex\API\Handler\Product;
use Woo_Tripletex\Traits\Singleton;
use Woo_Tripletex\Admin\Resources\CategoryResource;
use Woo_Tripletex\Admin\Resources\OrderResource;
use Woo_Tripletex\Admin\Resources\ProductResource;

class WooTripletexManager {
    use Singleton;

    /**
     * Get currency
     *
     * @return string
     */
    public function currency() {
        return get_woocommerce_currency();
    }

    /**
     * Get store currency symbol
     *
     * @return string
     */
    public function currency_symbol() {
        return get_woocommerce_currency_symbol();
    }

    /**
     * Get products from WooCommerce store
     *
     * @param array $args
     *
     * @return array
     */
    public function products( array $args = [] ) {
        $args = wp_parse_args(
            $args,
            [
                'limit'     => isset( $args['limit'] ) ? intval( $args['limit'] ) : 50,
                'page'      => isset( $args['page'] ) ? intval( $args['page'] ) : 1,
                'status'    => isset( $args['status'] ) ? $args['status'] : null,
                'paginate'  => true,
            ]
        );

        $products = wc_get_products( $args );

        return [
            'data'          => ProductResource::collection( $products->products ),
            'total'         => $products->total,
            'current_page'  => intval( $args['page'] ),
            'total_page'    => $products->max_num_pages,
        ];
    }

    /**
     * Get orders from WooCommerce store
     *
     * @param array $args
     *
     * @return array
     */
    public function orders( array $args = [] ) {
        $args = wp_parse_args(
            $args,
            [
                'limit'         => isset( $args['limit'] ) ? intval( $args['limit'] ) : 50,
                'page'          => isset( $args['page'] ) ? intval( $args['page'] ) : 1,
                'paginate'      => true,
                'status'        => [ 'completed' ],
//                'status'        => [ 'completed', 'refunded', 'on-hold', 'processing', 'cancelled', 'failed' ],
            ]
        );

        if ( isset( $args['after_updated'] ) ) {
            $args['date_modified'] = '>=' . $args['after_updated'];
            unset( $args['after_updated'] );
        }

        $data = wc_get_orders( $args );

        return [
            'data'          => OrderResource::collection( $data->orders ),
            'total'         => $data->total,
            'current_page'  => intval( $args['page'] ),
            'total_page'    => $data->max_num_pages,
        ];
    }

    /**
     * @param $order
     * @throws \Exception
     */
    public function processNewOrder( $order )
    {
        $productHandler = new Product();

        foreach ($order['orderLines'] as $key => $orderLine) {
            $response = $productHandler->getByName($orderLine['product']['name']);
            $response = json_decode($response, true);
            if (isset($response['fullResultSize']) && $response['fullResultSize']) {
                $product = $response['values'][0];
            } else {
                $response = $productHandler->create($orderLine['product']);
                $product = json_decode($response, true)['value'];
            }

            $order['orderLines'][$key]['product'] = $product;

        }

        $customer = new Customer();
        $response = $customer->getByEmail($order['customer']['email']);
        $response = json_decode($response, true);
        if (isset($response['fullResultSize']) && $response['fullResultSize']) {
            $customer = $response['values'][0];
        } else {
            $customer = $customer->create($order['customer']);
            $customer = json_decode($customer, true)['value'];
        }

        $orderHandler = new Order();

        return $orderHandler->setCustomer( $customer )
            ->setOrderLines( $order['orderLines'] )->create();
    }

    /**
     * @param $product
     * @throws \Exception
     */
    public function processTripletexProduct( $product )
    {
        $post_id = wp_insert_post( array(
            'post_title' => $product['name'],
            'post_content' => $product['description'],
            'post_status' =>  'publish',
            'post_type' => "product",
        ) );

        wp_set_object_terms( $post_id, 'simple', 'product_type' );
        update_post_meta( $post_id, '_visibility', 'visible' );
        if (!$product['isInactive']) {
            update_post_meta( $post_id, '_stock_status', 'instock');
        }

        update_post_meta( $post_id, '_regular_price', $product['priceExcludingVatCurrency'] );
        update_post_meta( $post_id, '_weight', $product['weight'] );
        update_post_meta( $post_id, '_sku', $product['number'] ? $product['number'] : 'TRIPLETEX-' . $product['id'] );
        update_post_meta( $post_id, '_price', $product['priceExcludingVatCurrency'] );

        return $post_id;
    }

    /**
     * Is WooCommerce active or not
     *
     *
     * @return bool
     */
    public function is_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Get WooCommerce categories
     *
     * @param array $args
     *
     * @return array
     */
    public function categories( array $args = [] ) {
        $terms = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ]
        );

        return [
            'data' => CategoryResource::collection( $terms ),
        ];
    }

    public function getSyncRecord( $content_type = null, $column = '*' )
    {
        global $wpdb;
        $condition = "";

        $table = $wpdb->prefix . "woo_tripletex";

        if ($content_type) {
            $condition = "WHERE content_type = '$content_type'";
        }

        return $wpdb->get_results( "SELECT $column FROM $table $condition", ARRAY_A);
    }

    public function getSycnedWPIds()
    {
        $records = $this->getSyncRecord('order', 'wp_id');

        return array_column($records, 'wp_id');
    }
}