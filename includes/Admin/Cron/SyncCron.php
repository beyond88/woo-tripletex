<?php

namespace Woo_Tripletex\Admin\Cron;

use Woo_Tripletex\Traits\Singleton;

class SyncCron
{
    use Singleton;

    public function __construct()
    {
        add_filter( 'cron_schedules', array( $this, 'woo_tripletex_add_schedules') );

        add_action( 'woo_tripletex_sync_orders_schedule', [ $this, 'woo_tripletex_sync_orders_schedule_fn' ] );

        add_action( 'woo_tripletex_sync_tt_to_wp_products_schedule', [ $this, 'woo_tripletex_sync_tt_to_wp_products_schedule_fn' ] );
    }

    public function woo_tripletex_add_schedules()
    {
        if(!isset($schedules["woo_tripletex_sync_5_min"])){
            $schedules["woo_tripletex_sync_5_min"] = array(
                'interval' => 5*60,
                'display' => __('Once every 5 minutes'));
        }

        if(!isset($schedules["woo_tripletex_sync_1_min"])){
            $schedules["woo_tripletex_sync_1_min"] = array(
                'interval' => 60,
                'display' => __('Once every 1 minutes'));
        }

        return $schedules;
    }

    /**
     * @throws \Exception
     * TODO:: use single event
     */
    public function woo_tripletex_sync_orders_schedule_fn()
    {
        WooCommerceToTripletexOrder::instance()->sync();
    }

    /**
     * @throws \Exception
     */
    public function woo_tripletex_sync_tt_to_wp_products_schedule_fn()
    {
        error_log('woo_tripletex_sync_tt_to_wp_products_schedule_fn');
        TripletexToWooCommerceProducts::instance()->sync();
    }

}