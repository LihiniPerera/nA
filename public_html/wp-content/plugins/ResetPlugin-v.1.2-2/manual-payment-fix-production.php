<?php
/**
 * RESET Production Manual Payment Fix System
 * 
 * This script is specifically for handling customers who paid successfully
 * but don't have payment_reference in their database records.
 * 
 * Usage: Access via admin and search by customer details
 * 
 * SECURITY: Only accessible by administrators
 */

// Security check
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator access required.');
}

// Load RESET plugin classes
$required_classes = array(
    'ResetDatabase' => 'includes/class-reset-database.php',
    'ResetTokens' => 'includes/class-reset-tokens.php',
    'ResetEmails' => 'includes/class-reset-emails.php',
    'ResetCore' => 'includes/class-reset-core.php'
);

foreach ($required_classes as $class_name => $file_path) {
    if (!class_exists($class_name)) {
        require_once($file_path);
    }
}

// Initialize instances
$db = ResetDatabase::getInstance();
$tokens = ResetTokens::getInstance();
$emails = ResetEmails::getInstance();
$core = ResetCore::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'search_purchases':
                $search_email = sanitize_email($_POST['search_email'] ?? '');
                $search_phone = sanitize_text_field($_POST['search_phone'] ?? '');
                break;
                
            case 'fix_purchase':
                $purchase_id = intval($_POST['purchase_id']);
                $add_payment_reference = isset($_POST['add_payment_reference']);
                
                if ($purchase_id > 0) {
                    $purchase = $db->get_purchase_by_id($purchase_id);
                    
                    if ($purchase) {
                        try {
                            echo '<div style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; color: #856404;">';
                            echo '<h2>🔧 Processing Manual Fix for Purchase #' . $purchase_id . '...</h2>';
                            
                            $update_data = array();
                            
                            // Add payment reference if requested and missing
                            if ($add_payment_reference && empty($purchase['payment_reference'])) {
                                $payment_reference = $core->generate_payment_reference('MANUAL');
                                $update_data['payment_reference'] = $payment_reference;
                                echo '<p>✅ Generated payment reference: ' . $payment_reference . '</p>';
                            }
                            
                            // Ensure purchase is marked as completed
                            if ($purchase['payment_status'] !== 'completed') {
                                $update_data['payment_status'] = 'completed';
                                echo '<p>✅ Purchase status updated to completed</p>';
                            }
                            
                            // Add transaction ID if missing
                            if (empty($purchase['sampath_transaction_id'])) {
                                $update_data['sampath_transaction_id'] = 'MANUAL_FIX_' . date('YmdHis');
                                echo '<p>✅ Transaction ID added</p>';
                            }
                            
                            // Update purchase if needed
                            if (!empty($update_data)) {
                                $db->update_purchase($purchase_id, $update_data);
                                echo '<p>✅ Purchase record updated</p>';
                            }
                            
                            // Mark key as used if not already
                            $token_info = $tokens->get_token_by_id($purchase['token_id']);
                            if ($token_info && !$token_info['is_used']) {
                                $tokens->use_token($purchase['token_id'], array(
                                    'name' => $purchase['purchaser_name'],
                                    'email' => $purchase['purchaser_email'],
                                    'phone' => $purchase['purchaser_phone']
                                ));
                                echo '<p>✅ Token marked as used</p>';
                            }
                            
                            // Generate invitation keys if not already done
                            if (!$purchase['invitation_tokens_generated']) {
                                $invitation_tokens = $tokens->generate_invitation_tokens($purchase['token_id']);
                                $db->update_purchase($purchase_id, array('invitation_tokens_generated' => 1));
                                echo '<p>✅ Generated ' . count($invitation_tokens) . ' invitation keys</p>';
                            }
                            
                            // Send confirmation email
                            $updated_purchase = $db->get_purchase_by_id($purchase_id);
                            $invitation_tokens = $tokens->get_invitation_tokens_by_parent_id($purchase['token_id']);
                            $email_sent = $emails->send_ticket_confirmation($updated_purchase, $invitation_tokens);
                            
                            if ($email_sent) {
                                echo '<p>✅ Confirmation email sent to ' . $purchase['purchaser_email'] . '</p>';
                            } else {
                                echo '<p>⚠️ Warning: Email might not have been sent</p>';
                            }
                            
                            echo '<p style="color: #155724; font-weight: bold;">🎉 Manual fix completed successfully!</p>';
                            echo '</div>';
                            
                        } catch (Exception $e) {
                            echo '<div style="background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 8px; color: #721c24;">';
                            echo '<h2>❌ Error</h2>';
                            echo '<p>Failed to process manual fix: ' . $e->getMessage() . '</p>';
                            echo '</div>';
                        }
                    }
                }
                break;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>RESET Production Manual Payment Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-primary { background: #007cba; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .purchase-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f1aeb5; }
    </style>
</head>
<body>
    <h1>🔧 RESET Production Manual Payment Fix</h1>
    
    <div class="alert alert-warning">
        <strong>⚠️ IMPORTANT:</strong> This tool is for customers who paid successfully but didn't receive their tickets due to missing payment references. Only use this for confirmed successful payments.
    </div>

    <div class="card">
        <h2>1. Search for Customer Purchase</h2>
        <form method="post">
            <input type="hidden" name="action" value="search_purchases">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="search_email">Customer Email:</label>
                    <input type="email" id="search_email" name="search_email" value="<?php echo esc_attr($search_email ?? ''); ?>" placeholder="customer@example.com">
                </div>
                <div class="form-group">
                    <label for="search_phone">Phone Number:</label>
                    <input type="text" id="search_phone" name="search_phone" value="<?php echo esc_attr($search_phone ?? ''); ?>" placeholder="0771234567">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Search Purchases</button>
        </form>
    </div>

    <?php
    // Display search results
    if (isset($search_email) || isset($search_phone)) {
        $search_conditions = array();
        $search_params = array();
        
        if (!empty($search_email)) {
            $search_conditions[] = "purchaser_email = %s";
            $search_params[] = $search_email;
        }
        
        if (!empty($search_phone)) {
            $search_conditions[] = "purchaser_phone = %s";
            $search_params[] = $search_phone;
        }
        
        if (!empty($search_conditions)) {
            global $wpdb;
            $table_purchases = $wpdb->prefix . 'reset_purchases';
            
            $sql = "SELECT * FROM {$table_purchases} WHERE " . implode(' OR ', $search_conditions) . " ORDER BY created_at DESC";
            $purchases = $wpdb->get_results($wpdb->prepare($sql, ...$search_params), ARRAY_A);
            
            if (!empty($purchases)) {
                echo '<div class="card">';
                echo '<h2>2. Found ' . count($purchases) . ' Purchase(s)</h2>';
                
                foreach ($purchases as $purchase) {
                    $needs_reference = empty($purchase['payment_reference']);
                    $is_completed = $purchase['payment_status'] === 'completed';
                    
                    echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
                    echo '<h3>Purchase #' . $purchase['id'] . '</h3>';
                    
                    echo '<div class="purchase-details">';
                    echo '<div>';
                    echo '<strong>Customer:</strong> ' . esc_html($purchase['purchaser_name']) . '<br>';
                    echo '<strong>Email:</strong> ' . esc_html($purchase['purchaser_email']) . '<br>';
                    echo '<strong>Phone:</strong> ' . esc_html($purchase['purchaser_phone']) . '<br>';
                    echo '</div>';
                    echo '<div>';
                    echo '<strong>Ticket:</strong> ' . esc_html($purchase['ticket_type']) . '<br>';
                    echo '<strong>Amount:</strong> Rs. ' . esc_html($purchase['total_amount'] ?? $purchase['ticket_price']) . '<br>';
                    echo '<strong>Date:</strong> ' . esc_html($purchase['created_at']) . '<br>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div style="margin: 10px 0;">';
                    echo '<strong>Status:</strong> ' . ($is_completed ? '✅ Completed' : '⏳ ' . $purchase['payment_status']) . '<br>';
                    echo '<strong>Payment Reference:</strong> ' . ($needs_reference ? '❌ Missing' : '✅ ' . $purchase['payment_reference']) . '<br>';
                    echo '<strong>Transaction ID:</strong> ' . (empty($purchase['sampath_transaction_id']) ? '❌ Missing' : '✅ ' . $purchase['sampath_transaction_id']) . '<br>';
                    echo '<strong>Invitation Keys:</strong> ' . ($purchase['invitation_tokens_generated'] ? '✅ Generated' : '❌ Not Generated') . '<br>';
                    echo '</div>';
                    
                    if ($is_completed) {
                        echo '<form method="post" style="margin-top: 15px;">';
                        echo '<input type="hidden" name="action" value="fix_purchase">';
                        echo '<input type="hidden" name="purchase_id" value="' . $purchase['id'] . '">';
                        
                        if ($needs_reference) {
                            echo '<label><input type="checkbox" name="add_payment_reference" checked> Add Payment Reference</label><br>';
                        }
                        
                        echo '<button type="submit" class="btn btn-success" onclick="return confirm(\'Are you sure you want to fix this purchase? This will send a confirmation email to the customer.\')">🔧 Fix This Purchase</button>';
                        echo '</form>';
                    } else {
                        echo '<div class="alert alert-danger">Purchase is not completed. Please verify payment before fixing.</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="card">';
                echo '<h2>No Purchases Found</h2>';
                echo '<p>No purchases found for the search criteria. Please check the email/phone and try again.</p>';
                echo '</div>';
            }
        }
    }
    ?>

    <div class="card">
        <h2>3. How to Use This Tool</h2>
        <ol>
            <li><strong>Search:</strong> Enter the customer's email or phone number</li>
            <li><strong>Verify:</strong> Confirm the purchase details match the customer who paid</li>
            <li><strong>Fix:</strong> Click "Fix This Purchase" to complete the process</li>
            <li><strong>Confirm:</strong> Customer will receive their e-ticket via email</li>
        </ol>
        
        <h3>What This Tool Does:</h3>
        <ul>
            <li>✅ Adds missing payment reference</li>
            <li>✅ Ensures purchase is marked as completed</li>
            <li>✅ Marks key as used</li>
            <li>✅ Generates invitation keys</li>
            <li>✅ Sends confirmation email with e-ticket</li>
        </ul>
    </div>
</body>
</html> 