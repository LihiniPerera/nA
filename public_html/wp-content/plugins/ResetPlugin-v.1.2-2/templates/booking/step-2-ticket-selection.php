<?php
// Get core instance and session data
$core = ResetCore::getInstance();
$session_data = $wizard->get_session_data();
$token_type = $session_data['token_type'] ?? '';
$ticket_selection = $session_data['step_data']['ticket_selection'] ?? array();

// Get ticket availability and capacity information
$ticket_availability = $capacity->get_ticket_availability($token_type);
$is_free_token = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));

// Get available tickets and capacity information
$available_tickets = $capacity->get_available_ticket_types($token_type);
$capacity_status = $capacity->get_capacity_status();
$current_tier = $capacity->get_current_ticket_tier();
$capacity_settings = $capacity->get_capacity_settings();
$thresholds = $capacity_settings['ticket_thresholds'];
$all_tickets = $core->get_ticket_pricing();

// Get active ticket for current tier
$tier_to_ticket = array(
    'early_bird' => 'general_early',
    'late_bird' => 'general_late',
    'very_late_bird' => 'general_very_late',
    'final' => null  // No tickets available
);
$active_ticket_key = $tier_to_ticket[$current_tier] ?? null;
$active_ticket = $all_tickets[$active_ticket_key] ?? null;

// Determine display ticket name based on token type
function get_display_ticket_name($token_type, $active_ticket) {
    if (!$active_ticket) return 'General Admission';
    
    switch ($token_type) {
        case 'sponsor':
            return $active_ticket['name'] . ' (FREE)';
        case 'free_ticket':
            return $active_ticket['name'] . ' (FREE)';
        case 'polo_ordered':
            return $active_ticket['name'] . ' (FREE)';
        case 'invitation':
        case 'normal':
        default:
            return $active_ticket['name'];
    }
}

// Determine display price and savings message
function get_display_price_info($token_type, $active_ticket) {
    if (!$active_ticket) return array('price' => 0, 'original_price' => 0, 'savings' => '');
    
    $is_free = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));
    
    if ($is_free) {
        // Only show savings message for free_ticket and sponsor, not polo_ordered
        $savings = '';
        if ($token_type === 'free_ticket' || $token_type === 'sponsor') {
            $savings = 'You saved Rs. ' . number_format($active_ticket['price']) . '!';
        }
        
        return array(
            'price' => 0,
            'original_price' => $active_ticket['price'],
            'savings' => $savings
        );
    }
    
    return array(
        'price' => $active_ticket['price'],
        'original_price' => 0,
        'savings' => ''
    );
}

// Get dynamic title based on key type
function get_dynamic_title($token_type) {
    switch ($token_type) {
        case 'free_ticket':
        case 'sponsor':
            return 'Your Free Ticket';
        case 'polo_ordered':
            return 'Thank you';
        case 'invitation':
            return 'Welcome to RESET 2025';
        case 'normal':
        default:
            return 'Your Ticket';
    }
}

// Get dynamic description based on key type
function get_dynamic_description($token_type) {
    switch ($token_type) {
        case 'free_ticket':
        case 'sponsor':
            return 'Congratulations! Your exclusive access key has unlocked a complimentary ticket.';
        case 'polo_ordered':
            return 'Your polo purchase has earned you complimentary access to RESET.';
        case 'invitation':
            return 'Here\'s your opportunity to purchase <span class="mobile-break"><br></span>a ticket for the main event.';
        case 'normal':
        default:
            return 'Based on current availability, here\'s your ticket for the main event.';
    }
}

// Get inviter information for invitation keys
function get_inviter_info($session_data) {
    // Only process invitation keys
    if ($session_data['token_type'] !== 'invitation') {
        return null;
    }
    
    // Get current key data to find parent_token_id
    $db = ResetDatabase::getInstance();
    $current_token = $db->get_token_by_id($session_data['token_id']);
    
    if (!$current_token || !$current_token['parent_token_id']) {
        return null;
    }
    
    // Get parent key data
    $parent_token = $db->get_token_by_id($current_token['parent_token_id']);
    
    if (!$parent_token) {
        return null;
    }
    
    // Get real name from parent key
    $real_name = trim($parent_token['used_by_name'] ?? '');
    
    // Get gaming name from purchase table
    $purchase = $db->get_purchase_by_token_id($current_token['parent_token_id']);
    $gaming_name = '';
    
    if ($purchase) {
        $gaming_name = trim($purchase['gaming_name'] ?? '');
    }
    
    // Format the name according to new requirements
    if (!empty($real_name) && !empty($gaming_name)) {
        // Split real name into words
        $name_parts = explode(' ', $real_name);
        
        if (count($name_parts) >= 2) {
            // Two or more names: "FirstName .LastInitial - GamingName"
            $first_name = $name_parts[0];
            $last_initial = strtoupper(substr($name_parts[1], 0, 1));
            return $first_name . ' .' . $last_initial . ' - ' . $gaming_name;
        } else {
            // One name: "Name - GamingName"
            return $real_name . ' - ' . $gaming_name;
        }
    } elseif (!empty($gaming_name)) {
        // Only gaming name available
        return $gaming_name;
    } elseif (!empty($real_name)) {
        // Only real name available
        return $real_name;
    }
    
    return null;
}

