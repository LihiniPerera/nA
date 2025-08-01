<?php
/**
 * Kadence functions and definitions
 *
 * This file must be parseable by PHP 5.2.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package kadence
 */

define( 'KADENCE_VERSION', '1.2.22' );
define( 'KADENCE_MINIMUM_WP_VERSION', '6.0' );
define( 'KADENCE_MINIMUM_PHP_VERSION', '7.4' );

// Bail if requirements are not met.
if ( version_compare( $GLOBALS['wp_version'], KADENCE_MINIMUM_WP_VERSION, '<' ) || version_compare( phpversion(), KADENCE_MINIMUM_PHP_VERSION, '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
	return;
}
// Include WordPress shims.
require get_template_directory() . '/inc/wordpress-shims.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/class-theme.php';

// Load the `kadence()` entry point function.
require get_template_directory() . '/inc/functions.php';

// Initialize the theme.
call_user_func( 'Kadence\kadence' );

// To disable the big image size threshold
add_filter( 'big_image_size_threshold', '__return_false' );

// To set the JPEG quality to the highest,uncompressed
add_filter( 'jpeg_quality', function() {
    return 100;
} );

/**
 * Polo Product Page Functionality
 * Custom AJAX handlers and functions for the polo product page
 */

// Add meta box for polo product ID selection
add_action( 'add_meta_boxes', 'polo_add_product_meta_box' );
function polo_add_product_meta_box() {
    add_meta_box(
        'polo_product_selection',
        'Polo Product Configuration',
        'polo_product_meta_box_callback',
        'page',
        'side',
        'high'
    );
}

function polo_product_meta_box_callback( $post ) {
    wp_nonce_field( 'polo_save_meta_box_data', 'polo_meta_box_nonce' );
    
    $polo_product_id = get_post_meta( $post->ID, 'polo_product_id', true );
    
    // Get all products for selection
    $products = wc_get_products( array(
        'limit' => -1,
        'status' => 'publish'
    ) );
    
    echo '<label for="polo_product_id">Select Product for Polo Page:</label>';
    echo '<select name="polo_product_id" id="polo_product_id" style="width: 100%; margin-top: 10px;">';
    echo '<option value="">Auto-detect polo product</option>';
    
    foreach ( $products as $product ) {
        $selected = selected( $polo_product_id, $product->get_id(), false );
        echo '<option value="' . $product->get_id() . '" ' . $selected . '>';
        echo esc_html( $product->get_name() ) . ' (ID: ' . $product->get_id() . ')';
        echo '</option>';
    }
    
    echo '</select>';
    echo '<p style="margin-top: 10px; font-style: italic;">Leave empty to auto-detect a product with "polo" in the name or SKU "woo-polo".</p>';
}

// Save polo product meta box data
add_action( 'save_post', 'polo_save_meta_box_data' );
function polo_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['polo_meta_box_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( $_POST['polo_meta_box_nonce'], 'polo_save_meta_box_data' ) ) {
        return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    if ( isset( $_POST['polo_product_id'] ) ) {
        update_post_meta( $post_id, 'polo_product_id', sanitize_text_field( $_POST['polo_product_id'] ) );
    }
}

// AJAX handler for adding products to cart from polo page
add_action( 'wp_ajax_polo_add_to_cart', 'polo_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_polo_add_to_cart', 'polo_ajax_add_to_cart' );

function polo_ajax_add_to_cart() {
    // Enable error logging for debugging
    $debug = current_user_can( 'manage_options' );
    
    if ( $debug ) {
        error_log( 'POLO ADD TO CART: Request started with data: ' . print_r( $_POST, true ) );
    }
    
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'polo_nonce' ) ) {
        if ( $debug ) error_log( 'POLO ADD TO CART: Nonce verification failed' );
        wp_send_json_error( 'Security check failed' );
        return;
    }
    
    // Get and sanitize data
    $product_id = intval( $_POST['product_id'] );
    $variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
    $quantity = intval( $_POST['quantity'] );
    $size = sanitize_text_field( $_POST['size'] );
    $color = sanitize_text_field( $_POST['color'] );
    
    if ( $debug ) {
        error_log( "POLO ADD TO CART: Processed data - Product ID: $product_id, Variation ID: $variation_id, Quantity: $quantity, Size: $size, Color: $color" );
    }
    
    // Validate required data
    if ( ! $product_id || ! $quantity ) {
        if ( $debug ) error_log( 'POLO ADD TO CART: Missing required data' );
        wp_send_json_error( 'Missing required product data' );
        return;
    }
    
    // Get the product
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        if ( $debug ) error_log( "POLO ADD TO CART: Product not found for ID: $product_id" );
        wp_send_json_error( 'Product not found' );
        return;
    }
    
    if ( $debug ) {
        error_log( "POLO ADD TO CART: Product loaded - Name: " . $product->get_name() . ", Type: " . $product->get_type() );
    }
    
    // Prepare cart item data (only for non-variation data like personalization)
    $cart_item_data = array();
    
    // Don't add size/color as custom data since they're handled by variations
    // This prevents duplication in cart display
    
    // Handle product variations if it's a variable product
    $variation = array();
    
    if ( $product->is_type( 'variable' ) ) {
        // If variation_id was provided by JavaScript, use it directly
        if ( $variation_id > 0 ) {
            // Verify this variation exists and belongs to this product
            $variation_product = wc_get_product( $variation_id );
            if ( $variation_product && $variation_product->get_parent_id() === $product_id ) {
                // Get variation attributes for cart
                $variation_attributes = $variation_product->get_variation_attributes();
                $variation = array();
                foreach ( $variation_attributes as $key => $value ) {
                    $variation['attribute_' . $key] = $value;
                }
            } else {
                $variation_id = 0; // Invalid variation, fallback to manual matching
            }
        }
        
        // Fallback: Try to find matching variation by attributes if no valid variation_id
        if ( $variation_id === 0 ) {
            $available_variations = $product->get_available_variations();
            
            foreach ( $available_variations as $available_variation ) {
                $match = true;
                
                // Check if size matches
                if ( $size && isset( $available_variation['attributes']['attribute_pa_size'] ) ) {
                    if ( strtolower( $available_variation['attributes']['attribute_pa_size'] ) !== strtolower( $size ) ) {
                        $match = false;
                    }
                }
                
                // Check if color matches
                if ( $color && isset( $available_variation['attributes']['attribute_pa_color'] ) ) {
                    if ( strtolower( $available_variation['attributes']['attribute_pa_color'] ) !== strtolower( $color ) ) {
                        $match = false;
                    }
                }
                
                if ( $match ) {
                    $variation_id = $available_variation['variation_id'];
                    $variation = $available_variation['attributes'];
                    break;
                }
            }
        }
    }
    
    // Ensure WooCommerce cart is loaded
    if ( ! WC()->cart ) {
        if ( $debug ) error_log( 'POLO ADD TO CART: WooCommerce cart not available' );
        wp_send_json_error( 'Cart not available' );
        return;
    }
    
    if ( $debug ) {
        error_log( "POLO ADD TO CART: Adding to cart - Product: $product_id, Variation: $variation_id, Quantity: $quantity" );
        error_log( "POLO ADD TO CART: Variation attributes: " . print_r( $variation, true ) );
        error_log( "POLO ADD TO CART: Cart data: " . print_r( $cart_item_data, true ) );
    }
    
    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart( 
        $product_id, 
        $quantity, 
        $variation_id, 
        $variation, 
        $cart_item_data 
    );
    
    if ( $debug ) {
        error_log( "POLO ADD TO CART: Cart item key: " . ( $cart_item_key ? $cart_item_key : 'false' ) );
        if ( ! $cart_item_key ) {
            error_log( "POLO ADD TO CART: WooCommerce notices: " . print_r( wc_get_notices(), true ) );
        }
    }
    
    if ( $cart_item_key ) {
        // Calculate the cart totals to ensure everything is up to date
        WC()->cart->calculate_totals();
        
        // Get cart fragments for updating cart display - the proper way
        $fragments = array();
        
        // Get cart count for header
        $cart_count = WC()->cart->get_cart_contents_count();
        
        // Generate fragments for common cart elements
        $fragments['.cart-contents-count'] = $cart_count;
        $fragments['.cart-count'] = $cart_count;
        $fragments['.kadence-cart-count'] = $cart_count;
        $fragments['span.count'] = $cart_count;
        
        // Get WooCommerce fragments
        $wc_fragments = apply_filters( 'woocommerce_add_to_cart_fragments', $fragments );
        
        if ( $debug ) {
            error_log( "POLO ADD TO CART: Success - Cart count: $cart_count, Hash: " . WC()->cart->get_cart_hash() );
        }
        
        wp_send_json_success( array(
            'message' => sprintf( 'Product added to cart successfully! Cart now has %d items.', $cart_count ),
            'cart_item_key' => $cart_item_key,
            'cart_hash' => WC()->cart->get_cart_hash(),
            'cart_count' => $cart_count,
            'fragments' => $wc_fragments,
            'cart_url' => wc_get_cart_url()
        ) );
    } else {
        // Get WooCommerce error notices if available
        $notices = wc_get_notices( 'error' );
        $error_message = 'Failed to add product to cart.';
        
        if ( ! empty( $notices ) ) {
            $error_message = $notices[0]['notice'] ?? $error_message;
            // Clear notices so they don't persist
            wc_clear_notices();
        }
        
        if ( $debug ) {
            error_log( "POLO ADD TO CART: Failed - Error: $error_message" );
            error_log( "POLO ADD TO CART: All notices: " . print_r( wc_get_notices(), true ) );
        }
        
        wp_send_json_error( $error_message );
    }
}

