<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://shopup.lt/
 * @since      1.5.0
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
class Woocommerce_Shopup_Venipak_Shipping_Admin_Product {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.5.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.5.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.5.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function add_venipak_shipping_options() {
		$product = wc_get_product(get_the_ID());

		woocommerce_wp_text_input( array(
			'id'          => 'shopup_venipak_shipping_min_age',
			'label'       => __( 'Min. buyer age', 'woocommerce-shopup-venipak-shipping' ),
			'placeholder' => '20',
			'desc_tip'    => 'true',
			'description' => __( 'To be able to use this option, you should agree with your Venipak manager.', 'woocommerce' ),
			'value'       => $product->get_meta('shopup_venipak_shipping_min_age', true ),
		) );

		// Add the new field _venipak_total_shipments
		$default_total_shipments = $product->get_meta('_omnivalt_total_shipments', true );
		woocommerce_wp_text_input( array(
			'id'          => '_venipak_total_shipments',
			'label'       => __( 'Venipak Total Shipments', 'woocommerce-shopup-venipak-shipping' ),
			'placeholder' => '',
			'desc_tip'    => 'true',
			'description' => __( 'Total shipments for Venipak.', 'woocommerce' ),
			'value'       => $product->get_meta('_venipak_total_shipments', true ) ? $product->get_meta('_venipak_total_shipments', true ) : $default_total_shipments,
		) );
	}

	public function save_venipak_shipping_options( $product_id ) {
		$product = wc_get_product($product_id);

		$shopup_venipak_shipping_min_age = $_POST['shopup_venipak_shipping_min_age'];
		if ( isset( $shopup_venipak_shipping_min_age ) ) {
			$product->update_meta_data('shopup_venipak_shipping_min_age', esc_attr( $shopup_venipak_shipping_min_age ) );
		}

		// Save the new field _venipak_total_shipments
		$venipak_total_shipments = $_POST['_venipak_total_shipments'];
		if ( isset( $venipak_total_shipments ) ) {
			$product->update_meta_data('_venipak_total_shipments', esc_attr( $venipak_total_shipments ) );
		}

		$product->save();
	}
}