$display_ticket_name = get_display_ticket_name($token_type, $active_ticket);
$price_info = get_display_price_info($token_type, $active_ticket);
$dynamic_title = get_dynamic_title($token_type);
$dynamic_description = get_dynamic_description($token_type);

// Get inviter information for invitation keys
$inviter_info = get_inviter_info($session_data);

// Initialize carousel component
$carousel = ResetCarousel::getInstance();

// Get ticket status information for all tiers
function get_ticket_status_info($current_tier, $thresholds, $current_tickets, $all_tickets) {
    $tiers = array(
        'early_bird' => array(
            'name' => 'Early Bird',
            'price' => $all_tickets['general_early']['price'] ?? 1500,
            'threshold' => $thresholds['early_bird']
        ),
        'late_bird' => array(
            'name' => 'Late Bird', 
            'price' => $all_tickets['general_late']['price'] ?? 3000,
            'threshold' => $thresholds['late_bird']
        ),
        'very_late_bird' => array(
            'name' => 'Very Late Bird',
            'price' => $all_tickets['general_very_late']['price'] ?? 4500,
            'threshold' => $thresholds['very_late_bird']
        )
    );
    
    $status_info = array();
    
    foreach ($tiers as $tier_key => $tier_data) {
        if ($current_tier == $tier_key) {
            // Current active tier
            $remaining = $tier_data['threshold'] - $current_tickets;
            $status_info[] = array(
                'name' => $tier_data['name'],
                'price' => $tier_data['price'],
                'status' => 'active',
                'text' => $remaining > 0 ? $remaining . ' left' : 'Available'
            );
        } elseif ($current_tickets >= $tier_data['threshold']) {
            // Past tier - sold out
            $status_info[] = array(
                'name' => $tier_data['name'],
                'price' => $tier_data['price'],
                'status' => 'sold_out',
                'text' => '✓ SOLD OUT'
            );
        } else {
            // Future tier - available next
            $status_info[] = array(
                'name' => $tier_data['name'],
                'price' => $tier_data['price'],
                'status' => 'upcoming',
                'text' => 'Available Next'
            );
        }
    }
    
    return $status_info;
}

$ticket_status_info = get_ticket_status_info($current_tier, $thresholds, $capacity_status['current_tickets'], $all_tickets);

// Calculate capacity progress percentage and color
$progress_percentage = ($capacity_status['current_tickets'] / $capacity_status['target_capacity']) * 100;
$progress_color = $progress_percentage < 50 ? '#10b981' : ($progress_percentage < 80 ? '#f59e0b' : '#ef4444');
?>

