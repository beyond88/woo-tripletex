<?php

namespace Woo_Tripletex\Admin\Cron;

use Woo_Tripletex\Admin\SyncRecordManager;
use Woo_Tripletex\Admin\WooTripletexManager;
use Woo_Tripletex\API\Handler\Product;
use Woo_Tripletex\Traits\Singleton;

class TripletexToWooCommerceProducts
{
    use Singleton;

    public $manager;
    public $recordManager;

    public $limit = 10; // 10 Order will be sync per minute
    public $paginateOptionName = 'woo_tripletex_tt_to_wp_products_sync_pagination';

    public function __construct()
    {
        $this->manager = WooTripletexManager::instance();
        $this->recordManager = SyncRecordManager::instance();
    }


    /**
     * @throws \Exception
     */
    public function sync()
    {
        $args = $this->getPaginate();

//        get synced id to skip existing records
        $syncedIds = $this->recordManager->getSyncedIds('tripletex_product', 'tripletex_id');

        $productHandler = new Product();
        $response = $productHandler->index($args);
        $response = json_decode($response, true);

        if (isset($response['fullResultSize']) && $response['fullResultSize']) {
            foreach ($response['values'] as $product) {
                if (in_array($product['id'], $syncedIds)) {
                    continue;
                }

                $product_id = $this->manager->processTripletexProduct( $product );

                $this->recordManager->addSyncRecord($product_id, $product['id'], 'tripletex_product');
            }

            // Continue to next page sync
            update_option( $this->paginateOptionName, [
                'from' => ($response['from'] +  $this->limit),
                'count' => $this->limit
            ]);

        } else {
            // Stop sync loop
            delete_option( $this->paginateOptionName );

            wp_clear_scheduled_hook('woo_tripletex_sync_tt_to_wp_products_schedule');
        }
    }

    private function getPaginate()
    {
        $pagination = get_option( $this->paginateOptionName );
        if ($pagination) {
            return $pagination;
        }

        return $this->initialPaginate();
    }

    private function initialPaginate()
    {
        add_option( $this->paginateOptionName, [
            'from' => 0,
            'count' => $this->limit
        ]);

        return [
            'from' => 0,
            'count' => $this->limit
        ];
    }
}