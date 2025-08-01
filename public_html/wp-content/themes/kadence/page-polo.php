<?php
/**
 * Template Name: Polo Product Page
 * Custom template for premium polo shirt product page
 */

defined( 'ABSPATH' ) || exit;

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    get_header();
    echo '<div class="polo-error"><h1>WooCommerce Required</h1><p>WooCommerce must be active to use this page.</p></div>';
    get_footer();
    return;
}


// Get the polo product - you can change this product ID in WordPress admin
$polo_product_id = get_post_meta(get_the_ID(), 'polo_product_id', true);

if (!$polo_product_id) {
    // Fallback - find product by SKU
    $polo_product_id = wc_get_product_id_by_sku('woo-polo');
    
    if (!$polo_product_id) {
        // Final fallback - get any product with 'polo' in name
        $products = wc_get_products(array(
            'limit' => 1,
            'status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_sku',
                    'value' => 'polo',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        if (empty($products)) {
            // Try searching by title
            $products = wc_get_products(array(
                'limit' => 1,
                'status' => 'publish',
                's' => 'polo'
            ));
        }
        
        if (!empty($products)) {
            $polo_product_id = $products[0]->get_id();
        }
    }
}

// Initialize product variable first
$product = null;

// Try to get the product - using global to ensure scope
global $polo_product;
$polo_product = null;

if ($polo_product_id) {
    $polo_product = wc_get_product($polo_product_id);
    // Validate the product object
    if ($polo_product && !is_object($polo_product)) {
        $polo_product = null;
    }
}

// Set local variable for backward compatibility
$product = $polo_product;

// Note: We don't use a random product fallback - only show the specific polo product with SKU 'woo-polo'

// Final check - if no product found, show error
if (!$polo_product || !is_object($polo_product)) {
    get_header();
    echo '<div class="polo-error" style="text-align: center; padding: 4rem 2rem; color: #ffffff; background: #000;">
        <h1 style="color: #F1C40F; margin-bottom: 1rem;">Polo Product Not Found</h1>
        <p style="color: #cccccc; margin-bottom: 2rem;">No product with SKU "woo-polo" found. Please:</p>
        <ul style="color: #cccccc; text-align: left; display: inline-block; margin-bottom: 2rem;">
            <li>Create a WooCommerce product with SKU "woo-polo"</li>
            <li>Or configure a different product in the page settings below</li>
            <li>Make sure the product is published and visible</li>
        </ul>
        <p><a href="' . admin_url('post.php?post=' . get_the_ID() . '&action=edit') . '" style="color: #F1C40F;">Configure Product in Page Settings</a></p>
        <p><a href="' . admin_url('post-new.php?post_type=product') . '" style="color: #F1C40F; margin-left: 1rem;">Create New Product</a></p>
    </div>';
    get_footer();
    return;
}

// Enqueue our custom assets
wp_enqueue_style('polo-custom-style', get_template_directory_uri() . '/assets/css/polo-custom.css', array(), wp_get_theme()->get('Version'));
wp_enqueue_script('polo-custom-script', get_template_directory_uri() . '/assets/js/polo-interactions.js', array('jquery'), wp_get_theme()->get('Version'), true);

// Localize script for AJAX and product data
$localize_product_id = 0;
if ($polo_product && is_object($polo_product) && method_exists($polo_product, 'get_id')) {
    $localize_product_id = $polo_product->get_id();
}

// If no product ID, the AJAX will handle it gracefully
// We only use the actual polo product, no random fallbacks

// Prepare variation data for JavaScript
$variation_data = array();
if ($polo_product && $polo_product->get_type() === 'variable') {
    $available_variations = $polo_product->get_available_variations();
    foreach ($available_variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        if ($variation_obj) {
            $size = '';
            // Get size from variation attributes
            if (isset($variation['attributes']['attribute_pa_size'])) {
                $size = $variation['attributes']['attribute_pa_size'];
            } elseif (isset($variation['attributes']['attribute_size'])) {
                $size = $variation['attributes']['attribute_size'];
            }
            
            if ($size) {
                $variation_data[$size] = array(
                    'variation_id' => $variation['variation_id'],
                    'price' => $variation_obj->get_price(),
                    'regular_price' => $variation_obj->get_regular_price(),
                    'sale_price' => $variation_obj->get_sale_price(),
                    'is_on_sale' => $variation_obj->is_on_sale(),
                    'price_html' => $variation_obj->get_price_html(),
                    'currency_symbol' => 'රු'
                );
            }
        }
    }
}

wp_localize_script('polo-custom-script', 'polo_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('polo_nonce'),
    'product_id' => $localize_product_id,
    'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'රු',
    'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '/checkout/',
    'variations' => $variation_data,
    'is_variable' => ($polo_product && $polo_product->get_type() === 'variable')
));

get_header();

// Restore product variable after header (WooCommerce unsets global $product)
$product = $polo_product;

// Initialize variables with empty defaults only - will be populated from real product
$product_images = array();
$main_image = array('', '', '');
$product_variations = array();
$attributes = array();
$reviews = array();
$average_rating = 0;
$review_count = 0;
$product_name = '';
$product_price = '';
$product_description = '';
$product_full_description = '';
$product_id = 0;
$is_on_sale = false;
$sale_price = 0;
$regular_price = 0;
$current_price = 0;
$stock_quantity = 0;
$product_type = 'simple';

// Extract product data if product exists and is valid
if ($polo_product && is_object($polo_product) && method_exists($polo_product, 'get_id')) {
    try {
        $product_id = $polo_product->get_id();
        $product_images = $polo_product->get_gallery_image_ids() ?: array();
        
        $image_id = $polo_product->get_image_id();
        if ($image_id) {
            $main_image_data = wp_get_attachment_image_src($image_id, 'full');
            if ($main_image_data && !empty($main_image_data[0])) {
                $main_image = $main_image_data;
            }
        }
        
        if (method_exists($polo_product, 'get_type') && $polo_product->get_type() === 'variable') {
            $product_variations = $polo_product->get_available_variations() ?: array();
        }
        
        $attributes = $polo_product->get_attributes() ?: array();
        
        // Get product name and type
        $product_name = $polo_product->get_name();
        $product_type = $polo_product->get_type();
        
        // Get pricing data safely - handle both simple and variable products
        if ($product_type === 'variable') {
            // Variable product - get price range
            $price_min = $polo_product->get_variation_price('min');
            $price_max = $polo_product->get_variation_price('max');
            
            if ($price_min && $price_max) {
                if ($price_min === $price_max) {
                    $product_price = wc_price($price_min);
                    $current_price = $price_min;
                } else {
                    $product_price = wc_price($price_min) . ' - ' . wc_price($price_max);
                    $current_price = $price_min;
                }
            }
            
            // Check if any variations are on sale
            $sale_price_min = $polo_product->get_variation_sale_price('min');
            $regular_price_min = $polo_product->get_variation_regular_price('min');
            $is_on_sale = $sale_price_min < $regular_price_min;
            
            if ($is_on_sale) {
                $sale_price = $sale_price_min;
                $regular_price = $regular_price_min;
            }
            
        } else {
            // Simple product - get direct price
            if (method_exists($polo_product, 'get_price')) {
                $current_price = floatval($polo_product->get_price());
                if ($current_price > 0) {
                    $product_price = wc_price($current_price);
                }
            }
            
            if (method_exists($polo_product, 'is_on_sale')) {
                $is_on_sale = $polo_product->is_on_sale();
            }
            
            if (method_exists($polo_product, 'get_sale_price')) {
                $sale_price = floatval($polo_product->get_sale_price());
            }
            
            if (method_exists($polo_product, 'get_regular_price')) {
                $regular_price = floatval($polo_product->get_regular_price());
            }
        }
        
        // Get stock quantity safely - handle both managed and unmanaged stock
        if (method_exists($polo_product, 'get_stock_quantity')) {
            $stock_quantity = $polo_product->get_stock_quantity();
            if ($stock_quantity === null || $stock_quantity === '') {
                // For products not managing stock, set a reasonable limit
                $stock_quantity = 999;
            }
        }
        
        // Get descriptions safely
        $short_desc = $polo_product->get_short_description();
        if (!empty($short_desc)) {
            $product_description = $short_desc;
        }
        
        $full_desc = $polo_product->get_description();
        if (!empty($full_desc)) {
            $product_full_description = $full_desc;
        }
        
        // Get reviews safely
        $reviews = get_comments(array(
            'post_id' => $product_id, 
            'comment_approved' => 1, 
            'comment_type' => 'review'
        )) ?: array();
        
        if (method_exists($product, 'get_average_rating')) {
            $average_rating = $product->get_average_rating() ?: 0;
        }
        
        if (method_exists($product, 'get_review_count')) {
            $review_count = $product->get_review_count() ?: 0;
        }
        
    } catch (Exception $e) {
        // If any product method fails, we'll use the defaults
        error_log('Polo page product data error: ' . $e->getMessage());
    }
}
?>

<main id="polo-main" class="polo-main-container">
    
    <!-- Hero Section -->
    <section class="polo-hero">
        <div class="polo-container">
            <div class="polo-hero-grid">
                
                <!-- Product Gallery -->
                <div class="polo-gallery">
                    <div class="polo-gallery-main">
                        <div class="polo-image-container">
                            <?php 
                            $image_src = $main_image[0];
                            if (empty($image_src) || strpos($image_src, 'polo-default.jpg') !== false) {
                                // Use a data URL placeholder if no real image
                                $image_src = 'data:image/svg+xml;base64,' . base64_encode('<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg" style="background:#F1C40F;"><rect width="100%" height="100%" fill="#F1C40F"/><text x="50%" y="50%" font-size="24" fill="#000" text-anchor="middle" dominant-baseline="middle">Premium Polo Shirt</text></svg>');
                            }
                            ?>
                            <img id="polo-main-image" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($product_name ?: 'Premium Polo Shirt'); ?>" class="polo-main-img">
                            <div class="polo-image-glow"></div>
                        </div>
                    </div>
                    
                    <!-- Gallery Thumbnails -->
                    <div class="polo-gallery-tabs">
                        <?php 
                        // Create gallery images array - use main image + gallery images
                        $all_images = array();
                        
                        // Add main image first
                        if (!empty($main_image[0]) && strpos($main_image[0], 'data:image') === false) {
                            $all_images[] = array(
                                'src' => $main_image[0], 
                                'label' => 'Front',
                                'view' => 'front'
                            );
                        }
                        
                        // Add gallery images
                        if (!empty($product_images)) {
                            $view_labels = array('Back', 'Side', 'Detail');
                            $view_names = array('back', 'side', 'detail');
                            $count = 0;
                            
                            foreach ($product_images as $image_id) {
                                if ($count >= 3) break; // Limit to 3 additional images
                                $thumb = wp_get_attachment_image_src($image_id, 'medium');
                                if ($thumb && !empty($thumb[0])) {
                                    $all_images[] = array(
                                        'src' => $thumb[0],
                                        'full' => wp_get_attachment_image_src($image_id, 'full')[0],
                                        'label' => $view_labels[$count],
                                        'view' => $view_names[$count]
                                    );
                                    $count++;
                                }
                            }
                        }
                        
                        // If no real images, create placeholder thumbnails
                        if (empty($all_images)) {
                            $placeholders = array(
                                array('label' => 'Front', 'view' => 'front'),
                                array('label' => 'Back', 'view' => 'back'), 
                                array('label' => 'Side', 'view' => 'side'),
                                array('label' => 'Detail', 'view' => 'detail')
                            );
                            
                            foreach ($placeholders as $placeholder) {
                                $all_images[] = array(
                                    'src' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="80" height="80" xmlns="http://www.w3.org/2000/svg" style="background:#333;"><rect width="100%" height="100%" fill="#333"/><text x="50%" y="45%" font-size="10" fill="#F1C40F" text-anchor="middle">' . $placeholder['label'] . '</text><text x="50%" y="65%" font-size="8" fill="#ccc" text-anchor="middle">View</text></svg>'),
                                    'label' => $placeholder['label'],
                                    'view' => $placeholder['view']
                                );
                            }
                        }
                        
                        // Display thumbnail buttons
                        foreach ($all_images as $index => $image):
                        ?>
                        <button class="polo-tab-btn polo-thumb-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-view="<?php echo esc_attr($image['view']); ?>"
                                data-full="<?php echo esc_url($image['full'] ?? $image['src']); ?>">
                            <img src="<?php echo esc_url($image['src']); ?>" alt="<?php echo esc_attr($image['label']); ?> view" class="polo-thumb-img">
                            <span class="polo-thumb-label"><?php echo esc_html($image['label']); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Product Details -->
                <div class="polo-details">
                    
                    <!-- Product Title -->
                    <h1 class="polo-title"><?php echo esc_html($product_name ?: 'Premium Polo Shirt'); ?></h1>
                    
                    <!-- Rating -->
                    <?php if ($review_count > 0): ?>
                    <div class="polo-rating">
                        <div class="polo-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="polo-star <?php echo $i <= $average_rating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="polo-review-count">(<?php echo $review_count; ?> reviews)</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Price -->
                    <div class="polo-price-section">
                        <?php if ($is_on_sale): ?>
                            <span class="polo-sale-price">රු <?php echo number_format($sale_price, 2); ?></span>
                            <span class="polo-regular-price">රු <?php echo number_format($regular_price, 2); ?></span>
                            <span class="polo-discount-badge">
                                <?php 
                                $discount_percent = $regular_price > 0 ? round((($regular_price - $sale_price) / $regular_price) * 100) : 0;
                                echo $discount_percent . '% OFF';
                                ?>
                            </span>
                        <?php else: ?>
                            <span class="polo-price">
                                <?php 
                                if (!empty($product_price)) {
                                    echo $product_price;
                                } else {
                                    echo '<em style="color: #F1C40F;">Price not set - please update product</em>';
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <div class="polo-description">
                        <?php 
                        if (!empty($product_description)) {
                            echo wpautop($product_description);
                        } else {
                            echo '<em style="color: #ccc;">No description set - please add product description</em>';
                        }
                        ?>
                    </div>
                    
                    <!-- Product Options -->
                    <div class="polo-options">
                        <!-- Size Selection -->
                        <div class="polo-size-section">
                            <label class="polo-option-label">Size</label>
                            <div class="polo-size-options">
                                <?php 
                                // Get sizes from product variations if it's a variable product
                                $sizes = array();
                                
                                if ($product_type === 'variable' && !empty($product_variations)) {
                                    // Extract sizes from variations
                                    $size_set = array();
                                    foreach ($product_variations as $variation) {
                                        if (isset($variation['attributes']['attribute_pa_size'])) {
                                            $size_set[] = $variation['attributes']['attribute_pa_size'];
                                        } elseif (isset($variation['attributes']['attribute_size'])) {
                                            $size_set[] = $variation['attributes']['attribute_size'];
                                        }
                                    }
                                    $sizes = array_unique($size_set);
                                    
                                    // Sort sizes in logical order
                                    $size_order = array('XS', 'S', 'M', 'L', 'XL', 'XXL');
                                    $ordered_sizes = array();
                                    foreach ($size_order as $order_size) {
                                        if (in_array($order_size, $sizes)) {
                                            $ordered_sizes[] = $order_size;
                                        }
                                    }
                                    // Add any remaining sizes not in standard order
                                    foreach ($sizes as $size) {
                                        if (!in_array($size, $ordered_sizes)) {
                                            $ordered_sizes[] = $size;
                                        }
                                    }
                                    $sizes = $ordered_sizes;
                                }
                                
                                // Fallback: Try to get sizes from product attributes
                                if (empty($sizes) && isset($attributes['pa_size']) && is_object($attributes['pa_size'])) {
                                    $size_terms = $attributes['pa_size']->get_terms();
                                    if (!empty($size_terms)) {
                                        $sizes = array();
                                        foreach ($size_terms as $term) {
                                            $sizes[] = $term->name;
                                        }
                                    }
                                }
                                
                                // Final fallback: use default sizes
                                if (empty($sizes)) {
                                    $sizes = array('XS', 'S', 'M', 'L', 'XL', 'XXL');
                                }
                                
                                foreach ($sizes as $index => $size):
                                ?>
                                <button class="polo-size-btn <?php echo ($size === 'M' || $index === 2) ? 'active' : ''; ?>" data-size="<?php echo esc_attr($size); ?>">
                                    <?php echo esc_html($size); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantity -->
                    <div class="polo-quantity-section">
                        <label class="polo-option-label">Quantity</label>
                        <div class="polo-quantity-controls">
                            <button class="polo-qty-btn polo-qty-minus">−</button>
                            <input type="number" class="polo-qty-input" value="1" min="1" max="<?php echo $stock_quantity ?: 999; ?>">
                            <button class="polo-qty-btn polo-qty-plus">+</button>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="polo-actions">
                        <button class="polo-add-to-cart-btn" data-product-id="<?php echo $product_id; ?>">
                            ADD TO CART
                        </button>
                        <button class="polo-buy-now-btn">
                            BUY NOW
                        </button>
                    </div>
                    
                    <!-- Floating Action Buttons -->
                    <div class="polo-floating-actions">
                        <button class="polo-float-btn polo-wishlist" title="Add to Wishlist">
                            <span>♡</span>
                        </button>
                        <button class="polo-float-btn polo-share" title="Share">
                            <span>⬆</span>
                        </button>
                        <button class="polo-float-btn polo-compare" title="Compare">
                            <span>⚖</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Assurance Bar -->
    <section class="polo-assurance">
        <div class="polo-container">
            <div class="polo-assurance-grid">
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">🚚</div>
                    <span>Free Shipping</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">↩</div>
                    <span>30-Day Returns</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">💎</div>
                    <span>Quality Guarantee</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">💳</div>
                    <span>Secure Payment</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Product Information Tabs -->
    <section class="polo-info-section">
        <div class="polo-container">
            <h2 class="polo-section-title">Product Information</h2>
            
            <div class="polo-tabs">
                <div class="polo-tab-nav">
                    <button class="polo-tab-nav-btn active" data-tab="description">Description</button>
                    <button class="polo-tab-nav-btn" data-tab="specifications">Specifications</button>
                    <button class="polo-tab-nav-btn" data-tab="size-guide">Size Guide</button>
                    <button class="polo-tab-nav-btn" data-tab="care">Care Instructions</button>
                </div>
                
                <div class="polo-tab-content">
                    <!-- Description Tab -->
                    <div class="polo-tab-pane active" id="description">
                        <h3>Premium Quality Design</h3>
                        <p>Our Premium Polo Shirt represents the perfect fusion of classic elegance and contemporary style. Meticulously crafted from the finest 100% cotton, this piece offers unparalleled comfort and durability. The modern slim-fit design ensures a flattering silhouette for all body types, while the breathable fabric keeps you comfortable throughout the day.</p>
                        <p>Whether you're heading to a business meeting, casual dinner, or weekend outing, this versatile polo adapts to any occasion. The signature embroidered logo adds a touch of sophistication, making it a must-have addition to your wardrobe.</p>
                        <?php 
                        /*
                        <p>
                            <?php 
                            if (!empty($product_full_description)) {
                                echo wpautop($product_full_description);
                            } else {
                                echo '<em style="color: #ccc;">No detailed description set - please add product content</em>';
                            }
                            ?>
                        </p>
                        <?php if (!empty($product_full_description)): ?>
                        <p>Whether you're heading to a business meeting, casual dinner, or weekend outing, this versatile polo adapts to any occasion. The signature embroidered logo adds a touch of sophistication, making it a must-have addition to your wardrobe.</p>
                        <?php endif; ?>*/?>
                    </div>
                    
                    <!-- Specifications Tab -->
                    <div class="polo-tab-pane" id="specifications">
                        <div class="polo-spec-grid">
                            <div class="polo-spec-item">
                                <h4>Material</h4>
                                <p>100% Premium Cotton</p>
                            </div>
                            <div class="polo-spec-item">
                                <h4>Fit</h4>
                                <p>Modern Slim Fit</p>
                            </div>
                            <div class="polo-spec-item">
                                <h4>Weight</h4>
                                <p>180 GSM</p>
                            </div>
                            <div class="polo-spec-item">
                                <h4>Collar Type</h4>
                                <p>Classic Polo Collar</p>
                            </div>
                            <div class="polo-spec-item">
                                <h4>Sleeve Length</h4>
                                <p>Short Sleeve</p>
                            </div>
                            <div class="polo-spec-item">
                                <h4>Origin</h4>
                                <p>Made in Italy</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Size Guide Tab -->
                    <div class="polo-tab-pane" id="size-guide">
                        <div class="polo-size-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Chest (inches)</th>
                                        <th>Length (inches)</th>
                                        <th>Shoulder (inches)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>XS</td><td>34-36</td><td>26</td><td>16</td></tr>
                                    <tr><td>S</td><td>36-38</td><td>27</td><td>17</td></tr>
                                    <tr><td>M</td><td>38-40</td><td>28</td><td>18</td></tr>
                                    <tr><td>L</td><td>40-42</td><td>29</td><td>19</td></tr>
                                    <tr><td>XL</td><td>42-44</td><td>30</td><td>20</td></tr>
                                    <tr><td>XXL</td><td>44-46</td><td>31</td><td>21</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Care Instructions Tab -->
                    <div class="polo-tab-pane" id="care">
                        <div class="polo-care-list">
                            <h3>Care Instructions</h3>
                            <ul>
                                <li><span class="care-icon">🧺</span> Machine wash cold with like colors</li>
                                <li><span class="care-icon">🚫</span> Do not bleach</li>
                                <li><span class="care-icon">🔥</span> Tumble dry low heat</li>
                                <li><span class="care-icon">🔥</span> Iron on medium heat if needed</li>
                                <li><span class="care-icon">💧</span> Professional dry cleaning recommended for best results</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Assurance Bar (Repeated) -->
    <section class="polo-assurance">
        <div class="polo-container">
            <div class="polo-assurance-grid">
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">🚚</div>
                    <span>Free Shipping</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">↩</div>
                    <span>30-Day Returns</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">💎</div>
                    <span>Quality Guarantee</span>
                </div>
                <div class="polo-assurance-item">
                    <div class="polo-assurance-icon">💳</div>
                    <span>Secure Payment</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Customer Reviews -->
    <?php if (!empty($reviews)): ?>
    <section class="polo-reviews">
        <div class="polo-container">
            <h2 class="polo-section-title">Customer Reviews</h2>
            
            <div class="polo-reviews-grid">
                <!-- Rating Summary -->
                <div class="polo-rating-summary">
                    <div class="polo-avg-rating">
                        <span class="polo-big-rating"><?php echo number_format($average_rating, 1); ?></span>
                        <div class="polo-rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="polo-star <?php echo $i <= $average_rating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <p>Based on <?php echo $review_count; ?> reviews</p>
                    </div>
                    
                    <div class="polo-rating-breakdown">
                        <?php
                        $rating_counts = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
                        foreach ($reviews as $review) {
                            $rating = get_comment_meta($review->comment_ID, 'rating', true);
                            if ($rating) {
                                $rating_counts[(int)$rating]++;
                            }
                        }
                        
                        for ($i = 5; $i >= 1; $i--):
                            $count = $rating_counts[$i];
                            $percentage = $review_count > 0 ? round(($count / $review_count) * 100) : 0;
                        ?>
                        <div class="polo-rating-bar">
                            <span><?php echo $i; ?>★</span>
                            <div class="polo-bar"><div class="polo-bar-fill" style="width: <?php echo $percentage; ?>%"></div></div>
                            <span><?php echo $percentage; ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Individual Reviews -->
                <div class="polo-reviews-list">
                    <?php foreach (array_slice($reviews, 0, 3) as $review): 
                        $rating = get_comment_meta($review->comment_ID, 'rating', true);
                    ?>
                    <div class="polo-review-item">
                        <div class="polo-review-header">
                            <h4><?php echo esc_html($review->comment_author); ?></h4>
                            <span class="polo-review-date"><?php echo human_time_diff(strtotime($review->comment_date)); ?> ago</span>
                        </div>
                        <?php if ($rating): ?>
                        <div class="polo-review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="polo-star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                        <p class="polo-review-text"><?php echo esc_html($review->comment_content); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Why Choose Our Polo -->
    <section class="polo-benefits">
        <div class="polo-container">
            <h2 class="polo-section-title">Why Choose Our Polo?</h2>
            
            <div class="polo-benefits-grid">
                <div class="polo-benefit-item">
                    <div class="polo-benefit-icon">🌱</div>
                    <h3>Sustainable Materials</h3>
                    <p>Made from 100% organic cotton sourced from certified sustainable farms. Eco-friendly production process.</p>
                </div>
                
                <div class="polo-benefit-item">
                    <div class="polo-benefit-icon">✂️</div>
                    <h3>Expert Craftsmanship</h3>
                    <p>Each polo is carefully crafted by skilled artisans with attention to every detail and finish.</p>
                </div>
                
                <div class="polo-benefit-item">
                    <div class="polo-benefit-icon">💎</div>
                    <h3>Premium Quality</h3>
                    <p>Superior cotton blend that maintains shape, color, and comfort wash after wash.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section class="polo-faq">
        <div class="polo-container">
            <h2 class="polo-section-title">Frequently Asked Questions</h2>
            
            <div class="polo-faq-list">
                <div class="polo-faq-item">
                    <button class="polo-faq-question">
                        What sizes are available? <span class="polo-faq-toggle">▼</span>
                    </button>
                    <div class="polo-faq-answer">
                        <p>We offer sizes from XS to XXL. Please refer to our size guide above for detailed measurements to ensure the perfect fit.</p>
                    </div>
                </div>
                
                <div class="polo-faq-item">
                    <button class="polo-faq-question">
                        How should I care for my polo? <span class="polo-faq-toggle">▼</span>
                    </button>
                    <div class="polo-faq-answer">
                        <p>Machine wash cold with like colors, tumble dry on low heat, and iron on medium if needed. For best results, we recommend professional dry cleaning.</p>
                    </div>
                </div>
                
                <div class="polo-faq-item">
                    <button class="polo-faq-question">
                        What's your return policy? <span class="polo-faq-toggle">▼</span>
                    </button>
                    <div class="polo-faq-answer">
                        <p>We offer a 30-day return policy for unworn items in original condition with tags attached. Returns are free and easy through our online portal.</p>
                    </div>
                </div>
                
                <div class="polo-faq-item">
                    <button class="polo-faq-question">
                        Do you offer international shipping? <span class="polo-faq-toggle">▼</span>
                    </button>
                    <div class="polo-faq-answer">
                        <p>Yes, we ship worldwide! International shipping is free on orders over $100. Delivery times vary by location, typically 5-14 business days.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
</main>

<?php get_footer(); ?>
<?php get_footer(); ?>