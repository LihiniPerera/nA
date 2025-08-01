/**
 * Polo Product Page Interactive JavaScript
 * Handles all user interactions and dynamic content
 */

(function($) {
    'use strict';

    // Main object to handle all polo page interactions
    const PoloInteractions = {
        
        // Initialize all components
        init: function() {
            this.galleryImageSwitching();
            this.sizeSelection();
            this.colorSelection();
            this.quantityControls();
            this.productTabs();
            this.faqAccordion();
            this.addToCartHandler();
            this.buyNowHandler();
            this.floatingActions();
            this.mobileOptimizations();
            
            console.log('Polo page interactions initialized');
        },

        // Gallery image switching functionality
        galleryImageSwitching: function() {
            const $galleryTabs = $('.polo-tab-btn');
            const $mainImage = $('#polo-main-image');
            const $thumbnails = $('.polo-thumb');
            
            // Store original image for fallback
            const originalSrc = $mainImage.attr('src');
            
            // Handle gallery tab clicks
            $galleryTabs.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const view = $this.data('view');
                
                // Update active state
                $galleryTabs.removeClass('active');
                $this.addClass('active');
                
                // Switch image based on view
                PoloInteractions.switchGalleryImage(view, $mainImage, $thumbnails, originalSrc);
                
                // Add loading state
                $mainImage.addClass('polo-loading');
                setTimeout(() => {
                    $mainImage.removeClass('polo-loading');
                }, 300);
            });

            // Handle thumbnail clicks if thumbnails exist
            $thumbnails.on('click', function() {
                const newSrc = $(this).data('full');
                if (newSrc) {
                    $mainImage.attr('src', newSrc);
                }
            });
        },

        // Switch gallery image based on view
        switchGalleryImage: function(view, $mainImage, $thumbnails, originalSrc) {
            // First try to find by data-view attribute on the active button
            const $activeBtn = $('.polo-tab-btn.active[data-view="' + view + '"]');
            if ($activeBtn.length && $activeBtn.data('full')) {
                $mainImage.attr('src', $activeBtn.data('full'));
                return;
            }
            
            // Fallback: try thumbnails by index
            const imageMap = {
                'front': 0,
                'back': 1,
                'side': 2,
                'detail': 3
            };
            
            const index = imageMap[view];
            
            if ($thumbnails.length > index) {
                const newSrc = $thumbnails.eq(index).data('full');
                if (newSrc) {
                    $mainImage.attr('src', newSrc);
                    return;
                }
            }
            
            // Final fallback to original image
            $mainImage.attr('src', originalSrc);
        },

        // Size selection functionality
        sizeSelection: function() {
            const $sizeButtons = $('.polo-size-btn');
            
            $sizeButtons.on('click', function(e) {
                    e.preventDefault();
                
                const $this = $(this);
                const size = $this.data('size');
                
                // Update active state
                $sizeButtons.removeClass('active');
                $this.addClass('active');
                
                // Store selected size for cart functionality
                PoloInteractions.selectedSize = size;
                
                // Update price based on variation
                PoloInteractions.updatePriceForVariation(size);
                
                // Trigger custom event
                $(document).trigger('polo:sizeSelected', [size]);
                
                console.log('Selected size:', size);
            });
        },

        // Color selection functionality
        colorSelection: function() {
            const $colorOptions = $('.polo-color-option');
            
            $colorOptions.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const color = $this.data('color');
                
                // Update active state
                $colorOptions.removeClass('active');
                $this.addClass('active');
                
                // Store selected color for cart functionality
                PoloInteractions.selectedColor = color;
                
                // Trigger custom event
                $(document).trigger('polo:colorSelected', [color]);
                
                console.log('Selected color:', color);
            });
        },

        // Quantity controls functionality
        quantityControls: function() {
            const $qtyInput = $('.polo-qty-input');
            const $plusBtn = $('.polo-qty-plus');
            const $minusBtn = $('.polo-qty-minus');
            
            // Plus button
            $plusBtn.on('click', function(e) {
                e.preventDefault();
                
                let currentVal = parseInt($qtyInput.val()) || 1;
                const maxVal = parseInt($qtyInput.attr('max')) || 999;
                
                if (currentVal < maxVal) {
                    $qtyInput.val(currentVal + 1);
                    PoloInteractions.updateQuantity(currentVal + 1);
                }
            });
            
            // Minus button
            $minusBtn.on('click', function(e) {
                e.preventDefault();
                
                let currentVal = parseInt($qtyInput.val()) || 1;
                const minVal = parseInt($qtyInput.attr('min')) || 1;
                
                if (currentVal > minVal) {
                    $qtyInput.val(currentVal - 1);
                    PoloInteractions.updateQuantity(currentVal - 1);
                }
            });
            
            // Direct input change
            $qtyInput.on('change', function() {
                const val = parseInt($(this).val()) || 1;
                const minVal = parseInt($(this).attr('min')) || 1;
                const maxVal = parseInt($(this).attr('max')) || 999;
                
                let newVal = Math.max(minVal, Math.min(maxVal, val));
                $(this).val(newVal);
                PoloInteractions.updateQuantity(newVal);
            });
        },

        // Update quantity and trigger events
        updateQuantity: function(quantity) {
            PoloInteractions.selectedQuantity = quantity;
            $(document).trigger('polo:quantityChanged', [quantity]);
            console.log('Quantity updated:', quantity);
        },

        // Product information tabs functionality
        productTabs: function() {
            const $tabNavBtns = $('.polo-tab-nav-btn');
            const $tabPanes = $('.polo-tab-pane');
            
            $tabNavBtns.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const targetTab = $this.data('tab');
                
                // Update navigation active state
                $tabNavBtns.removeClass('active');
                $this.addClass('active');
                
                // Update content active state
                $tabPanes.removeClass('active');
                $(`#${targetTab}`).addClass('active');
                
                // Add fade in animation
                $(`#${targetTab}`).addClass('polo-fade-in');
                
                console.log('Switched to tab:', targetTab);
            });
        },

        // FAQ accordion functionality
        faqAccordion: function() {
            const $faqQuestions = $('.polo-faq-question');
            
            $faqQuestions.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const $faqItem = $this.closest('.polo-faq-item');
                const $answer = $faqItem.find('.polo-faq-answer');
                const isActive = $faqItem.hasClass('active');
                
                // Close all other FAQ items
                $('.polo-faq-item').removeClass('active');
                
                // Toggle current item
                if (!isActive) {
                    $faqItem.addClass('active');
                    
                    // Smooth scroll to answer if needed
                    setTimeout(() => {
                    if (window.innerWidth <= 768) {
                            $('html, body').animate({
                                scrollTop: $faqItem.offset().top - 100
                            }, 300);
                        }
                    }, 100);
                }
                
                console.log('FAQ toggled:', $this.text().trim());
            });
        },

        // Add to cart functionality
        addToCartHandler: function() {
            const $addToCartBtn = $('.polo-add-to-cart-btn');
            
            $addToCartBtn.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const productId = $this.data('product-id');
                
                // Get selected options
                const quantity = PoloInteractions.selectedQuantity || 1;
                const size = PoloInteractions.selectedSize || 'M';
                const variationId = PoloInteractions.selectedVariationId || 0;
                
                // Add loading state
                $this.addClass('polo-loading').text('ADDING...');
                
                // Prepare data for WooCommerce - only include color if product has color variations
                const cartData = {
                    action: 'polo_add_to_cart',
                    product_id: productId,
                    variation_id: variationId,
                    quantity: quantity,
                    size: size,
                    nonce: polo_ajax.nonce
                };
                
                // Only add color if the product actually has color variations
                if (PoloInteractions.selectedColor) {
                    cartData.color = PoloInteractions.selectedColor;
                }
                
                // AJAX request to add to cart
                $.ajax({
                    url: polo_ajax.ajax_url,
                    type: 'POST',
                    data: cartData,
                    success: function(response) {
                        console.log('Add to cart response:', response);
                        
                        if (response.success && response.data) {
                            // Show success message from server
                            PoloInteractions.showNotification(response.data.message || 'Product added to cart!', 'success');
                            
                            // Update cart count immediately with response data
                            PoloInteractions.updateCartCountFromResponse(response.data);
                            
                            // Update cart fragments if they exist
                            if (response.data.fragments) {
                                PoloInteractions.updateCartFragments(response.data.fragments);
                            }
                            
                            // Trigger WooCommerce cart updated events
                            $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash, $this]);
                            $(document.body).trigger('wc_fragment_refresh');
                            
                            // Trigger custom polo event
                            $(document).trigger('polo:addToCartSuccess', [response.data]);
                            
                        } else {
                            // Handle error response - response.data can be string or object
                            let errorMessage = 'Failed to add to cart';
                            if (response.data) {
                                if (typeof response.data === 'string') {
                                    errorMessage = response.data;
                                } else if (response.data.message) {
                                    errorMessage = response.data.message;
                                }
                            }
                            PoloInteractions.showNotification(errorMessage, 'error');
                            console.error('Add to cart failed:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        PoloInteractions.showNotification('Network error - Failed to add to cart', 'error');
                        console.error('AJAX error:', xhr.responseText, status, error);
                    },
                    complete: function() {
                        // Remove loading state
                        $this.removeClass('polo-loading').text('ADD TO CART');
                    }
                });
                
                console.log('Add to cart clicked', cartData);
            });
        },

        // Buy now functionality
        buyNowHandler: function() {
            const $buyNowBtn = $('.polo-buy-now-btn');
            
            $buyNowBtn.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                
                // Add loading state
                $this.addClass('polo-loading').text('PROCESSING...');
                
                // First add to cart, then redirect to checkout
                $('.polo-add-to-cart-btn').trigger('click');
                
                // Listen for successful add to cart
                $(document).one('polo:addToCartSuccess', function() {
                    // Redirect to checkout
                    window.location.href = polo_ajax.checkout_url || '/checkout/';
                });
                
                // Remove loading state after timeout
                setTimeout(() => {
                    $this.removeClass('polo-loading').text('BUY NOW');
                }, 3000);
                
                console.log('Buy now clicked');
            });
        },

        // Floating action buttons
        floatingActions: function() {
            const $wishlistBtn = $('.polo-wishlist');
            const $shareBtn = $('.polo-share');
            const $compareBtn = $('.polo-compare');
            
            // Wishlist functionality
            $wishlistBtn.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const isActive = $this.hasClass('active');
                
                $this.toggleClass('active');
                
                if (!isActive) {
                    $this.find('span').text('♥');
                    PoloInteractions.showNotification('Added to wishlist!', 'success');
                } else {
                    $this.find('span').text('♡');
                    PoloInteractions.showNotification('Removed from wishlist', 'info');
                }
                
                console.log('Wishlist toggled:', !isActive);
            });
            
            // Share functionality
            $shareBtn.on('click', function(e) {
                e.preventDefault();
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Classic Polo',
                        text: 'Check out this amazing polo shirt!',
                        url: window.location.href
                    });
                } else {
                    // Fallback - copy to clipboard
                    PoloInteractions.copyToClipboard(window.location.href);
                    PoloInteractions.showNotification('Link copied to clipboard!', 'success');
                }
                
                console.log('Share clicked');
            });
            
            // Compare functionality
            $compareBtn.on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                $this.toggleClass('active');
                
                if ($this.hasClass('active')) {
                    PoloInteractions.showNotification('Added to compare list!', 'success');
                } else {
                    PoloInteractions.showNotification('Removed from compare list', 'info');
                }
                
                console.log('Compare toggled');
            });
        },

        // Mobile-specific optimizations
        mobileOptimizations: function() {
            // Sticky add to cart on mobile
            if (window.innerWidth <= 768) {
                $(window).on('scroll', function() {
                    const scrollTop = $(window).scrollTop();
                    const $actions = $('.polo-actions');
                    
                    if (scrollTop > 500) {
                        $actions.addClass('polo-sticky-mobile');
                    } else {
                        $actions.removeClass('polo-sticky-mobile');
                    }
                });
            }
            
            // Touch gestures for gallery
            let startX = null;
            const $gallery = $('.polo-gallery-main');
            
            $gallery.on('touchstart', function(e) {
                startX = e.touches[0].clientX;
            });
            
            $gallery.on('touchend', function(e) {
                if (!startX) return;
                
                const endX = e.changedTouches[0].clientX;
                const diff = startX - endX;
                
                if (Math.abs(diff) > 50) {
                    const $activTab = $('.polo-tab-btn.active');
                    let $nextTab;
                    
                    if (diff > 0) {
                        // Swipe left - next image
                        $nextTab = $activTab.next('.polo-tab-btn');
                    } else {
                        // Swipe right - previous image
                        $nextTab = $activTab.prev('.polo-tab-btn');
                    }
                    
                    if ($nextTab.length) {
                        $nextTab.trigger('click');
                    }
                }
                
                startX = null;
            });
        },

        // Utility functions
        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.polo-notification').remove();
            
            // Create notification element
            const $notification = $(`
                <div class="polo-notification polo-notification-${type}">
                    <span>${message}</span>
                    <button class="polo-notification-close">&times;</button>
                </div>
            `);
            
            // Add to page
            $('body').append($notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.find('.polo-notification-close').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Trigger custom event
            $(document).trigger('polo:notification', [message, type]);
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        },

        updateCartCount: function() {
            // Update cart count in header if element exists
            const $cartCount = $('.cart-count, .kadence-cart-count');
            if ($cartCount.length) {
                $.ajax({
                    url: polo_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'polo_get_cart_count',
                        nonce: polo_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $cartCount.text(response.data.count);
                        }
                    }
                });
            }
        },

        // Update cart count immediately from add to cart response
        updateCartCountFromResponse: function(responseData) {
            if (responseData.cart_count !== undefined) {
                // Update common cart count selectors
                const $cartCounts = $('.cart-count, .kadence-cart-count, .cart-contents-count, span.count');
                $cartCounts.text(responseData.cart_count);
                
                // Update cart count badge if it exists
                const $cartBadge = $('.cart-badge, .wc-cart-count');
                if ($cartBadge.length) {
                    $cartBadge.text(responseData.cart_count);
                    // Show badge if cart has items
                    if (responseData.cart_count > 0) {
                        $cartBadge.show();
                    }
                }
                
                console.log('Cart count updated to:', responseData.cart_count);
            }
        },

        // Update cart fragments from WooCommerce
        updateCartFragments: function(fragments) {
            if (fragments && typeof fragments === 'object') {
                $.each(fragments, function(selector, content) {
                    $(selector).replaceWith(content);
                });
                console.log('Cart fragments updated:', Object.keys(fragments));
            }
        },

        // Update price display based on selected variation
        updatePriceForVariation: function(size) {
            // Check if variation data exists and if this is a variable product
            if (!polo_ajax.is_variable || !polo_ajax.variations || !polo_ajax.variations[size]) {
                return;
            }
            
            const variation = polo_ajax.variations[size];
            const $priceSection = $('.polo-price-section');
            
            if (variation.is_on_sale && variation.sale_price) {
                // Show sale price
                const salePrice = parseFloat(variation.sale_price);
                const regularPrice = parseFloat(variation.regular_price);
                const discountPercent = Math.round(((regularPrice - salePrice) / regularPrice) * 100);
                
                $priceSection.html(`
                    <span class="polo-sale-price">රු ${salePrice.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    <span class="polo-regular-price">රු ${regularPrice.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    <span class="polo-discount-badge">${discountPercent}% OFF</span>
                `);
            } else {
                // Show regular price
                const price = parseFloat(variation.price);
                $priceSection.html(`
                    <span class="polo-price">රු ${price.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                `);
            }
            
            // Store current variation ID for cart functionality
            PoloInteractions.selectedVariationId = variation.variation_id;
            
            console.log('Price updated for size:', size, 'Variation ID:', variation.variation_id);
        },

        // Initialize default selections
        initializeDefaults: function() {
            PoloInteractions.selectedSize = 'M';
            PoloInteractions.selectedColor = null; // Don't default to gold
            PoloInteractions.selectedQuantity = 1;
            PoloInteractions.selectedVariationId = null;
            
            // Set initial price if this is a variable product
            if (polo_ajax.is_variable && polo_ajax.variations) {
                // Find the default size (M) or use the first available size
                let defaultSize = 'M';
                if (!polo_ajax.variations[defaultSize]) {
                    defaultSize = Object.keys(polo_ajax.variations)[0];
                }
                
                if (defaultSize && polo_ajax.variations[defaultSize]) {
                    PoloInteractions.updatePriceForVariation(defaultSize);
                    PoloInteractions.selectedSize = defaultSize;
                }
            }
        }
    };

    // WordPress/WooCommerce AJAX handlers
    const PoloAjax = {
        init: function() {
            // Add to cart AJAX handler (server-side)
            this.addToCartHandler();
            this.getCartCountHandler();
        },

        addToCartHandler: function() {
            // This would be handled in PHP via wp_ajax_polo_add_to_cart
            // See the PHP implementation needed in functions.php
        },

        getCartCountHandler: function() {
            // This would be handled in PHP via wp_ajax_polo_get_cart_count
            // See the PHP implementation needed in functions.php
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        PoloInteractions.initializeDefaults();
        PoloInteractions.init();
        PoloAjax.init();
    });

    // Initialize after WooCommerce scripts load
    $(window).on('load', function() {
        // Reinitialize if WooCommerce scripts have loaded
        if (typeof wc_add_to_cart_params !== 'undefined') {
            console.log('WooCommerce detected, optimizing interactions');
        }
    });

    // Handle window resize for responsive optimizations
    $(window).on('resize', function() {
        // Debounce resize events
        clearTimeout(PoloInteractions.resizeTimer);
        PoloInteractions.resizeTimer = setTimeout(function() {
            // Reinitialize mobile optimizations
            PoloInteractions.mobileOptimizations();
        }, 250);
    });

    // Global polo object for external access
    window.PoloPage = PoloInteractions;

})(jQuery);

