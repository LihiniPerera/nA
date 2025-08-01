<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked wc_empty_cart_message - 10
 */
do_action( 'woocommerce_cart_is_empty' );

?>

<!-- Custom Empty Cart Design -->
<div class="cart-empty-container">
    
    <!-- Empty Cart Hero Section -->
    <div class="cart-empty-hero">
        <div class="cart-empty-icon">
            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="59" stroke="#F1C40F" stroke-width="2" fill="rgba(241, 196, 15, 0.1)"/>
                <path d="M35 40h8l8 32h24l6-24h18" stroke="#F1C40F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="51" cy="84" r="4" fill="#F1C40F"/>
                <circle cx="75" cy="84" r="4" fill="#F1C40F"/>
                <path d="M43 72h32" stroke="#F1C40F" stroke-width="2" stroke-linecap="round"/>
                <path d="M47 64h24" stroke="#F1C40F" stroke-width="2" stroke-linecap="round"/>
                <path d="M51 56h16" stroke="#F1C40F" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div class="cart-empty-icon-glow"></div>
        </div>
        
        <h2 class="cart-empty-title">Your cart is empty</h2>
        <p class="cart-empty-subtitle">Ready to start shopping?</p>
        <p class="cart-empty-description">
            Discover amazing products, exclusive deals, and find exactly what you're looking for. Your perfect purchase is just a click away!
        </p>
        
        <!-- Action Buttons -->
        <div class="cart-empty-actions">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="cart-start-shopping-btn">
                Start Shopping
            </a>
        </div>
    </div>
    
    <!-- Popular Categories Section -->
    <div class="cart-popular-categories">
        <h3 class="cart-categories-title">Popular Categories</h3>
        <div class="cart-categories-grid">
            
            <?php
            // Get popular product categories
            $categories = get_terms( array(
                'taxonomy'   => 'product_cat',
                'orderby'    => 'count',
                'order'      => 'DESC',
                'number'     => 6,
                'hide_empty' => true,
                'exclude'    => array( get_option( 'default_product_cat' ) ), // Exclude uncategorized
            ) );
            
            // Default categories if none found
            $default_categories = array(
                array( 'name' => 'Electronics', 'icon' => '📱', 'count' => '250+ items' ),
                array( 'name' => 'Fashion', 'icon' => '👕', 'count' => '180+ items' ),
                array( 'name' => 'Home & Garden', 'icon' => '🏠', 'count' => '320+ items' ),
                array( 'name' => 'Sports', 'icon' => '⚽', 'count' => '150+ items' ),
                array( 'name' => 'Books', 'icon' => '📚', 'count' => '500+ items' ),
                array( 'name' => 'Beauty', 'icon' => '💄', 'count' => '120+ items' ),
            );
            
            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                foreach ( $categories as $index => $category ) :
                    if ( $index >= 6 ) break; // Limit to 6 categories
                    
                    $category_link = get_term_link( $category );
                    $category_count = $category->count;
                    
                    // Simple icon mapping based on category name
                    $icon = '🛍️'; // Default icon
                    $name_lower = strtolower( $category->name );
                    if ( strpos( $name_lower, 'electronic' ) !== false || strpos( $name_lower, 'tech' ) !== false ) $icon = '📱';
                    elseif ( strpos( $name_lower, 'fashion' ) !== false || strpos( $name_lower, 'clothing' ) !== false ) $icon = '👕';
                    elseif ( strpos( $name_lower, 'home' ) !== false || strpos( $name_lower, 'garden' ) !== false ) $icon = '🏠';
                    elseif ( strpos( $name_lower, 'sport' ) !== false || strpos( $name_lower, 'fitness' ) !== false ) $icon = '⚽';
                    elseif ( strpos( $name_lower, 'book' ) !== false ) $icon = '📚';
                    elseif ( strpos( $name_lower, 'beauty' ) !== false || strpos( $name_lower, 'cosmetic' ) !== false ) $icon = '💄';
                    ?>
                    
                    <a href="<?php echo esc_url( $category_link ); ?>" class="cart-category-card">
                        <div class="cart-category-icon"><?php echo $icon; ?></div>
                        <div class="cart-category-info">
                            <h4 class="cart-category-name"><?php echo esc_html( $category->name ); ?></h4>
                            <span class="cart-category-count"><?php echo esc_html( $category_count ); ?>+ items</span>
                        </div>
                    </a>
                    
                    <?php
                endforeach;
            else :
                // Fallback to default categories
                foreach ( $default_categories as $category ) :
                    ?>
                    
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="cart-category-card">
                        <div class="cart-category-icon"><?php echo $category['icon']; ?></div>
                        <div class="cart-category-info">
                            <h4 class="cart-category-name"><?php echo esc_html( $category['name'] ); ?></h4>
                            <span class="cart-category-count"><?php echo esc_html( $category['count'] ); ?></span>
                        </div>
                    </a>
                    
                    <?php
                endforeach;
            endif;
            ?>
            
        </div>
    </div>
    
    <!-- Trust Badges -->
    <div class="cart-empty-trust">
        <div class="cart-trust-badges">
            <div class="cart-trust-badge">
                <span class="cart-trust-icon">🚚</span>
                <span class="cart-trust-text">Free Shipping</span>
            </div>
            <div class="cart-trust-badge">
                <span class="cart-trust-icon">↩️</span>
                <span class="cart-trust-text">Easy Returns</span>
            </div>
            <div class="cart-trust-badge">
                <span class="cart-trust-icon">🔒</span>
                <span class="cart-trust-text">Secure Payment</span>
            </div>
            <div class="cart-trust-badge">
                <span class="cart-trust-icon">📞</span>
                <span class="cart-trust-text">24/7 Support</span>
            </div>
        </div>
    </div>
    
</div>

<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
    <p class="return-to-shop">
        <a class="button wc-backward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
            <?php
                /**
                 * Filter "Return To Shop" text.
                 *
                 * @since 4.6.0
                 * @param string $default_text Default text.
                 */
                echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'woocommerce' ) ) );
            ?>
        </a>
    </p>
<?php endif; ?>