// AJAX handler for getting cart count
add_action( 'wp_ajax_polo_get_cart_count', 'polo_ajax_get_cart_count' );
add_action( 'wp_ajax_nopriv_polo_get_cart_count', 'polo_ajax_get_cart_count' );

function polo_ajax_get_cart_count() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'polo_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    
    wp_send_json_success( array( 'count' => $cart_count ) );
}

// Custom cart item data functions removed - we now use proper WooCommerce variations
// which automatically handle size/color display in cart and orders

// Add custom template for polo page
add_filter( 'template_include', 'polo_page_template_redirect' );

function polo_page_template_redirect( $template ) {
    global $post;
    
    // Check if this is a page with slug 'polo' under 'shop'
    if ( is_page() && $post && $post->post_name === 'polo' ) {
        $parent_page = get_post( $post->post_parent );
        if ( $parent_page && $parent_page->post_name === 'shop' ) {
            $custom_template = locate_template( 'page-polo.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }
    }
    
    return $template;
}

// Ensure WooCommerce is active check
if ( ! function_exists( 'polo_is_woocommerce_active' ) ) {
    function polo_is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }
}

// Add admin notice if WooCommerce is not active
add_action( 'admin_notices', 'polo_woocommerce_admin_notice' );

function polo_woocommerce_admin_notice() {
    if ( ! polo_is_woocommerce_active() ) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Polo Page:</strong> WooCommerce must be active for the polo product page to function properly.';
        echo '</p></div>';
    }
}