<div class="step-2-content">
    <h2 class="step-title"><?php echo esc_html($dynamic_title); ?></h2>
    <p class="step-description"><?php echo wp_kses_post($dynamic_description); ?></p>
    
    <!-- Unified Ticket Display -->
    <div class="ticket-display-container">
        <!-- Invitation Message Section (Only for Invitation keys) -->
        <?php if ($token_type === 'invitation' && $inviter_info): ?>
            <div class="invitation-message-section">
                <div class="invitation-message-content">
                    You have been invited by <strong><?php echo esc_html($inviter_info); ?></strong>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Polo Impact Section (Only for Polo Ordered) -->
        <?php if ($token_type === 'polo_ordered'): ?>
            <div class="polo-impact-section">
                <div class="polo-impact-header">
                    <span>🏆</span>
                    <span class="polo-impact-text">Your Impact on Sri Lankan Esports</span>
                </div>
                <div class="polo-impact-content">
                    Every polo purchase helps fund community events, support local talent, and elevate Sri Lanka's presence in the global Esports scene. You're part of something bigger!
                </div>
            </div>
        <?php endif; ?>

        <!-- Dynamic Pricing Based on Capacity Notice (Hidden for polo_ordered) -->
        <?php if ($token_type !== 'polo_ordered'): ?>
            <div class="pricing-notice">
                <div class="pricing-text">
                    <h4>💡 Dynamic Pricing Based on Capacity</h4>
                    <p>Ticket prices increase every 100 attendees as we expand infrastructure - adding more ACs, PCs, seating, and technical support to ensure the best experience for everyone!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Purchases Carousel -->
    <?php 
    $carousel->render(array(
        'context' => 'booking',
        'min_purchases' => 5,
        'max_rows' => 3,
        'dynamic_scaling' => true,
        'title' => 'Recent purchases'
    )); 
    ?>

    <div class="ticket-display-container">                                
        <!-- Main Ticket Card -->
        <div class="main-ticket-card">
            <div class="ticket-title">General Admission</div>
            <?php 
            // Only hide sections for paying users when capacity is reached
            $paying_users = array('normal', 'invitation');
            $is_paying_user = in_array($token_type, $paying_users);
            $should_hide_sections = $is_paying_user && $ticket_availability['capacity_reached'];
            ?>
            <?php if (!$should_hide_sections): ?>
                <div class="ticket-header">
                    <div class="ticket-badge <?php echo ($token_type === 'free_ticket' || $token_type === 'polo_ordered' || $token_type === 'sponsor') ? 'free-polo-badge' : ''; ?>">
                        <?php if ($is_free_token): ?>
                            <span class="badge-text free">Your Ticket: <?php echo esc_html($display_ticket_name); ?></span>
                        <?php else: ?>
                            <span class="badge-text paid">Your Ticket: <?php echo esc_html($display_ticket_name); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ticket-price">
                    <?php if ($price_info['original_price'] > 0): ?>
                        <div class="original-price">Rs. <?php echo number_format($price_info['original_price']); ?></div>
                    <?php endif; ?>
                    <div class="price-amount">Rs. <?php echo number_format($price_info['price']); ?></div>
                    <?php if ($price_info['savings']): ?>
                        <div class="savings-message">💰 <?php echo esc_html($price_info['savings']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$is_free_token): ?>
                    <div class="urgency-message">
                        <div class="urgency-icon">⚡</div>
                        <div class="urgency-text">Grab yours soon - price increases when this tier sells out!</div>
                    </div>
                <?php endif; ?>
                
                <!-- Event Capacity Progress -->
                <div class="capacity-progress-section">
                    <div class="progress-label">Event Capacity Progress</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($progress_percentage, 100); ?>%; background-color: <?php echo $progress_color; ?>"></div>
                    </div>
                    <div class="progress-info">
                        <span><?php echo $capacity_status['current_tickets']; ?> / <?php echo $capacity_status['target_capacity']; ?> registered</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Ticket Status Section -->
            <div class="ticket-status-section">
                <?php foreach ($ticket_status_info as $tier_info): ?>
                    <div class="tier-status <?php echo $tier_info['status']; ?>">
                        <div class="tier-price">Rs. <?php echo number_format($tier_info['price']); ?></div>
                        <div class="tier-name"><?php echo esc_html($tier_info['name']); ?></div>
                        <div class="tier-status-text">
                            <?php if ($tier_info['status'] == 'active'): ?>
                                <span class="status-active"><?php echo esc_html($tier_info['text']); ?></span>
                            <?php elseif ($tier_info['status'] == 'sold_out'): ?>
                                <span class="status-sold-out"><?php echo esc_html($tier_info['text']); ?></span>
                            <?php else: ?>
                                <span class="status-upcoming"><?php echo esc_html($tier_info['text']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ticket-select-op">*Ticket is auto selected, just press&nbsp;continue.</div>

            <!-- Capacity Reached Notice (After ticket status section) - Only for paying users -->
            <?php if ($is_paying_user && $ticket_availability['capacity_reached']): ?>
                <div class="capacity-reached-notice">
                    <div class="capacity-content">
                        <h3>🚫 Booking Closed</h3>
                        <p><?php echo esc_html($ticket_availability['capacity_message']); ?></p>
                        <div class="capacity-badge">SOLD OUT</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Polo Complete Experience Section (Only for Polo Ordered) -->
            <?php if ($token_type === 'polo_ordered'): ?>
                <div class="polo-complete-experience-section">
                    <div class="polo-complete-experience-header">
                        <span class="polo-complete-icon">🎯</span>
                        <span class="polo-complete-text">Complete Your RESET Experience?</span>
                    </div>
                    <div class="polo-complete-experience-content">
                        Want to make your celebration even more special with our <strong>afterparty options?</strong>
                    </div>
                </div>
            <?php endif; ?>
            
                <!-- Exclusive Access Section (Only for Free and Polo keys) -->
            <?php if ($token_type === 'free_ticket' || $token_type === 'sponsor'): ?>
                <!-- Ready for Ultimate Experience Section -->
                <div class="ultimate-experience-section">
                    <div class="ultimate-experience-header">
                        <span class="ultimate-icon">🎯</span>
                        <span class="ultimate-text">Ready for the Ultimate Experience?</span>
                    </div>
                    <div class="ultimate-experience-content">
                        You're all set for the main event! Want to make your night even more memorable?<br>
                        <strong>Check out the after party options next.</strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Includes Section (Hidden for free_ticket, polo_ordered, and sponsor) -->
            <?php if ($token_type !== 'free_ticket' && $token_type !== 'sponsor' && $token_type !== 'polo_ordered' && $active_ticket && !empty($active_ticket['benefits'])): ?>
                <div class="includes-section">
                    <div class="includes-header">INCLUDES:</div>
                    <div class="includes-content">
                        <?php echo esc_html($active_ticket['benefits']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($token_type !== 'free_ticket' && $token_type !== 'sponsor' && $token_type !== 'polo_ordered'): ?>
                <!-- Current Tier Infrastructure -->
                <div class="tier-infrastructure">
                    <div class="infrastructure-icon">🏗️</div>
                    <div class="infrastructure-text">
                        <strong>Current Tier Infrastructure</strong><br>
                        Your ticket helps fund: Additional ACs for comfort, extended seating area, enhanced technical support, and premium audio-visual setup.
                    </div>
                </div> 
            <?php endif; ?>
        </div>
        
        <!-- Hidden form for data submission -->
        <form id="ticketSelectionForm" class="ticket-selection-form" style="display: none;">
            <input type="hidden" name="ticket_type" value="<?php echo esc_attr($active_ticket_key ?: $token_type); ?>">
        </form>
    </div>
</div>

<style>
/* Updated Step 2 Unified Styling */
.step-title {
    font-size: 28px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 12px;
    text-align: center;
    letter-spacing: -0.5px;
}

.ticket-display-container {
    max-width: 600px;
    margin: 0 auto;
}

.step-description {
    font-size: 16px;
    color: #666;
    text-align: center;
    margin-bottom: 30px;
}

/* Pricing Notice */
.pricing-notice {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    border-left: 4px solid #f9c613;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.pricing-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.pricing-text {
    color: #000000;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 500;
}

.ticket-title {
    font-size: 24px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 20px;
    margin-top: 20px;
    text-align: center;
}

.pricing-text h4 {
    color: #333;
    margin-bottom: 10px;
    font-size: 16px;
    margin-top: 0;
    font-weight: 500;
}

.pricing-text p {
    color: #333;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 0;
    margin-top: 0;
}
/* Main Ticket Card */
.main-ticket-card {
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.main-ticket-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
}

/* Ticket Header */
.ticket-header {
    text-align: center;
    margin-bottom: 35px;
}

.ticket-badge {
    display: inline-block;
    background: #f9c613;
    color: #000000;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ticket-badge.free-polo-badge {
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    color: #ffffff;
}

.badge-text.free {
    color: #ffffff;
}

/* Ticket Price */
.ticket-price {
    text-align: center;
    margin-bottom: 20px;
}

.original-price {
    font-size: 24px;
    font-weight: 400;
    color: #9ca3af;
    text-decoration: line-through;
    margin-bottom: 20px;
}

.price-amount {
    font-size: 48px;
    font-weight: 700;
    color: #000000;
    line-height: 1;
    margin-bottom: 8px;
}

.savings-message {
    background: linear-gradient(135deg, #FFD700, #FFC107);
    color: #333;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    display: inline-block;
    margin-bottom: 20px;
    margin-top: 20px;
}

/* Urgency Message */
.urgency-message {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}

.urgency-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.urgency-text {
    color: #92400e;
    font-size: 13px;
    font-weight: 500;
}

/* Capacity Progress Section */
.capacity-progress-section {
    margin-bottom: 24px;
    margin-top: 50px;
}

.progress-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.progress-bar {
    background: #f3f4f6;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.progress-info {
    text-align: center;
    font-size: 12px;
    color: #6b7280;
    margin-top: 6px;
    display: none;
}

/* Ticket Status Section */
.ticket-status-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 10px;
}

.ticket-select-op {
    color: #000;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
}

.tier-status {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s ease;
}

.tier-status.active {
    background: #f0fdf4;
    border-color: #10b981;
}

.tier-status.sold_out {
    background: #fef2f2;
    border-color: #ef4444;
    opacity: 0.8;
}

.tier-status.upcoming {
    background: #fefce8;
    border-color: #f59e0b;
}

.tier-price {
    font-size: 14px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 4px;
}

.tier-name {
    font-size: 11px;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 6px;
}

.tier-status-text {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    color: #10b981;
}

.status-sold-out {
    color: #ef4444;
}

.status-upcoming {
    color: #f59e0b;
}

/* Exclusive Access Section */
.exclusive-access-section {
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
    color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
}

.exclusive-access-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.exclusive-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}

.exclusive-text {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
}

.exclusive-access-content {
    font-size: 14px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.9);
}

