<?php
/**
 * Custom Cart Page Template
 * 
 * This template overrides the default WooCommerce cart template
 * Follows the dark theme design pattern established by the polo page
 * 
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo '<div class="cart-error"><h1>WooCommerce Required</h1><p>WooCommerce must be active to use this page.</p></div>';
    return;
}

do_action( 'woocommerce_before_cart' ); ?>

<div class="cart-main-container">
    <div class="cart-container">
        
        <!-- Cart Header -->
        <div class="cart-header">
            <div class="cart-back-to-store">
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="cart-back-btn">
                    <span class="cart-back-arrow">←</span>
                    Back to Store
                </a>
            </div>
            
            <div class="cart-title-container">
                <h1 class="cart-main-title">Shopping Cart</h1>
                <p class="cart-subtitle">Review your items and proceed to checkout</p>
            </div>
        </div>

        <?php if ( WC()->cart->is_empty() ) : ?>
            
            <?php wc_get_template( 'cart/cart-empty.php' ); ?>
            
        <?php else : ?>
            
            <!-- Cart Content Grid -->
            <div class="cart-content-grid">
                
                <!-- Cart Items Section -->
                <div class="cart-items-section">
                    
                    <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
                        <?php do_action( 'woocommerce_before_cart_table' ); ?>

                        <div class="cart-items-container">
                            <?php do_action( 'woocommerce_before_cart_contents' ); ?>

                            <?php
                            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
                                $product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

                                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                                    $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                                    ?>
                                    
                                    <div class="cart-item-card <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                        
                                        <!-- Product Image -->
                                        <div class="cart-item-image">
                                            <div class="cart-item-image-container">
                                                <?php
                                                $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
                                                
                                                if ( ! $product_permalink ) {
                                                    echo $thumbnail;
                                                } else {
                                                    printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
                                                }
                                                ?>
                                                <div class="cart-item-image-glow"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div class="cart-item-details">
                                            
                                            <!-- Product Name and Meta -->
                                            <div class="cart-item-info">
                                                <h3 class="cart-item-name">
                                                    <?php
                                                    if ( ! $product_permalink ) {
                                                        echo wp_kses_post( $product_name . '&nbsp;' );
                                                    } else {
                                                        echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
                                                    }
                                                    ?>
                                                </h3>
                                                
                                                <?php do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key ); ?>
                                                
                                                <!-- Product Meta (Size, Color, etc.) -->
                                                <div class="cart-item-meta">
                                                    <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
                                                </div>
                                                
                                                <!-- Backorder notification -->
                                                <?php if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) : ?>
                                                    <div class="cart-item-backorder">
                                                        <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) ); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Product Price -->
                                                <div class="cart-item-price">
                                                    <span class="cart-item-price-label">Price:</span>
                                                    <span class="cart-item-price-value">
                                                        <?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Quantity and Subtotal Controls -->
                                            <div class="cart-item-controls">
                                                
                                                <!-- Quantity Controls -->
                                                <div class="cart-item-quantity">
                                                    <label class="cart-quantity-label">Quantity</label>
                                                    <div class="cart-quantity-controls">
                                                        <button type="button" class="cart-qty-btn cart-qty-minus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">−</button>
                                                        
                                                        <?php
                                                        // Set proper min/max values for cart items
                                                        if ( $_product->is_sold_individually() ) {
                                                            $min_quantity = 1;
                                                            $max_quantity = 1;
                                                        } else {
                                                            $min_quantity = 1; // Cart items should have minimum of 1, not 0
                                                            $max_quantity = $_product->get_max_purchase_quantity();
                                                            
                                                            // If max quantity is -1 (unlimited) or 0, set a reasonable limit
                                                            if ( $max_quantity <= 0 ) {
                                                                $max_quantity = 9999;
                                                            }
                                                        }
                                                        ?>
                                                        
                                                        <input 
                                                            type="number" 
                                                            name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]" 
                                                            value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" 
                                                            min="<?php echo esc_attr( $min_quantity ); ?>" 
                                                            max="<?php echo esc_attr( $max_quantity ); ?>" 
                                                            step="1" 
                                                            class="cart-qty-input" 
                                                            data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
                                                            data-original-qty="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
                                                            aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
                                                        />
                                                        
                                                        <button type="button" class="cart-qty-btn cart-qty-plus" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">+</button>
                                                    </div>
                                                </div>
                                                
                                                <!-- Item Subtotal -->
                                                <div class="cart-item-subtotal">
                                                    <span class="cart-subtotal-label">Subtotal:</span>
                                                    <span class="cart-subtotal-value">
                                                        <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
                                                    </span>
                                                </div>
                                                
                                                <!-- Remove Item Button -->
                                                <div class="cart-item-remove">
                                                    <?php
                                                    echo apply_filters(
                                                        'woocommerce_cart_item_remove_link',
                                                        sprintf(
                                                            '<a href="%s" class="cart-remove-btn" aria-label="%s" data-product_id="%s" data-product_sku="%s" data-cart_item_key="%s"><span class="cart-remove-icon">×</span><span class="cart-remove-text">Remove</span></a>',
                                                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                                            esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
                                                            esc_attr( $product_id ),
                                                            esc_attr( $_product->get_sku() ),
                                                            esc_attr( $cart_item_key )
                                                        ),
                                                        $cart_item_key
                                                    );
                                                    ?>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php
                                }
                            }
                            ?>

                            <?php do_action( 'woocommerce_cart_contents' ); ?>
                        </div>

                        <!-- Cart Actions (Update Cart, Coupon) -->
                        <div class="cart-actions">
                            <?php if ( wc_coupons_enabled() ) { ?>
                                <div class="cart-coupon">
                                    <label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
                                    <input type="text" name="coupon_code" class="cart-coupon-input" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Enter promo code', 'woocommerce' ); ?>" />
                                    <button type="submit" class="cart-coupon-btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
                                        <?php esc_html_e( 'Apply Code', 'woocommerce' ); ?>
                                    </button>
                                    <?php do_action( 'woocommerce_cart_coupon' ); ?>
                                </div>
                            <?php } ?>

                            <button type="submit" class="cart-update-btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>">
                                <?php esc_html_e( 'Update Cart', 'woocommerce' ); ?>
                            </button>

                            <?php do_action( 'woocommerce_cart_actions' ); ?>
                            <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                        </div>

                        <?php do_action( 'woocommerce_after_cart_contents' ); ?>
                        <?php do_action( 'woocommerce_after_cart_table' ); ?>
                    </form>
                    
                    <!-- Continue Shopping Link -->
                    <div class="cart-continue-shopping">
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="cart-continue-btn">
                            ← Continue Shopping
                        </a>
                    </div>
                    
                </div>
                
                <!-- Order Summary Section -->
                <div class="cart-summary-section">
                    <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

                    <div class="cart-collaterals">
                        <?php
                            /**
                             * Cart collaterals hook.
                             *
                             * @hooked woocommerce_cross_sell_display
                             * @hooked woocommerce_cart_totals - 10
                             */
                            do_action( 'woocommerce_cart_collaterals' );
                        ?>
                    </div>

                    <?php do_action( 'woocommerce_after_cart_collaterals' ); ?>
                </div>
                
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>