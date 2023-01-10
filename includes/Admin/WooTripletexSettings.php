<?php

namespace Woo_Tripletex\Admin;
use Woo_Tripletex\API\TripletexAPI;
use Woo_Tripletex\Traits\Singleton;

class WooTripletexSettings {

    use Singleton;
    protected $id = 'settings_tab_woo_tripletex';

	/* Bootstraps the class and hooks required actions & filters.
     *
     */
    public function init()
    {
        add_filter( 'woocommerce_settings_tabs_array', [ $this, 'addSettingsTab' ], 50 );
        add_action( 'woocommerce_settings_tabs_' . $this->id, [ $this, 'settingsTab' ] );
        add_action( 'woocommerce_update_options_' . $this->id, [ $this, 'updateSettings' ] );
        add_action( 'woocommerce_sections_' . $this->id, [ $this, 'output_sections' ] );
        add_action('woocommerce_admin_field_sync_table', [ $this, 'print_sync_table' ] );
        add_action('woocommerce_admin_field_send_report_btn', [ $this, 'print_send_report_btn' ] );        

    }

    public function get_sections() {
        $sections = $this->get_own_sections();
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }

    protected function get_own_sections() {

        $isEnable = get_option('wc_settings_tab_woo_tripletex_is_enable');
        
        $tabs = [
			'general' => __( 'General', 'woocommerce' )
        ];

        if( $isEnable == 'yes' ) {
            $tabs['sync'] = __( 'Sync', 'woocommerce' );
            $tabs['report'] = __( 'Reports', 'woocommerce' );
        }
                
        return $tabs;
    }

