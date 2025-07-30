<?php
/**
 * RESET Manual Payment Fix System
 * 
 * This script helps recover payments that were successful at the bank
 * but didn't get processed properly by the system.
 * 
 * Usage: /wp-content/plugins/ResetPlugin-v.1.2-2/manual-payment-fix.php?ref=PAYMENT_REFERENCE
 * 
 * CRITICAL: This should only be used by administrators and only for 
 * payments that were confirmed successful by the bank.
 */

// Security check
if (!defined('ABSPATH')) {
    // Include WordPress if not already loaded
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator access required.');
}

// Get payment reference from URL
$payment_reference = sanitize_text_field($_GET['ref'] ?? '');

if (empty($payment_reference)) {
    wp_die('Missing payment reference. Usage: ?ref=PAYMENT_REFERENCE');
}

// Load RESET plugin classes
if (!class_exists('ResetDatabase')) {
    require_once('includes/class-reset-database.php');
}
if (!class_exists('ResetTokens')) {
    require_once('includes/class-reset-tokens.php');
}
if (!class_exists('ResetEmails')) {
    require_once('includes/class-reset-emails.php');
}

// Initialize instances
$db = ResetDatabase::getInstance();
$tokens = ResetTokens::getInstance();
$emails = ResetEmails::getInstance();

// Find the purchase
$purchase = $db->get_purchase_by_payment_reference($payment_reference);

if (!$purchase) {
    wp_die('Purchase not found for payment reference: ' . $payment_reference);
}

// Display current status
echo '<h1>🔧 RESET Manual Payment Fix</h1>';
echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 8px;">';
echo '<h2>Current Purchase Status</h2>';
echo '<strong>Payment Reference:</strong> ' . $payment_reference . '<br>';
echo '<strong>Purchase ID:</strong> ' . $purchase['id'] . '<br>';
echo '<strong>Customer:</strong> ' . $purchase['purchaser_name'] . ' (' . $purchase['purchaser_email'] . ')<br>';
echo '<strong>Phone:</strong> ' . $purchase['purchaser_phone'] . '<br>';
echo '<strong>Ticket Type:</strong> ' . $purchase['ticket_type'] . '<br>';
echo '<strong>Amount:</strong> Rs. ' . $purchase['total_amount'] . '<br>';
echo '<strong>Payment Status:</strong> <span style="color: ' . ($purchase['payment_status'] === 'completed' ? 'green' : 'red') . '">' . strtoupper($purchase['payment_status']) . '</span><br>';
echo '<strong>Created:</strong> ' . $purchase['created_at'] . '<br>';
echo '</div>';

// Get token info
$token = $db->get_token_by_id($purchase['token_id']);
if ($token) {
    echo '<div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 8px;">';
    echo '<h2>Key Information</h2>';
    echo '<strong>Key Code:</strong> ' . $token['token_code'] . '<br>';
    echo '<strong>Key Status:</strong> <span style="color: ' . ($token['status'] === 'used' ? 'green' : 'orange') . '">' . strtoupper($token['status']) . '</span><br>';
    echo '</div>';
}