/* Invitation Message Section */
.invitation-message-section {
    background: linear-gradient(135deg, #8B5FBF, #A569BD);
    box-shadow: 0 8px 25px rgba(139, 95, 191, 0.3);
    color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
}

.invitation-message-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.invitation-message-text {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
}

.invitation-message-content {
    font-size: 14px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.95);
}

.invitation-message-content strong {
    color: #ffffff;
    font-weight: 700;
}

/* Ultimate Experience Section */
.ultimate-experience-section {
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border: 2px solid #0ea5e9;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
    margin-top: 50px;
}

.ultimate-experience-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.ultimate-icon {
    background: rgba(14, 165, 233, 0.1);
    color: #0ea5e9;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.ultimate-text {
    font-size: 18px;
    font-weight: 700;
    color: #0c4a6e;
}

.ultimate-experience-content {
    font-size: 14px;
    line-height: 1.5;
    color: #0c4a6e;
}

.ultimate-experience-content strong {
    color: #0ea5e9;
}

/* Polo Impact Section */
.polo-impact-section {
        background: linear-gradient(135deg, #1976d2, #1e88e5);
    color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: center;
}

.polo-impact-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.polo-impact-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.polo-impact-text {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
}

.polo-impact-content {
    font-size: 14px;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.95);
}

