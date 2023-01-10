<?php

namespace Woo_Tripletex\Admin\Resources;

use Woo_Tripletex\Admin\Resources\JsonResource;

class CategoryResource extends JsonResource {
    /**
     * @inerhitDoc
     */
    protected $reset_keys = true;

    /**
     * @param \WP_Term $resource
     *
     * @return mixed|void
     */
    public function blueprint( $resource ) {
        return [
            'id' => $resource->term_id,
            'name' => $resource->name,
        ];
    }
}
