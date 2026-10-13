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
class Woocommerce_Shopup_Venipak_Shipping_Public_Pickup_Checkout {

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
	private $googlemap_api_key;

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	private $is_map_enabled;


	/**
	 *
	 *
	 * @since    1.2.0
	 */
	private $pickup_type;

	/**
	 *
	 *
	 * @since    1.3.0
	 */
	private $is_clusters_enabled;

	/**
	 *
	 *
	 * @since    1.25.10
	 */
	private $pickup_position;

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
		$this->googlemap_api_key = $settings->get_option_by_key('shopup_venipak_shipping_field_googlemapapikey');
		$this->is_map_enabled = $settings->get_option_by_key('shopup_venipak_shipping_field_ismapenabled');
		$this->pickup_type = $settings->get_option_by_key('shopup_venipak_shipping_field_pickuptype');
		$this->is_clusters_enabled = $settings->get_option_by_key('shopup_venipak_shipping_field_isclustersenabled');
		$this->pickup_position = $settings->get_option_by_key('shopup_venipak_shipping_field_pickupposition') ?: 'after_shipping';
	}

	/**
	 * Get the pickup position setting.
	 *
	 * @since    1.25.10
	 */
	public function get_pickup_position() {
		return $this->pickup_position;
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_pickup_options() {

		if (in_array('shopup_venipak_shipping_pickup_method', wc_get_chosen_shipping_method_ids())) {
			wc_get_template(
				'woocommerce/checkout/venipak-shipping-terminals.php',
				array(),
				'',
				untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/' );
		}
	}

	/**
	 * Render pickup selector inline after the pickup shipping method.
	 *
	 * @since    1.25.10
	 */
	public function add_venipak_shipping_pickup_options_inline( $method, $index ) {
		if ( ! is_checkout() ) return;

		$chosen_method_id = WC()->session->chosen_shipping_methods[ $index ];

		if ( $chosen_method_id !== $method->id || $method->method_id !== 'shopup_venipak_shipping_pickup_method' ) {
			return;
		}

		wc_get_template(
			'woocommerce/checkout/venipak-shipping-terminals-inline.php',
			array(),
			'',
			untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/' );
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_pickup_checkout_process() {
		if ( isset( $_POST['venipak_pickup_point'] ) && self::submitted_pickup_point_id( $_POST['venipak_pickup_point'] ) === '' )
			wc_add_notice( ( __( 'Select pickup point', 'woocommerce-shopup-venipak-shipping' ) ), "error" );
	}
	 
	 
	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_pickup_update_order_meta($order_id, $data) { 
		$submitted = isset($_POST['venipak_pickup_point']) ? $_POST['venipak_pickup_point'] : null;
		self::save_checkout_pickup(wc_get_order($order_id), $submitted);
	}
	

 
	public function add_venipak_shipping_pickup_update_order_meta_block_checkout($order, $request) { 
		// Paying for an existing order (/wc/store/v1/checkout/<id>, also /wc/store/checkout/<id>) is
		// not a delivery choice: the order already has its point, possibly one an admin set.
		if (preg_match('#/checkout/\d+$#', $request->get_route())) return;
		$body = $request->get_body();
		$fields = json_decode($body, true);  
		$submitted = isset($fields['shipping_address']['venipak_pickup_point']) ? $fields['shipping_address']['venipak_pickup_point'] : null;
		self::save_checkout_pickup($order, $submitted);
	}

	/**
	 * Keep the submitted point only on an order that ships with the pickup method; any other
	 * method clears the point. The block checkout still sends a point picked before the buyer
	 * switched to courier, and an order reused after a failed payment still has the earlier one.
	 * Both checkouts have created the order's shipping line by the time these hooks run.
	 * $submitted is null when the checkout sent no point; an earlier point then stays.
	 *
	 * @since    1.27.0
	 */
	private static function save_checkout_pickup($order, $submitted) {
		$is_pickup_order = venipak_order_shipping_method_id($order) === 'shopup_venipak_shipping_pickup_method';
		$nothing_to_do = $is_pickup_order ? $submitted === null : $order->get_meta('venipak_pickup_point') === '';
		if ($nothing_to_do) return;

		if ($is_pickup_order) {
			venipak_store_order_pickup($order, self::submitted_pickup_point_id($submitted));
		} else {
			venipak_clear_order_pickup($order);
		}
		$order->save();
	}

	/**
	 * The pickup point id a checkout submitted, or '' when the value is not an id — which
	 * venipak_store_order_pickup() takes as "no point" and clears. Venipak's ids are integers;
	 * anything else (a whole point as JSON, say) would be shown on the order screen and in
	 * emails as if it came from Venipak, so it never reaches the order.
	 * The block checkout sends the id as a JSON number, the classic checkout as a string.
	 *
	 * @since    1.27.0
	 */
	private static function submitted_pickup_point_id( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) ) return '';
		$value = trim( (string) $value );
		return ctype_digit( $value ) ? $value : '';
	}

	/**
	 *
	 *
	 * @since    1.14.9
	 */
	public function validate_cod( $fields, $errors ) {
		$items = venipak_fetch_pickups();

		if ($fields['payment_method'] === 'cod' && strpos($fields['shipping_method'][0], 'shopup_venipak_shipping_pickup_method') !== false) {
			foreach($items as $point) {
				if ($point["id"] == $_POST['venipak_pickup_point'] && $point["cod_enabled"] === 0) {
					$errors->add( 'validation', __( 'Cod incompatible', 'woocommerce-shopup-venipak-shipping' ) );
				}
			}
		}
	}
	
	
	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_pickup_points() {
		// Only the order edit screen sends order_id. The checkout path below reads the
		// session country and cart, which there belong to the logged-in manager, not to
		// the order — so that screen gets its own answer and never falls through.
		if ( isset( $_GET['order_id'] ) ) {
			echo json_encode( $this->get_pickup_points_for_order( absint( $_GET['order_id'] ) ) );
			wp_die();
		}

		$cart_total_weight = WC()->cart->cart_contents_weight;
		if (get_option('woocommerce_weight_unit') == 'g') {
			$cart_total_weight /= 1000;
		}

		switch (get_option( 'woocommerce_dimension_unit' )) {
		case 'm':
			$unit_multiplayer = 0.01;
			break;
		case 'mm':
			$unit_multiplayer = 10;
			break;
		default:
			$unit_multiplayer = 1;
		}

		$maximum_weight_pickup = 10;

		// Locker internal max dimensions (cm by default)
		$venipak_box_dims = [
			61 * $unit_multiplayer,
			39.5 * $unit_multiplayer,
			41 * $unit_multiplayer,
		];
		sort( $venipak_box_dims ); // allow any orientation by comparing sorted dims
		$venipak_max_volume = array_product( $venipak_box_dims );
		
		$is_valid_for_locker = true;
		$is_valid_for_pickup = true;
		$total_cart_volume = 0;
		
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data']; // Directly use the product data

			if ($product->is_type('variation')) {
				$product = new WC_Product_Variation($cart_item['variation_id']);
			}
			$product_dimensions = [
				(float) $product->get_length() ?: 0,
				(float) $product->get_width() ?: 0,
				(float) $product->get_height() ?: 0,
			];
			// Variations often have empty dimensions while the parent product has them.
			if (
				$product->is_type( 'variation' ) &&
				$product_dimensions[0] <= 0 &&
				$product_dimensions[1] <= 0 &&
				$product_dimensions[2] <= 0 &&
				! empty( $cart_item['product_id'] )
			) {
				$parent_product = wc_get_product( $cart_item['product_id'] );
				if ( $parent_product ) {
					$product_dimensions = [
						(float) $parent_product->get_length() ?: 0,
						(float) $parent_product->get_width() ?: 0,
						(float) $parent_product->get_height() ?: 0,
					];
				}
			}
			sort( $product_dimensions );
			$total_cart_volume += ( $product_dimensions[0] * $product_dimensions[1] * $product_dimensions[2] ) * $cart_item['quantity'];
			if (
				$product_dimensions[0] > $venipak_box_dims[0] ||
				$product_dimensions[1] > $venipak_box_dims[1] ||
				$product_dimensions[2] > $venipak_box_dims[2]
			) {
				$is_valid_for_locker = false;
			}
			$product_min_age = $product->get_meta('shopup_venipak_shipping_min_age');
			if ($product_min_age > 0) {
				$is_valid_for_locker = false;
				$is_valid_for_pickup = false;
			}
		}
		// if ($cart_total_weight > $maximum_weight_locker) {
		//   $is_valid_for_locker = false;
		// }
		if ($cart_total_weight > $maximum_weight_pickup) {
			$is_valid_for_pickup = false;
		}
		if ($total_cart_volume > $venipak_max_volume) {
			$is_valid_for_locker = false;
		}

		if ($this->pickup_type === 'all' && !$is_valid_for_locker && $is_valid_for_pickup) {
			$pickup_type = 1; // only pickup
		} elseif ($this->pickup_type === 'all' && $is_valid_for_locker && !$is_valid_for_pickup) {
			$pickup_type = 3; // only locker
		} else {
			$pickup_type = $this->pickup_type;
		}


		if (!$is_valid_for_locker && !$is_valid_for_pickup) {
			wp_die();
		}
		
		// Cast: venipak_fetch_pickups(null) would return every country instead of none.
		$country = (string) WC()->customer->get_shipping_country();
		$result = $this->filter_pickup_points_by_type( venipak_fetch_pickups( $country ), $pickup_type );
		echo json_encode($result);
		wp_die();
	}

	/**
	 * Points for the order edit screen: the order's shipping country, else its billing
	 * country, else every country. No cart checks — the manager picks for the order.
	 *
	 * @since    1.27.0
	 */
	private function get_pickup_points_for_order( $order_id ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) return [];

		$order = wc_get_order( $order_id );
		// A refund is also returned by wc_get_order but has no address.
		if ( ! ( $order instanceof WC_Order ) ) return [];

		$country = $order->get_shipping_country() ?: $order->get_billing_country();
		$points = $country ? venipak_fetch_pickups( $country ) : venipak_fetch_pickups();
		return $this->filter_pickup_points_by_type( $points, $this->pickup_type );
	}

	/**
	 * Keep only points of the given pickup type; 'all' keeps every point.
	 * The loose != is deliberate: the setting holds '1' / '3', the feed int 1 / 3.
	 *
	 * @since    1.27.0
	 */
	private function filter_pickup_points_by_type( $points, $pickup_type ) {
		$result = [];
		foreach ( $points as $item ) {
			if ( $pickup_type !== 'all' && $pickup_type != $item['type'] ) continue;
			$result[] = $item;
		}
		return $result;
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_checkout_settings() {
		echo json_encode(array(
			'googlemap_api_key' => $this->googlemap_api_key,
			'is_map_enabled' => $this->googlemap_api_key !== '' ? !!$this->is_map_enabled : false,
			'pickup_marker' => plugin_dir_url( __FILE__ ) . 'images/venipak-marker.svg',
			'locker_marker' => plugin_dir_url( __FILE__ ) . 'images/venipak-marker.svg'
		));
		wp_die();
	}

	 
}