// Check if already processed
if ($purchase['payment_status'] === 'completed') {
    echo '<div style="background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 8px; color: #155724;">';
    echo '<h2>✅ Payment Already Processed</h2>';
    echo '<p>This payment has already been marked as completed. The customer should have received their ticket.</p>';
    
    // Check invitation key
    $invitation_tokens = $tokens->get_invitation_tokens_by_parent_id($purchase['token_id']);
    if (!empty($invitation_tokens)) {
        echo '<h3>invitation keys (Generated):</h3>';
        foreach ($invitation_tokens as $inv_token) {
            echo '<code>' . $inv_token['token_code'] . '</code> ';
        }
    }
    echo '</div>';
} else {
    // Show fix button if payment is pending
    if (isset($_POST['fix_payment']) && $_POST['fix_payment'] === 'yes') {
        // Process the manual fix
        echo '<div style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; color: #856404;">';
        echo '<h2>🔧 Processing Manual Fix...</h2>';
        
        try {
            // 1. Update purchase status
            $db->update_purchase($purchase['id'], array(
                'payment_status' => 'completed',
                'sampath_transaction_id' => 'MANUAL_FIX_' . date('YmdHis')
            ));
            echo '<p>✅ Purchase status updated to completed</p>';
            
            // 2. Mark keys as used
            $token_used = $tokens->use_token($purchase['token_id'], array(
                'name' => $purchase['purchaser_name'],
                'email' => $purchase['purchaser_email'],
                'phone' => $purchase['purchaser_phone']
            ));
            echo '<p>✅ Key marked as used</p>';
            
            // 3. Generate invitation keys
            $invitation_tokens = $tokens->generate_invitation_tokens($purchase['token_id']);
            echo '<p>✅ Generated ' . count($invitation_tokens) . ' invitation keys</p>';
            
            // 4. Mark invitation key as generated
            $db->update_purchase($purchase['id'], array('invitation_tokens_generated' => 1));
            echo '<p>✅ Invitation Key marked as generated</p>';
            
            // 5. Send confirmation email
            $updated_purchase = $db->get_purchase_by_id($purchase['id']);
            $email_sent = $emails->send_ticket_confirmation($updated_purchase, $invitation_tokens);
            
            if ($email_sent) {
                echo '<p>✅ Confirmation email sent to ' . $purchase['purchaser_email'] . '</p>';
            } else {
                echo '<p>⚠️ Email sending failed - please check email configuration</p>';
            }
            
            echo '<div style="background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 8px; color: #155724;">';
            echo '<h3>🎉 Manual Fix Completed Successfully!</h3>';
            echo '<p>The customer should now have received their ticket confirmation email with:</p>';
            echo '<ul>';
            echo '<li>Ticket confirmation</li>';
            echo '<li>5 invitation keys</li>';
            echo '<li>Event details</li>';
            echo '</ul>';
            
            echo '<h4>Generated Invitation Keys:</h4>';
            foreach ($invitation_tokens as $inv_token) {
                echo '<code style="background: #f8f9fa; padding: 5px; margin: 2px; border-radius: 3px;">' . $inv_token['token_code'] . '</code><br>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div style="background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 8px; color: #721c24;">';
            echo '<h3>❌ Error Processing Manual Fix</h3>';
            echo '<p>Error: ' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
    } else {
        // Show confirmation form
        echo '<div style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; color: #856404;">';
        echo '<h2>⚠️ Manual Payment Fix Required</h2>';
        echo '<p>This payment appears to be stuck in "pending" status. If you have confirmed that:</p>';
        echo '<ul>';
        echo '<li>✅ Money was deducted from customer\'s account</li>';
        echo '<li>✅ Payment was successful at the bank</li>';
        echo '<li>✅ Customer did not receive their ticket</li>';
        echo '</ul>';
        echo '<p>Then you can proceed with the manual fix:</p>';
        
        echo '<form method="post">';
        echo '<input type="hidden" name="fix_payment" value="yes">';
        echo '<button type="submit" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">🔧 Fix Payment Now</button>';
        echo '</form>';
        
        echo '<p><strong>Warning:</strong> Only use this if you have confirmed the payment was successful at the bank. This action cannot be undone.</p>';
        echo '</div>';
    }
}

// Add back button
echo '<div style="margin: 20px 0;">';
echo '<a href="' . admin_url('admin.php?page=reset-dashboard') . '" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to RESET Dashboard</a>';
echo '</div>';

// Add admin instructions
echo '<div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border-radius: 8px;">';
echo '<h2>📋 Admin Instructions</h2>';
echo '<p><strong>How to find the payment reference:</strong></p>';
echo '<ol>';
echo '<li>Ask the customer for their bank transaction details</li>';
echo '<li>The payment reference should start with "RESET"</li>';
echo '<li>Check the purchase records in the database</li>';
echo '<li>Look for transactions around the time the customer made the payment</li>';
echo '</ol>';
echo '</div>';
?> 