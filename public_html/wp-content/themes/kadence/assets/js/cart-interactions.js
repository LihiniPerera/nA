/**
 * Custom Cart Page Interactive JavaScript
 * Handles all user interactions and AJAX functionality
 * Follows the same pattern as polo-interactions.js
 */

(function($) {
    'use strict';

    // Main object to handle all cart page interactions
    const CartInteractions = {
        
        // Configuration
        config: {
            debounceDelay: 500,
            animationDuration: 300,
            maxRetries: 3,
            loadingClass: 'cart-loading',
            errorClass: 'cart-error-message',
            successClass: 'cart-success-message'
        },
        
        // State management
        state: {
            isProcessing: false,
            pendingUpdates: new Map(),
            retryCount: new Map()
        },
        
        // Initialize all components
        init: function() {
            this.validateCartStructure();
            this.quantityControls();
            this.removeItems();
            this.couponHandling();
            this.mobileOptimizations();
            this.initializeLoading();
            this.setupEventListeners();
            
            console.log('Cart interactions initialized');
        },
        
        // Validate cart structure and fix missing data attributes
        validateCartStructure: function() {
            $('.cart-item-card').each(function() {
                const $card = $(this);
                const $qtyInput = $card.find('.cart-qty-input');
                const $qtyButtons = $card.find('.cart-qty-btn');
                
                const cardKey = $card.data('cart-item-key');
                const inputKey = $qtyInput.data('cart-item-key');
                
                // Ensure all elements have the cart item key
                if (cardKey && !inputKey) {
                    $qtyInput.attr('data-cart-item-key', cardKey);
                    console.log('Fixed missing cart-item-key on input:', cardKey);
                }
                
                if (cardKey) {
                    $qtyButtons.each(function() {
                        if (!$(this).data('cart-item-key')) {
                            $(this).attr('data-cart-item-key', cardKey);
                            console.log('Fixed missing cart-item-key on button:', cardKey);
                        }
                    });
                }
                
                // Store original quantity for revert functionality
                const currentQty = $qtyInput.val();
                if (currentQty && !$qtyInput.data('original-qty')) {
                    $qtyInput.attr('data-original-qty', currentQty);
                }
            });
        },

        // Quantity control functionality
        quantityControls: function() {
            const self = this;
            
            // Handle +/- button clicks
            $(document).on('click', '.cart-qty-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const $qtyInput = $btn.siblings('.cart-qty-input');
                const cartItemKey = $btn.data('cart-item-key') || $qtyInput.data('cart-item-key');
                const isPlus = $btn.hasClass('cart-qty-plus');
                const isMinus = $btn.hasClass('cart-qty-minus');
                
                console.log('Button clicked:', {
                    isPlus: isPlus,
                    isMinus: isMinus,
                    cartItemKey: cartItemKey,
                    inputFound: $qtyInput.length > 0
                });
                
                if (!$qtyInput.length || !cartItemKey) {
                    console.error('Missing quantity input or cart item key');
                    return;
                }
                
                let currentQty = parseInt($qtyInput.val()) || 1;
                const minQty = parseInt($qtyInput.attr('min')) || 1;
                const maxQty = parseInt($qtyInput.attr('max')) || 9999;
                let newQty = currentQty;
                
                if (isPlus) {
                    newQty = Math.min(currentQty + 1, maxQty);
                    console.log('Plus clicked, new quantity:', newQty);
                } else if (isMinus) {
                    newQty = Math.max(currentQty - 1, minQty);
                    console.log('Minus clicked, new quantity:', newQty);
                }
                
                // Validate quantity bounds
                if (newQty < minQty) {
                    self.showNotification(`Minimum quantity is ${minQty}`, 'error');
                    $qtyInput.val(minQty);
                    return;
                }
                
                if (newQty > maxQty) {
                    self.showNotification(`Maximum quantity is ${maxQty}`, 'error');
                    $qtyInput.val(maxQty);
                    return;
                }
                
                if (newQty !== currentQty) {
                    $qtyInput.val(newQty);
                    console.log('Updating cart item:', cartItemKey, 'to quantity:', newQty);
                    self.updateCartItem(cartItemKey, newQty, $qtyInput);
                }
            });
            
            // Handle direct input changes
            $(document).on('change', '.cart-qty-input', function() {
                const $input = $(this);
                const cartItemKey = $input.data('cart-item-key') || $input.closest('.cart-item-card').data('cart-item-key');
                const inputValue = $input.val().trim();
                const newQty = parseInt(inputValue);
                const minQty = parseInt($input.attr('min')) || 1;
                const maxQty = parseInt($input.attr('max')) || 9999;
                const originalQty = parseInt($input.data('original-qty')) || 1;
                
                console.log('Input change detected:', {
                    inputValue: inputValue,
                    newQty: newQty,
                    minQty: minQty,
                    maxQty: maxQty,
                    cartItemKey: cartItemKey
                });
                
                // Validate input
                if (!inputValue || isNaN(newQty) || newQty < minQty) {
                    self.showNotification(`Please enter a valid quantity (minimum ${minQty})`, 'error');
                    $input.val(originalQty);
                    return;
                }
                
                if (newQty > maxQty) {
                    self.showNotification(`Maximum quantity is ${maxQty}`, 'error');
                    $input.val(maxQty);
                    if (cartItemKey) {
                        self.updateCartItem(cartItemKey, maxQty, $input);
                    }
                    return;
                }
                
                // Update cart if quantity is valid and different
                if (cartItemKey && newQty !== originalQty) {
                    $input.data('original-qty', newQty);
                    self.updateCartItem(cartItemKey, newQty, $input);
                } else if (!cartItemKey) {
                    console.error('No cart item key found for input');
                }
            });
            
            // Handle keyboard input with debouncing (disabled to prevent conflicts)
            // let inputTimeout;
            // $(document).on('input', '.cart-qty-input', function() {
            //     const $input = $(this);
            //     clearTimeout(inputTimeout);
            //     
            //     inputTimeout = setTimeout(() => {
            //         $input.trigger('change');
            //     }, self.config.debounceDelay);
            // });
            
            // Add blur event to handle when user clicks away from input
            $(document).on('blur', '.cart-qty-input', function() {
                console.log('Input blur detected, triggering change event');
                $(this).trigger('change');
            });
        },

        // Update cart item via AJAX
        updateCartItem: function(cartItemKey, quantity, $element) {
            const self = this;
            
            // Prevent multiple simultaneous updates for the same item
            if (self.state.pendingUpdates.has(cartItemKey)) {
                return;
            }
            
            self.state.pendingUpdates.set(cartItemKey, true);
            
            const $cartItem = $element.closest('.cart-item-card');
            const $qtyControls = $cartItem.find('.cart-quantity-controls');
            
            // Add loading state
            $qtyControls.addClass(self.config.loadingClass);
            
            // If quantity is 0, remove the item
            if (quantity === 0) {
                self.removeCartItem(cartItemKey, $cartItem);
                return;
            }
            
            // Prepare AJAX data
            const updateData = {
                action: 'cart_update_quantity',
                cart_item_key: cartItemKey,
                quantity: quantity,
                nonce: cart_ajax.nonce
            };
            
            $.ajax({
                url: cart_ajax.ajax_url,
                type: 'POST',
                data: updateData,
                success: function(response) {
                    if (response.success) {
                        // Update item subtotal
                        if (response.data.item_subtotal) {
                            $cartItem.find('.cart-subtotal-value').html(response.data.item_subtotal);
                        }
                        
                        // Update cart fragments (order summary)
                        if (response.data.fragments) {
                            self.updateCartFragments(response.data.fragments);
                        }
                        
                        // Update cart totals if cart is empty
                        if (response.data.cart_empty) {
                            self.handleEmptyCart();
                        }
                        
                        // Show success feedback with custom message
                        const message = response.data.message || 'Cart updated successfully';
                        self.showNotification(message, 'success');
                        
                        // Trigger WooCommerce events
                        $(document.body).trigger('updated_cart_totals');
                        $(document.body).trigger('wc_fragment_refresh');
                        
                    } else {
                        // Handle error
                        const errorMessage = response.data || 'Failed to update cart';
                        console.error('Cart update failed:', response);
                        self.showNotification(errorMessage, 'error');
                        
                        // Revert quantity
                        self.revertQuantity($element, cartItemKey);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cart update error:', xhr.responseText, status, error);
                    self.showNotification('Network error - Failed to update cart', 'error');
                    
                    // Retry logic
                    const retryCount = self.state.retryCount.get(cartItemKey) || 0;
                    if (retryCount < self.config.maxRetries) {
                        self.state.retryCount.set(cartItemKey, retryCount + 1);
                        setTimeout(() => {
                            self.updateCartItem(cartItemKey, quantity, $element);
                        }, 1000 * (retryCount + 1));
                    } else {
                        self.revertQuantity($element, cartItemKey);
                    }
                },
                complete: function() {
                    // Remove loading state
                    $qtyControls.removeClass(self.config.loadingClass);
                    self.state.pendingUpdates.delete(cartItemKey);
                    self.state.retryCount.delete(cartItemKey);
                }
            });
        },

        // Remove item functionality
        removeItems: function() {
            const self = this;
            
            $(document).on('click', '.cart-remove-btn', function(e) {
                e.preventDefault();
                
                const $removeBtn = $(this);
                const cartItemKey = $removeBtn.data('cart_item_key');
                const $cartItem = $removeBtn.closest('.cart-item-card');
                const productName = $cartItem.find('.cart-item-name').text().trim();
                
                // Confirmation dialog
                if (!confirm(`Are you sure you want to remove "${productName}" from your cart?`)) {
                    return;
                }
                
                self.removeCartItem(cartItemKey, $cartItem);
            });
        },

        // Remove cart item via AJAX
        removeCartItem: function(cartItemKey, $cartItem) {
            const self = this;
            
            // Add loading state
            $cartItem.addClass(self.config.loadingClass);
            
            // Use WooCommerce's remove URL for fallback
            const removeUrl = $cartItem.find('.cart-remove-btn').attr('href');
            
            const removeData = {
                action: 'cart_remove_item',
                cart_item_key: cartItemKey,
                nonce: cart_ajax.nonce
            };
            
            $.ajax({
                url: cart_ajax.ajax_url,
                type: 'POST',
                data: removeData,
                success: function(response) {
                    if (response.success) {
                        // Animate item removal
                        $cartItem.fadeOut(self.config.animationDuration, function() {
                            $(this).remove();
                        });
                        
                        // Update cart fragments
                        if (response.data.fragments) {
                            self.updateCartFragments(response.data.fragments);
                        }
                        
                        // Handle empty cart
                        if (response.data.cart_empty) {
                            setTimeout(() => {
                                self.handleEmptyCart();
                            }, self.config.animationDuration);
                        }
                        
                        self.showNotification('Item removed from cart', 'success');
                        
                        // Trigger WooCommerce events
                        $(document.body).trigger('removed_from_cart', [response.data.fragments, response.data.cart_hash]);
                        $(document.body).trigger('wc_fragment_refresh');
                        
                    } else {
                        self.showNotification('Failed to remove item', 'error');
                        $cartItem.removeClass(self.config.loadingClass);
                    }
                },
                error: function() {
                    // Fallback to page redirect
                    window.location.href = removeUrl;
                }
            });
        },

        // Coupon handling
        couponHandling: function() {
            const self = this;
            
            $(document).on('submit', '.woocommerce-cart-form', function(e) {
                const $form = $(this);
                const $couponBtn = $form.find('.cart-coupon-btn');
                const $couponInput = $form.find('.cart-coupon-input');
                
                // Only handle coupon submission
                if (!$couponBtn.is(':focus') && !e.originalEvent?.submitter?.classList.contains('cart-coupon-btn')) {
                    return; // Let normal form submission proceed
                }
                
                e.preventDefault();
                
                const couponCode = $couponInput.val().trim();
                if (!couponCode) {
                    self.showNotification('Please enter a coupon code', 'error');
                    return;
                }
                
                self.applyCoupon(couponCode, $couponBtn, $couponInput);
            });
        },

        // Apply coupon via AJAX
        applyCoupon: function(couponCode, $button, $input) {
            const self = this;
            
            // Add loading state
            $button.addClass(self.config.loadingClass).text('Applying...');
            $input.prop('disabled', true);
            
            const couponData = {
                action: 'cart_apply_coupon',
                coupon_code: couponCode,
                nonce: cart_ajax.nonce
            };
            
            $.ajax({
                url: cart_ajax.ajax_url,
                type: 'POST',
                data: couponData,
                success: function(response) {
                    if (response.success) {
                        // Update cart fragments (order summary)
                        if (response.data.fragments) {
                            self.updateCartFragments(response.data.fragments);
                        }
                        
                        // Clear coupon input
                        $input.val('');
                        
                        // Show success message from server
                        const message = response.data.message || 'Coupon applied successfully!';
                        self.showNotification(message, 'success');
                        
                        // Trigger events
                        $(document.body).trigger('applied_coupon_in_checkout', [couponCode]);
                        $(document.body).trigger('updated_cart_totals');
                        $(document.body).trigger('wc_fragment_refresh');
                        
                    } else {
                        const errorMessage = response.data || 'Invalid coupon code';
                        self.showNotification(errorMessage, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Failed to apply coupon', 'error');
                },
                complete: function() {
                    // Remove loading state
                    $button.removeClass(self.config.loadingClass).text('Apply Code');
                    $input.prop('disabled', false);
                }
            });
        },

        // Mobile optimizations
        mobileOptimizations: function() {
            const self = this;
            
            // Touch-friendly quantity controls
            if ('ontouchstart' in window) {
                $('.cart-qty-btn').on('touchstart', function() {
                    $(this).addClass('cart-btn-active');
                }).on('touchend', function() {
                    const $btn = $(this);
                    setTimeout(() => {
                        $btn.removeClass('cart-btn-active');
                    }, 150);
                });
            }
            
            // Swipe to remove (optional)
            if (cart_ajax.enable_swipe_remove) {
                let startX, startY, currentX, currentY;
                
                $(document).on('touchstart', '.cart-item-card', function(e) {
                    const touch = e.originalEvent.touches[0];
                    startX = touch.clientX;
                    startY = touch.clientY;
                });
                
                $(document).on('touchmove', '.cart-item-card', function(e) {
                    if (!startX || !startY) return;
                    
                    const touch = e.originalEvent.touches[0];
                    currentX = touch.clientX;
                    currentY = touch.clientY;
                    
                    const diffX = startX - currentX;
                    const diffY = startY - currentY;
                    
                    // Detect horizontal swipe
                    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                        const $item = $(this);
                        
                        if (diffX > 0) { // Swipe left
                            $item.addClass('cart-swipe-left');
                        } else { // Swipe right
                            $item.removeClass('cart-swipe-left');
                        }
                    }
                });
                
                $(document).on('touchend', '.cart-item-card', function() {
                    startX = startY = currentX = currentY = null;
                });
            }
            
            // Sticky checkout button on mobile
            self.setupStickyCheckout();
        },

        // Setup sticky checkout button for mobile
        setupStickyCheckout: function() {
            if (window.innerWidth <= 768) {
                const $checkoutBtn = $('.checkout-button');
                const $stickyContainer = $('<div class="cart-sticky-checkout"></div>');
                
                if ($checkoutBtn.length && !$('.cart-sticky-checkout').length) {
                    const $clonedBtn = $checkoutBtn.clone();
                    $stickyContainer.append($clonedBtn);
                    $('body').append($stickyContainer);
                    
                    // Show/hide based on scroll
                    $(window).on('scroll', function() {
                        const scrollTop = $(window).scrollTop();
                        const documentHeight = $(document).height();
                        const windowHeight = $(window).height();
                        const footerOffset = $('footer').offset()?.top || documentHeight;
                        
                        if (scrollTop + windowHeight > footerOffset - 100) {
                            $stickyContainer.removeClass('visible');
                        } else if (scrollTop > 200) {
                            $stickyContainer.addClass('visible');
                        } else {
                            $stickyContainer.removeClass('visible');
                        }
                    });
                }
            }
        },

        // Initialize loading indicators
        initializeLoading: function() {
            // Add loading CSS if not already present
            if (!$('#cart-loading-styles').length) {
                const loadingCSS = `
                    <style id="cart-loading-styles">
                        .cart-btn-active { transform: scale(0.95); opacity: 0.8; }
                        .cart-swipe-left { transform: translateX(-80px); }
                        .cart-swipe-left .cart-remove-btn { opacity: 1; pointer-events: auto; }
                        .cart-sticky-checkout { 
                            position: fixed; 
                            bottom: 0; 
                            left: 0; 
                            right: 0; 
                            background: var(--cart-bg-card); 
                            border-top: 1px solid var(--cart-border); 
                            padding: 16px; 
                            transform: translateY(100%); 
                            transition: transform 0.3s ease;
                            z-index: 9999;
                        }
                        .cart-sticky-checkout.visible { transform: translateY(0); }
                        .cart-sticky-checkout .checkout-button { margin: 0; }
                    </style>
                `;
                $('head').append(loadingCSS);
            }
        },

        // Setup additional event listeners
        setupEventListeners: function() {
            const self = this;
            
            // Listen for WooCommerce events
            $(document.body).on('updated_cart_totals', function() {
                console.log('Cart totals updated');
            });
            
            $(document.body).on('cart_page_refreshed', function() {
                console.log('Cart page refreshed');
                self.init(); // Reinitialize interactions
            });
            
            // Handle browser back/forward
            $(window).on('popstate', function() {
                location.reload();
            });
            
            // Handle page visibility change
            $(document).on('visibilitychange', function() {
                if (!document.hidden) {
                    // Refresh cart when page becomes visible (handles cart changes in other tabs)
                    setTimeout(() => {
                        $(document.body).trigger('wc_fragment_refresh');
                    }, 500);
                }
            });
        },

        // Handle empty cart state
        handleEmptyCart: function() {
            const $cartContent = $('.cart-content-grid');
            
            if ($cartContent.length) {
                $cartContent.fadeOut(this.config.animationDuration, function() {
                    // Reload page to show empty cart template
                    window.location.reload();
                });
            }
        },

        // Update cart fragments (WooCommerce standard)
        updateCartFragments: function(fragments) {
            if (fragments) {
                $.each(fragments, function(key, value) {
                    const $element = $(key);
                    if ($element.length) {
                        // Add a smooth transition
                        $element.fadeTo(200, 0.5, function() {
                            $element.html($(value).html()).fadeTo(200, 1);
                        });
                    } else {
                        // Fallback: try to find parent containers
                        if (key === '.cart-summary-card' && $('.cart-summary-section').length) {
                            $('.cart-summary-section').fadeTo(200, 0.5, function() {
                                $('.cart-summary-section').html('<div class="cart-collaterals">' + value + '</div>').fadeTo(200, 1);
                            });
                        }
                    }
                });
            }
        },

        // Revert quantity on error
        revertQuantity: function($element, cartItemKey) {
            // Get original quantity from the element's data attribute or previous value
            const originalQty = parseInt($element.data('original-qty')) || parseInt($element.attr('data-original-qty')) || 1;
            console.log('Reverting quantity to:', originalQty);
            $element.val(originalQty);
            
            // Show user feedback
            this.showNotification('Quantity reverted due to error', 'error');
        },

        // Show notification messages
        showNotification: function(message, type) {
            const $container = $('.cart-container');
            const iconClass = type === 'error' ? '❌' : '✅';
            const $notification = $(`
                <div class="cart-notification ${type === 'error' ? this.config.errorClass : this.config.successClass}" style="display: none;">
                    <span class="cart-notification-icon">${iconClass}</span>
                    <span class="cart-notification-message">${message}</span>
                    <button class="cart-notification-close" aria-label="Close">&times;</button>
                </div>
            `);
            
            // Remove existing notifications
            $('.cart-notification').fadeOut(200, function() {
                $(this).remove();
            });
            
            // Add new notification with animation
            $container.prepend($notification);
            $notification.slideDown(300);
            
            // Auto-remove after delay
            setTimeout(() => {
                $notification.slideUp(300, function() {
                    $(this).remove();
                });
            }, type === 'error' ? 5000 : 3000);
            
            // Manual close
            $notification.find('.cart-notification-close').on('click', function() {
                $notification.slideUp(300, function() {
                    $(this).remove();
                });
            });
        },

        // Utility: Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        CartInteractions.init();
    });

    // Reinitialize on AJAX complete (for compatibility)
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Only reinitialize for WooCommerce cart updates
        if (settings.url && settings.url.includes('wc-ajax')) {
            setTimeout(() => {
                CartInteractions.init();
            }, 100);
        }
    });

    // Export for external access if needed
    window.CartInteractions = CartInteractions;

})(jQuery);