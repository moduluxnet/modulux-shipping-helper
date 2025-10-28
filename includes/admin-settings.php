<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

function modulux_register_shipping_helper_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('Shipping Helper', 'modulux-shipping-helper'),
        __('Shipping Helper', 'modulux-shipping-helper'),
        'manage_woocommerce',
        'modulux-shipping-helper',
        'modulux_render_shipping_helper_settings_page'
    );
}
add_action('admin_menu', 'modulux_register_shipping_helper_settings_page');

/**
 * Enqueue admin scripts and styles
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'woocommerce_page_modulux-shipping-helper') {
        wp_enqueue_style('modulux-shipping-helper-admin-css', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css', [], '1.0');
        wp_enqueue_script(
            'modulux-shipping-helper-admin-js',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin.js',
            [],
            '1.0',
            true
        );
        wp_localize_script('modulux-shipping-helper-admin-js', 'modulux_i18n', [
            'remove_unit_confirm' => __('Are you sure you want to remove this unit? This action cannot be undone after you Save Settings, and removing a unit will remove it from all products that use it.', 'modulux-shipping-helper'),
            'remove_rule_confirm' => __('Are you sure you want to remove this unit? This action cannot be undone after you Save Settings.', 'modulux-shipping-helper'),
            'add_unit_label'      => __('Add Unit', 'modulux-shipping-helper'),
            'add_rule_label'      => __('Add Rule', 'modulux-shipping-helper'),
        ]);
    }
});

/**
 * Render the settings page for Modulux Shipping Helper
 */
