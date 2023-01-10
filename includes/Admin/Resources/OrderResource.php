<?php

namespace Woo_Tripletex\Admin\Resources;

use Woo_Tripletex\Enums\EmailAttachmentType;
use Woo_Tripletex\Enums\InvoiceSendMethod;
use Woo_Tripletex\Helpers;

class OrderResource extends JsonResource {

    public $country;
    public $currency;
    /**
     * Date format
     */
    const DATE_FORMAT = 'Y-m-d h:i:s';

    public function blueprint( $order ) {
        if ( $order->get_parent_id() ) {
            $order = wc_get_order( $order->get_parent_id() );
        }

        $this->setCountry( $order );
        $this->setCurrency();

        $data = [
            'id' => $order->get_id(),
            'receiverEmail' => $order->get_billing_email(),
            'deliveryDate' => $order->get_date_created()->format( self::DATE_FORMAT ),
            'orderDate' => $order->get_date_created()->format( self::DATE_FORMAT ),
            'orderLines' => OrderItemResource::collection( $order->get_items() ),
            'isSubscription' => false,
            'customer' => $this->customer( $order ),
            'invoiceComment'            => '',
            'deliveryAddress'            => [
                'addressLine1' => $order->get_shipping_address_1(),
                'addressLine2' => $order->get_shipping_address_2(),
                'postalCode' => $order->get_shipping_postcode(),
                'city' => $order->get_shipping_city(),
                'country' => $this->country,
                'currency' => $this->currency,
            ],
        ];

        return $data;
    }

    private function setCountry( $order ) {
        $helper = new Helpers();
        $this->country = [
            'id' => $helper->findCountryIdByISO( $order->get_billing_country() ),
            'version' => 0
        ];
    }

    /**
     * Transform customer
     *
     * @param $order
     *
     * @return array
     */
    protected function customer( $order ) {
        $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        return [
            'name' => $name,
            'email' => $order->get_billing_email(),
            'invoiceEmail' => $order->get_billing_email(),
            'overdueNoticeEmail' => $order->get_billing_email(),
            'phoneNumberMobile' => $order->get_billing_phone(),
            'displayName' => $order->get_billing_first_name(),
            'isPrivateIndividual' => true,
            'singleCustomerInvoice' => true,
            'invoiceSendMethod' => InvoiceSendMethod::EMAIL,
            'emailAttachmentType' => EmailAttachmentType::LINK,
            'deliveryAddress' => [
                'addressLine1' => $order->get_billing_address_1(),
                'addressLine2' => $order->get_billing_address_2(),
                'postalCode' => $order->get_billing_postcode(),
                'city' => $order->get_billing_city(),
                'country' => $this->country,
            ],
        ];
    }

    private function setCurrency() {
        $helper = new Helpers();
        $this->currency = [
            'id' => $helper->findCurrencyIdByCode( get_woocommerce_currency() ),
            'version' => 0,
            'code' => get_woocommerce_currency(),
            'displayName' => get_woocommerce_currency(),
            'factor' => 1
        ];
    }
}