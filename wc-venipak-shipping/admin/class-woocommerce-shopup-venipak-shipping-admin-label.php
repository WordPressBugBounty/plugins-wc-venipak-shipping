<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://shopup.lt/
 * @since      1.7.0
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
class Woocommerce_Shopup_Venipak_Shipping_Admin_Label {

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
	 * @since    1.11.0
	 */
	private $label_format;

	/**
   *
   *
   * @since    1.0.0
   */
	private $settings;

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
		$this->venipak_username = $settings->get_option_by_key('shopup_venipak_shipping_field_username');
		$this->venipak_password = $settings->get_option_by_key('shopup_venipak_shipping_field_password');
		// get_option_by_key() returns null for a key that was never saved — the 'a4' default
		// declared on the settings field only feeds the radio markup, not reads. Shops that
		// upgraded from before 1.11.0 and never re-saved their settings would otherwise post
		// an empty format and get an unusable answer back.
		$this->label_format = $settings->get_option_by_key('shopup_venipak_shipping_field_labelformat') ?: 'a4';

	}

	public function add_venipak_shipping_bulk_action_process( $redirect_to, $action, $post_ids ) {
		if ( $action === 'shopup_venipak_shipping_labels' ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( -1, 403 );
			}

			$pack_no_collection = array();
			foreach ( $post_ids as $post_id ) {
				// Keep the pack numbers in the order the merchant selected them.
				foreach ( $this->get_order_pack_numbers( $post_id ) as $pack_number ) {
					$pack_no_collection[] = $pack_number;
				}
			}

			if ( ! $pack_no_collection ) {
				$this->fail_with_message( __( 'None of the selected orders has been dispatched to Venipak yet, so there are no labels to print.', 'woocommerce-shopup-venipak-shipping' ) );
			}

			$this->stream_pdf(
				'https://go.venipak.lt/ws/print_label',
				array(
					'user' => $this->venipak_username,
					'pass' => $this->venipak_password,
					'pack_no' => $pack_no_collection,
					'format' => $this->label_format,
					'carrier' => 'all'
				),
				'venipak-labels.pdf'
			);
		}
		return $redirect_to;
	}



	public function get_label_pdf() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1, 403 );
		}
		$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_die();
		}

		$pack_numbers = $this->get_order_pack_numbers( $order_id );
		if ( ! $pack_numbers ) {
			$this->fail_with_message( __( 'This order has not been dispatched to Venipak yet, so it has no label.', 'woocommerce-shopup-venipak-shipping' ) );
		}

		$this->stream_pdf(
			'https://go.venipak.lt/ws/print_label',
			array(
				'user' => $this->venipak_username,
				'pass' => $this->venipak_password,
				'pack_no' => $pack_numbers,
				'format' => $this->label_format,
				'carrier' => 'all'
			),
			'venipak-label-' . $order_id . '.pdf'
		);
	}


	public function get_manifest_pdf() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1, 403 );
		}
		$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_die();
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die();
		}
		$venipak_shipping_order_data = json_decode( $order->get_meta( 'venipak_shipping_order_data', true ), true );
		$manifest = is_array( $venipak_shipping_order_data ) && ! empty( $venipak_shipping_order_data['manifest'] )
			? $venipak_shipping_order_data['manifest']
			: '';

		if ( ! $manifest ) {
			$this->fail_with_message( __( 'This order has not been dispatched to Venipak yet, so it has no manifest.', 'woocommerce-shopup-venipak-shipping' ) );
		}

		$this->stream_pdf(
			'https://go.venipak.lt/ws/print_list',
			array(
				'user' => $this->venipak_username,
				'pass' => $this->venipak_password,
				'code' => $manifest
			),
			'venipak-manifest-' . $order_id . '.pdf'
		);
	}

	/**
	 * Pack numbers of a dispatched order, or an empty array for anything not dispatched.
	 *
	 * @since    1.26.4
	 */
	private function get_order_pack_numbers( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		$venipak_shipping_order_data = json_decode( $order->get_meta( 'venipak_shipping_order_data', true ), true );
		if ( ! is_array( $venipak_shipping_order_data ) || empty( $venipak_shipping_order_data['pack_numbers'] ) || ! is_array( $venipak_shipping_order_data['pack_numbers'] ) ) {
			return array();
		}

		return $venipak_shipping_order_data['pack_numbers'];
	}

	/**
	 * Ask Venipak for a PDF and send it to the browser.
	 *
	 * Everything is checked *before* the PDF content type goes out: declaring a PDF and then
	 * echoing an empty or non-PDF body is what turns every failure here into a blank viewer
	 * window with nothing to go on. A bulk print of many labels is also the slowest call the
	 * plugin makes, so it cannot run on WordPress's 5 second default timeout.
	 *
	 * @since    1.26.4
	 */
	private function stream_pdf( $url, $body, $filename ) {
		$response = wp_remote_post( $url, array(
			'body' => $body,
			'timeout' => 60,
			'headers' => array(
				'Referer' => 'https://woocommerce.com/'
			)
		) );

		if ( is_wp_error( $response ) ) {
			$this->fail_with_message( sprintf(
				/* translators: %s: transport error reported by WordPress */
				__( 'Venipak did not answer: %s', 'woocommerce-shopup-venipak-shipping' ),
				$response->get_error_message()
			) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$pdf = wp_remote_retrieve_body( $response );

		// The endpoint answers errors as plain text or XML on the same 200 response, so the
		// body itself has to be inspected rather than just the status code.
		if ( $status !== 200 || strncmp( ltrim( substr( $pdf, 0, 16 ) ), '%PDF', 4 ) !== 0 ) {
			$detail = trim( strip_tags( $pdf ) );
			error_log( 'VENIPAK ' . $url . ' returned status ' . $status . ': ' . substr( $detail, 0, 500 ) );
			$this->fail_with_message( sprintf(
				/* translators: %s: error text returned by Venipak */
				__( 'Venipak returned no printable PDF: %s', 'woocommerce-shopup-venipak-shipping' ),
				$detail !== '' ? $detail : __( 'empty response', 'woocommerce-shopup-venipak-shipping' )
			) );
		}

		if ( headers_sent() ) {
			$this->fail_with_message( __( 'Another plugin has already sent output, so the PDF cannot be delivered.', 'woocommerce-shopup-venipak-shipping' ) );
		}

		// Stray output from anything else on the page would corrupt the PDF stream.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		echo $pdf;
		exit;
	}

	/**
	 * Show the merchant why no PDF is coming instead of handing the viewer a blank page.
	 *
	 * @since    1.26.4
	 */
	private function fail_with_message( $message ) {
		wp_die(
			esc_html( $message ),
			esc_html__( 'Venipak', 'woocommerce-shopup-venipak-shipping' ),
			array( 'back_link' => true, 'response' => 500 )
		);
	}
}
