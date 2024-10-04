<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shopup_Venipak_Shipping
 * @subpackage Woocommerce_Shopup_Venipak_Shipping/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shopup_Venipak_Shipping
 * @subpackage Woocommerce_Shopup_Venipak_Shipping/public
 * @author     ShopUp <info@shopup.lt>
 */
class Woocommerce_Shopup_Venipak_Shipping_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_shortcode( 'venipak_tracking', array( $this, 'venipak_shipping_status_shortcode' ) );
	}

	/**
	 * 
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_logo( $label, $method ) {
		$icon = '<img width="60" class="wc-venipak-shipping-logo" src="' . plugin_dir_url( __FILE__ ) . 'images/venipak-logo.png' . '" />';  
		if ( $method->method_id === "shopup_venipak_shipping_pickup_method" ||  $method->method_id === "shopup_venipak_shipping_courier_method") {
			return "{$icon} {$label}";
		}
		return $label;  
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Load styles only on cart and checkout pages
		if ( is_cart() || is_checkout() ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-shopup-venipak-shipping-public.css?v=' . $this->version );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Load scripts only on cart and checkout pages
		if ( is_cart() || is_checkout() ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-shopup-venipak-shipping-public.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'google_cluster_js', 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', array(), '1', true );
			wp_enqueue_script( 'shopup_select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array(), '1', true );
			wp_enqueue_style( 'shopup_select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '1', true );
			wp_add_inline_script( $this->plugin_name, "window.adminUrl = '" . admin_url(). "';" );
		}
	}

	/**
	 * Shortcode to display Venipak shipping tracking status.
	 *
	 * @since 1.0.0
	 */
	public function venipak_shipping_status_shortcode( $atts ) {
		// If order_id is not passed in the shortcode attributes, attempt to get it from the global order context
		if ( ! isset( $atts['order_id'] ) ) {
			$order = $this->get_current_order(); // Use a custom function to retrieve the order dynamically
		} else {
			$order = wc_get_order( $atts['order_id'] );
		}

		$order = wc_get_order( $atts['order_id'] );
		if ( ! $order ) {
			return ''; // No valid order found
		}

		// Retrieve Venipak shipping order data from the order meta
		$venipak_shipping_order_data = json_decode( $order->get_meta( 'venipak_shipping_order_data' ), true );

		if ( $venipak_shipping_order_data && isset( $venipak_shipping_order_data['pack_numbers'][0] ) ) {
			$pack_number = $venipak_shipping_order_data['pack_numbers'][0];
			
			// Get the shipping country from the order
			$shipping_country = $order->get_shipping_country();
			
			// Determine the domain based on the shipping country
			$domain = 'com'; // Default to 'com'
			if ( $shipping_country === 'LT' ) {
				$domain = 'lt';
			} elseif ( $shipping_country === 'LV' ) {
				$domain = 'lv';
			} elseif ( $shipping_country === 'EE' ) {
				$domain = 'ee';
			}

			// Return the tracking link HTML
			return '<p>' . __( 'Your tracking order code ', 'woocommerce-shopup-venipak-shipping' ) . ' <a href="https://venipak.' . $domain . '/tracking/track/' . $pack_number . '/">' . $pack_number . '</a></p>';
		}

		return ''; // Return nothing if no shipping data is available
	}

	/**
	 * Custom function to retrieve the current order in the email processing flow.
	 *
	 * This will try to get the order from the global context of WooCommerce emails.
	 *
	 * @since 1.0.0
	 */
	public function get_current_order() {
		// Check if the order object is available globally (useful in email templates)
		if ( isset( $GLOBALS['order'] ) && $GLOBALS['order'] instanceof WC_Order ) {
			return $GLOBALS['order'];
		}

		// Return null if no order is available
		return null;
	}
}
