<?php
// Get the core instance
$core = ResetCore::getInstance();

// Get complete booking data
$booking_data = $wizard->get_complete_booking_data();
$personal_info = $booking_data['personal_info'] ?? array();
$ticket_selection = $booking_data['ticket_selection'] ?? array();
$selected_addons = $booking_data['selected_addons'] ?? array();
$pricing = $booking_data['pricing'] ?? array();
$is_free_token = $booking_data['is_free_token'] ?? false;
$token_type = $booking_data['token_type'] ?? '';

// Check if this is a polo_ordered token
$is_polo_ordered = ($token_type === 'polo_ordered');

// Get formatted addon details
$addon_details = $addons->format_addons_for_summary($selected_addons, $token_type);

// Get ticket details for normal keys
$ticket_name = '';
$ticket_price = 0;
if (!$is_free_token && !empty($ticket_selection['ticket_type'])) {
    $all_tickets = $core->get_ticket_pricing();
    $ticket_name = $all_tickets[$ticket_selection['ticket_type']]['name'] ?? 'Unknown Ticket';
    
    // Try to get ticket price from pricing data first, then directly from core
    $ticket_price = $pricing['ticket_price'] ?? 0;
    if ($ticket_price == 0) {
        $ticket_price = $core->get_current_ticket_price($ticket_selection['ticket_type']);
    }
}
?>

<div class="checkout-container">
    <div class="checkout-header">
        <h2 class="checkout-title">Order Summary</h2>
        <p class="checkout-subtitle">Review your booking details</p>
    </div>
    
    <div class="checkout-content">
        <div class="checkout-main">
            <!-- Order Summary Card -->
            <div class="order-summary-card">
                <!-- Ticket Information -->
                <div class="order-section">
                    <h3 class="section-title">
                        <span class="section-icon">🎟️</span>
                        Ticket Information
                    </h3>
                    
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name">
                                <?php if ($is_free_token): ?>
                                    FREE Ticket (<?php 
                                        $token_types = $core->get_token_types();
                                        echo esc_html($token_types[$booking_data['token_type']]['name'] ?? ucfirst($booking_data['token_type']));
                                    ?>)
                                <?php else: ?>
                                    <?php echo esc_html($ticket_name); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_free_token): ?>
                                <div class="item-description">General Admission</div>
                            <?php endif; ?>
                        </div>
                        <div class="item-price">
                            <?php if ($is_free_token): ?>
                                <span class="price-free">Rs. 0</span>
                            <?php else: ?>
                                <span class="price-amount">Rs. <?php echo number_format($ticket_price); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Add-ons -->
                <?php if (!empty($addon_details)): ?>
                    <div class="order-section">
                        <h3 class="section-title">
                            <span class="section-icon">🍺</span>
                            Add-ons
                        </h3>
                        
                        <?php foreach ($addon_details as $addon): ?>
                            <div class="order-item <?php echo ($addon['is_free'] ?? false) ? 'free-item' : ''; ?>">
                                <div class="item-details">
                                    <div class="item-name">
                                        <?php echo esc_html($addon['name']); ?>
                                        <?php if ($addon['is_free'] ?? false): ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-description"><?php echo esc_html($addon['description']); ?></div>
                                </div>
                                <div class="item-price">
                                    <?php if ($addon['is_free'] ?? false): ?>
                                        <span class="price-free">Rs. 0</span>
                                    <?php else: ?>
                                        <span class="price-amount"><?php echo esc_html($addon['formatted_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Total -->
                <div class="order-total">
                    <div class="total-row">
                        <span class="total-label">Total Amount</span>
                        <span class="total-amount">Rs. <?php echo number_format($pricing['total_amount'] ?? 0); ?></span>
                    </div>
                    <?php if (($pricing['total_amount'] ?? 0) == 0): ?>
                        <div class="free-badge">
                            <span class="badge">FREE CONFIRMATION</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Important Information -->
            <div class="important-info-card">
                <h3 class="info-title">
                    <span class="info-icon">ℹ️</span>
                    Important Information
                </h3>
                <div class="info-content">
                    <div class="info-item">
                        <strong>📅 Event Date:</strong> July 27, 2025
                    </div>
                    <div class="info-item">
                        <strong>🆔 Entry Requirements:</strong> Valid e-ticket
                    </div>
                    <?php if (!$is_free_token): ?>
                        <div class="info-item">
                            <strong>🎁 Bonus:</strong> 5 invitation keys to share with friends
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <strong>⚠️ Note:</strong> Tickets are non-refundable. Age 21+ required.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Checkout Actions -->
        <div class="checkout-actions">
            <div class="action-card">
                <div class="customer-info">
                    <h4>Booking Details</h4>
                    <p><strong><?php echo esc_html($personal_info['name'] ?? ''); ?></strong></p>
                    <p><?php echo esc_html($personal_info['email'] ?? ''); ?></p>
                    <p><?php echo esc_html($personal_info['phone'] ?? ''); ?></p>
                    <?php if (!empty($personal_info['gaming_name'])): ?>
                        <p>🎮 <?php echo esc_html($personal_info['gaming_name']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <?php 
                    // FIXED: Check total amount instead of just free key status
                    // Free keys with paid add-ons should go through payment
                    $total_amount = $pricing['total_amount'] ?? 0;
                    if ($total_amount == 0): 
                    ?>
                        <button type="button" class="btn-checkout free" onclick="confirmFreeBooking()">
                            <span class="btn-icon">✅</span>
                            <span class="btn-text">Confirm Free Ticket</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-checkout payment" onclick="proceedToPayment()">
                            <span class="btn-icon">💳</span>
                            <span class="btn-text">Proceed to Payment</span>
                            <span class="btn-amount">Rs. <?php echo number_format($total_amount); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="security-note">
                    <div class="security-icon">🔒</div>
                    <div class="security-text">
                        <p>Your information is secure and encrypted.</p>
                        <p>You'll receive a confirmation email after <?php echo (($pricing['total_amount'] ?? 0) == 0) ? 'confirmation' : 'payment'; ?>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
}

.checkout-header {
    text-align: center;
    margin-bottom: 30px;
}

.checkout-title {
    font-size: 32px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 8px;
}

.checkout-subtitle {
    font-size: 16px;
    color: #666;
    margin: 0;
}

.checkout-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
    align-items: start;
}

.checkout-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-summary-card, .important-info-card, .action-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.order-section {
    margin-bottom: 24px;
}

.order-section:last-child {
    margin-bottom: 0;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #000000;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f9c613;
}

.section-icon {
    font-size: 20px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
    border-bottom: none;
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 16px;
    font-weight: 600;
    color: #000000;
    margin-bottom: 4px;
}

.item-description {
    font-size: 14px;
    color: #666;
}

.item-price {
    text-align: right;
}

.price-amount {
    font-size: 18px;
    font-weight: 700;
    color: #000000;
}

.price-free {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
}

/* Free item styling */
.order-item.free-item {
    border-radius: 8px;
    padding: 16px;
    margin: 8px 0;
}

.free-badge-inline {
    display: inline-block;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 8px;
    vertical-align: top;
}

.order-total {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px solid #f9c613;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.total-label {
    font-size: 20px;
    font-weight: 600;
    color: #000000;
}

.total-amount {
    font-size: 24px;
    font-weight: 800;
    color: #000000;
}

.free-badge {
    text-align: center;
}

.badge {
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.important-info-card {
    background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
    border: 1px solid #f9c613;
}

.info-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #000000;
    margin-bottom: 16px;
}

.info-icon {
    font-size: 20px;
}

.info-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    font-size: 14px;
    color: #333;
    line-height: 1.5;
}