// Notification styles (added via JavaScript since they're dynamic)
const notificationStyles = `
<style>
.polo-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--polo-bg-card);
    border: 1px solid var(--polo-border);
    border-radius: var(--polo-border-radius-small);
    padding: 1rem 1.5rem;
    color: var(--polo-text-primary);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 300px;
    box-shadow: var(--polo-shadow);
    animation: slideInRight 0.3s ease;
}

.polo-notification-success {
    border-left: 4px solid var(--polo-primary);
}

.polo-notification-error {
    border-left: 4px solid #e74c3c;
}

.polo-notification-info {
    border-left: 4px solid #3498db;
}

.polo-notification-close {
    background: none;
    border: none;
    color: var(--polo-text-secondary);
    font-size: 1.5rem;
    cursor: pointer;
    margin-left: auto;
    transition: var(--polo-transition);
}

.polo-notification-close:hover {
    color: var(--polo-primary);
}

.polo-sticky-mobile {
    position: fixed !important;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--polo-bg-card);
    border-top: 1px solid var(--polo-border);
    padding: 1rem;
    z-index: 1000;
    flex-direction: column;
    gap: 0.5rem;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .polo-notification {
        right: 10px;
        left: 10px;
        min-width: auto;
    }
}
</style>
`;

// Add notification styles to head
document.head.insertAdjacentHTML('beforeend', notificationStyles);