/* Polo Complete Experience Section */
.polo-complete-experience-section {
    background: linear-gradient(135deg, #e8f5e8, #f0fff0);
    border: 2px solid #4CAF50;
    color: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
    margin-top: 45px;
}

.polo-complete-experience-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.polo-complete-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #000000;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.polo-complete-text {
    font-size: 18px;
    font-weight: 700;
    color: #000000;
}

.polo-complete-experience-content {
    font-size: 14px;
    line-height: 1.5;
    color: #000000;
}

.polo-complete-experience-content strong {
    color: #000000;
    font-weight: 700;
}

/* Includes Section */
.includes-section {
    background: rgba(249, 198, 19, 0.1);
    border: 1px solid #f9c613;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.includes-header {
    font-size: 12px;
    font-weight: 700;
    color: #000000;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: center;
}

.includes-content {
    font-size: 14px;
    color: #92400e;
    line-height: 1.4;
    font-weight: 500;
    text-align: center;
}

/* Tier Infrastructure */
.tier-infrastructure {
    background: #f0f9ff;
    border-left: 4px solid #2196f3;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.infrastructure-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.infrastructure-text {
    color: #1e40af;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 400;
}

.infrastructure-text strong {
    font-weight: 700;
}

/* Capacity Reached Notice */
.capacity-reached-notice {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 2px solid #ef4444;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    position: relative;
    margin-bottom: 20px;
}

.capacity-reached-notice::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    height: 4px;
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
    border-radius: 12px 12px 0 0;
}

.capacity-content h3 {
    color: #7f1d1d;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
    letter-spacing: -0.3px;
}

.capacity-content p {
    color: #991b1b;
    font-size: 16px;
    margin-bottom: 16px;
    line-height: 1.4;
}

.capacity-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}



