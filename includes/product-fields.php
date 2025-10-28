<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add custom weight unit to product shipping tab
 */
function modulux_add_product_weight_unit_option() {
    global $product_object;

    $weight_unit = $product_object->get_meta('_weight_unit');

    $options = modulux_get_weight_units(); // dynamic options from settings

    $wc_weight_unit = get_option('woocommerce_weight_unit', 'kg'); // default WooCommerce weight unit
    if (!in_array($wc_weight_unit, $options)) {
        $options[$wc_weight_unit] = $wc_weight_unit; // ensure default unit is available
    }

    woocommerce_wp_radio(array(
        'id'      => '_weight_unit',
        'label'   => __('Weight Unit', 'modulux-shipping-helper'),
        'options' => $options,
        'value'   => $weight_unit ?: $wc_weight_unit, // use product meta or default WooCommerce unit
        'desc_tip' => true,
        'custom_attributes' => array(
            'data-placeholder' => __('Select a weight unit', 'modulux-shipping-helper'),
        ),
        'description' => __('Select the weight unit for this product. This will be used in shipping calculations.', 'modulux-shipping-helper'),
    ));
}
add_action('woocommerce_product_options_dimensions', 'modulux_add_product_weight_unit_option');

/**
 * Save weight unit when product is saved
 */
function modulux_save_product_weight_unit_option($product) {
    if (
        isset($_POST['modulux_units_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['modulux_units_nonce'])), 'modulux_save_units') &&
        isset($_POST['_weight_unit'])
    ) {
        $product->update_meta_data('_weight_unit', sanitize_text_field(wp_unslash($_POST['_weight_unit'])));
    }
}
add_action('woocommerce_admin_process_product_object', 'modulux_save_product_weight_unit_option');

/**
 * Get custom weight units from plugin settings
 */
function modulux_get_weight_units() {
    $stored_units = get_option('modulux_weight_units');

    // Default units if none are defined
    if (empty($stored_units) || !is_array($stored_units)) {
        //$stored_units = ['Kg', 'Desi', 'Litre', 'Pound'];
        $stored_units = [
            'kg',
            'g',
            'lbs',
            'oz',
        ];        
    }

    // Convert array into key=>value format for radio input
    return array_combine($stored_units, $stored_units);
}
