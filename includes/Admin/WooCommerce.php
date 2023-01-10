<?php

namespace Woo_Tripletex\Admin;

use Woo_Tripletex\Admin\Resources\OrderResource;
use Woo_Tripletex\Traits\Singleton;

class WooCommerce {
    use Singleton;

    public $manager;

    public function __construct()
    {

        $this->manager = WooTripletexManager::instance();
    }
    /**
     * Register post update hooks
     */
    public function register_hooks() {
        add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order' ], 10, 4 );
        add_action( 'woocommerce_order_refunded', [ $this, 'create_order_refund' ], 10, 2 );
        add_action( 'woocommerce_refund_deleted', [ $this, 'delete_order_refund' ], 10, 2 );
        add_action( 'after_delete_post', [ $this, 'delete_order' ], 10, 2 );
    }

    /**
     * Handling order
     *
     * @param $order_id
     * @param $status_from
     * @param $status_to
     * @param $order
     * @throws \Exception
     */
    public function handle_order( $order_id, $status_from, $status_to, $order )
    {
        // Pass only completed orders
        if ($order->get_status() !== 'completed') {
            return;
        }

        $recordManager = SyncRecordManager::instance();
        $existingRecord = $recordManager->find($order->get_id(), 'order');

        if ($existingRecord) {
            return;
        }

        $order = OrderResource::single( $order );

        $insertedOrder = $this->manager->processNewOrder($order);
        $insertedOrder = json_decode($insertedOrder, true);

        $recordManager->addSyncRecord($order['id'], $insertedOrder['value']['id']);
    }

    /**
     * Create a new refund
     *
     * @param $order_id
     * @param $refund_id
     */
    public function create_order_refund( $order_id, $refund_id ) {

    }

    /**
     * Delete Refund/Delete order
     *
     * @param $refund_id
     * @param $order_id
     */
    public function delete_order_refund( $refund_id, $order_id ) {

    }

    /**
     * Delete order
     *
     * @param $order_id
     * @param \WP_Post $post
     */
    public function delete_order( $order_id, \WP_Post $post ) {
        if ( $post->post_type !== 'shop_order' ) {
            return;
        }

        // further step
    }
}