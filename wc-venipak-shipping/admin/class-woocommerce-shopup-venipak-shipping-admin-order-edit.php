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
class Woocommerce_Shopup_Venipak_Shipping_Admin_Order_Edit {

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
	 * @since    1.2.0
	 */
	private $pickup_type;

	private $shopup_venipak_shipping_field_forcedispatch;

	private $shopup_venipak_shipping_field_maxpackproducts;

	private $iseventoptiondisabled ;

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
		$this->pickup_type = $settings->get_option_by_key('shopup_venipak_shipping_field_pickuptype');
		$this->shopup_venipak_shipping_field_forcedispatch = $settings->get_option_by_key('shopup_venipak_shipping_field_forcedispatch');
		$optionValue = $settings->get_option_by_key('shopup_venipak_shipping_field_maxpackproducts');
		if ($optionValue !== null) {
			$this->shopup_venipak_shipping_field_maxpackproducts = $optionValue;
		} else {
			$this->shopup_venipak_shipping_field_maxpackproducts = 1000;
		}
		$iseventoptiondisabled = $settings->get_option_by_key('shopup_venipak_shipping_field_iseventoptiondisabled'); 
		if ($iseventoptiondisabled !== null) {
			$this->iseventoptiondisabled = 1; 
		} else { 
			$this->iseventoptiondisabled = 0;
		}
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_order_edit( $order ) {

		$shipping_method = @array_shift($order->get_shipping_methods());

		if ($shipping_method !== null && isset($shipping_method['method_id'])) {
			$shipping_method_id = $shipping_method['method_id'];
		} else {
			// Handle the case where no shipping method is available
			$shipping_method_id = null;
		}
    	
		if (!$this->shopup_venipak_shipping_field_forcedispatch && $shipping_method_id !== 'shopup_venipak_shipping_courier_method' && $shipping_method_id !== 'shopup_venipak_shipping_pickup_method') return;

		// A courier order is delivered to the address, so a stored point is never used and cannot be changed here.
		$is_courier_order = $shipping_method_id === 'shopup_venipak_shipping_courier_method';
		$venipak_pickup_point_id = $order->get_meta('venipak_pickup_point', true );
		$venipak_pickup_point_title = $this->get_venipak_point_title($order);
		$status = $this->get_venipak_status($order);
		$status_title = $this->get_venipak_status_title($status);
		$tracking_code = $this->get_venipak_tracking_code($order);
		$manifest = $this->get_venipak_manifest($order);
		$pack_collection = $this->get_venipak_packs($order);
		$pack_count = sizeof($pack_collection);
		$weight = $this->get_venipak_weight($order);
		$product_count = $this->get_product_count($order);
		$error_message = $this->get_venipak_error($order);
		$is_event_option_disabled = $this->iseventoptiondisabled;
		$order_event_detail = $this->get_order_event_detail($order);  
		$pack_number = $this->get_order_pack_no($order);

		if(isset($pack_number) && $pack_number != '')
		{
            $order_tracking_data = $this->get_order_tracking_data($pack_number , $order, $order_event_detail); 
		}
		 
		$get_venipak_shipping_order_data = $this->get_venipak_shipping_order_data($order); 
		$is_unconfirmed = Woocommerce_Shopup_Venipak_Shipping_Admin_Dispatch::is_unconfirmed($get_venipak_shipping_order_data);
		// The same rule the dispatch reads the customs input by.
		$needs_customs_details = Woocommerce_Shopup_Venipak_Shipping_Admin_Dispatch::order_needs_customs_details( $order );
		// An empty shipment value means the order total, which only an EUR order can supply.
		$is_eur_order = $order->get_currency() === 'EUR';
		if ( $needs_customs_details ) {
			// The customs block offers "Choose from library".
			wp_enqueue_media();
		}

		?>
		<br class="clear" />
		<h4><?php echo __( 'Venipak shipping', 'woocommerce-shopup-venipak-shipping' ); ?></h4>
		<div class="venipak-shipping-box">
			<p><strong><?php echo __( 'Total weight', 'woocommerce-shopup-venipak-shipping' ); ?></strong><?php echo $weight . ' ' . __( 'kg.', 'woocommerce-shopup-venipak-shipping' ) ?></p>
			<p><strong><?php echo __( 'Products count', 'woocommerce-shopup-venipak-shipping' ); ?></strong><?php echo $product_count . ' ' . __( 'vnt.', 'woocommerce-shopup-venipak-shipping' ) ?></p>
			<p><strong><?php echo __( 'Packages count', 'woocommerce-shopup-venipak-shipping' ); ?></strong><?php echo $pack_count . ' ' . __( 'vnt.', 'woocommerce-shopup-venipak-shipping' )?></p>
		<?php if ( $is_courier_order ) { ?>
			<p class="description"><?php echo esc_html( $this->courier_order_hint() ); ?></p>
		<?php } else { ?>
			<p>
				<strong><?php echo __( 'Pickup point', 'woocommerce-shopup-venipak-shipping' ); ?></strong>
				<span id="venipak-pickup-point-title"><?php echo esc_html( $venipak_pickup_point_title ?: __( 'Not selected', 'woocommerce-shopup-venipak-shipping' ) ); ?></span>
				<button type="button" class="button button-small" id="venipak-pickup-point-change"><?php esc_html_e( 'Change', 'woocommerce-shopup-venipak-shipping' ); ?></button>
			</p>
			<?php // The editor's script is in the admin js and reads the order and the stored point from here. The select stays inside the order form, so Update still posts the chosen point. ?>
			<div id="venipak-pickup-point-editor" data-order-id="<?php echo (int) $order->get_id(); ?>" data-stored-id="<?php echo esc_attr( (string) $venipak_pickup_point_id ); ?>" style="display: none;">
				<?php if ( $status === 'sent' || $is_unconfirmed ) { ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'This order is already sent to Venipak. A new point applies only after "Dispatch one more time".', 'woocommerce-shopup-venipak-shipping' ); ?></p></div>
				<?php } ?>
				<?php
				// The select holds the stored point so that Update keeps it, also one Venipak no longer lists.
				$default_options = array();
				if ( $venipak_pickup_point_id !== '' ) {
					$default_options[ $venipak_pickup_point_id ] = $venipak_pickup_point_title ?: $venipak_pickup_point_id . ' (' . __( 'no longer available', 'woocommerce-shopup-venipak-shipping' ) . ')';
				}
				woocommerce_wp_select( array(
					'id' => 'venipak_pickup_point',
					'label' => __( 'Pickup point', 'woocommerce-shopup-venipak-shipping' ),
					'class' => 'venipak_pickup_point',
					'wrapper_class' => 'form-field-wide',
					'options' => $default_options
				) );
				?>
				<?php // What this page was loaded with: Update applies the select only if it differs, so a page that went stale in another tab does not undo a newer point. ?>
				<input type="hidden" name="venipak_pickup_point_loaded" value="<?php echo esc_attr( (string) $venipak_pickup_point_id ); ?>" />
				<button type="button" class="button button-primary" id="venipak-pickup-point-save"><?php esc_html_e( 'Save', 'woocommerce-shopup-venipak-shipping' ); ?></button>
				<button type="button" class="button" id="venipak-pickup-point-cancel"><?php esc_html_e( 'Cancel', 'woocommerce-shopup-venipak-shipping' ); ?></button>
				<button type="button" class="button" id="venipak-pickup-point-remove"><?php esc_html_e( 'Remove point', 'woocommerce-shopup-venipak-shipping' ); ?></button>
			</div>
			<p id="venipak-pickup-point-status" role="status"></p>
		<?php } ?>
		<?php if ($tracking_code) { ?>
			<p><strong><?php echo __( 'Tracking number', 'woocommerce-shopup-venipak-shipping' ); ?></strong><?php echo $tracking_code ?></p>
		<?php } ?>

