<?php
/**
 * List of terminals - rendered after all shipping methods (table row)
 */
?>

<tr class="wc-venipak-shipping-terminals">
    <td colspan="2">
        <div class="venipak-pickup-wrapper">
            <div>
                <label for="venipak_pickup_point" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e( 'Select Venipak pickup point:', 'woocommerce-shopup-venipak-shipping' ); ?></label>
                <select name="venipak_pickup_point" id="venipak_pickup_point" class="venipak_pickup_point">
                    <option value=""><?php _e( 'Select pickup point', 'woocommerce-shopup-venipak-shipping' ); ?></option>
                </select>
            </div>
            <div style="clear: both; padding-top: 15px; text-align: right;" id="selected-pickup-info"></div>
            <div id="venipak-map" style="display: none; height: 300px;"></div>
            <script>
                if (window.venipakShipping) {
                    window.venipakShipping.init();
                }
            </script>
        </div>
    </td>
</tr>
