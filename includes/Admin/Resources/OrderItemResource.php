<?php

namespace Woo_Tripletex\Admin\Resources;

use Woo_Tripletex\Admin\Resources\JsonResource;

class OrderItemResource extends JsonResource {

    protected $reset_keys = true;

    /**
     * @inheritDoc
     */
    public function blueprint( $order_item ) {
        /** @var \WC_Order_Item_Product $order_item */
        $product = $order_item->get_product();

        return [
            'product' => $product ? ProductResource::single($product) : null,
            'isSubscription' => false,
            'count' => $order_item->get_quantity(),
            'unitPriceExcludingVatCurrency' => floatval( $order_item->get_total() / $order_item->get_quantity() ),
            'discount' => 0,
            'vatType' => [
                'percentage' => 0,
                'deductionPercentage' => 0,
            ]
        ];
    }
}
