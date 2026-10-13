<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://shopup.lt/
 * @since      1.0.0
 *
 * @package    Woocommerce_Shopup_Venipak_Shipping
 * @subpackage Woocommerce_Shopup_Venipak_Shipping/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shopup_Venipak_Shipping
 * @subpackage Woocommerce_Shopup_Venipak_Shipping/admin
 * @author     ShopUp <info@shopup.lt>
 */
class Woocommerce_Shopup_Venipak_Shipping_Admin_Dispatch {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     *
     *
     * @since    1.0.0
     */
    private $settings;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_username;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_password;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_userid;

    /**
     *
     *
     * @since    1.15.0
     */
    private $venipak_manifest;

    private $venipak_return_service;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_name;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_company_code;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_country;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_city;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_address;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_post_code;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_contact_person;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_contact_tel;

    /**
     *
     *
     * @since    1.0.0
     */
    private $venipak_sender_contact_email;

    /**
     *
     *
     * @since    1.5.6
     */
    private $venipak_is_status_change_disabled;

    /**
     *
     *
     * @since    1.5.8
     */
    private $shopup_venipak_shipping_field_maxpackproducts;

    /**
     *
     *
     * @since    1.13.0
     */
    private $shopup_venipak_shipping_field_forcedispatch;

    private $venipak_sender_eori;

    /**
     * Customs document types Venipak's import API accepts as doc_type.
     *
     * @since    1.27.0
     */
    const CUSTOMS_DOCUMENT_TYPES = array( 'commercial_invoice', 'pro_forma_invoice', 'certificate_of_origin' );

    /**
     * The document travels base64-encoded inside the import XML, so it is kept small.
     *
     * @since    1.27.0
     */
    const CUSTOMS_DOCUMENT_MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Sender countries Venipak ships from without a global shipment; any other sender is global.
     *
     * @since    1.27.0
     */
    const BALTIC_COUNTRIES = array( 'LT', 'LV', 'EE' );

    /**
     * Consignee countries Venipak delivers to without a global shipment
     * (BALTIC_COUNTRIES + INTERNATIONAL_COUNTRIES).
     *
     * @since    1.27.0
     */
    const NETWORK_COUNTRIES = array( 'LT', 'LV', 'EE', 'PL', 'FI' );

    /**
     * Consignee countries that make a shipment from the Baltics "international" for Venipak:
     * its own shipment type, marked with an <international> block instead of <global>.
     *
     * @since    1.27.0
     */
    const INTERNATIONAL_COUNTRIES = array( 'PL', 'FI' );

    /**
     * Venipak's weight group boundary for international shipments: one shipment holds either
     * packs up to this weight or heavier ones, and the heavier ones need their dimensions.
     *
     * @since    1.27.0
     */
    const INTERNATIONAL_WEIGHT_GROUP_KG = 30;

