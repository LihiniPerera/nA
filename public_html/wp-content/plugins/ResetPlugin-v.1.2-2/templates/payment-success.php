<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET Event - Payment Successful</title>
    <?php wp_head(); ?>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000000;
            min-height: 100vh;
            color: #333;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 25px 50px rgba(255,255,255,0.1);
            max-width: 700px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
        }
        
        .local-dev-banner {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            padding: 15px;
            text-align: center;
            font-weight: 700;
            margin: 6px 0 0 0;
            border-radius: 0;
            border: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .success-content {
            padding: 40px;
            text-align: center;
        }
        
        .event-logo {
            position: relative;
            display: inline-block;
        }
        
        .event-logo img {
            max-width: 200px !important;
            height: auto !important;
            transition: transform 0.3s ease !important;
            display: unset !important;
        }
        
        .event-logo img:hover {
            transform: scale(1.05);
        }
        
        .success-checkmark {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            border: 3px solid white;
        }
        
        .success-title {
            font-size: 36px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }
        
        .success-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .ticket-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            text-align: left;
            border: 1px solid #e9ecef;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #000000;
            font-size: 16px;
        }
        
        .detail-value {
            color: #333;
            font-size: 16px;
            font-weight: 500;
        }
        
        .ticket-type-highlight {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invitation-tokens {
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
            border: 2px solid #f9c613;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            position: relative;
        }
        
        .tokens-title {
            font-size: 24px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 15px;
            margin-top: 10px;
        }

        .event-details {
            font-size: 24px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 25px;
            margin-top: 10px;
        }
        
        .tokens-description {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .tokens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }
        
        .token-code {
            background: white;
            border: 2px solid #f9c613;
            border-radius: 10px;
            padding: 15px 10px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 16px;
            color: #000000;
            text-align: center;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .token-code:hover {
            background: #f9c613;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(249, 198, 19, 0.3);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            box-shadow: 0 6px 20px rgba(249, 198, 19, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 10px 30px rgba(249, 198, 19, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 30px rgba(108, 117, 125, 0.4);
        }
        
        .important-note {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 2px solid #2196f3;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            position: relative;
        }
        
        .important-note::before {
            content: 'ℹ️';
            position: absolute;
            top: -12px;
            left: 25px;
            background: #2196f3;
            padding: 6px 10px;
            border-radius: 50%;
            font-size: 18px;
        }
        
        .important-note h4 {
            color: #1565c0;
            margin-top: 10px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .important-note p {
            color: #1565c0;
            margin-bottom: 0;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .loading-tokens {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 30px;
            background: rgba(249, 198, 19, 0.1);
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .booking-status {
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-radius: 15px;
            border: 2px solid #28a745;
        }
        
        .status-badge.success {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .status-details {
            color: #155724;
            font-size: 14px;
            font-weight: 500;
        }
        
        .addon-name {
            background: #f9c613;
            color: #000000;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 10px;
            display: inline-block;
        }
        
        .addon-price {
            background: #f9c613;
            color: #000000;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 13px;
        }
        
        .key-important {
            text-align: left;
            font-size: 18px;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .tokens-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .success-container {
                margin: 10px;
            }
            
            .success-content {
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 28px;
            }
            
            .success-message {
                font-size: 16px;
            }
            
            .ticket-details {
                padding: 15px;
            }
            
            .invitation-tokens {
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .addon-row .detail-value {
                display: flex;
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .addon-name {
                font-size: 12px;
                padding: 6px 12px;
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .addon-price {
                font-size: 12px;
                padding: 3px 10px;
            }
            
            .btn {
                padding: 15px 25px;
                font-size: 14px;
            }
            
            .event-logo img {
                max-width: 160px;
            }

            .key-important {
                font-size: 14px;
            }

            .wizard-container-body {
                padding: 6px;
            }
        }
        
        /* Animations */
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .success-checkmark {
            animation: successPulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="wizard-container-body">
    <?php
    // Get payment reference from URL
    $payment_reference = sanitize_text_field($_GET['ref'] ?? '');
    $is_local_mode = isset($_GET['local']) && $_GET['local'] === '1';
    
    // Initialize variables
    $purchase = null;
    $invitation_tokens = array();
    $ticket_display_name = '';
    $purchase_addons = array();
    
    if ($payment_reference) {
        // Load real purchase data
        if (class_exists('ResetDatabase')) {
            $db = ResetDatabase::getInstance();
            
            // Try to get purchase by payment reference first
            $purchase = $db->get_purchase_by_payment_reference($payment_reference);
            
            // If not found, try to get by purchase ID (for wizard bookings)
            if (!$purchase && is_numeric($payment_reference)) {
                $purchase = $db->get_purchase_by_id(intval($payment_reference));
            }
            
            if ($purchase && class_exists('ResetTokens')) {
                $invitation_tokens = ResetTokens::getInstance()->get_invitation_tokens_by_parent_id((int)$purchase['token_id']);
                
                // Get ticket display name
                $ticket_display_name = get_ticket_display_name($purchase['ticket_type']);
                
                // Get add-ons for this purchase
                $purchase_addons = $db->get_addons_for_purchase((int)$purchase['id']);
                
                // Apply same filtering logic as in summary - hide free addon for polo_ordered users if paid addon selected
                if (!empty($purchase_addons)) {
                    $token = $db->get_token_by_id($purchase['token_id']);
                    $token_type = $token['token_type'] ?? '';
                    
                    if ($token_type === 'polo_ordered') {
                        $has_paid_addon = false;
                        foreach ($purchase_addons as $addon) {
                            if ($addon['addon_key'] !== 'afterpart_package_0') {
                                $has_paid_addon = true;
                                break;
                            }
                        }
                        
                        // If polo_ordered user has paid addon, hide the free addon
                        if ($has_paid_addon) {
                            $purchase_addons = array_filter($purchase_addons, function($addon) {
                                return $addon['addon_key'] !== 'afterpart_package_0';
                            });
                        }
                    }
                }
            }
        }
    }
    
    // Function to get ticket display name
    function get_ticket_display_name($ticket_key) {
        // IMPROVED: Handle empty/null values
        if (empty($ticket_key)) {
            error_log("DEBUG: get_ticket_display_name called with empty ticket_key");
            return 'Free Ticket'; // Default for empty values (likely free keys)
        }
        
        // DEBUG: Log what we're receiving (remove this after testing)
        error_log("DEBUG: get_ticket_display_name called with: " . $ticket_key);
        
        // Try to get from database first (for dynamic ticket types)
        if (class_exists('ResetCore')) {
            $core = ResetCore::getInstance();
            $all_tickets = $core->get_ticket_pricing();
            if (isset($all_tickets[$ticket_key])) {
                return $all_tickets[$ticket_key]['name'];
            }
        }
        
        // Fallback to static mapping for regular tickets
        $ticket_names = [
            'general_early' => 'Early Bird',
            'general_late' => 'Late Bird', 
            'general_very_late' => 'Very Late Bird',
            'afterparty_package_1' => 'Afterparty - Package 01',
            'afterparty_package_2' => 'Afterparty - Package 02',
            'free_ticket' => 'Free Ticket'
        ];
        
        // FIXED: Add key type mappings for free keys
        $token_type_names = [
            'free_ticket' => 'Free Ticket',
            'polo_ordered' => 'FREE Ticket',
            'sponsor' => 'FREE Ticket',
            'normal' => 'Regular Ticket'
        ];
        
        // Check regular tickets first, then key types
        if (isset($ticket_names[$ticket_key])) {
            return $ticket_names[$ticket_key];
        } elseif (isset($token_type_names[$ticket_key])) {
            return $token_type_names[$ticket_key];
        }
        
        // IMPROVED: Better fallback for unrecognized values
        error_log("DEBUG: Unrecognized ticket_key: " . $ticket_key . " - using fallback");
        return ucfirst(str_replace('_', ' ', $ticket_key)) ?: 'Free Ticket';
    }
    ?>
    
    <div class="success-container">
        <?php if ($is_local_mode): ?>
        <div class="local-dev-banner">
            🚧 LOCAL DEVELOPMENT MODE - Payment Gateway Bypassed
        </div>
        <?php endif; ?>
        
        <div class="success-content">
            <div class="event-logo">
                <?php
                $core = ResetCore::getInstance();
                echo $core->get_logo_html('black', '400', '', '200px');
                ?>
            </div>
            
            <h1 class="success-title">🎉 Congratulations!</h1>
            <p class="success-message">
                Your ticket for RESET 2025 has been successfully booked and confirmed. 
                Get ready for the Reunion!
            </p>
            
            <?php if ($purchase): ?>
            <div>
                <div class="status-badge success">
                    ✅ Booking Confirmed
                </div>
            </div>
            <?php endif; ?>
            
            <div class="invitation-tokens">
                <h3 class="tokens-title">Your 5 Invitation Keys</h3>
                <p class="tokens-description">Share these keys with your friends so they can join the event too!</p>
                
                <div class="tokens-grid">
                    <?php if (!empty($invitation_tokens)): ?>
                        <?php foreach ($invitation_tokens as $token): ?>
                            <div class="token-code" title="Click to copy"><?php echo esc_html($token['token_code']); ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="loading-tokens" style="grid-column: 1 / -1;">
                            Invitation keys are being generated...
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="key-important">
                    <strong style="font-weight: 700;">Important:</strong><br>
                    <span>
                    • Each invitation key can only be used once.<br>
                    • Each key allows one person to book a paid ticket. Share them wisely!<br>
                    </span>
                </div>
            </div>
            
            <div class="important-note">
                <h4>Important Reminders</h4>
                <p>
                    • You will receive a confirmation email with your ticket details<br>
                    • Download your e-ticket using the button below for event entry<br>
                    • Please arrive at the venue 30 minutes before the event<br>
                </p>
            </div>

            <div class="ticket-details" id="ticketDetails">
                <h3 class="event-details">📅 Event Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Event:</span>
                    <span class="detail-value">RESET 2025</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y', strtotime(RESET_EVENT_DATE)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">10:00 AM - 11:00 PM</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Venue:</span>
                    <span class="detail-value">Trace Expert City, Colombo - Bay 07</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ticket Holder:</span>
                    <span class="detail-value"><?php echo $purchase ? esc_html($purchase['purchaser_name']) : 'Unknown'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ticket Type:</span>
                    <span class="detail-value">
                        <?php if ($ticket_display_name): ?>
                            <span class="ticket-type-highlight"><?php echo esc_html($ticket_display_name); ?></span>
                        <?php else: ?>
                            Unknown
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if (!empty($purchase_addons)): ?>
                    <?php foreach ($purchase_addons as $addon): ?>
                        <div class="detail-row addon-row">
                            <span class="detail-label">Add-on:</span>
                            <span class="detail-value">
                                <span class="addon-name"><?php echo esc_html($addon['name']); ?></span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">
                        <?php 
                        if ($purchase) {
                            // FIXED: Correct amount calculation for free keys with add-ons
                            $total_amount = 0;
                            
                            // First, try to use total_amount if it exists and is > 0
                            if (isset($purchase['total_amount']) && $purchase['total_amount'] > 0) {
                                $total_amount = floatval($purchase['total_amount']);
                            } else {
                                // Fallback: Calculate from ticket_price + addon_total
                                $ticket_price = floatval($purchase['ticket_price'] ?? 0);
                                $addon_total = floatval($purchase['addon_total'] ?? 0);
                                $total_amount = $ticket_price + $addon_total;
                            }
                            
                            echo 'Rs. ' . number_format($total_amount, 2);
                        } else {
                            echo 'Unknown';
                        }
                        ?>
                    </span>
                </div>
                <?php if ($purchase && $purchase['payment_reference']): ?>
                <div class="detail-row">
                    <span class="detail-label">Reference:</span>
                    <span class="detail-value"><?php echo esc_html($purchase['payment_reference']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="#" class="btn btn-primary" id="downloadTicket">Download E-Ticket</a>
                <a href="<?php echo home_url(); ?>" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($is_local_mode): ?>
        // Debug information for local development
        console.log('=== RESET Success Page Debug ===');
        console.log('Payment Reference from URL:', '<?php echo esc_js($payment_reference); ?>');
        console.log('Purchase found:', <?php echo $purchase ? 'true' : 'false'; ?>);
        <?php if ($purchase): ?>
        console.log('Purchase data:', <?php echo json_encode($purchase); ?>);
        console.log('Invitation keys count:', <?php echo count($invitation_tokens); ?>);
        console.log('Add-ons purchased:', <?php echo json_encode($purchase_addons); ?>);
        console.log('Add-ons count:', <?php echo count($purchase_addons); ?>);
        <?php endif; ?>
        console.log('================================');
        <?php endif; ?>
        
        <?php if (!$purchase): ?>
        // No purchase data found
        console.error('No purchase data found for reference: <?php echo esc_js($payment_reference); ?>');
        alert('⚠️ Purchase data not found. You will be redirected to the main page in 3 seconds.');
        setTimeout(() => {
            window.location.href = '<?php echo site_url('/reset'); ?>';
        }, 3000);
        <?php endif; ?>
        
        // Add copy to clipboard for keys
        document.querySelectorAll('.token-code').forEach(token => {
            token.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent).then(() => {
                    // Show copy feedback
                    const original = this.textContent;
                    this.textContent = 'COPIED!';
                    this.style.background = '#28a745';
                    this.style.color = 'white';
                    
                    setTimeout(() => {
                        this.textContent = original;
                        this.style.background = 'white';
                        this.style.color = '#000000';
                    }, 1000);
                }).catch(() => {
                    console.log('Copy failed');
                });
            });
        });
        
        // Add download ticket functionality
        const downloadBtn = document.getElementById('downloadTicket');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                <?php if ($purchase): ?>
                // Show loading state
                const originalText = downloadBtn.innerHTML;
                downloadBtn.innerHTML = '⏳ Generating PDF...';
                downloadBtn.disabled = true;
                
                // Create download attempt
                try {
                    // Create iframe for silent download (better error detection)
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = '<?php echo site_url('/reset/pdf/' . $purchase['id']); ?>';
                    document.body.appendChild(iframe);
                    
                    // Set up timeout for download detection
                    let downloadTimeout;
                    let downloadCompleted = false;
                    
                    // Listen for error messages from iframe
                    const messageHandler = (event) => {
                        if (event.data && event.data.type === 'pdf_error') {
                            const errorMsg = event.data.message || 'PDF generation failed';
                            const errorCode = event.data.error_code || 'UNKNOWN_ERROR';
                            
                            // Provide user-friendly error messages
                            let userMessage = errorMsg;
                            switch(errorCode) {
                                case 'PURCHASE_NOT_FOUND':
                                    userMessage = 'Ticket not found. Please refresh the page and try again.';
                                    break;
                                case 'SERVICE_UNAVAILABLE':
                                    userMessage = 'Service temporarily unavailable. Please try again in a moment.';
                                    break;
                                case 'INVALID_ID':
                                case 'INVALID_FORMAT':
                                    userMessage = 'Invalid ticket ID. Please refresh the page.';
                                    break;
                                case 'GENERATION_ERROR':
                                    userMessage = 'PDF generation failed. Please contact support if this continues.';
                                    break;
                            }
                            
                            handleDownloadError(userMessage);
                        }
                    };
                    
                    // Add message listener
                    window.addEventListener('message', messageHandler);
                    
                    // Check if download started (fallback method)
                    const checkDownload = () => {
                        // Try to detect if PDF loaded successfully
                        setTimeout(() => {
                            if (!downloadCompleted) {
                                // Check iframe content for errors before assuming success
                                try {
                                    if (iframe.contentDocument) {
                                        const errorMeta = iframe.contentDocument.querySelector('meta[name="pdf-error"]');
                                        if (errorMeta) {
                                            const errorCode = errorMeta.getAttribute('content');
                                            handleDownloadError('PDF generation failed (' + errorCode + ')');
                                            return;
                                        }
                                    }
                                } catch (e) {
                                    // Cross-origin error - likely successful PDF download
                                }
                                
                                // Assume success if no error occurred within reasonable time
                                handleDownloadSuccess();
                                downloadCompleted = true;
                            }
                        }, 3000); // Wait 3 seconds for PDF generation
                    };
                    
                    // Handle successful download
                    const handleDownloadSuccess = () => {
                        if (downloadCompleted) return;
                        downloadCompleted = true;
                        
                        // Clean up
                        window.removeEventListener('message', messageHandler);
                        clearTimeout(downloadTimeout);
                        
                        // Reset download attempts counter on success
                        downloadAttempts = 0;
                        
                        // Reset button
                        downloadBtn.innerHTML = '✅ Downloaded! Download Again?';
                        downloadBtn.disabled = false;
                        
                        // Show success message
                        showDownloadMessage('✅ Ticket downloaded successfully!', 'success');
                        
                        // Remove iframe
                        if (iframe && iframe.parentNode) {
                            iframe.parentNode.removeChild(iframe);
                        }
                    };
                    
                    // Handle download errors
                    const handleDownloadError = (errorMsg = 'Download failed. Please try again.') => {
                        if (downloadCompleted) return;
                        downloadCompleted = true;
                        
                        // Track download attempts
                        downloadAttempts++;
                        
                        // Clean up
                        window.removeEventListener('message', messageHandler);
                        clearTimeout(downloadTimeout);
                        
                        // Check if this is a repeated failure
                        if (downloadAttempts >= maxAttempts) {
                            handleRepeatedFailure();
                            return;
                        }
                        
                        // Reset button with attempt indicator
                        downloadBtn.innerHTML = `❌ Try Again (${downloadAttempts}/${maxAttempts})`;
                        downloadBtn.disabled = false;
                        
                        // Show error message with attempt count
                        const attemptMsg = downloadAttempts > 1 ? ` (Attempt ${downloadAttempts}/${maxAttempts})` : '';
                        showDownloadMessage('❌ ' + errorMsg + attemptMsg, 'error');
                        
                        // Remove iframe
                        if (iframe && iframe.parentNode) {
                            iframe.parentNode.removeChild(iframe);
                        }
                        
                        // Add troubleshooting tips after second failure
                        if (downloadAttempts >= 2) {
                            setTimeout(() => {
                                addRefreshOption();
                            }, 1000);
                        }
                    };
                    
                    // Add download retry counter for better UX
                    let downloadAttempts = 0;
                    const maxAttempts = 3;
                    
                    // Enhance error handling for repeated failures
                    function handleRepeatedFailure() {
                        downloadAttempts++;
                        
                        if (downloadAttempts >= maxAttempts) {
                            const downloadBtn = document.getElementById('downloadTicket');
                            if (downloadBtn) {
                                downloadBtn.innerHTML = '⚠️ Multiple Failures - Contact Support';
                                downloadBtn.disabled = true;
                            }
                            
                            showDownloadMessage(`❌ Download failed ${maxAttempts} times. Please contact support with this error:<br><br><code><?php echo esc_js($purchase['payment_reference'] ?? 'No reference'); ?></code>`, 'error');
                            addRefreshOption();
                        }
                    }
                    
                    // Set up iframe error detection
                    iframe.onload = () => {
                        // Check if we got an error page
                        try {
                            if (iframe.contentDocument) {
                                const title = iframe.contentDocument.title;
                                const errorMeta = iframe.contentDocument.querySelector('meta[name="pdf-error"]');
                                
                                if (title === 'PDF Generation Error' || errorMeta) {
                                    const errorCode = errorMeta ? errorMeta.getAttribute('content') : 'UNKNOWN_ERROR';
                                    handleDownloadError('PDF generation failed (' + errorCode + ')');
                                    return;
                                }
                            }
                            
                            // Check for WordPress error pages
                            if (iframe.contentWindow && iframe.contentWindow.location.href.includes('wp-die')) {
                                handleDownloadError('PDF generation failed - please contact support.');
                                return;
                            }
                            
                            // PDF likely loaded successfully
                            handleDownloadSuccess();
                        } catch (e) {
                            // Cross-origin error usually means PDF downloaded successfully
                            handleDownloadSuccess();
                        }
                    };
                    
                    iframe.onerror = () => {
                        handleDownloadError('Network error - please check your connection.');
                    };
                    
                    // Fallback timeout
                    downloadTimeout = setTimeout(() => {
                        if (!downloadCompleted) {
                            handleDownloadError('Download timeout - please try again.');
                        }
                    }, 15000); // Increased to 15 second timeout for slow connections
                    
                    // Start download check
                    checkDownload();
                    
                } catch (error) {
                    console.error('Download error:', error);
                    downloadBtn.innerHTML = '❌ Try Again';
                    downloadBtn.disabled = false;
                    showDownloadMessage('❌ Download failed: ' + error.message, 'error');
                }
                
                <?php else: ?>
                showDownloadMessage('❌ No purchase data available for download.', 'error');
                <?php endif; ?>
            });
        }
        
        // Helper function to show download messages
        function showDownloadMessage(message, type) {
            // Remove any existing messages
            const existingMsg = document.querySelector('.download-message');
            if (existingMsg) {
                existingMsg.remove();
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'download-message';
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                z-index: 10000;
                font-size: 14px;
                font-weight: 600;
                box-shadow: 0 5px 20px rgba(${type === 'success' ? '40, 167, 69' : '220, 53, 69'}, 0.3);
                max-width: 350px;
                word-wrap: break-word;
                line-height: 1.4;
            `;
            
            // Add close button for error messages
            if (type === 'error') {
                message += '<br><br><small style="opacity: 0.8;">Click to dismiss</small>';
                messageDiv.style.cursor = 'pointer';
                messageDiv.addEventListener('click', () => {
                    if (messageDiv && messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                });
            }
            
            messageDiv.innerHTML = message;
            document.body.appendChild(messageDiv);
            
            // Auto-remove success messages after 5 seconds (but keep error messages)
            if (type === 'success') {
                setTimeout(() => {
                    if (messageDiv && messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 5000);
            }
        }
        
        // Helper function to add refresh page option for errors
        function addRefreshOption() {
            // Check if option already exists
            if (document.querySelector('.refresh-option')) return;
            
            const refreshDiv = document.createElement('div');
            refreshDiv.className = 'refresh-option';
            refreshDiv.style.cssText = `
                text-align: center;
                margin-top: 20px;
                padding: 15px;
                background: rgba(255, 193, 7, 0.1);
                border: 1px solid #ffc107;
                border-radius: 8px;
            `;
            
            refreshDiv.innerHTML = `
                <p style="margin: 0 0 10px 0; color: #856404;">
                    <strong>💡 Troubleshooting Tips:</strong>
                </p>
                <ul style="text-align: left; margin: 10px 0; color: #856404;">
                    <li>Try refreshing the page if the ticket info seems outdated</li>
                    <li>Check your internet connection</li>
                    <li>Contact support if the problem persists</li>
                </ul>
                <button onclick="window.location.reload()" class="btn btn-primary" style="font-size: 14px; padding: 8px 16px;">
                    🔄 Refresh Page
                </button>
            `;
            
            // Add after the action buttons
            const actionButtons = document.querySelector('.action-buttons');
            if (actionButtons) {
                actionButtons.parentNode.insertBefore(refreshDiv, actionButtons.nextSibling);
            }
        }
        
        // Show helpful info on page load if no purchase data
        <?php if (!$purchase): ?>
        showDownloadMessage('⚠️ No purchase data found. Please check the URL or contact support.', 'error');
        addRefreshOption();
        <?php endif; ?>
        
        // Add download retry counter for better UX
        let downloadAttempts = 0;
        const maxAttempts = 3;
        
        // Enhance error handling for repeated failures
        function handleRepeatedFailure() {
            downloadAttempts++;
            
            if (downloadAttempts >= maxAttempts) {
                const downloadBtn = document.getElementById('downloadTicket');
                if (downloadBtn) {
                    downloadBtn.innerHTML = '⚠️ Multiple Failures - Contact Support';
                    downloadBtn.disabled = true;
                }
                
                showDownloadMessage(`❌ Download failed ${maxAttempts} times. Please contact support with this error:<br><br><code><?php echo esc_js($purchase['payment_reference'] ?? 'No reference'); ?></code>`, 'error');
                addRefreshOption();
            }
        }
        
        // Clear any session storage
        sessionStorage.removeItem('reset_token');
        sessionStorage.removeItem('reset_purchase_id');
        
        <?php if ($is_local_mode): ?>
        console.log('🚧 RESET Ticketing System - Local Development Mode');
        console.log('Payment gateway bypassed for local testing');
        <?php if ($purchase): ?>
        console.log('Purchase data:', <?php echo json_encode($purchase); ?>);
        <?php endif; ?>
        <?php endif; ?>
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 