// ==========================================
// CUSTOM CART PAGE FUNCTIONALITY
// ==========================================

/**
 * Enqueue custom cart assets only on cart page
 */
function enqueue_custom_cart_assets() {
    if (function_exists('is_cart') && is_cart()) {
        // Enqueue custom cart CSS
        wp_enqueue_style(
            'cart-custom-css',
            get_template_directory_uri() . '/assets/css/cart-custom.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue custom cart JavaScript
        wp_enqueue_script(
            'cart-interactions',
            get_template_directory_uri() . '/assets/js/cart-interactions.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script with necessary data
        wp_localize_script('cart-interactions', 'cart_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cart_nonce'),
            'checkout_url' => wc_get_checkout_url(),
            'shop_url' => wc_get_page_permalink('shop'),
            'enable_swipe_remove' => apply_filters('cart_enable_swipe_remove', true),
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_cart_assets');

/**
 * AJAX handler for updating cart item quantity
 */
function cart_ajax_update_quantity() {
    // Enable error logging for debugging
    $debug = current_user_can('manage_options');
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'cart_nonce')) {
        if ($debug) error_log('CART UPDATE: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Get and sanitize data
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $quantity = intval($_POST['quantity']);
    
    if ($debug) {
        error_log("CART UPDATE: Key: $cart_item_key, Quantity: $quantity");
    }
    
    // Validate required data
    if (!$cart_item_key) {
        if ($debug) error_log('CART UPDATE: Missing cart item key');
        wp_send_json_error('Missing cart item key');
        return;
    }
    
    if ($quantity < 1) {
        if ($debug) error_log('CART UPDATE: Invalid quantity - must be at least 1');
        wp_send_json_error('Quantity must be at least 1');
        return;
    }
    
    // Ensure WooCommerce cart is loaded
    if (!WC()->cart) {
        if ($debug) error_log('CART UPDATE: WooCommerce cart not available');
        wp_send_json_error('Cart not available');
        return;
    }
    
    try {
        // Get cart contents
        $cart = WC()->cart->get_cart();
        
        if (!isset($cart[$cart_item_key])) {
            wp_send_json_error('Cart item not found');
            return;
        }
        
        $cart_item = $cart[$cart_item_key];
        $product = $cart_item['data'];
        
        // Validate quantity against product limits
        if ($product->is_sold_individually() && $quantity > 1) {
            wp_send_json_error('This product can only be purchased individually (max quantity: 1)');
            return;
        }
        
        $max_quantity = $product->get_max_purchase_quantity();
        if ($max_quantity > 0 && $quantity > $max_quantity) {
            wp_send_json_error("Maximum quantity for this product is {$max_quantity}");
            return;
        }
        
        // Update cart item quantity (quantity is guaranteed to be >= 1 at this point)
        $cart_updated = WC()->cart->set_quantity($cart_item_key, $quantity, true);
        
        if (!$cart_updated) {
            wp_send_json_error('Failed to update cart item quantity');
            return;
        }
        
        // Calculate totals
        WC()->cart->calculate_totals();
        
        // Prepare response data
        $response_data = array(
            'cart_item_key' => $cart_item_key,
            'quantity' => $quantity,
            'cart_empty' => WC()->cart->is_empty(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'message' => 'Cart updated successfully',
            'fragments' => array()
        );
        
        // Get updated item subtotal if item still exists
        if ($quantity > 0) {
            $updated_cart = WC()->cart->get_cart();
            if (isset($updated_cart[$cart_item_key])) {
                $response_data['item_subtotal'] = WC()->cart->get_product_subtotal(
                    $updated_cart[$cart_item_key]['data'],
                    $updated_cart[$cart_item_key]['quantity']
                );
            }
        }
        
        // Add cart totals fragment for order summary update
        ob_start();
        wc_get_template('cart/cart-totals.php');
        $cart_totals_html = ob_get_clean();
        
        $response_data['fragments']['.cart-summary-card'] = '<div class="cart-summary-card">' . $cart_totals_html . '</div>';
        $response_data['fragments']['.cart-summary-section'] = '<div class="cart-summary-section"><div class="cart-collaterals">' . $cart_totals_html . '</div></div>';
        
        if ($debug) {
            error_log("CART UPDATE SUCCESS: " . print_r($response_data, true));
        }
        
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        if ($debug) {
            error_log("CART UPDATE ERROR: " . $e->getMessage());
        }
        wp_send_json_error('Failed to update cart: ' . $e->getMessage());
    }
}
add_action('wp_ajax_cart_update_quantity', 'cart_ajax_update_quantity');
add_action('wp_ajax_nopriv_cart_update_quantity', 'cart_ajax_update_quantity');

/**
 * AJAX handler for removing cart item
 */
function cart_ajax_remove_item() {
    $debug = current_user_can('manage_options');
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'cart_nonce')) {
        if ($debug) error_log('CART REMOVE: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    
    if (!$cart_item_key) {
        wp_send_json_error('Invalid cart item');
        return;
    }
    
    try {
        // Remove item from cart
        $removed = WC()->cart->remove_cart_item($cart_item_key);
        
        if (!$removed) {
            wp_send_json_error('Failed to remove item from cart');
            return;
        }
        
        // Calculate totals
        WC()->cart->calculate_totals();
        
        $response_data = array(
            'cart_item_key' => $cart_item_key,
            'cart_empty' => WC()->cart->is_empty(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'fragments' => apply_filters('woocommerce_update_cart_fragments', array()),
            'cart_hash' => WC()->cart->get_cart_hash()
        );
        
        if ($debug) {
            error_log("CART REMOVE SUCCESS: " . print_r($response_data, true));
        }
        
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        if ($debug) {
            error_log("CART REMOVE ERROR: " . $e->getMessage());
        }
        wp_send_json_error('Failed to remove item: ' . $e->getMessage());
    }
}
add_action('wp_ajax_cart_remove_item', 'cart_ajax_remove_item');
add_action('wp_ajax_nopriv_cart_remove_item', 'cart_ajax_remove_item');

/**
 * AJAX handler for applying coupon
 */
function cart_ajax_apply_coupon() {
    $debug = current_user_can('manage_options');
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'cart_nonce')) {
        if ($debug) error_log('CART COUPON: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    $coupon_code = sanitize_text_field($_POST['coupon_code']);
    
    if (!$coupon_code) {
        wp_send_json_error('Please enter a coupon code');
        return;
    }
    
    try {
        // Apply coupon
        $result = WC()->cart->apply_coupon($coupon_code);
        
        if (!$result) {
            // Get error messages
            $error_messages = wc_get_notices('error');
            $error_message = 'Invalid coupon code';
            
            if (!empty($error_messages)) {
                $error_message = wp_strip_all_tags($error_messages[0]['notice']);
            }
            
            // Clear notices to prevent them showing elsewhere
            wc_clear_notices();
            
            wp_send_json_error($error_message);
            return;
        }
        
        // Calculate totals
        WC()->cart->calculate_totals();
        
        $response_data = array(
            'coupon_code' => $coupon_code,
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'message' => sprintf('Coupon "%s" applied successfully!', $coupon_code),
            'fragments' => array()
        );
        
        // Add cart totals fragment for order summary update
        ob_start();
        wc_get_template('cart/cart-totals.php');
        $cart_totals_html = ob_get_clean();
        
        $response_data['fragments']['.cart-summary-card'] = '<div class="cart-summary-card">' . $cart_totals_html . '</div>';
        $response_data['fragments']['.cart-summary-section'] = '<div class="cart-summary-section"><div class="cart-collaterals">' . $cart_totals_html . '</div></div>';
        
        // Clear any success notices
        wc_clear_notices();
        
        if ($debug) {
            error_log("CART COUPON SUCCESS: " . print_r($response_data, true));
        }
        
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        if ($debug) {
            error_log("CART COUPON ERROR: " . $e->getMessage());
        }
        wp_send_json_error('Failed to apply coupon: ' . $e->getMessage());
    }
}
add_action('wp_ajax_cart_apply_coupon', 'cart_ajax_apply_coupon');
add_action('wp_ajax_nopriv_cart_apply_coupon', 'cart_ajax_apply_coupon');

/**
 * Add custom body class for cart page styling
 */
function add_custom_cart_body_class($classes) {
    if (function_exists('is_cart') && is_cart()) {
        $classes[] = 'custom-cart-page';
        $classes[] = 'dark-theme';
    }
    return $classes;
}
add_filter('body_class', 'add_custom_cart_body_class');

/**
 * Hide default WooCommerce cart notices on our custom cart page
 */
function hide_cart_notices_on_custom_cart() {
    // Temporarily disabled to prevent potential issues
    // if (function_exists('is_cart') && is_cart()) {
    //     remove_action('woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10);
    //     remove_action('woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', 10);
    // }
}
// add_action('wp', 'hide_cart_notices_on_custom_cart');

/**
 * Ensure cart fragments are updated for our custom cart
 */
function update_cart_fragments_for_custom_cart($fragments) {
    // Only add fragments if not already processing to prevent infinite loop
    static $processing = false;
    
    if (!$processing && !wp_doing_ajax()) {
        $processing = true;
        
        // Update cart totals sidebar
        ob_start();
        ?>
        <div class="cart-summary-card">
            <?php wc_get_template('cart/cart-totals.php'); ?>
        </div>
        <?php
        $fragments['.cart-summary-card'] = ob_get_clean();
        
        $processing = false;
    }
    
    return $fragments;
}
add_filter('woocommerce_update_cart_fragments', 'update_cart_fragments_for_custom_cart');

/**
 * Add cart count to header for theme integration
 */
function get_cart_count_for_header() {
    if (WC()->cart) {
        return WC()->cart->get_cart_contents_count();
    }
    return 0;
}

/**
 * Helper function to check if custom cart is enabled
 */
function is_custom_cart_enabled() {
    return apply_filters('enable_custom_cart', true);
}

/**
 * Add custom cart settings to WooCommerce settings (optional)
 */
function add_custom_cart_settings($settings) {
    // Temporarily disabled - may be causing conflicts
    return $settings;
}
// add_filter('woocommerce_get_settings_advanced', 'add_custom_cart_settings');

/**
 * Compatibility with common WooCommerce plugins
 */
function ensure_plugin_compatibility_for_cart() {
    // Temporarily disabled
    // if (function_exists('wc_get_theme_support')) {
    //     add_theme_support('wc-product-gallery-zoom');
    //     add_theme_support('wc-product-gallery-lightbox');
    //     add_theme_support('wc-product-gallery-slider');
    // }
    // 
    // if (function_exists('woocustomizer_library_get_default')) {
    //     add_filter('wcz-cart-proceed-btn', '__return_true');
    // }
}
// add_action('after_setup_theme', 'ensure_plugin_compatibility_for_cart');

/**
 * Add admin notice for cart page setup
 */
function custom_cart_admin_notice() {
    // Temporarily disabled
    // if (is_admin() && current_user_can('manage_options')) {
    //     $cart_page_id = wc_get_page_id('cart');
    //     if ($cart_page_id && is_custom_cart_enabled()) {
    //         $cart_page = get_post($cart_page_id);
    //         if ($cart_page && $cart_page->post_status === 'publish') {
    //             $template_path = get_template_directory() . '/woocommerce/cart/cart.php';
    //             if (!file_exists($template_path)) {
    //                 echo '<div class="notice notice-warning"><p>';
    //                 echo '<strong>Custom Cart:</strong> Cart template files are missing.';
    //                 echo '</p></div>';
    //             }
    //         }
    //     }
    // }
}
// add_action('admin_notices', 'custom_cart_admin_notice');

/**
 * Clear cart fragments cache when needed
 */
function clear_cart_fragments_cache() {
    // Temporarily disabled
    // if (function_exists('WC')) {
    //     WC()->cart->calculate_totals();
    // }
}
// add_action('woocommerce_cart_updated', 'clear_cart_fragments_cache');

/**
 * Add security headers for cart page
 */
function add_cart_security_headers() {
    // Temporarily disabled
    // if (function_exists('is_cart') && is_cart()) {
    //     header('X-Content-Type-Options: nosniff');
    //     header('X-Frame-Options: SAMEORIGIN');
    //     header('X-XSS-Protection: 1; mode=block');
    // }
}
// add_action('send_headers', 'add_cart_security_headers');

/**
 * Performance optimization: Preload cart assets
 */
function preload_cart_assets() {
    // Temporarily disabled
    // if (function_exists('is_cart') && is_cart()) {
    //     echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/css/cart-custom.css" as="style">';
    //     echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/js/cart-interactions.js" as="script">';
    // }
}
// add_action('wp_head', 'preload_cart_assets', 1);

/**
 * Disable cart page caching for logged-in users
 */
function disable_cart_caching() {
    // Temporarily disabled
    // if (function_exists('is_cart') && is_cart() && is_user_logged_in()) {
    //     if (!defined('DONOTCACHEPAGE')) {
    //         define('DONOTCACHEPAGE', true);
    //     }
    //     nocache_headers();
    // }
}
// add_action('template_redirect', 'disable_cart_caching');

// ==========================================
// END CUSTOM CART PAGE FUNCTIONALITY
// ==========================================