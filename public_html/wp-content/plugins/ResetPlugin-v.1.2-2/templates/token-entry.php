<?php
// Ensure WordPress is fully loaded
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

// Ensure WordPress is initialized
if (!function_exists('wp_create_nonce')) {
    wp_die('WordPress not properly loaded');
}

// Ensure the plugin is loaded and initialized
if (!class_exists('ResetTicketingPlugin')) {
    wp_die('RESET Plugin not loaded');
}

// Force plugin initialization if not already done
$plugin = ResetTicketingPlugin::getInstance();

// Ensure AJAX actions are registered
if (!has_action('wp_ajax_reset_validate_token')) {
    wp_die('AJAX actions not registered');
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET Event - Key Entry</title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .reset-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
        }
        
        .reset-logo {
            margin-bottom: 40px;
        }
        
        .reset-logo img {
            max-width: 280px !important;
            height: auto !important;
            transition: transform 0.3s ease !important;
            display: unset !important;
        }
        
        .reset-logo img:hover {
            transform: scale(1.05);
        }
        
        .token-form {
            margin-bottom: 20px;
        }
        
        .token-input {
                width: 100%;
                padding: 18px 20px !important;
                font-size: 18px;
                border: 2px solid #e1e5e9 !important;
                border-radius: 12px !important;
                text-align: center;
                text-transform: uppercase;
                letter-spacing: 2px;
                margin-bottom: 25px;
                box-sizing: border-box;
                transition: all 0.3s ease;
                background: #fafafa !important;
                color: #000000 !important;
        }
        
        .token-input:focus {
            outline: none !important;
            border-color: #f9c613 !important;
            box-shadow: 0 0 0 3px rgba(249, 198, 19, 0.2) !important;
            background: white !important;
            transform: translateY(-2px);
            color: #000 !important;
            font-weight: 600;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            border: none;
            padding: 18px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(249, 198, 19, 0.4);
            background: linear-gradient(135deg, #ffdd44 0%, #f9c613 100%);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.3);
        }
        
        .message {
            padding: 18px;
            border-radius: 12px;
            margin: 25px 0;
            font-weight: 500;
            border: 2px solid transparent;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .message.polo-thank-you {
            background: none;
            color: #000000;
            border: none;
            padding: 18px;
            border-radius: 12px;
            margin: 25px 0;
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            line-height: 1.7;
        }
        
        .loading {
            display: none;
            margin: 25px 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #f9c613;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        .help-text {
            font-size: 14px;
            color: #000000;
            line-height: 1.5;
            margin-bottom: 25px;
            padding: 12px 7px;
            transition: all 0.3s ease;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #999;
            font-size: 14px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
            margin-right: 15px;
        }

        .event-details-btn {
            position: relative;
            overflow: hidden;
        }

        .btn-secondary-event-details {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            border: 2px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-event-details-main {
            width: 100%;
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-secondary-event-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
        }

        .btn-event-details-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .event-details-btn:hover::after {
            transform: translateY(-50%) translateX(5px);
        }

        .event-details-btn::after {
            content: '';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .reset-container {
                padding: 30px;
                margin: 20px;
            }
            
            .reset-logo img {
                max-width: 240px;
            }
            
            .token-input {
                padding: 15px;
                font-size: 16px;
            }
            
            .submit-btn {
                padding: 15px 30px;
                font-size: 16px;
            }

            .message.polo-thank-you {
                font-size: 16px;
            }

            .help-text {
                font-size: 14px;
                padding: 12px 16px;
            }

            .btn-event-details-main {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-logo">
            <?php
            $core = ResetCore::getInstance();
            echo $core->get_logo_html('black', '400', '', '280px');
            ?>
        </div>
        
        <form id="tokenForm" class="token-form">
            <input 
                type="text" 
                id="tokenCode" 
                name="token_code" 
                class="token-input" 
                placeholder="ENTER YOUR KEY"
                required
                maxlength="8"
                minlength="6"
                pattern="[A-Z0-9]{6,8}"
                title="Key must be 6-8 characters (letters and numbers only)"
            >
            
            <div class="help-text">
                If you don't have a key, check with your friends in SL Esports;<br> they might have one for you.
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                Book a Ticket
            </button>
            <div class="without-key-event-details">
                <div class="divider">or</div>
                <div>
                    <a href="<?php echo site_url('/reset/event-details/?from=key_entry'); ?>" class="btn-event-details-main btn-secondary-event-details event-details-btn">Event Details</a>
                </div>
            </div>
        </form>
        
        <!-- Fallback form for when AJAX fails -->
        <form id="fallbackForm" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" style="display: none;">
            <input type="hidden" name="action" value="reset_validate_token">
            <input type="hidden" name="token_code" id="fallbackTokenCode">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('reset_nonce'); ?>">
        </form>
        
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Validating key...</p>
        </div>
        
        <div id="message" class="message" style="display: none;"></div>
    </div>

    <script>
    // Define AJAX variables for token validation
    var resetAjax = {
        ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
        siteUrl: '<?php echo site_url(); ?>'
    };
    
    // Function to get fresh nonce
    function getFreshNonce() {
        return '<?php echo wp_create_nonce('reset_nonce'); ?>';
    }
    

    
    document.addEventListener('DOMContentLoaded', function() {
        const tokenForm = document.getElementById('tokenForm');
        const tokenInput = document.getElementById('tokenCode');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loading');
        const messageDiv = document.getElementById('message');
        const helpText = document.querySelector('.help-text');
        const withoutKeyEventDetails = document.querySelector('.without-key-event-details');
        
        // Auto-format key input
        tokenInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
        
        tokenForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tokenCode = tokenInput.value.trim();
            
            if (!tokenCode) {
                showMessage('Please enter a key.', 'error');
                return;
            }
            
            validateToken(tokenCode);
        });
        
        function validateToken(tokenCode) {
            setLoading(true);
            hideMessage();
            hideHelpText(); // Hide help text when validation starts
            hideWithoutKeyEventDetails(); // Hide event details section when validation starts
            
            // Get fresh nonce
            var nonce = getFreshNonce();
            
            // Ensure we have a valid nonce before proceeding
            if (!nonce || nonce === '' || nonce.length < 10) {
                setLoading(false);
                showHelpText();
                showMessage('Security token error. Please refresh the page and try again.', 'error');
                return;
            }
            
            // Create form data for better compatibility
            var formData = new FormData();
            formData.append('action', 'reset_validate_token');
            formData.append('token_code', tokenCode);
            formData.append('nonce', nonce);
            
            fetch(resetAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    // Try to get more detailed error information
                    return response.text().then(text => {
                        console.log('Error response text:', text);
                        throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success && data.data.valid) {
                    // Keep button disabled but show "taking you inside" state
                    setTakingYouInside(true);
                    showMessage(data.data.message, 'success');
                    
                    // Show polo message if it exists (for polo_ordered tokens)
                    if (data.data.polo_message) {
                        showPoloMessage(data.data.polo_message);
                    }
                    
                    // Initialize wizard session and redirect to step 1
                    setTimeout(() => {
                        initializeWizardSession(tokenCode);
                    }, 1500);
                } else {
                    // Only re-enable button on error
                    setLoading(false);
                    showHelpText(); // Show help text again on validation failure
                    showWithoutKeyEventDetails(); // Show event details section again on validation failure
                    const message = data.data ? data.data.message : 'An error occurred. Please try again.';
                    showMessage(message, 'error');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                
                // Show help text and event details on error
                showHelpText();
                showWithoutKeyEventDetails();
                
                // Check if it's a 403 error (nonce/permission issue)
                if (error.message && error.message.includes('403')) {
                    redirectToBooking(tokenCode);
                } else {
                    // Try fallback form submission
                    tryFallbackSubmission(tokenCode);
                }
            });
        }
        
        function setLoading(isLoading) {
            if (isLoading) {
                loading.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Validating...';
            } else {
                loading.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Book a Ticket';
            }
        }
        
        function setTakingYouInside(isProcessing) {
            if (isProcessing) {
                loading.style.display = 'none';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Taking you inside...';
            } else {
                loading.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Book a Ticket';
            }
        }
        
        function showMessage(message, type) {
            messageDiv.innerHTML = message;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
        }
        
        function showPoloMessage(message) {
            // Create or get the polo message div
            let poloMessageDiv = document.getElementById('polo-message');
            if (!poloMessageDiv) {
                poloMessageDiv = document.createElement('div');
                poloMessageDiv.id = 'polo-message';
                messageDiv.parentNode.insertBefore(poloMessageDiv, messageDiv.nextSibling);
            }
            
            poloMessageDiv.innerHTML = message;
            poloMessageDiv.className = 'message polo-thank-you';
            poloMessageDiv.style.display = 'block';
        }
        
        function hideMessage() {
            messageDiv.style.display = 'none';
            const poloMessageDiv = document.getElementById('polo-message');
            if (poloMessageDiv) {
                poloMessageDiv.style.display = 'none';
            }
        }
        
        function showHelpText() {
            if (helpText) {
                helpText.style.display = 'block';
            }
        }
        
        function hideHelpText() {
            if (helpText) {
                helpText.style.display = 'none';
            }
        }
        
        function showWithoutKeyEventDetails() {
            if (withoutKeyEventDetails) {
                withoutKeyEventDetails.style.display = 'block';
            }
        }
        
        function hideWithoutKeyEventDetails() {
            if (withoutKeyEventDetails) {
                withoutKeyEventDetails.style.display = 'none';
            }
        }
        
        function initializeWizardSession(tokenCode) {
            // Initialize wizard session via AJAX
            var nonce = getFreshNonce();
            var formData = new FormData();
            formData.append('action', 'reset_initialize_wizard');
            formData.append('token_code', tokenCode);
            formData.append('nonce', nonce);
            
            fetch(resetAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to event details page with valid session
                    window.location.href = '<?php echo site_url('/reset/event-details'); ?>';
                } else {
                    // Fallback to old booking form if initialization fails
                    window.location.href = '<?php echo site_url('/reset/booking'); ?>?token=' + encodeURIComponent(tokenCode);
                }
            })
            .catch(error => {
                console.error('Error initializing wizard:', error);
                // Fallback to old booking form
                window.location.href = '<?php echo site_url('/reset/booking'); ?>?token=' + encodeURIComponent(tokenCode);
            });
        }
        
        function tryFallbackSubmission(tokenCode) {
            // Set the token in the fallback form
            document.getElementById('fallbackTokenCode').value = tokenCode;
            
            // Show message to user
            showMessage('AJAX failed, trying alternative method...', 'error');
            
            // Submit the fallback form
            setTimeout(() => {
                document.getElementById('fallbackForm').submit();
            }, 1000);
        }
        
        // Final fallback: redirect to booking page with token
        function redirectToBooking(tokenCode) {
            setLoading(false);
            showHelpText();
            showMessage('Redirecting to booking page...', 'success');
            
            setTimeout(() => {
                window.location.href = '<?php echo site_url('/reset/booking'); ?>?token=' + encodeURIComponent(tokenCode);
            }, 1500);
        }
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 