/* Mobile Responsiveness */
@media (max-width: 768px) {
    .step-title {
        font-size: 24px;
    }
    
    .main-ticket-card {
        padding: 20px;
    }
    
    .price-amount {
        font-size: 40px;
    }
    
    .ticket-status-section {
        grid-template-columns: 1fr;
        gap: 8px;
        margin-bottom: 10px;
    }
    
    .ticket-select-op {
        font-size: 14px;
        margin-bottom: 35px;
    }

    .tier-status {
        padding: 10px;
    }
    
    .pricing-notice {
        padding: 14px;
    }
    
    .pricing-text {
        font-size: 13px;
    }

    .step-description {
        font-size: 14px;
    }

    .pricing-text h4 {
       font-size: 14px; 
    }

    .pricing-text p {
        font-size: 12px;
    }

    .urgency-message {
        align-items: baseline;
    }

    .mobile-break {
        display: inline;
    }

    .urgency-text {
        text-align: center;
    }

    .includes-section {
        border-left: 4px solid #f9c613;
        border-right: unset;
        border-top: unset;
        border-bottom: unset;
    }

    .includes-content {
        font-size: 12px;
    }

    .infrastructure-text {
        font-size: 12px;
    }

    .exclusive-access-section {
        padding: 16px;
    }

    .exclusive-text {
        font-size: 16px;
    }

    .exclusive-access-content {
        font-size: 11px;
    }

    .invitation-message-section {
        padding: 16px;
    }

    .invitation-message-text {
        font-size: 16px;
    }

    .invitation-message-content {
        font-size: 13px;
    }

    .ultimate-experience-section {
        padding: 16px;
    }

    .ultimate-text {
        font-size: 16px;
    }

    .ultimate-experience-content {
        font-size: 13px;
    }

    .original-price {
        font-size: 20px;
    }

    .ultimate-experience-header {
        align-items: flex-start;
    }

    .polo-impact-section {
        padding: 16px;
    }

    .polo-impact-text {
        font-size: 16px;
    }

    .polo-impact-content {
        font-size: 13px;
    }

    .polo-complete-experience-section {
        padding: 16px;
    }

    .polo-complete-text {
        font-size: 16px;
    }

    .polo-complete-experience-content {
        font-size: 13px;
    }


}

@media (max-width: 480px) {
    .step-title {
        font-size: 22px;
    }
    
    .main-ticket-card {
        padding: 16px;
    }
    
    .price-amount {
        font-size: 36px;
    }
    
    .ticket-badge {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .pricing-notice {
        padding: 12px;
    }
    
    .pricing-text {
        font-size: 12px;
    }

    .exclusive-access-section {
        padding: 14px;
    }

    .exclusive-text {
        font-size: 14px;
    }

    .exclusive-access-content {
        font-size: 11px;
    }

    .invitation-message-section {
        padding: 14px;
    }

    .invitation-message-text {
        font-size: 14px;
    }

    .invitation-message-content {
        font-size: 12px;
    }

    .ultimate-experience-section {
        padding: 14px;
    }

    .ultimate-text {
        font-size: 14px;
    }

    .ultimate-experience-content {
        font-size: 12px;
    }

    .original-price {
        font-size: 18px;
    }

    .polo-impact-section {
        padding: 14px;
    }

    .polo-impact-text {
        font-size: 14px;
    }

    .polo-impact-content {
        font-size: 12px;
    }

    .polo-complete-experience-section {
        padding: 14px;
    }

    .polo-complete-text {
        font-size: 14px;
    }

    .polo-complete-experience-content {
        font-size: 12px;
    }


}
</style>

<script>
// Step 2 specific validation with unified logic
function validateCurrentStep() {
    <?php if ($is_paying_user && $ticket_availability['capacity_reached']): ?>
        // Capacity reached for paying users - cannot proceed
        showMessage('Ticket booking is closed due to capacity limits.', 'error');
        return false;
    <?php else: ?>
        // For all key types, determine the ticket type to save
        const ticketType = <?php echo wp_json_encode($active_ticket_key ?: $token_type); ?>;
        
        // Save data via AJAX and then proceed to next step
        saveStepData(2, {
            ticket_type: ticketType
        }, function() {
            // On success, proceed to next step
            proceedToNextStep(<?php echo $wizard->get_next_step(2); ?>);
        });
        
        return false; // Prevent default redirect, let AJAX handle it
    <?php endif; ?>
}

function saveStepData(step, data, successCallback) {
    setLoading(true);
    
    fetch(resetAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_save_step_data',
            step: step,
            data: JSON.stringify(data),
            nonce: resetAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        setLoading(false);
        if (data.success) {
            console.log('Step data saved');
            if (successCallback && typeof successCallback === 'function') {
                successCallback();
            }
        } else {
            showMessage(data.data?.message || 'Failed to save data', 'error');
        }
    })
    .catch(error => {
        setLoading(false);
        console.error('Error saving step data:', error);
        showMessage('Network error. Please try again.', 'error');
    });
}


</script> 