.checkout-actions {
    position: sticky;
    top: 20px;
}

.customer-info {
    margin-bottom: 24px;
}

.customer-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #000000;
    margin-bottom: 12px;
}

.customer-info p {
    margin: 4px 0;
    font-size: 14px;
    color: #666;
}

.action-buttons {
    margin-bottom: 20px;
}

.btn-checkout {
    width: 100%;
    border: none;
    border-radius: 12px;
    padding: 16px 15px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-checkout.payment {
    background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
    color: #000000;
}

.btn-checkout.payment:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(249, 198, 19, 0.4);
}

.btn-checkout.free {
    background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
    color: white;
}

.btn-checkout.free:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
}

.btn-icon {
    font-size: 18px;
}

.btn-text {
    font-weight: 600;
}

.btn-amount {
    font-weight: 700;
    margin-left: 10px;
}

.security-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.security-icon {
    font-size: 20px;
    margin-top: 2px;
}

.security-text {
    flex: 1;
}

.security-text p {
    margin: 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.security-text p:first-child {
    margin-bottom: 4px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .checkout-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .checkout-actions {
        position: static;
        order: -1;
    }
    
    .order-summary-card, .important-info-card, .action-card {
        padding: 20px;
    }
    
    .checkout-title {
        font-size: 24px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .item-name {
        font-size: 14px;
    }
    
    .price-amount, .price-free {
        font-size: 16px;
    }
    
    .total-label {
        font-size: 18px;
    }
    
    .total-amount {
        font-size: 20px;
    }

    .btn-checkout {
        padding: 14px 10px;
        font-size: 14px;
    }

    .btn-icon {
        display: none;
    }

    .step-content {
        padding: 10px !important;
    }
}

@media (max-width: 480px) {
    .checkout-container {
        padding: 0 10px;
    }
    
    .order-summary-card, .important-info-card, .action-card {
        padding: 16px;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .item-price {
        text-align: left;
    }
    
    .btn-checkout {
        padding: 14px 10px;
        font-size: 14px;
    }
    
    .order-item.free-item {
        padding: 12px;
        margin: 6px 0;
    }
    
    .free-badge-inline {
        font-size: 9px;
        padding: 2px 6px;
    }
}
</style>

<script>
function confirmFreeBooking() {
    console.log('Confirming free booking...');
    
    // Show loading state
    const btn = document.querySelector('.btn-checkout.free');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Processing...</span>';
    btn.disabled = true;
    
    // Make AJAX request to process free booking
    fetch(resetAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_process_free_booking',
            nonce: resetAjax.nonce
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Free booking response:', data);
        if (data.success) {
            // Redirect to success page
            window.location.href = data.data.redirect_url;
        } else {
            // Show error message
            const errorMessage = (data.data && data.data.message) || data.message || 'Unknown error occurred';
            alert('Error: ' + errorMessage);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function proceedToPayment() {
    console.log('Proceeding to payment...');
    
    // Show loading state
    const btn = document.querySelector('.btn-checkout.payment');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Processing...</span>';
    btn.disabled = true;
    
    // Make AJAX request to process payment
    fetch(resetAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_process_payment',
            nonce: resetAjax.nonce
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Payment response:', data);
        if (data.success) {
            // Redirect to payment gateway or success page
            window.location.href = data.data.redirect_url;
        } else {
            // Show error message
            const errorMessage = (data.data && data.data.message) || data.message || 'Unknown error occurred';
            alert('Error: ' + errorMessage);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script> 