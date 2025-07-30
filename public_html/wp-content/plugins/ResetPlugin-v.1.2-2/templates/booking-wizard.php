<?php
// Get the wizard instance
$wizard = ResetBookingWizard::getInstance();
$capacity = ResetCapacity::getInstance();
$addons = ResetAddons::getInstance();

// Get current step from URL or session
$requested_step = intval(get_query_var('step', 1));
$session_data = $wizard->get_session_data();

// If no session exists, redirect to key entry
if (empty($session_data)) {
    wp_redirect(site_url('/reset'));
    exit;
}

// Validate step range
$current_step = max(1, min($requested_step, 4));

// Update session's current_step to match the URL step
if (isset($_SESSION['reset_booking_wizard'])) {
    $_SESSION['reset_booking_wizard']['current_step'] = $current_step;
}

$step_progress = $wizard->get_step_progress();

// Debug: Log step information


// No automatic redirects - all steps are shown sequentially

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET Event - Book Your Ticket</title>
    <?php wp_head(); ?>
    
    <!-- Enqueue necessary scripts for AJAX -->
    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
    <script>
        // Define AJAX variables for step functionality
        var resetAjax = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('reset_nonce'); ?>',
            siteUrl: '<?php echo site_url(); ?>'
        };
    </script>
    
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
        
        .wizard-container {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(255,255,255,0.1);
            max-width: 1000px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .wizard-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
        }
        
        .wizard-header {
            text-align: center;
            padding: 30px 30px 0 30px;
            background: white;
        }
        
        .logo-container img {
            max-width: 200px !important;
            height: auto !important;
            transition: transform 0.3s ease !important;
            display: unset !important;
        }
        
        .logo-container img:hover {
            transform: scale(1.05);
        }
        
        .step-content {
            padding: 30px;
            min-height: 400px;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px 30px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 198, 19, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #f9c613;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .wizard-container-body {
                padding: 6px;
            }
            
            .wizard-container {
                margin: 10px;
            }

            .wizard-header {
                padding: 30px 10px 0 10px;
            }

            .step-content {
                padding: 20px;
            }
            
            .navigation-buttons {
                padding: 15px 20px 20px 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-button {
                width: 100%;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
            
            /* Reorder buttons for mobile - Continue first, then Back/Event Details */
            .nav-button.continue {
                order: 1;
            }
            
            .nav-button.back {
                order: 2;
            }
        }
    </style>
</head>
<body class="wizard-container-body">
    <div class="wizard-container">
        <div class="wizard-header">
            <div class="logo-container">
                <?php
                $core = ResetCore::getInstance();
                echo $core->get_logo_html('black', '400', '', '200px');
                ?>
            </div>
            
            <!-- Step Progress Indicator -->
            <?php include(plugin_dir_path(__FILE__) . 'booking/step-progress-indicator.php'); ?>
        </div>
        
        <div class="step-content">
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Processing...</p>
            </div>
            
            <div id="message" class="message" style="display: none;"></div>
            

            
            <!-- Load the appropriate step content -->
            <?php
            switch ($current_step) {
                case 1:
                    include(plugin_dir_path(__FILE__) . 'booking/step-1-personal-info.php');
                    break;
                case 2:
                    include(plugin_dir_path(__FILE__) . 'booking/step-2-ticket-selection.php');
                    break;
                case 3:
                    include(plugin_dir_path(__FILE__) . 'booking/step-3-addons.php');
                    break;
                case 4:
                    include(plugin_dir_path(__FILE__) . 'booking/step-4-summary.php');
                    break;
                default:
                    echo '<p>Invalid step: ' . $current_step . '</p>';
            }
            ?>
        </div>
        
        <div class="navigation-buttons">
            <div class="nav-button back">
                <?php if ($current_step > 1): ?>
                    <a href="<?php echo site_url('/reset/booking/step/' . $wizard->get_previous_step($current_step)); ?>" class="btn btn-secondary">
                        ← Back
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_url('/reset/event-details'); ?>" class="btn btn-secondary">
                        ← Event Details
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="nav-button continue">
                <?php if ($current_step < 4): ?>
                    <?php
                    // Check if capacity is reached on step 2 - only for paying users
                    $is_disabled = false;
                    $disabled_reason = '';
                    if ($current_step == 2) {
                        $token_type = $session_data['token_type'] ?? '';
                        // Only check capacity for normal and invitation users
                        $paying_users = array('normal', 'invitation');
                        if (in_array($token_type, $paying_users)) {
                            $ticket_availability = $capacity->get_ticket_availability($token_type);
                            if ($ticket_availability['capacity_reached']) {
                                $is_disabled = true;
                                $disabled_reason = 'Ticket booking is closed due to capacity limits';
                            }
                        }
                    }
                    ?>
                    <button type="button" class="btn btn-primary" id="continueBtn" onclick="nextStep()" 
                            <?php if ($is_disabled): ?>disabled title="<?php echo esc_attr($disabled_reason); ?>"<?php endif; ?>>
                        <?php echo $is_disabled ? 'Booking Closed' : 'Continue →'; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    
    // Global wizard JavaScript functions
    function showMessage(message, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + type;
        messageDiv.style.display = 'block';
        messageDiv.scrollIntoView({ behavior: 'smooth' });
    }
    
    function hideMessage() {
        document.getElementById('message').style.display = 'none';
    }
    
    function setLoading(isLoading) {
        const loading = document.getElementById('loading');
        const continueBtn = document.getElementById('continueBtn');
        
        if (isLoading) {
            loading.style.display = 'block';
            if (continueBtn) {
                continueBtn.disabled = true;
                continueBtn.textContent = 'Processing...';
            }
        } else {
            loading.style.display = 'none';
            if (continueBtn) {
                continueBtn.disabled = false;
                continueBtn.textContent = 'Continue →';
            }
        }
    }
    
    function nextStep() {
        // This will be overridden by each step's specific validation
        const currentStep = <?php echo $current_step; ?>;
        const nextStepNum = <?php echo $wizard->get_next_step($current_step); ?>;
        
        // Basic validation - should be overridden by step-specific functions
        if (validateCurrentStep && typeof validateCurrentStep === 'function') {
            const isValid = validateCurrentStep();
            
            if (isValid) {
                proceedToNextStep(nextStepNum);
            }
        } else {
            proceedToNextStep(nextStepNum);
        }
    }
    
    function proceedToNextStep(stepNumber) {
        const url = '<?php echo site_url('/reset/booking/step/'); ?>' + stepNumber;
        window.location.href = url;
    }
    
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        const message = document.getElementById('message');
        if (message && message.style.display !== 'none') {
            message.style.display = 'none';
        }
    }, 5000);
    </script>
    
    <style>
    /* Disabled button styling */
    .btn[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
        background-color: #ccc !important;
        color: #666 !important;
        border-color: #bbb !important;
    }
    
    .btn[disabled]:hover {
        background-color: #ccc !important;
        color: #666 !important;
        border-color: #bbb !important;
    }
    </style>
    
    <?php wp_footer(); ?>
</body>
</html> 