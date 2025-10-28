<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Add custom button to suggest weight
add_action('woocommerce_product_options_shipping', function () {
    echo '<div class="options_group">';
    echo '<p class="form-field"><button type="button" class="button" id="modulux-suggest-weight">' . esc_html__('Suggest Weight', 'modulux-shipping-helper') . '</button></p>';
    echo '</div>';
});

// Enqueue admin script for weight suggestion
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    if (get_post_type() !== 'product') return;

    wp_enqueue_script('jquery');
    wp_enqueue_script(
        'modulux-shipping-helper-suggest-weight-js',
        plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/suggest-weight.js',
        ['jquery'],
        '1.0',
        true
    );
    wp_localize_script('modulux-shipping-helper-suggest-weight-js', 'modulux_weight_suggestor', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('modulux_suggest_weight'),
        'suggested_applied' => __('Suggested weight applied:', 'modulux-shipping-helper'),
        'no_suggestion' => __('No weight suggestion available.', 'modulux-shipping-helper'),
        'error_fetching' => __('Error fetching weight suggestion.', 'modulux-shipping-helper')
    ]);    
});

// Handle AJAX request to suggest weight based on other products in same category or tags
add_action('wp_ajax_modulux_suggest_weight', function () {
    check_ajax_referer('modulux_suggest_weight', 'nonce');

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $post_id = intval($_POST['post_id']);
    if (!$post_id) {
        wp_send_json_error();
    }

    $suggestion = null;
    $weights = [];

    // Get product categories and tags
    $cats = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'ids']);
    $tags = wp_get_post_terms($post_id, 'product_tag', ['fields' => 'ids']);

    // Fetch other products that share same category or tags
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 100,
        'post_status'    => 'publish',
        'post__not_in'   => [$post_id], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
        'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'relation' => 'OR',
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cats,
                'include_children' => true,
            ],
            [
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $tags,
            ]
        ]
    ];

    $products = get_posts($args);

    foreach ($products as $p) {
        $w  = get_post_meta($p->ID, '_weight', true);
        $wu = get_post_meta($p->ID, '_weight_unit', true) ?: 'Kg';

        if (!empty($w) && is_numeric($w)) {
            $key = $w . ':' . $wu;
            if (!isset($weights[$key])) {
                $weights[$key] = 0;
            }
            $weights[$key]++;
        }
    }

    // Pick the most frequent weight/unit pair
    if (!empty($weights)) {
        arsort($weights);
        [$weight, $unit] = explode(':', array_key_first($weights));
        $suggestion = [
            'weight' => floatval($weight),
            'unit'   => $unit,
        ];
        wp_send_json_success($suggestion);
    } else {
        wp_send_json_error();
    }
});