function modulux_render_shipping_helper_settings_page() {
    // WooCommerce Flat Rate shipping being enabled.
    $flat_rate_enabled = false;
    $shipping_methods = WC()->shipping()->get_shipping_methods();

    foreach (WC_Shipping_Zones::get_zones() as $zone) {
        foreach ($zone['shipping_methods'] as $method) {
            if ($method->id === 'flat_rate' && $method->enabled === 'yes') {
                $flat_rate_enabled = true;
                break 2;
            }
        }
    }

    // Default zone fallback (in case all zones are removed)
    if (! $flat_rate_enabled) {
        $default_zone = new WC_Shipping_Zone(0);
        foreach ($default_zone->get_shipping_methods() as $method) {
            if ($method->id === 'flat_rate' && $method->enabled === 'yes') {
                $flat_rate_enabled = true;
                break;
            }
        }
    }

    // If Flat Rate is not enabled, show a warning notice
    if (! $flat_rate_enabled) {
        echo '<div class="notice notice-warning"><p>';
        // translators: %s is the URL to WooCommerce shipping settings
        printf(wp_kses_post(__('This plugin requires WooCommerce <strong>Flat Rate</strong> shipping method to be enabled in at least one zone. Please configure your shipping methods under <a href="%s">WooCommerce → Settings → Shipping</a>.', 'modulux-shipping-helper')), esc_url(admin_url('admin.php?page=wc-settings&tab=shipping')));
        echo '</p></div>';
    } else {
        echo '<div class="notice notice-info"><p>';
        echo wp_kses_post(__('Shipping rates configured here will only apply to WooCommerce <strong>Flat Rate</strong> shipping methods.', 'modulux-shipping-helper'));
        echo '</p></div>';
    }

    // Get WooCommerce default weight unit and all available units
    $wc_weight_unit = get_option('woocommerce_weight_unit', 'kg');
    $wc_weight_units = [
        'kg'  => __('Kilograms (kg)', 'modulux-shipping-helper'),
        'g'   => __('Grams (g)', 'modulux-shipping-helper'),
        'lbs' => __('Pounds (lbs)', 'modulux-shipping-helper'),
        'oz'  => __('Ounces (oz)', 'modulux-shipping-helper'),
        //'desi'  => __('Desi', 'modulux-shipping-helper'),
        //'litre'  => __('Litre', 'modulux-shipping-helper'),
        //'pound'  => __('Pound', 'modulux-shipping-helper'),
    ];

    // Get weight units from options or set default
    $units = get_option('modulux_weight_units', array_keys($wc_weight_units));

    // Load shipping rules or apply default (only once)
    $shipping_rules = get_option('modulux_shipping_rules', false);
    if ($shipping_rules === false) {
        $shipping_rules = [
            $wc_weight_unit => [ //Kg was the default unit
                'rules' => [
                    ['max' => 1,  'price' => 5.95],
                    ['max' => 2,  'price' => 7.45],
                    ['max' => 3,  'price' => 8.95],
                    ['max' => 5,  'price' => 11.95],
                    ['max' => 10, 'price' => 16.95],
                    ['max' => 15, 'price' => 22.95],
                    ['max' => 20, 'price' => 28.95],
                    ['max' => 25, 'price' => 35.95],
                    ['max' => 30, 'price' => 43.95],
                ],
                'fallback' => 1.5 // Default fallback price for Kg
            ],
            '_meta' => [
                'vat' => 0, // Default VAT is 0%
                'free_shipping_threshold' => 75
            ]
        ];
        update_option('modulux_shipping_rules', $shipping_rules);
    }

    $vat = isset($shipping_rules['_meta']['vat']) ? $shipping_rules['_meta']['vat'] : 0;
    $free_threshold = isset($shipping_rules['_meta']['free_shipping_threshold']) ? $shipping_rules['_meta']['free_shipping_threshold'] : 75;

    $index = 0; // Initialize index for unit numbering
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Modulux Shipping Helper Settings', 'modulux-shipping-helper'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="#tab-units" class="nav-tab nav-tab-active"><?php esc_html_e('Weight Units', 'modulux-shipping-helper'); ?></a>
            <a href="#tab-rates" class="nav-tab"><?php esc_html_e('Shipping Rates', 'modulux-shipping-helper'); ?></a>
            <a href="#tab-what-is-this" class="nav-tab"><?php esc_html_e('What is this?', 'modulux-shipping-helper'); ?></a>
        </h2>        

        <form method="post">
            <?php wp_nonce_field('modulux_save_units', 'modulux_units_nonce'); ?>

            <div id="tab-units" class="tab-content active">
                <table class="form-table" id="modulux-weight-unit-table">
                    <tbody>
                    <?php foreach ($units as $unit) : ?>
                        <tr>
                            <td>
                                <?php 
                                // translators: %d is the unit number (1, 2, 3, ...)
                                echo esc_html(sprintf(__('Unit %d:', 'modulux-shipping-helper'), $index + 1)); $index++; ?>
                                <input type="text" name="modulux_weight_units[]" value="<?php echo esc_attr($unit); ?>" />
                                <button type="button" class="button remove-unit">×</button>
                                <?php if ($unit === $wc_weight_unit) : ?>
                                    <span class="description"><?php 
                                        // translators: %s is the default WooCommerce weight unit (e.g. Kilograms (kg))
                                        echo wp_kses(sprintf(__('This is the default WooCommerce weight unit: <strong>%s</strong>', 'modulux-shipping-helper'), esc_html($wc_weight_units[$unit])), array('strong' => array()));
                                    ?></span>
                                <?php elseif (in_array($unit, array_keys($wc_weight_units))) : ?>
                                    <span class="description"><?php 
                                        // translators: %s is the available WooCommerce weight unit (e.g. Pounds (lbs))
                                        echo wp_kses(sprintf(__('This unit is available in WooCommerce: <strong>%s</strong>', 'modulux-shipping-helper'), esc_html($wc_weight_units[$unit])), array('strong' => array()));
                                    ?></span>
                                <?php else : ?>
                                    <span class="description"><?php esc_html_e('This unit will be available for products.', 'modulux-shipping-helper'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-weight-unit"><?php esc_html_e('Add Unit', 'modulux-shipping-helper'); ?></button></p>
            </div>
            <?php //} ?>

            <?php
            // Shipping Rates Section
            if (function_exists('get_woocommerce_currency_symbol')) {
                $currency_symbol = get_woocommerce_currency_symbol();
            } else {
                $currency_symbol = '$'; // Fallback if WooCommerce is not available
            }

            // Get the current calculation mode
            $mode = get_option('modulux_shipping_calc_mode', 'heaviest');
            ?>
            <div id="tab-rates" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Calculation Mode', 'modulux-shipping-helper'); ?></th>
                        <td>
                            <select name="modulux_shipping_calc_mode">
                                <option value="heaviest" <?php selected($mode, 'heaviest'); ?>><?php esc_html_e('Heaviest product per unit', 'modulux-shipping-helper'); ?></option>
                                <option value="total" <?php selected($mode, 'total'); ?>><?php esc_html_e('Total weight per unit', 'modulux-shipping-helper'); ?></option>
                            </select>
                            <span class="description">
                                <?php esc_html_e('Choose how to calculate shipping per weight unit.', 'modulux-shipping-helper'); ?>
                            </span>                            
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('VAT (%)', 'modulux-shipping-helper'); ?></th>
                        <td><input type="number" name="modulux_vat" value="<?php echo esc_attr($vat); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php 
                        // translators: %s is the currency symbol (e.g. $)
                        echo wp_kses_post(sprintf(__('Free Shipping Minimum (%s)', 'modulux-shipping-helper'), esc_html($currency_symbol))); ?></th>
                        <td><input type="number" name="modulux_free_shipping_threshold" value="<?php echo esc_attr($free_threshold); ?>" /></td>
                    </tr>
                </table>

                <?php foreach ($units as $unit) : ?>
                    <hr/>                    
                    <h3><?php echo esc_html($unit); ?></h3>
                    <?php
                    $fallback = isset($shipping_rules[$unit]['fallback']) ? floatval($shipping_rules[$unit]['fallback']) : '';
                    ?>
                    <p>
                        <label>
                            <?php 
                            // translators: %s is the weight unit (e.g. Kg, Litre, Desi, Pound)
                            echo wp_kses_post(sprintf(__('Fallback price per <strong><u>%s</u></strong> (used if above all rules):', 'modulux-shipping-helper'), esc_html($unit))); ?>
                            <input type="number" step="0.01" name="modulux_shipping_rules[<?php echo esc_attr($unit); ?>][fallback]" value="<?php echo esc_attr($fallback); ?>" />
                        </label>
                    </p>
                    <table class="form-table modulux-shipping-unit-table" data-unit="<?php echo esc_attr($unit); ?>">
                        <thead>
                        <tr>
                            <th><?php 
                            // translators: %s is the weight unit (e.g. Kg, Litre, Desi, Pound)
                            echo wp_kses_post(sprintf(__('Max <u>%s</u> (≤)', 'modulux-shipping-helper'), esc_html($unit))); ?></th>
                            <th>
                                <?php
                                // translators: %s is the currency symbol (e.g. $)
                                echo esc_html(sprintf(__('Shipping Cost (%s)', 'modulux-shipping-helper'), $currency_symbol));
                                ?>
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $rules = isset($shipping_rules[$unit]['rules']) ? $shipping_rules[$unit]['rules'] : [];
                        foreach ($rules as $row) :
                            ?>
                            <tr>
                                <td><input type="number" step="0.01" name="modulux_shipping_rules[<?php echo esc_attr($unit); ?>][max][]" value="<?php echo esc_attr($row['max']); ?>" /></td>
                                <td><input type="number" step="0.01" name="modulux_shipping_rules[<?php echo esc_attr($unit); ?>][price][]" value="<?php echo esc_attr($row['price']); ?>" /></td>
                                <td><button type="button" class="button remove-shipping-rule">×</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><button type="button" class="button add-shipping-rule" data-unit="<?php echo esc_attr($unit); ?>"><?php esc_html_e('Add Rule', 'modulux-shipping-helper'); ?></button></p>
                <?php endforeach; ?>
            </div>
            <?php //} ?>

            <div id="tab-what-is-this" class="tab-content">
                <h2>What is Modulux Shipping Helper for WooCommerce?</h2>
                <p>
                    <strong>Modulux Shipping Helper for WooCommerce</strong> is a lightweight WooCommerce extension that enhances the Flat Rate shipping method by adding support for:
                </p>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li>Custom weight units per product (e.g. Kg, Litre, Desi, Pound)</li>
                    <li>Per-unit shipping rules based on total or heaviest weight</li>
                    <li>Fallback pricing when no rule matches</li>
                    <li>Automatic VAT inclusion and free shipping threshold</li>
                    <li>Optional “Suggest Weight” button based on similar products (category/tag)</li>
                    <li>Designed to work only with the <strong>Flat Rate</strong> shipping method</li>
                </ul>

                <h3>How does the shipping logic work?</h3>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li>Products are grouped by their custom unit (e.g., Kg, Litre, etc.)</li>
                    <li>You can choose between <strong>total</strong> or <strong>heaviest</strong> calculation modes:
                        <ul style="list-style: circle; padding-left: 20px;">
                            <li><strong>Total:</strong> Adds up the total weight per unit</li>
                            <li><strong>Heaviest:</strong> Charges for the single heaviest product per unit</li>
                        </ul>
                    </li>
                    <li>Shipping cost is calculated for each unit independently</li>
                    <li>Matched rates are summed (e.g. Lbs: $5.95 + Litre: $5.00 = $10.95)</li>
                    <li>Fallback rate is used if no range matches</li>
                    <li>VAT is applied to the final shipping total (if enabled)</li>
                    <li>If the cart subtotal passes the free shipping threshold, shipping is free</li>
                </ul>

                <h3>Smart Suggestions</h3>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li>Missing product weight? Use the <strong>Suggest Weight</strong> button in the product edit screen</li>
                    <li>Suggestions are based on SKU, category, or product title/tag similarity</li>
                    <li>You can always override the suggested values manually</li>
                </ul>

                <h3>Important</h3>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li>This plugin only affects WooCommerce <strong>Flat Rate</strong> shipping methods</li>
                    <li>Local Pickup, Free Shipping, and custom methods remain untouched</li>
                    <li>Fallback usage is logged in the order notes for admin reference</li>
                </ul>

                <p>
                    Need help or want to share feedback? Visit <a href="https://modulux.net/contact" target="_blank">modulux.net/contact</a>
                    &nbsp;|&nbsp;
                    <a href="https://wordpress.org/support/plugin/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Support', 'modulux-shipping-helper'); ?></a>
                    &nbsp;|&nbsp;
                    <a href="https://modulux.net" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Author', 'modulux-shipping-helper'); ?></a>                    
                </p>

                <p style="opacity: 0.7; font-size: 90%; margin-top: 40px;">
                    © <?php echo esc_html(gmdate('Y')); ?> Modulux. <?php esc_html_e('All rights reserved.', 'modulux-shipping-helper'); ?>
                </p>
            </div>
            <hr/>            
            <?php submit_button(__('Save Settings', 'modulux-shipping-helper'), 'primary', 'save', false, ['style' => 'float: left;']); ?>
            <?php submit_button(__('Reset to Defaults', 'modulux-shipping-helper'), 'secondary', 'reset', false, ['style' => 'float: right;', 'onclick' => "return confirm('". esc_attr__('Are you sure you want to reset all settings to default?', 'modulux-shipping-helper') ."');"]); ?>
        </form>
    </div>  
    <?php
}



function modulux_save_shipping_helper_settings() {

    // Only run on our plugin settings page
    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'modulux-shipping-helper'
    ) {
        return;
    }

    if (!isset($_POST['modulux_weight_units']) || !is_array($_POST['modulux_weight_units'])) return; // Prevent saving if no units are provided

    // Check if the user has permission to manage WooCommerce settings
    if (!current_user_can('manage_woocommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('You do not have permission to manage WooCommerce settings.', 'modulux-shipping-helper') . '</p></div>';
        });
        return;
    }

    // Check if the form was submitted
    if (!isset($_POST['modulux_units_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['modulux_units_nonce'])), 'modulux_save_units')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to save the settings. Please try again.', 'modulux-shipping-helper') . '</p></div>';
        });        
        return; // Nonce check failed, do not proceed
    }

    $calc_mode = isset($_POST['modulux_shipping_calc_mode']) ? sanitize_text_field(wp_unslash($_POST['modulux_shipping_calc_mode'])) : 'heaviest';
    update_option('modulux_shipping_calc_mode', $calc_mode);

    // Reset settings if the reset button was clicked
    if (isset($_POST['reset'])) {
        // Reset to default settings
        delete_option('modulux_weight_units');
        delete_option('modulux_shipping_rules');
        
        // Show success message to the admin notices
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Modulux shipping settings reset to default.', 'modulux-shipping-helper') . '</p></div>';
        });
        return;
    }
    
    // Save weight units
    if ( isset( $_POST['modulux_weight_units'] ) && is_array( $_POST['modulux_weight_units'] ) ) {
        // Unslash and sanitize deeply
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $raw_units = wp_unslash( $_POST['modulux_weight_units'] );

        // Sanitize each string and preserve "0"
        $cleaned = array_filter(
            (array) map_deep( $raw_units, 'sanitize_text_field' ),
            'strlen'
        );

        update_option( 'modulux_weight_units', $cleaned );

        // Success notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__( 'Weight units saved successfully.', 'modulux-shipping-helper' ) .
                '</p></div>';
        } );
    }

    // Save shipping rules
    if (isset($_POST['modulux_shipping_rules']) && is_array($_POST['modulux_shipping_rules'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $raw_rules = wp_unslash( $_POST['modulux_shipping_rules'] );

        $shipping_rules = [];

        foreach ( (array) $raw_rules as $unit_raw => $data_raw ) {
            // Sanitize the unit key used as an array index (fixes InputNotSanitized)
            $unit = sanitize_key( $unit_raw );
            if ( $unit === '' ) {
                continue;
            }

            $data = (array) $data_raw;

            // Fallback: allow empty -> null, else non-negative float
            $fallback = null;
            if ( isset($data['fallback']) && $data['fallback'] !== '' ) {
                $fallback = (float) $data['fallback'];
                if ( $fallback < 0 ) {
                    $fallback = 0.0;
                }
            }

            $shipping_rules[ $unit ] = [
                'rules'    => [],
                'fallback' => $fallback,
            ];

            // Max/price arrays (may be missing or mismatched lengths)
            $max_list   = isset($data['max'])   ? (array) $data['max']   : [];
            $price_list = isset($data['price']) ? (array) $data['price'] : [];

            foreach ( $max_list as $i => $max_raw ) {
                // Ensure there is a corresponding price
                if ( ! array_key_exists($i, $price_list) ) {
                    continue;
                }

                // Cast to numbers safely
                $max   = is_scalar($max_raw)          ? (float) $max_raw          : 0.0;
                $price = is_scalar($price_list[$i])   ? (float) $price_list[$i]   : null;

                if ( $price === null ) {
                    continue;
                }

                // Keep your original validation: max > 0 and price >= 0
                if ( $max > 0 && $price >= 0 ) {
                    $shipping_rules[ $unit ]['rules'][] = [
                        'max'   => $max,
                        'price' => $price,
                    ];
                }
            }

            // Optional: normalize order by ascending 'max'
            if ( ! empty($shipping_rules[$unit]['rules']) ) {
                usort(
                    $shipping_rules[$unit]['rules'],
                    static function($a, $b) { return $a['max'] <=> $b['max']; }
                );
            }
        }

        // Vat and free shipping threshold
        /*$shipping_rules['_meta'] = [
            'vat' => isset($_POST['modulux_vat']) ? floatval($_POST['modulux_vat']) : 0,
            'free_shipping_threshold' => isset($_POST['modulux_free_shipping_threshold']) ? floatval($_POST['modulux_free_shipping_threshold']) : 0,
        ];*/
        $vat_raw   = isset($_POST['modulux_vat']) ? sanitize_text_field(wp_unslash($_POST['modulux_vat'])) : '';
        $vat       = is_numeric($vat_raw) ? max(0, (float) $vat_raw) : 0.0;

        $fst_raw   = isset($_POST['modulux_free_shipping_threshold']) ? sanitize_text_field(wp_unslash($_POST['modulux_free_shipping_threshold'])) : '';
        $fst       = is_numeric($fst_raw) ? max(0, (float) $fst_raw) : 0.0;

        $shipping_rules['_meta'] = [
            'vat' => $vat,
            'free_shipping_threshold' => $fst,
        ];        

        update_option('modulux_shipping_rules', $shipping_rules);
    }

}
add_action('admin_init', 'modulux_save_shipping_helper_settings');