<?php
/**
 * Payment Error Template
 * 
 * This template displays payment error messages to users
 * when payment processing fails.
 */

// Get error details from query parameters
$error_code = sanitize_text_field($_GET['error'] ?? 'unknown');
$error_message = sanitize_text_field($_GET['message'] ?? '');

// Get event details
$event_details = array(
    'name' => 'RESET',
    'full_name' => 'RESET - Sri Lanka\'s Premier Esports Event',
    'date' => '2025-07-27',
    'formatted_date' => 'July 27, 2025'
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Error - <?php echo esc_html($event_details['name']); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 4em;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 300;
        }
        .error-message {
            font-size: 1.2em;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .error-details h3 {
            color: #495057;
            margin-top: 0;
            font-size: 1.1em;
        }
        .error-details p {
            color: #6c757d;
            margin-bottom: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
            color: white;
        }
        .support-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
        }
        .support-info h4 {
            color: #495057;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        
        <h1 class="error-title">Payment Error</h1>
        
        <div class="error-message">
            We're sorry, but there was an issue processing your payment for the <?php echo esc_html($event_details['full_name']); ?> event.
        </div>
        
        <div class="error-details">
            <h3>Error Details:</h3>
            <p><strong>Error Code:</strong> <?php echo esc_html($error_code); ?></p>
            <?php if (!empty($error_message)): ?>
                <p><strong>Message:</strong> <?php echo esc_html($error_message); ?></p>
            <?php endif; ?>
            <p><strong>Event:</strong> <?php echo esc_html($event_details['full_name']); ?></p>
            <p><strong>Event Date:</strong> <?php echo esc_html($event_details['formatted_date']); ?></p>
        </div>
        
        <div class="action-buttons">
            <a href="<?php echo esc_url(site_url('/reset')); ?>" class="btn btn-primary">
                Try Again
            </a>
            <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-secondary">
                Go to Homepage
            </a>
        </div>
        
        <div class="support-info">
            <h4>Need Help?</h4>
            <p>If you continue to experience issues, please contact our support team:</p>
            <p>
                <strong>Email:</strong> support@nooballiance.lk<br>
                <strong>Phone:</strong> +94 77 123 4567
            </p>
            <p>Please include the error code and your key information when contacting support.</p>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html> 