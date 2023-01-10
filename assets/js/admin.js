(function($) {
    'use strict';

    function checkIfSyncPage()
    {
        let params = new window.URLSearchParams(window.location.search);

        if (params.get('page') === 'wc-settings' && params.get('tab') === 'settings_tab_woo_tripletex') {
                window.sync_status_interval = setInterval(pingSyncStatus, 5000);
        } else {
            resetButtonLoading();
        }
    }

    function pingSyncStatus()
    {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: woo_tripletex.ajax_url,
            data: {
                action: 'sync_status',
                security: woo_tripletex.nonce,
            },
            success: function(response) {
                console.log("PING RESPONSE: ");
                console.log(response.data);
                if (response.data.data === false) {
                    clearInterval(window.sync_status_interval);
                    resetButtonLoading();
                    WTBtnResponse();
                } else {
                    let data = response.data.data;
                    let message = `<p class="wt_success">Sync in progress</p>`;
                    syncButtonLoading();
                    WTBtnResponse(response.data.selector, message);

                    if(response.data.selector == '#sync_order_message') {
                        showBtn('#woo-tripletex-order-stop-sync-btn');
                    }
                    
                    if(response.data.selector == '#sync_tt_to_wp_products_message') {
                        showBtn('#woo-tripletex-product-stop-sync-btn');
                    }
                }
            },
            fail: function (data) {
                clearInterval(window.sync_status_interval);
            }
        });
    }

    pingSyncStatus();

    checkIfSyncPage();

    function syncButtonLoading(button) {
        $(button).addClass("wt_button_loading");
        $('.wt_button').prop('disabled', true);
        // $('.wt_button').css('cursor', 'wait');
        $(button).children().css('color', '#009579');
    }

    function WTBtnResponse(result_selector, html = "") {
        if (!result_selector) {
            result_selector = '.sync_message'
        }
        $(result_selector).html(html);
    }

    function resetButtonLoading() {
        $('.wt_button').removeClass("wt_button_loading");
        $('.wt_button').css('cursor', '');
        $('.wt_button').prop('disabled', false);
        $('.wt_button').children().css('color', '#ffffff');
    }

    $('body').on('click', '#woo-tripletex-order-sync-btn', function(){
        WTSendAjax('sync_order',
            '#woo-tripletex-order-sync-btn',
            '#sync_order_message',
        );
        showBtn('#woo-tripletex-order-stop-sync-btn');
    });

    $('body').on('click', '#woo-tripletex-tt-to-wp-sync-btn', function(){
        WTSendAjax('sync_tt_to_wp_products',
            '#woo-tripletex-tt-to-wp-sync-btn',
            '#sync_tt_to_wp_products_message',
        );
        showBtn('#woo-tripletex-product-stop-sync-btn');
    });

    $('body').on('click', '#woo-tripletex-order-stop-sync-btn', function(){
        WTSendAjax('stop_sync_order',
            '',
            '',
        );

        clearInterval(window.sync_status_interval);
        hideBtn('#woo-tripletex-order-stop-sync-btn');
    });

    $('body').on('click', '#woo-tripletex-product-stop-sync-btn', function(){
        WTSendAjax('stop_sync_order',
            '',
            '',
        );

        clearInterval(window.sync_status_interval);
        hideBtn('#woo-tripletex-product-stop-sync-btn');
    });

    function WTSendAjax(action, button, result_selector) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: woo_tripletex.ajax_url,
            data: {
                action: action,
                security: woo_tripletex.nonce,
            },
            success: function(response) {
                syncButtonLoading(button);
                WTBtnResponse(result_selector, response.data);
                checkIfSyncPage();
            },
            error: function(){
            }
        });
    }

    function showBtn(selector){
        $(selector).show();
    }
    
    function hideBtn(selector){
        $(selector).hide();
    }

    $('input[name="wc_settings_tab_woo_tripletex_custom_date_range"]').daterangepicker();

    $( '#wc_settings_tab_woo_tripletex_report_type' )
    .on( 'change', function () {
       if( $( this ).val() == 'custom_date'){
        showBtn( 'input#wc_settings_tab_woo_tripletex_custom_date_range' );
       } else {
        hideBtn( 'input#wc_settings_tab_woo_tripletex_custom_date_range' );
       }
    } )
    .trigger( 'change' );

    var jqXHR;
    $('body').on('click', '#woo_tripletex_send_report', function(){
        
        let that = $(this);
        let queryDate = '';
        let reportType = $('#wc_settings_tab_woo_tripletex_report_type').val();

        if( reportType == 'this_month' ) {

            var date = new Date(), y = date.getFullYear(), m = date.getMonth();
            var firstDay = new Date(y, m, 1).toLocaleDateString('en-US', {
                day : 'numeric',           
                month : 'numeric',
                year : 'numeric'
            }).split(' ').join('/');
            var lastDay = new Date(y, m + 1, 0).toLocaleDateString('en-US', {
                day : 'numeric',     
                month : 'numeric',
                year : 'numeric'
            }).split(' ').join('/');

            queryDate = firstDay + ' - '+ lastDay;

        } else if( reportType == 'this_year' ) {

            const currentYear = new Date().getFullYear();
            const firstDay = new Date(currentYear, 0, 1).toLocaleDateString('en-US', {
                day : 'numeric',           
                month : 'numeric',
                year : 'numeric'
            }).split(' ').join('/');
           
            const lastDay = new Date(currentYear, 11, 31).toLocaleDateString('en-US', {
                day : 'numeric',           
                month : 'numeric',
                year : 'numeric'
            }).split(' ').join('/');

            queryDate = firstDay + ' -' + lastDay;

        } else {
            queryDate = $('#wc_settings_tab_woo_tripletex_custom_date_range').val();

        }  

        that.addClass('wt_button_loading');
        that.prop('disabled', true);
        that.children().css('color', '#009579');
        showBtn('#woo_tripletex_stop_generate');
        jqXHR = $.ajax({
            type: 'POST',
            dataType: 'json',
            url: woo_tripletex.ajax_url,
            data: {
                action: 'monthly_sales_report',
                security: woo_tripletex.nonce,
                queryDate: queryDate
            },
            success: function(response) {
                console.log('get response==>',response)
                that.removeClass('wt_button_loading');
                that.prop('disabled', false);
                that.children().css('color', '#FFF');
                $('#report_generate_message').html(response);
                hideBtn('#woo_tripletex_stop_generate');
            },
            error: function(){
                that.removeClass('wt_button_loading');
                that.prop('disabled', false);
                that.children().css('color', '#FFF');
                hideBtn('#woo_tripletex_stop_generate');
            }
        });
    });

    $("#woo_tripletex_stop_generate").click(function(){ jqXHR.abort(); });
})(jQuery);