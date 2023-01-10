<?php

namespace Woo_Tripletex;

use Woo_Tripletex\Admin\WooTripletexSettings;

/**
 * The admin class
 */
class Admin {

    /**
     * Initialize the class
     */
    function __construct() {
        WooTripletexSettings::instance()->init();
    }

    /**
     * Dispatch and bind actions
     *
     * @return void
     */
    public function dispatch_actions( $main ) {

    }
}