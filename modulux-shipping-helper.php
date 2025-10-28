<?php
/**
 * Plugin Name: Modulux Shipping Helper for WooCommerce
 * Description: Adds custom weight units and shipping logic, pricing to WooCommerce.
 * Author: Modulux.net
 * Version: 1.0.0
 * Text Domain: modulux-shipping-helper
 * Requires at least: 5.6
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI: https://modulux.net
 * Plugin URI: https://modulux.net/modulux-shipping-helper
 * GitHub Plugin URI: https://github.com/sgeray/modulux-shipping-helper.git
 * GitHub Branch: main
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure WooCommerce is active
add_action('plugins_loaded', function() {
    if ( ! class_exists('WooCommerce') ) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            esc_html_e('Modulux Shipping Helper for WooCommerce requires WooCommerce to be installed and active.', 'modulux-shipping-helper');
            echo '</p></div>';
        });
        return;
    }

    // Load plugin files
    require_once __DIR__ . '/includes/product-fields.php';
    require_once __DIR__ . '/includes/admin-settings.php';
    require_once __DIR__ . '/includes/shipping-logic.php';
    require_once __DIR__ . '/includes/suggest-weight.php';
});