    /**
     * Shipment types returned by get_shipment_type().
     *
     * @since    1.27.0
     */
    const SHIPMENT_DOMESTIC = 'domestic';
    const SHIPMENT_INTERNATIONAL = 'international';
    const SHIPMENT_GLOBAL = 'global';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version, $settings ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = $settings;
        $this->venipak_userid = $settings->get_option_by_key('shopup_venipak_shipping_field_userid');
        $this->venipak_username = $settings->get_option_by_key('shopup_venipak_shipping_field_username');
        $this->venipak_password = $settings->get_option_by_key('shopup_venipak_shipping_field_password');
        $this->venipak_manifest = $settings->get_option_by_key('shopup_venipak_shipping_field_manifest') ?: '001';
        $this->venipak_return_service = intval($settings->get_option_by_key('shopup_venipak_shipping_field_return_service'));
        $this->venipak_sender_name = $settings->get_option_by_key('shopup_venipak_shipping_field_sendername');
        $this->venipak_sender_company_code = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercompanycode');
        $this->venipak_sender_country = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercountry');
        $this->venipak_sender_city = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercity');
        $this->venipak_sender_address = $settings->get_option_by_key('shopup_venipak_shipping_field_senderaddress');
        $this->venipak_sender_post_code = $settings->get_option_by_key('shopup_venipak_shipping_field_senderpostcode');
        $this->venipak_sender_contact_person = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercontactperson');
        $this->venipak_sender_contact_tel = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercontacttel');
        $this->venipak_sender_contact_email = $settings->get_option_by_key('shopup_venipak_shipping_field_sendercontactemail');
        $this->venipak_is_status_change_disabled = $settings->get_option_by_key('shopup_venipak_shipping_field_isstatuschangedisabled');
        $this->shopup_venipak_shipping_field_maxpackproducts = intval($settings->get_option_by_key('shopup_venipak_shipping_field_maxpackproducts'));
        $this->shopup_venipak_shipping_field_forcedispatch = $settings->get_option_by_key('shopup_venipak_shipping_field_forcedispatch');
        $this->venipak_sender_eori = $settings->get_option_by_key('shopup_venipak_shipping_field_sendereori');
    }

    /**
     *
     *
     * @since    1.0.0
     */
    public function add_venipak_shipping_bulk_action_process( $redirect_to, $action, $post_ids ) {
        if ( $action === 'shopup_venipak_shipping_dispatch' ) {
            foreach ($post_ids as $order_id) {
                $this->venipak_shipping_dispatch_order([$order_id], false);
            }
        }
        return $redirect_to;
    }

    /**
     *
     *
     * @since    1.0.0
     */
    public function add_venipak_shipping_dispatch_force() {
        $this->dispatch_from_order_page( true );
    }

    /**
     *
     *
     * @since    1.0.0
     */
    public function add_venipak_shipping_dispatch() {
        $this->dispatch_from_order_page( false );
    }

    /**
     * The Dispatch buttons on the order page. $force ("Dispatch one more time") first clears the
     * pack numbers of the earlier dispatch so that new ones are reserved.
     *
     * @since    1.27.0
     */
    private function dispatch_from_order_page( $force ) {
        check_ajax_referer( 'venipak_shipping_admin', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( -1, 403 );
        }
        $order_id = intval( $_POST['order_id'] );
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( -1, 404 );
        }
        $packs = isset( $_POST['packs'] ) ? $_POST['packs'] : false;
        if ( $packs ) {
            foreach ( $packs as $i => $pack ) {
                // A decimal comma is read as a point, so 4,5 kg is 4.5. Not wc_format_decimal(): in a
                // store whose price separator is "." it drops the comma and makes 4,5 into 45.
                foreach ( array( 'weight', 'width', 'height', 'length' ) as $key ) {
                    $packs[ $i ][ $key ] = str_replace( ',', '.', trim( wp_unslash( $pack[ $key ] ) ) );
                }
                // Typed in the store's dimension unit (the table header names it); Venipak wants metres.
                // Text that is not a number becomes 0, which every check below reads as "not filled in".
                foreach ( array( 'width', 'height', 'length' ) as $key ) {
                    $packs[ $i ][ $key ] = round( wc_get_dimension( (float) $packs[ $i ][ $key ], 'm' ), 3 );
                }
            }
        }
        // The customs input is checked before the reset below. A refusal found later, inside the
        // dispatch but before anything reaches Venipak, puts the saved data back further down.
        // Without packs (the orders-list button, which has no customs fields) it is not read: such a
        // dispatch is refused for the missing dimensions, recorded on the order as in bulk.
        $customs = $packs && self::order_needs_customs_details( $order ) ? $this->get_customs_input_from_request( $order ) : null;
        if ( is_wp_error( $customs ) ) {
            self::print_dispatch_error( $customs->get_error_message() );
            wp_die();
        }

        if ( $force ) {
            $order_data_before_reset = $order->get_meta('venipak_shipping_order_data', true);
            $venipak_shipping_order_data = json_decode($order->get_meta('venipak_shipping_order_data', true), true);
            $venipak_shipping_order_data['status'] = '';
            $venipak_shipping_order_data['pack_numbers'] = [];
            $order->update_meta_data('venipak_shipping_order_data', json_encode($venipak_shipping_order_data));
            $order->save();
        }

        $result = $this->venipak_shipping_dispatch_order([$order_id], $packs, $customs);
        if ( $force && $result && ! empty( $result['refused'] ) ) {
            // Nothing was sent, so the earlier dispatch still stands: keep it, not the refusal.
            $order->update_meta_data('venipak_shipping_order_data', $order_data_before_reset);
            $order->save();
        }

        if (!$result) wp_die();

        if ($result['status'] === 'ok') {
            $pack_numbers = $result['data']['pack_numbers'];
            $pack_numbers_string = implode(', ', $pack_numbers);
            $content = '<div class="venipak-shipping-pack" style="display: flex; gap: 5px;"><a style="font-size: 20px; display: flex; align-items: center; gap: 5px;" class="button button-primary" title="' . esc_attr( $pack_numbers_string ) . '" target="_blank" href="' . admin_url('admin-ajax.php') . '?action=woocommerce_shopup_venipak_shipping_get_label_pdf&order_id=' . $order_id . '"><span class="dashicons dashicons-tag"></span></a> <a style="font-size: 20px; display: flex; align-items: center; gap: 5px;" class="button button-primary" target="_blank" href="' . admin_url('admin-ajax.php') . '?action=woocommerce_shopup_venipak_shipping_get_manifest_pdf&order_id=' . $order_id . '"><span class="dashicons dashicons-media-document"></span></a></div>';
            echo $content;
        } else {
            self::print_dispatch_error( $result['data'] );
        }
        wp_die();
    }

    /**
     * Red, as the order page shows a stored dispatch error, so a refusal reads as one.
     *
     * @since    1.27.0
     */
    private static function print_dispatch_error( $message ) {
        echo '<p style="color: red;">' . esc_html( $message ) . '</p>';
    }

    /**
     * The customs document and shipment value the order page sends with a global dispatch.
     *
     * @since    1.27.0
     * @return   array|WP_Error    'document' => see get_customs_document_from_request(),
     *                             'value' => decimal string, or null for the order total.
     */
    private function get_customs_input_from_request( $order ) {
        $document = $this->get_customs_document_from_request();
        if ( is_wp_error( $document ) ) {
            return $document;
        }

        $value = isset( $_POST['value'] ) ? trim( wp_unslash( $_POST['value'] ) ) : '';
        if ( $value === '' ) {
            // Empty means the order total at dispatch time, which is only a value in EUR for an EUR order.
            if ( $order->get_currency() !== 'EUR' ) {
                return new WP_Error( 'venipak_customs_value', sprintf(
                    /* translators: %s: order currency code, e.g. "USD" */
                    __( 'Enter the shipment value in EUR: this order is in %s.', 'woocommerce-shopup-venipak-shipping' ),
                    $order->get_currency()
                ) );
            }
            return array( 'document' => $document, 'value' => null );
        }

        // Not wc_format_decimal(): it strips what it cannot read, so "1e3" would become 13.00.
        $value = str_replace( ',', '.', $value );
        // Compared in whole cents, so 0.001 is refused instead of being sent as 0.00.
        if ( ! is_numeric( $value ) || round( (float) $value, 2 ) <= 0 ) {
            return new WP_Error( 'venipak_customs_value', __( 'The shipment value must be a positive number.', 'woocommerce-shopup-venipak-shipping' ) );
        }
        return array( 'document' => $document, 'value' => number_format( (float) $value, 2, '.', '' ) );
    }

    /**
     * The customs document comes either as an uploaded file ('customs_document') or as a media
     * library attachment ID ('customs_document_id'). An upload is read straight from PHP's temp
     * file and never stored: invoices carry personal data, and the uploads folder is public.
     *
     * @since    1.27.0
     * @return   array|null|WP_Error    array('type', 'name', 'content' => base64), null when no document was given.
     */
    private function get_customs_document_from_request() {
        $file = isset( $_FILES['customs_document'] ) ? $_FILES['customs_document'] : null;
        $has_file = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
        $attachment_id = isset( $_POST['customs_document_id'] ) ? absint( $_POST['customs_document_id'] ) : 0;
        if ( ! $has_file && ! $attachment_id ) {
            return null;
        }

        $doc_type = isset( $_POST['doc_type'] ) ? sanitize_key( wp_unslash( $_POST['doc_type'] ) ) : '';
        if ( ! in_array( $doc_type, self::CUSTOMS_DOCUMENT_TYPES, true ) ) {
            return new WP_Error( 'venipak_customs_document', __( 'Unknown customs document type.', 'woocommerce-shopup-venipak-shipping' ) );
        }

        if ( $has_file ) {
            if ( $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE ) {
                return new WP_Error( 'venipak_customs_document', self::customs_document_too_large_message() );
            }
            if ( $file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file( $file['tmp_name'] ) ) {
                return new WP_Error( 'venipak_customs_document', __( 'The customs document could not be uploaded.', 'woocommerce-shopup-venipak-shipping' ) );
            }
            return $this->read_customs_document( $file['tmp_name'], $file['name'], $doc_type );
        }

        $path = get_attached_file( $attachment_id );
        if ( get_post_type( $attachment_id ) !== 'attachment' || get_post_mime_type( $attachment_id ) !== 'application/pdf' || ! $path || ! is_readable( $path ) ) {
            return new WP_Error( 'venipak_customs_document', __( 'The selected customs document was not found in the media library.', 'woocommerce-shopup-venipak-shipping' ) );
        }
        return $this->read_customs_document( $path, basename( $path ), $doc_type );
    }

    /**
     * Checks shared by both sources. The PDF test looks at the content, not the name: every PDF
     * starts with "%PDF-" (the same rule finfo applies), and it needs no optional PHP extension.
     *
     * @since    1.27.0
     */
    private function read_customs_document( $path, $name, $doc_type ) {
        if ( filesize( $path ) > self::customs_document_max_bytes() ) {
            return new WP_Error( 'venipak_customs_document', self::customs_document_too_large_message() );
        }
        $content = file_get_contents( $path );
        if ( $content === false || strncmp( $content, '%PDF-', 5 ) !== 0 ) {
            return new WP_Error( 'venipak_customs_document', __( 'The customs document must be a PDF file.', 'woocommerce-shopup-venipak-shipping' ) );
        }
        return array(
            'type' => $doc_type,
            'name' => sanitize_file_name( $name ),
            // Encoded once here: the dispatch retry loop rebuilds the XML up to four times.
            'content' => base64_encode( $content ),
        );
    }

    /**
     * Our cap or the server's upload limit, whichever is lower. Also checked in the browser (admin.php).
     *
     * @since    1.27.0
     */
    public static function customs_document_max_bytes() {
        return min( self::CUSTOMS_DOCUMENT_MAX_BYTES, wp_max_upload_size() );
    }

    public static function customs_document_too_large_message() {
        return sprintf(
            /* translators: %s: maximum file size, e.g. "5 MB" */
            __( 'The customs document is too large. The limit is %s.', 'woocommerce-shopup-venipak-shipping' ),
            size_format( self::customs_document_max_bytes() )
        );
    }

    /**
     * A dispatch that never got Venipak's answer recorded. The pack numbers are saved before the
     * request goes out, the status only after the reply is handled, so a request cut off in
     * between (PHP time limit, a fatal in another plugin) leaves pack numbers with no status.
     * Venipak may well have registered the shipment, so the label is offered for these orders:
     * the label endpoint reports it if Venipak has no such parcel, and confirms the order if it has.
     *
     * Before 1.14.0 (which renamed product_count to products_count) some rejected dispatches were
     * stored without a status too; those are old failures, not interrupted ones.
     *
     * @since    1.26.6
     */
    public static function is_unconfirmed( $order_data ) {
        if ( ! is_array( $order_data ) || empty( $order_data['pack_numbers'] ) || ! isset( $order_data['products_count'] ) ) {
            return false;
        }
        $status = isset( $order_data['status'] ) ? $order_data['status'] : '';
        return $status !== 'sent' && $status !== 'error';
    }

    /**
     * The country the shipment goes to, as written into <consignee><country>: the pickup point's
     * for a pickup order, else the order address's. $pickup_point is venipak_delivery_pickup_point().
     *
     * @since    1.27.0
     */
    public static function get_consignee_country( $order, $pickup_point ) {
        return $pickup_point ? $pickup_point['country'] : self::get_order_address( $order )['country'];
    }

    /**
     * Venipak's shipment type, decided by the countries alone: global from a sender outside the
     * Baltics or to a destination outside Venipak's network, else international to Poland or
     * Finland, else domestic. An order without a country is not taken for global: it cannot be
     * dispatched anyway, and Venipak's own error then names the missing country.
     *
     * @since    1.27.0
     */
    public static function get_shipment_type( $sender_country, $consignee_country ) {
        if ( ! in_array( $sender_country, self::BALTIC_COUNTRIES, true ) || self::is_outside_network( $consignee_country ) ) {
            return self::SHIPMENT_GLOBAL;
        }
        if ( in_array( $consignee_country, self::INTERNATIONAL_COUNTRIES, true ) ) {
            return self::SHIPMENT_INTERNATIONAL;
        }
        return self::SHIPMENT_DOMESTIC;
    }

    private static function is_outside_network( $consignee_country ) {
        return $consignee_country !== '' && ! in_array( $consignee_country, self::NETWORK_COUNTRIES, true );
    }

    /**
     * Whether the order page asks for customs details (document, value in EUR): only for a
     * destination outside Venipak's network, where customs apply. A non-Baltic sender shipping
     * within the network is global in the XML but needs none. A pickup order whose point is gone
     * gets none either: its dispatch is refused for the missing point, which should speak first.
     *
     * @since    1.27.0
     */
    public static function order_needs_customs_details( $order ) {
        if ( self::has_missing_pickup_point( $order ) ) {
            return false;
        }
        return self::is_outside_network( self::get_consignee_country( $order, venipak_delivery_pickup_point( $order ) ) );
    }

    private static function has_missing_pickup_point( $order ) {
        return venipak_order_shipping_method_id( $order ) === 'shopup_venipak_shipping_pickup_method' && ! venipak_delivery_pickup_point( $order );
    }

    /**
     *
     *
     * @since    1.26.4
     */
    private function missing_pickup_point_message() {
        return __( 'The selected pickup point is no longer available. Choose another one on the order before dispatching.', 'woocommerce-shopup-venipak-shipping' );
    }

    /**
     * Keep orders whose pickup point cannot be resolved out of the dispatch entirely.
     *
     * The XML builder treats "no pickup point" as "courier", so an order for a point that
     * has left Venipak's feed would otherwise be accepted and delivered to the buyer's home
     * address instead of the locker, with no error anywhere.
     *
     * @since    1.26.4
     */
    private function reject_orders_with_missing_pickup_point( $order_ids ) {
        $dispatchable = array();

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) continue;

            $venipak_shipping_order_data = json_decode( $order->get_meta( 'venipak_shipping_order_data' ), true );
            if ( ! $venipak_shipping_order_data ) {
                $venipak_shipping_order_data = array();
            }

            // Already dispatched: the XML builder skips it anyway, and its pickup point
            // having since vanished is no reason to mark a shipped order as failed.
            if ( isset( $venipak_shipping_order_data['status'] ) && $venipak_shipping_order_data['status'] === 'sent' ) {
                $dispatchable[] = $order_id;
                continue;
            }

            if ( self::has_missing_pickup_point( $order ) ) {
                $this->record_dispatch_refusal( $order, $this->missing_pickup_point_message() );
                continue;
            }

            $dispatchable[] = $order_id;
        }

        return $dispatchable;
    }

    /**
     * Mark an order as refused before anything was sent to Venipak, with the reason shown on
     * the order and in the orders list.
     *
     * Every other writer of this meta goes through getImportXML(), which fills the keys below
     * in. Readers therefore only test the decoded array for truthiness before dereferencing, so
     * refusing without them would hand them a partial array — a blank product count in the
     * metabox and, on the customer's side, a tracking link built from a pack number that does
     * not exist.
     *
     * @since    1.27.0
     */
    private function record_dispatch_refusal( $order, $message ) {
        $venipak_shipping_order_data = json_decode( $order->get_meta( 'venipak_shipping_order_data' ), true );
        if ( ! $venipak_shipping_order_data ) {
            $venipak_shipping_order_data = array();
        }
        $products_count = 0;
        foreach ( $order->get_items() as $product_item ) {
            $products_count += $product_item->get_quantity();
        }
        $venipak_shipping_order_data = array_merge( array(
            'pack_numbers' => array(),
            'manifest' => '',
            'products_count' => $products_count,
        ), $venipak_shipping_order_data );

        $venipak_shipping_order_data['status'] = 'error';
        $venipak_shipping_order_data['error_message'] = $message;
        $order->update_meta_data( 'venipak_shipping_order_data', json_encode( $venipak_shipping_order_data ) );
        $order->save();
    }

    /**
     * A message if any manual pack misses its dimensions or description, which Venipak needs for
     * a shipment outside its network; null if all are filled in.
     *
     * @since    1.27.0
     */
    private function incomplete_packs_error( $packs ) {
        foreach ( $packs as $pack ) {
            if ( ! ( $pack['width'] > 0 && $pack['height'] > 0 && $pack['length'] > 0 ) || trim( $pack['description'] ) === '' ) {
                return __( 'Fill in width, height, length and description for every package going outside LT, LV, EE, PL and FI.', 'woocommerce-shopup-venipak-shipping' );
            }
        }
        return null;
    }

    /**
     * A message if a manual pack is under 1 cm on every side, null otherwise. Such a pack almost
     * always means sizes typed in metres, as the order page took them before it used the store's
     * unit. Dimensions in metres; a side left at 0 is "not given", not small.
     *
     * @since    1.27.0
     */
    private function implausibly_small_packs_error( $packs ) {
        foreach ( $packs as $pack ) {
            $sides = array( $pack['width'], $pack['height'], $pack['length'] );
            if ( min( $sides ) > 0 && max( $sides ) < 0.01 ) {
                $unit = get_option( 'woocommerce_dimension_unit' );
                // A 30 x 20 x 10 cm box as the merchant would type it in the store's unit.
                $example = array();
                foreach ( array( 30, 20, 10 ) as $side_cm ) {
                    $example[] = round( wc_get_dimension( $side_cm, $unit, 'cm' ), 2 );
                }
                return sprintf(
                    /* translators: 1: the store's dimension unit, e.g. "cm"; 2: a 30 x 20 x 10 cm box in that unit, e.g. "30 × 20 × 10" */
                    __( 'Package sizes are entered in %1$s, but one package comes out under 1 cm on every side. Check the sizes: a box of 30 × 20 × 10 cm is entered as %2$s.', 'woocommerce-shopup-venipak-shipping' ),
                    $unit,
                    implode( ' × ', $example )
                );
            }
        }
        return null;
    }

    /**
     * A pack's volume in m³ for <volume>, from its sides in metres: fixed-point with up to six
     * decimals, since PHP would write a small float as "8.0E-6". '0' when a side is missing or the
     * pack is under 1 cm³.
     *
     * @since    1.27.0
     */
    private static function format_volume( $width, $height, $length ) {
        return rtrim( rtrim( sprintf( '%.6F', (float) $width * (float) $height * (float) $length ), '0' ), '.' );
    }

    /**
     * Why Venipak would refuse these packs as one international shipment, or null if it would
     * not. Each pack: 'weight' in kg (manual packs as entered, built packs converted from the
     * store's weight unit), and 'width', 'height', 'length' in metres when known.
     *
     * @since    1.27.0
     */
    private function international_packs_error( $packs ) {
        $heavy_packs = array();
        foreach ( $packs as $pack ) {
            if ( $pack['weight'] > self::INTERNATIONAL_WEIGHT_GROUP_KG ) {
                $heavy_packs[] = $pack;
            }
        }
        if ( $heavy_packs && count( $heavy_packs ) < count( $packs ) ) {
            return __( 'Shipments to Poland and Finland cannot mix packages up to 30 kg with heavier ones. Dispatch the heavier packages separately.', 'woocommerce-shopup-venipak-shipping' );
        }
        foreach ( $heavy_packs as $pack ) {
            if ( ! isset( $pack['width'], $pack['height'], $pack['length'] ) || ! ( $pack['width'] > 0 && $pack['height'] > 0 && $pack['length'] > 0 ) ) {
                return __( 'Packages over 30 kg to Poland or Finland need width, height and length.', 'woocommerce-shopup-venipak-shipping' );
            }
        }
        return null;
    }

    /**
     *
     *
     * @since    1.0.0
     */
    public function venipak_shipping_dispatch_order($order_ids, $packs, $customs = null) {
        $url = 'https://go.venipak.lt/import/send.php';

        $order_ids = $this->reject_orders_with_missing_pickup_point( $order_ids );
        if ( ! $order_ids ) {
            return array( 'status' => 'error', 'data' => $this->missing_pickup_point_message(), 'refused' => true );
        }

        // Retry offsets: 0 (first try), then +1, +5, +10 if pack numbers collide
        $retry_offsets = array( 0, 1, 5, 10 );
        $result_xml_string = '';
        $transport_error = '';

        foreach ( $retry_offsets as $offset ) {
            if ( $offset > 0 ) {
                // Bump the pack number counter before retrying
                $this->settings->reserve_pack_numbers( $offset );
            }

            $xml = $this->getImportXML( $order_ids, $packs, $customs );
            if ( is_wp_error( $xml ) ) {
                return array( 'status' => 'error', 'data' => $xml->get_error_message(), 'refused' => true );
            }
            if ( ! $xml ) return null;

            $body = array( 'user' => $this->venipak_username, 'pass' => $this->venipak_password, 'xml_text' => $xml );
            $args = array(
                'body'    => $body,
                // A request carrying a document of up to 5 MB (base64 inside the XML) needs longer to upload.
                'timeout' => $customs && $customs['document'] ? 120 : 45,
                'headers' => array(
                    'Referer' => 'https://woocommerce.com/',
                ),
            );
            $response = wp_remote_post( $url, $args );

            // An empty body means the request never reached Venipak — a broken resolver or a
            // timeout, not an accepted shipment. Treating it as success is how a bulk dispatch
            // once flipped 170 orders to "sent" with nothing registered and no labels.
            if ( is_wp_error( $response ) ) {
                $transport_error = $response->get_error_message();
                $result_xml_string = '';
                break;
            }

            $transport_error = '';
            $status_code = wp_remote_retrieve_response_code( $response );
            $result_xml_string = wp_remote_retrieve_body( $response );

            if ( $status_code !== 200 ) {
                $transport_error = sprintf(
                    /* translators: %d: HTTP status code returned by Venipak */
                    __( 'Venipak answered with HTTP %d', 'woocommerce-shopup-venipak-shipping' ),
                    $status_code
                );
                break;
            }

            if ( $result_xml_string === '' ) {
                $transport_error = __( 'Venipak returned an empty response — the shipment was NOT registered.', 'woocommerce-shopup-venipak-shipping' );
                break;
            }

            // Success — break out of retry loop
            if ( strpos( $result_xml_string, 'type="ok"' ) !== false ) {
                break;
            }

            // Only retry if the error is about pack number being in use
            if ( stripos( $result_xml_string, 'is already in use' ) === false ) {
                break;
            }

            // The stored pack numbers belong to another parcel. If the request dies before the
            // retry saves new ones, the order must not offer a label for someone else's parcel.
            $this->mark_dispatch_failed( $order_ids[0], strip_tags( $result_xml_string ) );
        }

        if ( ! $transport_error && strpos( $result_xml_string, 'type="ok"' ) !== false ) {
            foreach ($order_ids as $order_id) {
                $order = wc_get_order( $order_id );
                // Record Venipak's acceptance before changing the order status: that change runs
                // every status hook (customer emails, other plugins), and if one of them dies the
                // shipment must not be left looking undispatched.
                $venipak_shipping_order_data = json_decode($order->get_meta('venipak_shipping_order_data', true), true);
                $venipak_shipping_order_data['status'] = 'sent';
                $venipak_shipping_order_data['error_message'] = '';
                $order->update_meta_data('venipak_shipping_order_data', json_encode($venipak_shipping_order_data));
                $order->save();

                if (!$this->venipak_is_status_change_disabled)  {
                    $order->update_status('completed', __( 'Order dispatched to Venipak', 'woocommerce-shopup-venipak-shipping' ));
                }
            }
            return array('status' => 'ok', 'data' => $venipak_shipping_order_data);
        } else {
            $error_message = $transport_error ? $transport_error : strip_tags($result_xml_string);
            $this->mark_dispatch_failed( $order_ids[0], $error_message );
            if ( $transport_error ) {
                error_log( 'VENIPAK ' . $url . ' dispatch failed: ' . $transport_error );
            }
            return array('status' => 'error', 'data' => $error_message);
        }
    }

    private function mark_dispatch_failed( $order_id, $error_message ) {
        $order = wc_get_order( $order_id );
        $venipak_shipping_order_data = json_decode($order->get_meta('venipak_shipping_order_data', true), true);
        $venipak_shipping_order_data['status'] = 'error';
        $venipak_shipping_order_data['error_message'] = $error_message;
        $order->update_meta_data('venipak_shipping_order_data', json_encode($venipak_shipping_order_data));
        $order->save();
    }

    /**
     *
     *
     * @since    1.0.0
     */
    public function getImportXML($order_ids, $packs, $customs = null) {
        date_default_timezone_set('Europe/Vilnius');
        $client_id = $this->venipak_userid;
        $venipak_return_service =  $this->venipak_return_service;
        $document = new \DOMDocument( '1.0', 'utf-8' );
        // descriotion
        $description = $document->createElement( 'description' );
        $description_attr = $document->createAttribute('type');
        $description_attr->value = "1";
        $description->appendChild($description_attr);
        $document->appendChild($description);
        //manifest
        $manifest = $document->createElement( 'manifest' );
        $manifest_attr = $document->createAttribute('title');
        $manifest_value = $client_id . date('ymd') . $this->venipak_manifest;
        $manifest_attr->value = $manifest_value;
        $manifest->appendChild($manifest_attr);
        $description->appendChild($manifest);
        $xml_not_empty = false;

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            $shipping_method = @array_shift($order->get_shipping_methods());
            $shipping_method_id = $shipping_method['method_id'];
            if (!$this->shopup_venipak_shipping_field_forcedispatch && $shipping_method_id !== 'shopup_venipak_shipping_courier_method' && $shipping_method_id !== 'shopup_venipak_shipping_pickup_method') continue;

            $payment_method = $order->get_payment_method();
            $venipak_shipping_order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);

            if (!$venipak_shipping_order_data) {
                $venipak_shipping_order_data = [];
            } else {
                if (isset($venipak_shipping_order_data['status']) && $venipak_shipping_order_data['status'] === 'sent') { continue; };
            }

            $venipak_pickup_point = venipak_delivery_pickup_point($order);

            $order_products = [];
            $min_age = 0;
            $shipment_description_text = '';
            $prev_title = 'no-repeat';
            foreach ( $order->get_items() as $item_id => $product_item ) {
                $product = $product_item->get_product();
                if (!$product) continue;
                if ($prev_title !== $product->get_title()) {
                    $shipment_description_text .= $product->get_title() . PHP_EOL;
                    $prev_title = $product->get_title();
                }
                $product_min_age = $product->get_meta('shopup_venipak_shipping_min_age');
                if ($product_min_age && $product_min_age > $min_age) {
                    $min_age = $product_min_age;
                }
                $product_quantity = $product_item->get_quantity();
                for ($i = 0; $i < $product_quantity; $i++) {
                    $order_products[] = $product;
                }
            }

            $shipment = $document->createElement( 'shipment' );
            $manifest->appendChild($shipment);

            $sender = $document->createElement( 'sender' );
            $shipment->appendChild($sender);

            $sender_name = $document->createElement( 'name', $this->venipak_sender_name );
            $sender_company_code = $document->createElement( 'company_code',  $this->venipak_sender_company_code );
            $sender_country = $document->createElement( 'country', $this->venipak_sender_country );
            $sender_city = $document->createElement( 'city', $this->venipak_sender_city );
            $sender_address = $document->createElement( 'address', $this->venipak_sender_address );
            $sender_post_code = $document->createElement( 'post_code', preg_replace('/\D/', '', $this->venipak_sender_post_code));
            $contact_person = $document->createElement( 'contact_person', $this->venipak_sender_contact_person );
            $contact_tel = $document->createElement( 'contact_tel', $this->venipak_sender_contact_tel );
            $contact_email = $document->createElement( 'contact_email', $this->venipak_sender_contact_email );

            $sender->appendChild($sender_name);
            $sender->appendChild($sender_company_code);
            $sender->appendChild($sender_country);
            $sender->appendChild($sender_city);
            $sender->appendChild($sender_address);
            $sender->appendChild($sender_post_code);
            $sender->appendChild($contact_person);
            $sender->appendChild($contact_tel);
            $sender->appendChild($contact_email);

            $consignee = $document->createElement( 'consignee' );
            $shipment->appendChild($consignee);
            $order_address = $this->get_order_address($order);
            $consignee_country = self::get_consignee_country($order, $venipak_pickup_point);
            $country = $document->createElement( 'country', $consignee_country );

            if ($venipak_pickup_point) {
                $name = $document->createElement( 'name', $venipak_pickup_point['name'] );
                $company_code = $document->createElement( 'company_code',  $venipak_pickup_point['code'] );
                $consignee->appendChild($company_code);
                $city = $document->createElement( 'city', $venipak_pickup_point['city'] );
                $address = $document->createElement( 'address', $venipak_pickup_point['address'] );
                $post_code = $document->createElement( 'post_code', preg_replace('/\D/', '', $venipak_pickup_point['zip']));
            } else {
                $name = $document->createElement( 'name', $order_address['full_name'] );
                $city = $document->createElement( 'city', $order_address['city'] );
                $address = $document->createElement( 'address', $order_address['address'] );
                $post_code = $document->createElement( 'post_code', $order_address['postcode'] );

                $comment_door_code = $document->createElement( 'comment_door_code', $order->get_meta('venipak_door_code') );
                $comment_office_no = $document->createElement( 'comment_office_no', $order->get_meta('venipak_office_no') );

                $venipak_delivery_time = $min_age  > 0 ? 'nwd14_17' : $order->get_meta('venipak_delivery_time');
                $delivery_type = $document->createElement( 'delivery_type', $venipak_delivery_time );
            }

            $consignee->appendChild($name);
            $consignee->appendChild($country);
            $consignee->appendChild($city);
            $consignee->appendChild($address);
            $consignee->appendChild($post_code);

            $contact_person = $document->createElement('contact_person', $order_address['contact_person']);
            $consignee->appendChild($contact_person);
            $contact_tel = $document->createElement( 'contact_tel', $order_address['phone'] );
            $consignee->appendChild($contact_tel);
            $contact_email = $document->createElement( 'contact_email', $order->get_billing_email() );
            $consignee->appendChild($contact_email);

            $attribute = $document->createElement( 'attribute' );
            $shipment->appendChild($attribute);

            if (!$venipak_pickup_point) {
                $attribute->appendChild($comment_door_code);
                $attribute->appendChild($delivery_type);
                $attribute->appendChild($comment_office_no);
                $comment_call = $document->createElement( 'comment_call', '1' );
                $attribute->appendChild($comment_call);
            }

            $shipment_code = $document->createElement( 'shipment_code', $order->get_id() );
            $attribute->appendChild($shipment_code);
            $doc_no = $document->createElement( 'doc_no', $order->get_id() );
            $attribute->appendChild($doc_no);

            if ($payment_method === 'cod') {
                $cod = $document->createElement( 'cod', $order->get_total() );
                $attribute->appendChild($cod);
                $cod_type = $document->createElement( 'cod_type', 'EUR' );
                $attribute->appendChild($cod_type);
            }

            $comment_text = $document->createElement( 'comment_text', $order->get_customer_note() );
            $attribute->appendChild($comment_text);

            if ($min_age > 0) {
                $min_age_node = $document->createElement( 'min_age', $min_age );
                $attribute->appendChild($min_age_node);
            }

            if ($venipak_return_service > 0) {
                $return_service = $document->createElement('return_service', $venipak_return_service);
                $return_consignee = $document->createElement('return_consignee');
                    $return_doc_consignee_name = $document->createElement('name', $this->venipak_sender_name );
                    $return_doc_consignee_company_code = $document->createElement( 'company_code',  $this->venipak_sender_company_code );
                    $return_doc_consignee_country = $document->createElement( 'country', $this->venipak_sender_country );
                    $return_doc_consignee_city = $document->createElement( 'city', $this->venipak_sender_city );
                    $return_doc_consignee_address = $document->createElement( 'address', $this->venipak_sender_address );
                    $return_doc_consignee_post_code = $document->createElement( 'post_code', preg_replace('/\D/', '', $this->venipak_sender_post_code));
                    $return_doc_consignee_contact_person = $document->createElement( 'contact_person', $this->venipak_sender_contact_person );
                    $return_doc_consignee_contact_tel = $document->createElement( 'contact_tel', $this->venipak_sender_contact_tel );

                    $return_consignee->appendChild($return_doc_consignee_name);
                    $return_consignee->appendChild($return_doc_consignee_company_code);
                    $return_consignee->appendChild($return_doc_consignee_country);
                    $return_consignee->appendChild($return_doc_consignee_city);
                    $return_consignee->appendChild($return_doc_consignee_address);
                    $return_consignee->appendChild($return_doc_consignee_post_code);
                    $return_consignee->appendChild($return_doc_consignee_contact_person);
                    $return_consignee->appendChild($return_doc_consignee_contact_tel);
                    $return_consignee->appendChild($return_doc_consignee_name);
                $shipment->appendChild($return_consignee);   
                $attribute->appendChild($return_service);           
            }

            $shipment_type = self::get_shipment_type($this->venipak_sender_country, $consignee_country);
            $is_global = $shipment_type === self::SHIPMENT_GLOBAL;
            $is_international = $shipment_type === self::SHIPMENT_INTERNATIONAL;
            if ($is_international) {
                // Our senders are Baltic, so the block stays empty: its only field, pickup_date, is for PL/FI senders.
                $attribute->appendChild($document->createElement( 'international' ));
            }
            if ($is_global) {
                $global = $document->createElement( 'global' );
                $attribute->appendChild($global);
                $global_delivery = $document->createElement( 'global_delivery', 'global' );
                $global->appendChild($global_delivery);
                $shipment_description = $document->createElement( 'shipment_description', $shipment_description_text );
                $global->appendChild($shipment_description);
                $value = $document->createElement( 'value', $customs && $customs['value'] !== null ? $customs['value'] : $order->get_total() );
                $global->appendChild($value);
                if ($this->venipak_sender_eori) {
                    $global->appendChild($document->createElement( 'eori', $this->venipak_sender_eori ));
                }
                if ($customs && $customs['document']) {
                    $document_node = $document->createElement( 'document' );
                    $document_node->appendChild($document->createElement( 'doc_type', $customs['document']['type'] ));
                    $document_node->appendChild($document->createElement( 'doc_name', $customs['document']['name'] ));
                    $document_node->appendChild($document->createElement( 'doc_mime', 'application/pdf' ));
                    $document_node->appendChild($document->createElement( 'doc_content', $customs['document']['content'] ));
                    $global->appendChild($document_node);
                }
            }
            // Count how many pack numbers this order needs
            $needed_packs = 0;
            $products_with_shipments = [];
            $products_without_shipments = [];

            if ($packs) {
                $needed_packs = sizeof($packs);
            } else {
                foreach ($order_products as $product) {
                    $product_total_shipments = $product->get_meta('_venipak_total_shipments', true) ?: $product->get_meta('_omnivalt_total_shipments', true);
                    if ($product_total_shipments) {
                        $products_with_shipments[] = $product;
                        $needed_packs += intval($product_total_shipments);
                    } else {
                        $products_without_shipments[] = $product;
                    }
                }
                $needed_packs += $venipak_pickup_point ? 1 : max(1, ceil(sizeof($products_without_shipments) / $this->shopup_venipak_shipping_field_maxpackproducts));
            }

            // Manual packs are checked before any pack number is reserved. Callers send one order
            // per call, so refusing it ends the call.
            $pack_error = $is_international && $packs ? $this->international_packs_error($packs) : null;
            // Outside the network Venipak needs every pack's dimensions and description. Built packs
            // have neither. A non-Baltic sender within the network goes as before.
            if (self::is_outside_network($consignee_country)) {
                $pack_error = $packs
                    ? $this->incomplete_packs_error($packs)
                    : __( 'Shipments to countries outside LT, LV, EE, PL and FI need each package\'s dimensions and description. Enter them in the packages table on the order page and dispatch from there.', 'woocommerce-shopup-venipak-shipping' );
            }
            if (!$pack_error && $packs) {
                $pack_error = $this->implausibly_small_packs_error($packs);
            }
            if ($pack_error) {
                $this->record_dispatch_refusal($order, $pack_error);
                return new WP_Error('venipak_packs_refused', $pack_error);
            }

            // Atomically reserve pack numbers
            $reserved_start = $this->settings->reserve_pack_numbers($needed_packs);
            $current_pack_nr = $reserved_start;

            // Initialize pack numbers array
            $pack_numbers = [];
            $total_weight = 0;

            if ($packs) {
                for ($i = 0; $i < sizeof($packs); $i++) {
                    $pack = $document->createElement( 'pack' );
                    $current_pack_nr++;
                    $pack_nr = $this->settings->format_pack_number($current_pack_nr);
                    $pack_no = $document->createElement( 'pack_no', $pack_nr );
                    $pack->appendChild($pack_no);
                    $packs[$i]['weight'] = max($packs[$i]['weight'], 0.1);

                    if ($is_global || ($is_international && $packs[$i]['weight'] > self::INTERNATIONAL_WEIGHT_GROUP_KG)) {
                        $width = $document->createElement('width', $packs[$i]['width']);
                        $pack->appendChild($width);
                        $height = $document->createElement('height', $packs[$i]['height']);
                        $pack->appendChild($height);
                        $length = $document->createElement('length', $packs[$i]['length']);
                        $pack->appendChild($length);
                    }

                    $volume = self::format_volume($packs[$i]['width'], $packs[$i]['height'], $packs[$i]['length']);
                    if ($volume !== '0') {
                        $pack->appendChild($document->createElement('volume', $volume));
                    }

                    if ($is_global || $packs[$i]['weight'] > 0) {
                        $weight = $document->createElement('weight', $packs[$i]['weight']);
                        $pack->appendChild($weight);
                    }

                    if ($is_global || $packs[$i]['description'] != '') {
                        $description = $document->createElement('description', htmlspecialchars($packs[$i]['description']));
                        $pack->appendChild($description);
                    }
                    $shipment->appendChild($pack);
                    $pack_numbers[] = $pack_nr;
                    $total_weight += $packs[$i]['weight'];
                }
            } else {
                $auto_packs = [];
                // Build packs for products with total_shipments
                foreach ($products_with_shipments as $product) {
                    $product_total_shipments = $product->get_meta('_venipak_total_shipments', true) ?: $product->get_meta('_omnivalt_total_shipments', true);
                    // Each pack carries its share of the product weight, in kg, as the order page's pack table shows it.
                    $product_weight_kg = wc_get_weight($this->get_product_weight($product), 'kg');
                    $pack_weight = max($product_weight_kg / max(intval($product_total_shipments), 1), 0.1);
                    for ($i = 0; $i < $product_total_shipments; $i++) {
                        $current_pack_nr++;
                        $pack_nr = $this->settings->format_pack_number($current_pack_nr);
                        $pack = $document->createElement('pack');
                        $pack_no = $document->createElement('pack_no', $pack_nr);
                        $pack->appendChild($pack_no);

                        $weight = $document->createElement('weight', $pack_weight);
                        $pack->appendChild($weight);
                        $auto_packs[] = array('weight' => $pack_weight);

                        $description = $document->createElement('description', htmlspecialchars($product->get_title()));
                        $pack->appendChild($description);

                        $shipment->appendChild($pack);
                        $pack_numbers[] = $pack_nr;
                        $total_weight += $pack_weight;
                    }
                }
                // Continue with existing logic for products without total_shipments
                $pack_count = $venipak_pickup_point ? 1 : ceil(sizeof($products_without_shipments) / $this->shopup_venipak_shipping_field_maxpackproducts);
                for ($i = 0; $i < $pack_count; $i++) {
                    $current_pack_nr++;
                    $pack_nr = $this->settings->format_pack_number($current_pack_nr);
                    $pack = $document->createElement('pack');
                    $pack_no = $document->createElement('pack_no', $pack_nr);
                    $pack->appendChild($pack_no);

                    $range_from = $i * $this->shopup_venipak_shipping_field_maxpackproducts;
                    $range_to = $range_from + $this->shopup_venipak_shipping_field_maxpackproducts;
                    $pack_weight = 0;
                    $pack_description = '';
                    $prev_title = 'no-repeat';

                    for ($y = $range_from; $y < $range_to; $y++) {
                        if (!array_key_exists($y, $products_without_shipments)) break;
                        $pack_weight += $this->get_product_weight($products_without_shipments[$y]);
                        if ($prev_title !== $products_without_shipments[$y]->get_title()) {
                            $pack_description .= $products_without_shipments[$y]->get_title() . PHP_EOL;
                            $prev_title = $products_without_shipments[$y]->get_title();
                        }
                    }
                    $pack_weight = max(wc_get_weight($pack_weight, 'kg'), 0.1);
                    $weight = $document->createElement('weight', $pack_weight);
                    $pack->appendChild($weight);
                    $auto_packs[] = array('weight' => $pack_weight);
                    $description = $document->createElement('description', htmlspecialchars($pack_description));
                    $pack->appendChild($description);
                    $shipment->appendChild($pack);
                    $pack_numbers[] = $pack_nr;
                    $total_weight += $pack_weight;
                }

                // Built packs only know their weight here, after their numbers were reserved; the
                // unused numbers are a harmless gap. Built packs carry no dimensions.
                $pack_error = $is_international ? $this->international_packs_error($auto_packs) : null;
                if ($pack_error) {
                    $this->record_dispatch_refusal($order, $pack_error);
                    return new WP_Error('venipak_packs_refused', $pack_error);
                }
            }

            // Update venipak_shipping_order_data. These pack numbers have not been sent yet, so an
            // 'error' left from an earlier attempt no longer describes them: until Venipak answers
            // the order is in flight, which is_unconfirmed() recognises if the answer never comes.
            $venipak_shipping_order_data['status'] = '';
            $venipak_shipping_order_data['manifest'] = $manifest_value;
            $venipak_shipping_order_data['pack_numbers'] = $pack_numbers;
            $venipak_shipping_order_data['weight'] = $total_weight;
            $venipak_shipping_order_data['products_count'] = sizeof($order_products);
            $order->update_meta_data('venipak_shipping_order_data', json_encode($venipak_shipping_order_data));
            $order->save();
            $xml_not_empty = true;

        }
        return $xml_not_empty ? $document->saveXML() : null;
    }

    public function get_product_weight($product) {
        if (!$product->get_virtual()) {
            $weight = $product->get_weight();
            if ($weight) {
                return $weight;
            }
        }

        return 0;
    }

    public static function get_order_address($order) {
        $ship_to_billing = get_option('woocommerce_ship_to_destination');
        $is_shipping_address = $order->get_shipping_address_1() && $ship_to_billing !== 'billing_only';

        $company = $order->get_shipping_company() !== '' ? $order->get_shipping_company() : $order->get_billing_company();
        if ($company) {
            $full_name = $company;
        } elseif ($is_shipping_address) {
            $full_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        } else {
            $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        if ($is_shipping_address) {
            $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
            $city = $order->get_shipping_city();
            $postcode = $order->get_shipping_postcode();
            $country = $order->get_shipping_country();
            $phone = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
        } else {
            $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
            $city = $order->get_billing_city();
            $postcode = $order->get_billing_postcode();
            $country = $order->get_billing_country();
            $phone = $order->get_billing_phone();
        }

        $contact_person = $is_shipping_address ? $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        return [
            'full_name' => $full_name,
            'contact_person' => $contact_person,
            'address' => $address,
            'city' => $city,
            'postcode' => preg_replace('/\D/', '', $postcode),
            'country' => $country,
            'phone' => $phone,
        ];
    }
}