    public function output_sections() {
        global $current_section;

        $sections = $this->get_sections();

        if ( empty( $sections ) || 1 === count( $sections ) ) {
            return;
        }

        echo '<ul class="subsubsub">';

        $array_keys = array_keys( $sections );

        foreach ( $sections as $id => $label ) {
            $url       = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) );
            $class     = ( $current_section === $id ? 'current' : '' );
            $separator = ( end( $array_keys ) === $id ? '' : '|' );
            $text      = esc_html( $label );
            echo "<li><a href='$url' class='$class'>$text</a> $separator </li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo '</ul><br class="clear" />';
    }

    /**
		 * Output the HTML for the settings.
		 */
		public function output() {
			global $current_section;

			// We can't use "get_settings_for_section" here
			// for compatibility with derived classes overriding "get_settings".
			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::output_fields( $settings );
		}
    
    /* Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function addSettingsTab( $settings_tabs )
    {
        $settings_tabs['settings_tab_woo_tripletex'] = __( 'Tripletex', 'woo-tripletex' );
        return $settings_tabs;
    }


    /* Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::getSettings()
     */
    public function settingsTab()
    {
        global $current_section;

        if ( wp_cache_get('invalid_tripletex_tokens') ) {
            $this->notConnectedWarning();
        }

        $settings = $this->getSettings( $current_section ); 
        woocommerce_admin_fields( $settings );
    }

    private function notConnectedWarning()
    {
        $class = 'notice notice-error';
        $message = __( 'Tripletex not connected!. Please use valid tokens to connect.', 'woo-tripletex' );
        $doc_link = __( 'To know more: ', 'woo-tripletex' ) .'<a target="_blank" href="https://tripletex.no/v2-docs/">'. __( 'Visit API Doc', 'woo-tripletex' ) .'</a>';

        printf( '<div class="%1$s"><p>%2$s</p>'. $doc_link .'</div>',  esc_attr( $class ), esc_html( $message ) );
    }


    /* Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::getSettings()
     */
    public function updateSettings()
    {
        woocommerce_update_options( self::getSettings() );

        $api = new TripletexAPI();

        $token = $api->generateSessionToken();

        if( $token ){
            wp_cache_delete('invalid_tripletex_tokens');
        } else {
            wp_cache_add('invalid_tripletex_tokens', 'true');
        }
    }


    /* Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *  
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     * 'text|password|textarea|checkbox|select|multiselect',
     */
    public function getSettings( $section = null )
    {

        switch( $section ){

            case 'general' :
                $settings = array(
                    'section_title' => array(
                        'name'     => __( 'Tripletex integration', 'woo-tripletex' ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_settings_tab_woo_tripletex_title_general'
                    ),
                    'is_enable' => array(
                        'name' => __( 'Enable/Disable sync', 'woo-tripletex' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Check to enable', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_is_enable'
                    ),
                    'base_url' => array(
                        'name' => __( 'Base Url', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc_tip' => __( 'For development mode: https://api.tripletex.io', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'API base URL. (<a href="%s" target="_blank">https://tripletex.no</a>).', 'woo-tripletex' ), 'https://tripletex.no' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_base_url'
                    ),
                    'consumer_token' => array(
                        'name' => __( 'Consumer token', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_consumer_token'
                    ),
                    'employee_token' => array(
                        'name' => __( 'Employee token', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_employee_token'
                    ),
                    'income_account' => array(
                        'name' => __( 'Income account', 'woo-tripletex' ),
                        'type' => 'text',
                        'default' => '3000',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'Sales revenue, taxable - 3000', 'woo-tripletex' ), '' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_income_account'
                    ),
                    'payment_type' => array(
                        'name' => __( 'Payment type', 'woo-tripletex' ),
                        'type' => 'text',
                        'default' => '1900',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'Cash NOK - 1900', 'woo-tripletex' ), '' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_payment_type'
                    ),
                    'section_end' => array(
                         'type' => 'sectionend',
                         'id' => 'wc_settings_tab_woo_tripletex_end-general'
                    )
                );

            break;
            case 'sync':
                $settings = array(
                    'section_sync_title' => array(
                        'name'     => __( 'Sync settings for existing data', 'woo-tripletex' ),
                        'type'     => 'title',
                        'desc'     => __( 'Send existing customer, product, orders to tripletex', 'woo-tripletex' ),
                        'id'       => 'wc_settings_tab_woo_tripletex_section_title_sync'
                    ),

                    'section_sync_table' => array(
                        'name' => __( '', 'woo-tripletex' ),
                        'type' => 'sync_table',
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_sync_table'
                    ),                    

                    'section_end' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_settings_tab_woo_tripletex_section_end-sync'
                    )
                );
            break;
            case 'report':
                $settings = array(
                    'section_title' => array(
                        'name'     => __( 'Generate sales report', 'woo-tripletex' ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_settings_tab_woo_tripletex_section_title_report'
                    ),
                    'report_type' => array(
                        'name' => __( 'Report', 'woo-tripletex' ),
                        'type' => 'select',
                        'options' => [
                            'this_month' => __( 'This month', 'woo-tripletex' ),
                            'this_year' => __( 'This year', 'woo-tripletex' ),
                            'custom_date' => __( 'Custom date', 'woo-tripletex' ),
                        ],
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_report_type'
                    ),
                    'custom_date_range' => array(
                        'name' => __( '', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc'        => '',
                        'default' => '',
                        'id'   => 'wc_settings_tab_woo_tripletex_custom_date_range',
                        'class'=> 'custom_date_range',
                    ),                    
                                    
                    'send_report_btn' => array(
                        'name' => __( '', 'woo-tripletex' ),
                        'type' => 'send_report_btn',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_send_report_btn'
                    ),
                    'section_end' => array(
                         'type' => 'sectionend',
                         'id' => 'wc_settings_tab_woo_tripletex_section_end-report'
                    )
                );

            break;
            default:
                $settings = array(
                    'section_title' => array(
                        'name'     => __( 'Tripletex integration', 'woo-tripletex' ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_settings_tab_woo_tripletex_title_general'
                    ),
                    'is_enable' => array(
                        'name' => __( 'Enable/Disable sync', 'woo-tripletex' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Check to enable', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_is_enable'
                    ),
                    'base_url' => array(
                        'name' => __( 'Base Url', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc_tip' => __( 'For development mode: https://api.tripletex.io', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'API base URL. (<a href="%s" target="_blank">https://tripletex.no</a>).', 'woo-tripletex' ), 'https://tripletex.no' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_base_url'
                    ),
                    'consumer_token' => array(
                        'name' => __( 'Consumer token', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_consumer_token'
                    ),
                    'employee_token' => array(
                        'name' => __( 'Employee token', 'woo-tripletex' ),
                        'type' => 'text',
                        'desc' => __( '', 'woo-tripletex' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_employee_token'
                    ),
                    'income_account' => array(
                        'name' => __( 'Income account', 'woo-tripletex' ),
                        'type' => 'text',
                        'default' => '3000',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'Sales revenue, taxable - 3000', 'woo-tripletex' ), '' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_income_account'
                    ),
                    'payment_type' => array(
                        'name' => __( 'Payment type', 'woo-tripletex' ),
                        'type' => 'text',
                        'default' => '1900',
                        'desc_tip' => __( '', 'woo-tripletex' ),
                        'desc'        => sprintf( __( 'Cash NOK - 1900', 'woo-tripletex' ), '' ),
                        'id'   => 'wc_settings_tab_woo_tripletex_payment_type'
                    ),
                    'section_end' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_settings_tab_woo_tripletex_end-general'
                    )
                );             

        }       

        // Set default
        if (!get_option('wc_settings_tab_woo_tripletex_is_enable')) {
            update_option('wc_settings_tab_woo_tripletex_is_enable', 'yes');
        }

        if (!get_option('wc_settings_tab_woo_tripletex_base_url')) {
            update_option('wc_settings_tab_woo_tripletex_base_url', 'https://api.tripletex.io');
        }

        return apply_filters( 'wc_settings_tab_woo_tripletex', $settings, $section );
    }

    private function customRowTableHeader() 
    {
        return '<table class="form-table">
            <tbody>';
    }

    private function customRowTableFooter() 
    {
        return '</tbody></table>';
    }

    private function customRows($rows) 
    {
        $table = $this->customRowTableHeader();
        foreach ($rows as $row) {
            $table .= '<tr valign="top">
                    <th scope="row" class="titledesc" style="width: 30%">'. $row['th'] .'</th>
                    <td class="forminp forminp-text" style="width: 10%">'. $row['td'] .'</td>
                    <td class="forminp forminp-text" style="width: 50%">'. $row['td_extra'] .'</td>
                </tr>';
        }

        $table .= $this->customRowTableFooter();
        return $table;
    }

    public function print_sync_table()
    {
        //https://www.w3schools.com/charsets/ref_emoji.asp
        $rows = [
            [
                'th' => '<label>'. __('WooCommerce &#128073; Tripletex', 'woo-tripletex') .'</label>',
                'td' => '<button id="woo-tripletex-order-sync-btn" type="button" class="wt_button">
                            <span>'. __('&#10004 Sync now', 'woo-tripletex') .'</span>
                        </button><span class="sync_message" id="sync_order_message"></span>
                ',
                'td_extra' => '<button id="woo-tripletex-order-stop-sync-btn" type="button" class="wt_button_extra">
                            <span>'. __('Stop sync', 'woo-tripletex') .'</span>
                        </button>
                ',
            ],
            
            [
                'th' => '<label>'. __('Tripletex Products &#128072; WooCommerce Product', 'woo-tripletex') .'</label>',
                'td' => '<button id="woo-tripletex-tt-to-wp-sync-btn" type="button" class="wt_button">
                <span>'. __('&#10004 Sync now', 'woo-tripletex') .'</span>
                </button><span class="sync_message" id="sync_tt_to_wp_products_message"></span>',

                'td_extra' => '<button id="woo-tripletex-product-stop-sync-btn" type="button" class="wt_button_extra">
                            <span>'. __('Stop sync', 'woo-tripletex') .'</span>
                        </button>
                ',                
            ],
        ];

        echo $this->customRows($rows);
    }

    public function print_custom_date()
    {
        
        $table = $this->customRowTableHeader();
            $table .= '<tr valign="top">';
                $table .= '<td class="forminp forminp-text" style="width:200px"></td>';
                $table .= '<td class="forminp forminp-text" style="">';
               
                $table .= 'From <input type="date" name="from_date" id="from_date">';
                $table .= 'to <input type="date" name="from_date" id="to_date">';
                
                $table .= '</td>';

            $table .= '</tr>';

        $table .= $this->customRowTableFooter();

        echo $table; 

    }    

    public function print_send_report_btn()
    {
        $table = $this->customRowTableHeader();
            $table .= '<tr valign="top">';
                $table .= '<td class="forminp forminp-text" style="width:200px"></td>';
                $table .= '<td class="forminp forminp-text">';
                    $table .= '<p class="report_generate_message" id="report_generate_message"></p>';
                    $table .= '<button type="button" class="wt_report_button" name="woo_tripletex_send_report" id="woo_tripletex_send_report"><span style="color: rgb(255, 255, 255);">✔ '.__('Generate', 'woo-tripletex').'</span></button>';                   
                    $table .= '<button type="button" class="wt_stop_generating" name="woo_tripletex_stop_generate" id="woo_tripletex_stop_generate"><span style="color: rgb(255, 255, 255);"> '.__('Stop Generating', 'woo-tripletex').'</span></button>';
                $table .= '</td>';

            $table .= '</tr>';

        $table .= $this->customRowTableFooter();

        echo $table; 
    }
}