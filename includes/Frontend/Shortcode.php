<?php

namespace Woo_Tripletex\Frontend;

/**
 * Shortcode handler class
 */
class Shortcode {

    /**
     * Initializes the class
     */
    function __construct() {
        add_shortcode( 'woo-tripletex', [ $this, 'render_shortcode' ] );
    }

    /**
     * Shortcode handler class
     *
     * @param  array $atts
     * @param  string $content
     *
     * @return string
     */
    public function render_shortcode( $atts, $content = '' ) {
        wp_enqueue_script( 'woo-tripletex-script' );
        wp_enqueue_style( 'woo-tripletex-style' );

        return '<div class="woo-tripletex-shortcode">Hello from Shortcode</div>';
    }
}
