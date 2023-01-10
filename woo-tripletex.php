<?php
/**
 * Plugin Name: Woo-Tripletex
 * Description: A WooCommerce plugin for Tripletex.
 * Plugin URI: https://github.com/beyond88/woo-tripletex
 * Author: Mohiuddin Abdul Kader
 * Author URI: https://github.com/beyond88
 * Version: 1.0.0
 * Text Domain:       woo-tripletex
 * Domain Path:       /languages
 * Requires PHP:      5.6
 * Requires at least: 4.4
 * Tested up to:      5.7
 *
 * WC requires at least: 3.1
 * WC tested up to:   5.1.0
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

use Woo_Tripletex\Admin\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
/**
 * The main plugin class
 */
final class WooTripletex {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.0';

    /**
     * Class constructor
     */
    private function __construct() {
        //REMOVE THIS AFTER DEV
        error_reporting(E_ALL ^ E_DEPRECATED);

        $this->define_constants();

        register_activation_hook( WOOTRIPLETEX_FILE, [ $this, 'activate' ] );

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Initializes a singleton instance
     *
     * @return \WooTripletex
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'WOOTRIPLETEX_VERSION', self::version );
        define( 'WOOTRIPLETEX_FILE', __FILE__ );
        define( 'WOOTRIPLETEX_PATH', __DIR__ );
        define( 'WOOTRIPLETEX_URL', plugins_url( '', WOOTRIPLETEX_FILE ) );
        define( 'WOOTRIPLETEX_ASSETS', WOOTRIPLETEX_URL . '/assets' );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {
        new Woo_Tripletex\Assets();
        new Woo_Tripletex\Admin\Cron\SyncCron();

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            new Woo_Tripletex\Ajax();
        }

        if ( is_admin() ) {
            new Woo_Tripletex\Admin();
        } else {
            //new Woo_Tripletex\Frontend();
        }

        if ( get_option('wc_settings_tab_woo_tripletex_is_enable') == 'yes' ) {
            WooCommerce::instance()->register_hooks();
        }

        new Woo_Tripletex\API();
    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate() {
        $installer = new Woo_Tripletex\Installer();
        $installer->run();
    }
}

/**
 * Initializes the main plugin
 */
function WooTripletex() {
    return WooTripletex::init();
}

// kick-off the plugin
WooTripletex();