		<?php if($pack_number && $is_event_option_disabled == 0 && ($order_event_detail == '' || $order_event_detail != 'Delivered')){ 
				if(!empty($order_tracking_data) )  {
				foreach($order_tracking_data as $data)
				{  
					?>
					<span><?php echo isset($data['date']) ? esc_html($data['date']) : '';?><br><strong><?php echo isset($data['pack_status_text']) ? esc_html($data['pack_status_text']) : ''?></strong></span> 
					<?php
				}
			}
			}
			elseif(isset($order_event_detail) && $order_event_detail == 'Delivered'  && $is_event_option_disabled == 0){
				foreach($get_venipak_shipping_order_data['pack_status_text'] as $key => $pack_status_text)
				{  
					?>
					<span><?php echo isset($get_venipak_shipping_order_data['date'][$key]) ? esc_html($get_venipak_shipping_order_data['date'][$key]) : '';?><br><strong><?php echo isset($pack_status_text) ? esc_html($pack_status_text) : ''?></strong></span> 
					<?php
				} 
			}?> 
			<p><strong><?php echo __( 'Dispatch status', 'woocommerce-shopup-venipak-shipping' ); ?></strong><?php echo $status_title ?></p>
			<div>
				<p><?php echo __( 'Packages count', 'woocommerce-shopup-venipak-shipping' ); ?>: <button onclick="addPackage();" type="button"><?php echo __( 'Add package', 'woocommerce-shopup-venipak-shipping' ); ?></button></p>
				<table id="packages-table">
					<tr>
						<?php // Dimensions are typed in the store's unit and converted to metres on dispatch. ?>
						<th><?php echo esc_html( $this->dimension_header( __( 'Width', 'woocommerce-shopup-venipak-shipping' ) ) ); ?></th>
						<th><?php echo esc_html( $this->dimension_header( __( 'Height', 'woocommerce-shopup-venipak-shipping' ) ) ); ?></th>
						<th><?php echo esc_html( $this->dimension_header( __( 'Length', 'woocommerce-shopup-venipak-shipping' ) ) ); ?></th>
						<th><?php echo __( 'Weight', 'woocommerce-shopup-venipak-shipping' ); ?></th>
						<th><?php echo __( 'Description', 'woocommerce-shopup-venipak-shipping' ); ?></th>
						<th><?php echo __( 'Remove', 'woocommerce-shopup-venipak-shipping' ); ?></th>
					</tr>
					<?php for ($i = 0; $i < $pack_count; $i++) { ?>
					<tr class="venipak-pack">
						<td><input class="venipak-pack-width" style="width: 70px;" type="text" name="width[]" value="<?php echo esc_attr($pack_collection[$i]['width']); ?>" /></td>
						<td><input class="venipak-pack-height" style="width: 70px;" type="text" name="height[]" value="<?php echo esc_attr($pack_collection[$i]['height']); ?>" /></td>
						<td><input class="venipak-pack-length" style="width: 70px;" type="text" name="length[]" value="<?php echo esc_attr($pack_collection[$i]['length']); ?>" /></td>
						<td><input class="venipak-pack-weight" style="width: 70px;" type="text" name="weight[]" value="<?php echo esc_attr($pack_collection[$i]['weight']); ?>" /></td>
						<td><textarea class="venipak-pack-description" name="description[]"><?php echo esc_textarea($pack_collection[$i]['description']); ?></textarea></td>
						<td><button onclick="removePackage(this)" type="button"><?php echo __( 'Remove package', 'woocommerce-shopup-venipak-shipping' ); ?></button></td>
					</tr>
					<?php }	?>
				</table>
				<?php if ( $needs_customs_details ) { ?>
				<?php // No name attributes: these are sent by the Dispatch button, not by the order form's Update. ?>
				<div id="shopup_venipak_shipping_customs">
					<p>
						<label for="shopup_venipak_shipping_doc_type"><?php esc_html_e( 'Customs document', 'woocommerce-shopup-venipak-shipping' ); ?></label><br />
						<select id="shopup_venipak_shipping_doc_type">
							<option value="commercial_invoice"><?php esc_html_e( 'Commercial invoice', 'woocommerce-shopup-venipak-shipping' ); ?></option>
							<option value="pro_forma_invoice"><?php esc_html_e( 'Pro forma invoice', 'woocommerce-shopup-venipak-shipping' ); ?></option>
							<option value="certificate_of_origin"><?php esc_html_e( 'Certificate of origin', 'woocommerce-shopup-venipak-shipping' ); ?></option>
						</select>
					</p>
					<p>
						<label class="button"><?php esc_html_e( 'Upload file', 'woocommerce-shopup-venipak-shipping' ); ?><input id="shopup_venipak_shipping_doc_file" type="file" accept="application/pdf" style="display: none;" /></label>
						<button id="shopup_venipak_shipping_doc_library" class="button" type="button" data-title="<?php esc_attr_e( 'Customs document', 'woocommerce-shopup-venipak-shipping' ); ?>"><?php esc_html_e( 'Choose from library', 'woocommerce-shopup-venipak-shipping' ); ?></button>
						<input id="shopup_venipak_shipping_doc_id" type="hidden" />
					</p>
					<p id="shopup_venipak_shipping_doc_chosen" style="display: none;"><span class="name"></span> <a href="#" title="<?php esc_attr_e( 'Remove', 'woocommerce-shopup-venipak-shipping' ); ?>">&#10005;</a></p>
					<p class="description"><?php esc_html_e( 'PDF. Needed for destinations outside the EU. For invoices prefer "Upload file": files in the media library are publicly reachable.', 'woocommerce-shopup-venipak-shipping' ); ?></p>
					<p>
						<label for="shopup_venipak_shipping_value"><?php esc_html_e( 'Shipment value, EUR', 'woocommerce-shopup-venipak-shipping' ); ?></label><br />
						<input id="shopup_venipak_shipping_value" type="number" min="0.01" step="0.01" value="" placeholder="<?php echo $is_eur_order ? esc_attr( $order->get_total() ) : ''; ?>" />
					</p>
					<?php if ( ! $is_eur_order ) { ?>
					<p class="description"><?php
						/* translators: %s: order total with its currency, e.g. "120.00 USD" */
						printf( esc_html__( 'Order total: %s — enter the value in EUR.', 'woocommerce-shopup-venipak-shipping' ), esc_html( $order->get_total() . ' ' . $order->get_currency() ) );
					?></p>
					<?php } ?>
				</div>
				<?php } ?>
				<br />
			<?php if ($status !== 'sent') { ?>
				<span class="button button-primary" onclick="event.stopPropagation(); shopup_venipak_shipping_dispatch_order_by_id({ id: <?php echo $order->get_id(); ?>, button: this });"><?php echo __( 'Dispatch', 'woocommerce-shopup-venipak-shipping' ) ?></span>
			<?php } ?>
			<?php if ($status === 'sent') { ?>
				<span class="button button-primary" onclick="event.stopPropagation(); shopup_venipak_shipping_dispatch_order_by_id({ id: <?php echo $order->get_id(); ?>, newDispatch: true, button: this });"><?php echo __( 'Dispatch one more time', 'woocommerce-shopup-venipak-shipping' ) ?></span>
			<?php } ?>
			</div>
			<div id="shopup_venipak_shipping_wrapper_order_<?php echo $order->get_id(); ?>" style="margin-top: 10px;">
			<?php if ($status === 'error') { ?>
				<p style="color: red;"><?php echo esc_html($error_message) ?></p>
			<?php } ?>
			<?php if ($status === 'sent' || $is_unconfirmed) { ?>
				<div>
					<a class="button button-primary" target="_blank" href="<?php echo admin_url('admin-ajax.php'); ?>?action=woocommerce_shopup_venipak_shipping_get_label_pdf&order_id=<?php echo $order->get_id(); ?>"><?php echo __( 'Print labels', 'woocommerce-shopup-venipak-shipping' ) ?></a>
					<a class="button button-primary" target="_blank" href="<?php echo admin_url('admin-ajax.php'); ?>?action=woocommerce_shopup_venipak_shipping_get_manifest_pdf&order_id=<?php echo $order->get_id(); ?>"><?php echo sprintf( __( 'Print manifest (%s)', 'woocommerce-shopup-venipak-shipping' ), esc_html($manifest)) ?></a>
				</div>
			<?php } ?>
			</div>
		</div>
		<script>
		function addPackage() {
			jQuery('#packages-table tr:last').after('<tr class="venipak-pack"><td><input class="venipak-pack-width" style="width: 70px;" type="text" name="width[]" /></td><td><input class="venipak-pack-height" style="width: 70px;" type="text" name="height[]" /></td><td><input class="venipak-pack-length" style="width: 70px;" type="text" name="length[]" /></td><td><input class="venipak-pack-weight" style="width: 70px;" type="text" name="weight[]" /></td><td><textarea class="venipak-pack-description" name="description[]"></textarea></td><td><button onclick="removePackage(this)" type="button"><?php echo __( "Remove package", "woocommerce-shopup-venipak-shipping" ); ?></button></td></tr>');
		}

