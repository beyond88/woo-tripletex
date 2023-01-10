<?php

namespace Woo_Tripletex\Admin\Cron;

use Woo_Tripletex\Admin\SyncRecordManager;
use Woo_Tripletex\Admin\WooTripletexManager;
use Woo_Tripletex\Traits\Singleton;

class WooCommerceToTripletexOrder
{
    use Singleton;

    public $manager;
    public $recordManager;

    public $orderLimit = 10; // 10 Order will be sync per minute
    public $paginateOptionName = 'woo_tripletex_orders_sync_pagination';

    public function __construct()
    {
        $this->manager = WooTripletexManager::instance();
        $this->recordManager = SyncRecordManager::instance();
    }

    /**
     * @throws \Exception
     * TODO:: use single event
     */
    public function sync()
    {
        $args = $this->getPaginate();

        $args['except'] = $this->recordManager->getSyncedIds('order', 'wp_id');

        $orders = $this->manager->orders( $args );

        foreach ($orders['data'] as $order) {
            $insertedOrder = $this->manager->processNewOrder( $order );
            $insertedOrder = json_decode($insertedOrder, true);

            $this->recordManager->addSyncRecord($order['id'], $insertedOrder['value']['id']);
        }

        if (($orders['total_page'] <= $args['page']) || $orders['total'] == 0) {
            // Stop sync loop
            delete_option( $this->paginateOptionName );

            wp_clear_scheduled_hook('woo_tripletex_sync_orders_schedule');
        } else {
            // Continue to next page sync
            update_option( $this->paginateOptionName, [
                'current_page' => ($orders['current_page']+1),
                'total_page' => $orders['total_page'],
                'total_item' => $orders['total'],
            ]);
        }
    }

    private function getPaginate()
    {
        $pagination = get_option( $this->paginateOptionName );
        if (!$pagination) {
            return $this->initialPaginate();
        }

        return [
            'page' => $pagination['current_page'],
            'limit' => $this->orderLimit
        ];
    }

    private function initialPaginate()
    {
        add_option( $this->paginateOptionName, [
            'current_page' => 1,
            'total_page' => 1,
            'total_item' => 0,
        ]);

        return [
            'page' => 1,
            'limit' => $this->orderLimit
        ];
    }
}