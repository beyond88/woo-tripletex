<?php

namespace Woo_Tripletex\Admin\Resources;

use Woo_Tripletex\Admin\Resources\JsonResource;

class ProductResource extends JsonResource {

    /**
     * @inheritDoc
     */
    public function blueprint( $resource ) {
        /** @var $resource \WC_Product */
        return [
            'name'          => $resource->get_name(),
            'number' => $resource->get_sku(),
            'description' => $resource->get_description(),
            'isInactive' => false,
            'costExcludingVatCurrency' =>  floatval( $resource->get_price() ),
            'priceIncludingVatCurrency' => floatval( $resource->get_price() ),
            'isStockItem' => true,
            'weight' => $resource->get_weight(),
            'account' => ['id' => 32287100, 'number' => 1900 ]
        ];
    }
}