		function removePackage(cb) {
			jQuery(cb).closest('tr').remove();
		}
		</script>
		<?php
	}

	/**
	 * Saves the pickup point chosen in the order page's editor without the order's Update button.
	 * An empty point_id removes the point.
	 */
	public function save_pickup_point() {
		check_ajax_referer( 'venipak_shipping_admin', 'nonce' );
		// The same gate as the point list: whoever may edit the order may change its point.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change this order.', 'woocommerce-shopup-venipak-shipping' ) ), 403 );
		}

		// A refund is also returned by wc_get_order but is no order to deliver.
		$order = wc_get_order( isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0 );
		if ( ! ( $order instanceof WC_Order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce-shopup-venipak-shipping' ) ), 404 );
		}
		if ( venipak_order_shipping_method_id( $order ) === 'shopup_venipak_shipping_courier_method' ) {
			wp_send_json_error( array( 'message' => $this->courier_order_hint() ), 400 );
		}

		// Only an explicit empty string removes the point; no value at all is a bad request.
		if ( ! isset( $_POST['point_id'] ) || ! is_string( $_POST['point_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown pickup point.', 'woocommerce-shopup-venipak-shipping' ) ), 400 );
		}
		$point_id = trim( wp_unslash( $_POST['point_id'] ) );
		if ( $point_id !== '' && ( ! ctype_digit( $point_id ) || ! venipak_find_pickup_by_id( $point_id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown pickup point.', 'woocommerce-shopup-venipak-shipping' ) ), 400 );
		}

		if ( $point_id !== trim( (string) $order->get_meta( 'venipak_pickup_point', true ) ) ) {
			venipak_store_order_pickup( $order, $point_id );
			$order->save();
			$order->add_order_note( $this->pickup_point_note( $this->get_venipak_point_title( $order ) ), 0, true );
		}

		wp_send_json_success( array(
			'point_id' => $point_id,
			'title' => (string) $this->get_venipak_point_title( $order ),
		) );
	}

	/**
	 * Hides the plugin's own order meta from WooCommerce's Custom Fields box: with high-performance order
	 * storage the box writes its page-load copy back after our hooks, undoing a newer point or dispatch.
	 */
	public function is_protected_meta( $protected, $meta_key ) {
		return $protected || in_array( $meta_key, array( 'venipak_pickup_point', 'venipak_pickup_point_data', 'venipak_shipping_order_data' ), true );
	}

	private function pickup_point_note( $title ) {
		if ( $title === null ) {
			return __( 'Venipak pickup point removed.', 'woocommerce-shopup-venipak-shipping' );
		}
		/* translators: %s: the pickup point's name and address */
		return sprintf( __( 'Venipak pickup point changed to %s.', 'woocommerce-shopup-venipak-shipping' ), esc_html( $title ) );
	}

	private function courier_order_hint() {
		return __( 'Courier orders are delivered to the address. To send this order to a pickup point, change its shipping method to Venipak pickup point.', 'woocommerce-shopup-venipak-shipping' );
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function add_venipak_shipping_order_save( $order_id ) {
		$order = wc_get_order($order_id);
		if ( ! isset( $_POST['venipak_pickup_point'] ) && ! isset( $_POST['venipak_pickup_point_loaded'] ) ) {
			venipak_clear_order_pickup( $order );
		} else {
			// WooCommerce hands this hook $_POST still slashed. An empty select posts nothing.
			$posted = isset( $_POST['venipak_pickup_point'] ) && is_string( $_POST['venipak_pickup_point'] ) ? trim( wp_unslash( $_POST['venipak_pickup_point'] ) ) : '';
			// The order page says which point it was loaded with: only a different choice counts, so a page
			// gone stale in another tab cannot undo a newer point. An older page is compared with the stored point.
			$before = isset( $_POST['venipak_pickup_point_loaded'] ) && is_string( $_POST['venipak_pickup_point_loaded'] )
				? trim( wp_unslash( $_POST['venipak_pickup_point_loaded'] ) )
				: trim( (string) $order->get_meta( 'venipak_pickup_point', true ) );
			// An order from before the id-only meta posts its whole stored point back as JSON;
			// an unchanged value is left alone so such an order keeps its point. A new choice
			// must be an id, as at checkout: anything else clears, as an empty value does.
			if ( $posted !== $before ) {
				venipak_store_order_pickup( $order, ctype_digit( $posted ) ? $posted : '' );
			}
		}
		$order->save();
	}

	/**
	 * "Width, cm": a pack table column with the store's dimension unit.
	 */
	private function dimension_header($column) {
		/* translators: 1: column name, e.g. "Width"; 2: the store's dimension unit, e.g. "cm" */
		return sprintf( __( '%1$s, %2$s', 'woocommerce-shopup-venipak-shipping' ), $column, get_option( 'woocommerce_dimension_unit' ) );
	}

	/**
	 * The point the parcel goes to, as the order screen shows it; null for delivery to the address.
	 */
	public function get_venipak_point_title($order) {
		$venipak_pickup_entity = venipak_delivery_pickup_point($order, $source);
		if (!$venipak_pickup_entity) {
			return null;
		}

		$title = $venipak_pickup_entity['name'] . ' ' . $venipak_pickup_entity['address'] . ' ' . $venipak_pickup_entity['city'];
		if ($source !== 'live') {
			// Answered from the order's own snapshot: Venipak no longer lists this point, so
			// say so here rather than let it read as an ordinary, still-valid choice.
			$title .= ' (' . __( 'no longer available', 'woocommerce-shopup-venipak-shipping' ) . ')';
		}
		return $title;
	}

	public function get_venipak_status($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return isset($order_data['status']) ? $order_data['status'] : null;
		}
		return null;
	}

	public function get_venipak_tracking_code($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return $order_data['pack_numbers'] ? implode('<br/>', array_map('esc_html', $order_data['pack_numbers'])) : '';
		}
		return null;
	}

	public function get_venipak_manifest($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return $order_data['manifest'];
		}
		return null;
	}

	public function get_venipak_error($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return isset($order_data['error_message']) ? $order_data['error_message'] : '';
		}
		return null;
	}

	public function get_order_event_detail($order) {
	    $order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);

	    if ($order_data && isset($order_data['event']) && is_array($order_data['event'])) {
	        return end($order_data['event']);
	    }

	    return null;
	}


	public function get_order_tracking_data($pack_number, $order, $order_event_detail) { 

		if( $order_event_detail != "Delivered")
		{
			$url = "https://tracking.venipak.com/api/v1/events?pack_no=" . $pack_number;

        // WordPress API request
        $response = wp_remote_get($url, [
            'headers' => [
                "Accept" => "application/json",
                "Content-Type" => "application/json"
            ]
        ]);


        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("API Request failed: $error_message");
            return null;
        }

        // Extract response body and decode JSON
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if ($status !== 200) {
            error_log("API returned status code $status: $body");
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            error_log("Failed to decode API response.");
            return null;
        }

		$order_meta = json_decode($order->get_meta('venipak_shipping_order_data'), true); 
		$ischanged = false;
		if (is_array($order_meta) && isset($order_meta['pack_numbers']) && is_array($order_meta['pack_numbers'])) {
		$order_meta['event'] = [];
		$order_meta['date'] = [];
		$order_meta['pack_status_text'] = [];
		}

		foreach ($data as $a) { 
			if (is_array($order_meta) && isset($order_meta['pack_numbers']) && is_array($order_meta['pack_numbers'])) {  
				$pack_numbers = $order_meta['pack_numbers'];
				$ischanged = false; 
				if (!empty($pack_numbers[0]) && isset($a['pack_no']) && !empty($a['pack_no']) ) {
					$ischanged = true;
					$order_meta['pack_numbers'][0] = $a['pack_no'];
				} 
				if (isset($a['event']) && !empty($a['event'])) {
					$ischanged = true;
					$order_meta['event'][] = $a['event'];
				} 
				if (isset($a['date']) && !empty($a['date'])) {
					$ischanged = true;
					$order_meta['date'][] = $a['date'];
				}
		 
				if (isset($a['pack_status_text']) && !empty($a['pack_status_text'])) {
					$ischanged = true;
					$order_meta['pack_status_text'][] = $a['pack_status_text'];
				} 
				if ($ischanged) {
					$order->update_meta_data('venipak_shipping_order_data', json_encode($order_meta));
					$order->save();
		 
					$order_meta = json_decode($order->get_meta('venipak_shipping_order_data', true), true);
				}
				} 	 
		} 
		return $data;
		} 
		else{
			return [];
		}
	}
	

	public function get_venipak_shipping_order_data($order)
	{
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return $order_data;;
		}
		return null;
	}
	
	public function get_order_pack_no($order)
	{
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		// Called on every render of the order screen, including orders that carry this meta
		// without any pack numbers — a failed dispatch, or one cleared for re-dispatch.
		if ($order_data && !empty($order_data['pack_numbers'][0])) {
			return $order_data['pack_numbers'][0];
		}
		return null;
	} 
	


	public function get_venipak_packs($order) {
		$order_products = [];
		$order_description = '';
		$weight = 0;
		foreach ( $order->get_items() as $item_id => $product_item ) {
			$product = $product_item->get_product();
			if (!$product) continue;
			$product_weight = $this->get_product_weight($product);
			$product_quantity = $product_item->get_quantity();
			$order_description .= $product_item->get_product()->get_title() . PHP_EOL;
			for ($i = 0; $i < $product_quantity; $i++) {
				$order_products[] = $product;
				$weight += $product_weight;
			}
		}
		$weight = wc_get_weight($weight, 'kg');
		$pack_collection = array();
		// A pickup point takes one parcel, as the dispatch builds it.
		if (venipak_delivery_pickup_point($order)) {
			$pack_collection[] = array(
				'length' => 0,
				'width' => 0,
				'height' => 0,
				'weight' => $weight,
				'description' => $order_description,
			);
			return $pack_collection;
		}

		$products_with_shipments = [];
		$products_without_shipments = [];

		foreach ($order_products as $product) {
			$product_total_shipments = $product->get_meta('_venipak_total_shipments', true) ?: $product->get_meta('_omnivalt_total_shipments', true);
			if ($product_total_shipments) {
				$products_with_shipments[] = $product;
			} else {
				$products_without_shipments[] = $product;
			}
		}

		foreach ($products_with_shipments as $product) {
			$product_total_shipments = $product->get_meta('_venipak_total_shipments', true) ?: $product->get_meta('_omnivalt_total_shipments', true);
			$pack_weight = $this->get_product_weight($product);
			$pack_description = $product->get_title() . PHP_EOL;
			$pack_weight = max(wc_get_weight($pack_weight, 'kg'), 0.1);
			for ($j = 0; $j < $product_total_shipments; $j++) {
				$pack_collection[] = array(
					'weight' => $pack_weight / $product_total_shipments,
					'description' => $pack_description,
				);
			}
		}

		$pack_count = ceil(sizeof($products_without_shipments) / $this->shopup_venipak_shipping_field_maxpackproducts);


		for ($i = 0; $i < $pack_count; $i++) {
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
			$pack_weight = wc_get_weight($pack_weight, 'kg');
			$pack_collection[] = array(
				'length' => 0,
				'width' => 0,
				'height' => 0,
				'weight' => $pack_weight,
				'description' => $pack_description,
			);
		}

		return $pack_collection;
	}

	public function get_venipak_weight($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data && isset($order_data['status']) && $order_data['status'] === 'sent') {
			return $order_data['weight'];
		}

		$weight = 0;
		foreach ( $order->get_items() as $item_id => $product_item ) {
			$product = $product_item->get_product();
			if (!$product) continue;
			$weight += $this->get_product_weight($product) * $product_item->get_quantity();
		}

		return wc_get_weight($weight, 'kg');
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

	public function get_venipak_status_title($status) {
		switch($status) {
			case "waiting":
				return __( 'Waiting', 'woocommerce-shopup-venipak-shipping' );
			case "sent":
				return __( 'Sent', 'woocommerce-shopup-venipak-shipping' );
			case "error":
				return __( 'Error', 'woocommerce-shopup-venipak-shipping' );
		}
		return null;
	}

	public function get_product_count($order) {
		$order_data = json_decode($order->get_meta('venipak_shipping_order_data'), true);
		if ($order_data) {
			return $order_data['products_count'];
		}
		$count = 0;
		foreach ( $order->get_items() as $item_id => $product_item ) {
			$product_quantity = $product_item->get_quantity();
			$count += $product_quantity;
		}
		return $count;
	}
}
