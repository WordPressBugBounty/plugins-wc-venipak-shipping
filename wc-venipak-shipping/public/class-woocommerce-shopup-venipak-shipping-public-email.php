<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://shopup.lt/
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
class Woocommerce_Shopup_Venipak_Shipping_Public_Email {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $settings ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->settings = $settings;

	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_tracking_number( $order, $sent_to_admin, $plain_text, $email ) {
		$country = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();

		switch ($country) {
			case "LT":
				$domain = 'lt';
				break;
			case "EE":
				$domain = 'ee';
				break;
			case "LV":
				$domain = 'lv';
				break;
			default:
				$domain = 'lt/en/';
		}
		$gls_tracking_number = $order->get_meta('venipak_gls');
		if ($gls_tracking_number) {
    		echo '<p>' . __( 'Your tracking order code number:', 'woocommerce-shopup-venipak-shipping' ) . ' ' . esc_html( $gls_tracking_number ) . '</p>';
		} else {
			$venipak_shipping_order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
      // An order can carry this meta without ever having been dispatched — a failed dispatch
      // writes it too. Testing the array alone would email a tracking link with no code in it.
      if ($venipak_shipping_order_data && !empty($venipak_shipping_order_data['pack_numbers'][0])) {
        $this->print_tracking_link( $domain, $venipak_shipping_order_data['pack_numbers'][0] );
      } else {
        $tracking = $order->get_meta('venipak_shipping_tracking');
        if ($tracking) {
          $this->print_tracking_link( $domain, $this->settings->format_pack_number($tracking) );
        }
      }
		}
	}

	/**
	 * The pack number comes from Venipak, so it is escaped like any outside text.
	 */
	private function print_tracking_link( $domain, $tracking_code ) {
		$url = 'https://venipak.' . $domain . '/tracking/track/' . rawurlencode( $tracking_code ) . '/';
		echo '<p>' . __( 'Your tracking order code ', 'woocommerce-shopup-venipak-shipping' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html( $tracking_code ) . '</a></p>';
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_selected_pickup_info( $order, $sent_to_admin, $plain_text, $email ) {
		$venipak_pickup = venipak_delivery_pickup_point( $order );

		if (!$venipak_pickup) return;

		// Escaped or stripped below: this is Venipak's feed text, or, on an order placed before checkout
		// accepted only ids, whatever the buyer's browser sent.
		$name = !empty($venipak_pickup['display_name']) ? $venipak_pickup['display_name'] : $venipak_pickup['name'];

		if ($plain_text) {
			// Same shape as WooCommerce's own plain email sections: an upper-case heading, then the text.
			echo "\n" . esc_html( wc_strtoupper( esc_html__( 'Your selected pickup location', 'woocommerce-shopup-venipak-shipping' ) ) ) . "\n\n";
			// Not esc_html: WooCommerce turns the plain email back into text with $plain_search,
			// whose catch-all drops entities it does not know, so "O'Brien" would lose its '.
			echo wp_strip_all_tags( $name . ', ' . $venipak_pickup['address'] . ', ' . $venipak_pickup['city'] ) . "\n";
			return;
		}

		echo '<h2>' . esc_html__( 'Your selected pickup location', 'woocommerce-shopup-venipak-shipping' ) . ':</h2>';
		echo '<p><b>' . esc_html( $name ) . '</b>, ' . esc_html( $venipak_pickup['address'] ) . ', ' . esc_html( $venipak_pickup['city'] ) . '</p>';
	}
}
