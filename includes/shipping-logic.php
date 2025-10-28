<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modify flat rate shipping cost based on heaviest product and custom unit rules.
 */
add_filter('woocommerce_package_rates', 'modulux_custom_shipping_by_weight_units', 10, 2);

function modulux_custom_shipping_by_weight_units($rates, $package) {
    if (!modulux_is_flat_rate_enabled()) {
        return $rates;
    }

    $has_flat_rate = false;
    foreach ($rates as $rate_id => $rate) {
        if ($rate->method_id === 'flat_rate') {
            $has_flat_rate = true;
            break;
        }
    }

    if (!$has_flat_rate) {
        return $rates;
    }

    $shipping_rules = get_option('modulux_shipping_rules', []);
    $meta = $shipping_rules['_meta'] ?? ['vat' => 0, 'free_shipping_threshold' => 5000];
    $vat = floatval($meta['vat']);
    $free_threshold = floatval($meta['free_shipping_threshold']);

    $cart_total = WC()->cart->subtotal;
    $unit_weights = [];
    $debug_info = [];

    // Detect calculation mode
    $calc_mode = get_option('modulux_shipping_calc_mode', 'heaviest');
    if (!in_array($calc_mode, ['total', 'heaviest'])) {
        $calc_mode = 'heaviest';
    }

    // 1. Gather weights
    foreach ($package['contents'] as $item) {
        $product = $item['data'];
        $qty     = $item['quantity'];
        $weight  = floatval($product->get_weight());
        $unit    = $product->get_meta('_weight_unit') ?: 'Kg';

        if ($weight <= 0 || $qty <= 0) {
            continue;
        }

        $line_weight = $weight * $qty;

        if ($calc_mode === 'total') {
            $unit_weights[$unit] = ($unit_weights[$unit] ?? 0) + $line_weight;
        } else { // heaviest
            if (!isset($unit_weights[$unit]) || $line_weight > $unit_weights[$unit]) {
                $unit_weights[$unit] = $line_weight;
            }
        }
    }

    // 2. Calculate shipping cost per unit
    $total_shipping = 0;
    $debug_lines = [];

    foreach ($unit_weights as $unit => $total_weight) {
        $unit_rules = $shipping_rules[$unit] ?? [];
        $rules = $unit_rules['rules'] ?? [];
        $fallback_rate = isset($unit_rules['fallback']) ? floatval($unit_rules['fallback']) : 0;

        $matched_cost = null;

        foreach ($rules as $rule) {
            if ($total_weight <= floatval($rule['max'])) {
                $matched_cost = floatval($rule['price']);
                break;
            }
        }

        if ($matched_cost === null) {
            $matched_cost = $fallback_rate > 0 ? $total_weight * $fallback_rate : $total_weight * 1.5;
        }

        $total_shipping += $matched_cost;
        $debug_lines[] = sprintf('%s: %.2f unit → $%.2f', ucfirst($unit), $total_weight, $matched_cost);
    }

    // 3. VAT
    if ($vat > 0) {
        $total_shipping *= (1 + $vat / 100);
    }

    // 4. Free shipping threshold
    if ($cart_total >= $free_threshold) {
        $total_shipping = 0;
        $label = __('Free Shipping', 'modulux-shipping-helper');
    } else {
        $label = __('Shipping (', 'modulux-shipping-helper') . implode(', ', $debug_lines) . ')';
    }

    // 5. Override flat rate
    foreach ($rates as $rate_id => $rate) {
        if ($rate->method_id === 'flat_rate') {
            $rates[$rate_id]->cost = $total_shipping;
            $rates[$rate_id]->label = $label;
        }
    }

    return $rates;
}


/**
 * Formating weight with the weight different weight units
 *
 * @param [type] $weight_string
 * @param [type] $weight
 * @return void
 */
function modulux_woo_shipping_helper_custom_format_weight( $weight_string, $weight ){
    $weight_string  = wc_format_localized_decimal( $weight );

    if ( ! empty( $weight_string ) ) {
        $weight_label = get_post_meta( get_the_ID(), '_weight_unit', true );
        // translators: 1: weight, 2: weight unit.
        $weight_string = sprintf(_nx( '%1$s  %2$s', '%1$s  %2$s', $weight, 'formatted weight', 'modulux-shipping-helper' ), $weight_string, $weight_label ? $weight_label : get_option( 'woocommerce_weight_unit' ));
    } else {
        $weight_string = __( 'N/A', 'modulux-shipping-helper' );
    }

    return $weight_string;
}

add_filter( 'woocommerce_format_weight', 'modulux_woo_shipping_helper_custom_format_weight', 20, 2 );

/**
 * Check if any flat_rate method is enabled across all shipping zones
 */
function modulux_is_flat_rate_enabled() {
    foreach (WC_Shipping_Zones::get_zones() as $zone) {
        foreach ($zone['shipping_methods'] as $method) {
            if ($method->id === 'flat_rate' && $method->enabled === 'yes') {
                return true;
            }
        }
    }

    // Check the default shipping zone
    $default_zone = new WC_Shipping_Zone(0);
    foreach ($default_zone->get_shipping_methods() as $method) {
        if ($method->id === 'flat_rate' && $method->enabled === 'yes') {
            return true;
        }
    }

    return false;
}
