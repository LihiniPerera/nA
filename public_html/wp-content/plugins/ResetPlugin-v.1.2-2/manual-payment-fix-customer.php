<?php
/**
 * RESET Manual Payment Fix System - Customer Search
 * 
 * This script helps recover payments by searching with customer information
 * when payment references are not available or not working.
 * 
 * Usage: /wp-content/plugins/ResetPlugin-v.1.2-2/manual-payment-fix-customer.php
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

// Handle search form submission
$search_results = array();
$search_performed = false;
if (isset($_POST['search_customer'])) {
    $search_email = sanitize_email($_POST['customer_email'] ?? '');
    $search_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    $search_name = sanitize_text_field($_POST['customer_name'] ?? '');
    
    if (empty($search_email) && empty($search_phone) && empty($search_name)) {
        $search_error = 'Please provide at least one search criteria (email, phone, or name).';
    } else {
        $search_performed = true;
        
        // Build search query
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'reset_purchases';
        
        $where_conditions = array();
        $params = array();
        
        if (!empty($search_email)) {
            $where_conditions[] = "purchaser_email = %s";
            $params[] = $search_email;
        }
        
        if (!empty($search_phone)) {
            $where_conditions[] = "purchaser_phone LIKE %s";
            $params[] = '%' . $search_phone . '%';
        }
        
        if (!empty($search_name)) {
            $where_conditions[] = "purchaser_name LIKE %s";
            $params[] = '%' . $search_name . '%';
        }
        
        $where_clause = implode(' OR ', $where_conditions);
        
        $query = "SELECT * FROM {$table_purchases} WHERE {$where_clause} ORDER BY created_at DESC LIMIT 20";
        
        if (!empty($params)) {
            $search_results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        }
    }
}

// Handle manual fix
if (isset($_POST['fix_purchase_id']) && isset($_POST['confirm_fix'])) {
    $purchase_id = intval($_POST['fix_purchase_id']);
    $purchase = $db->get_purchase_by_id($purchase_id);
    
    if ($purchase && $purchase['payment_status'] === 'pending') {
        try {
            echo '<div style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; color: #856404;">';
            echo '<h2>🔧 Processing Manual Fix for Purchase #' . $purchase_id . '...</h2>';
            
            // 1. Update purchase status
            $db->update_purchase($purchase_id, array(
                'payment_status' => 'completed',
                'sampath_transaction_id' => 'MANUAL_FIX_CUSTOMER_' . date('YmdHis')
            ));
            echo '<p>✅ Purchase status updated to completed</p>';
            
            // 2. Mark token as used
            $token_used = $tokens->use_token($purchase['token_id'], array(
                'name' => $purchase['purchaser_name'],
                'email' => $purchase['purchaser_email'],
                'phone' => $purchase['purchaser_phone']
            ));
            echo '<p>✅ Token marked as used</p>';
            
            // 3. Generate invitation keys
            $invitation_tokens = $tokens->generate_invitation_tokens($purchase['token_id']);
            echo '<p>✅ Generated ' . count($invitation_tokens) . ' invitation keys</p>';
            
            // 4. Mark invitation keys as generated
            $db->update_purchase($purchase_id, array('invitation_tokens_generated' => 1));
            echo '<p>✅ Invitation keys marked as generated</p>';
            
            // 5. Send confirmation email
            $updated_purchase = $db->get_purchase_by_id($purchase_id);
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
            echo '<li>' . count($invitation_tokens) . ' invitation keys</li>';
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
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>RESET Manual Payment Fix - Customer Search</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .search-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007cba; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .purchase-item { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007cba; }
        .purchase-pending { border-left-color: #dc3545; }
        .purchase-completed { border-left-color: #28a745; }
        .status-pending { color: #dc3545; font-weight: bold; }
        .status-completed { color: #28a745; font-weight: bold; }
        .fix-form { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 8px; }
    </style>
</head>
<body>

<h1>🔧 RESET Manual Payment Fix - Customer Search</h1>

<div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border-radius: 8px;">
    <h2>📋 Instructions</h2>
    <p>Use this tool when payment references are not available or not working. Search for customers by their information to find and fix pending payments.</p>
    <p><strong>Use this only for payments confirmed successful by the bank!</strong></p>
</div>

<!-- Search Form -->
<div class="search-form">
    <h2>🔍 Search Customer Records</h2>
    <form method="post">
        <div class="form-group">
            <label for="customer_email">Customer Email:</label>
            <input type="email" id="customer_email" name="customer_email" value="<?php echo isset($_POST['customer_email']) ? esc_attr($_POST['customer_email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="customer_phone">Customer Phone:</label>
            <input type="text" id="customer_phone" name="customer_phone" value="<?php echo isset($_POST['customer_phone']) ? esc_attr($_POST['customer_phone']) : ''; ?>" placeholder="0771234567 or 1234567">
        </div>
        
        <div class="form-group">
            <label for="customer_name">Customer Name:</label>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo isset($_POST['customer_name']) ? esc_attr($_POST['customer_name']) : ''; ?>" placeholder="Full name or partial name">
        </div>
        
        <button type="submit" name="search_customer" class="btn btn-primary">🔍 Search Records</button>
    </form>
    
    <?php if (isset($search_error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">
            ❌ <?php echo $search_error; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Search Results -->
<?php if ($search_performed): ?>
    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>📊 Search Results</h2>
        
        <?php if (empty($search_results)): ?>
            <p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px;">
                ⚠️ No purchases found matching your search criteria. 
                Try searching with different information or partial matches.
            </p>
        <?php else: ?>
            <p>Found <?php echo count($search_results); ?> purchase(s):</p>
            
            <?php foreach ($search_results as $purchase): ?>
                <div class="purchase-item <?php echo $purchase['payment_status'] === 'pending' ? 'purchase-pending' : 'purchase-completed'; ?>">
                    <h3>Purchase #<?php echo $purchase['id']; ?> 
                        <span class="status-<?php echo $purchase['payment_status']; ?>">
                            (<?php echo strtoupper($purchase['payment_status']); ?>)
                        </span>
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <strong>Customer:</strong> <?php echo esc_html($purchase['purchaser_name']); ?><br>
                            <strong>Email:</strong> <?php echo esc_html($purchase['purchaser_email']); ?><br>
                            <strong>Phone:</strong> <?php echo esc_html($purchase['purchaser_phone']); ?><br>
                        </div>
                        <div>
                            <strong>Ticket:</strong> <?php echo esc_html($purchase['ticket_type']); ?><br>
                            <strong>Amount:</strong> Rs. <?php echo esc_html($purchase['total_amount'] ?? $purchase['ticket_price']); ?><br>
                            <strong>Date:</strong> <?php echo esc_html($purchase['created_at']); ?><br>
                        </div>
                    </div>
                    
                    <?php if (!empty($purchase['payment_reference'])): ?>
                        <p><strong>Payment Reference:</strong> <code><?php echo esc_html($purchase['payment_reference']); ?></code></p>
                    <?php else: ?>
                        <p><strong>Payment Reference:</strong> <em>Not set</em></p>
                    <?php endif; ?>
                    
                    <?php if ($purchase['payment_status'] === 'pending'): ?>
                        <div class="fix-form">
                            <h4>⚠️ Pending Payment - Ready for Manual Fix</h4>
                            <p>If you have confirmed this payment was successful at the bank, you can fix it:</p>
                            
                            <form method="post" onsubmit="return confirm('Are you sure you want to mark this payment as completed? This action cannot be undone.');">
                                <input type="hidden" name="fix_purchase_id" value="<?php echo $purchase['id']; ?>">
                                <button type="submit" name="confirm_fix" class="btn btn-danger">
                                    🔧 Fix Payment for Purchase #<?php echo $purchase['id']; ?>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p style="color: #28a745;">✅ This payment has already been completed.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Navigation -->
<div style="margin: 20px 0;">
    <a href="<?php echo admin_url('admin.php?page=reset-dashboard'); ?>" class="btn btn-primary">← Back to RESET Dashboard</a>
    <a href="manual-payment-fix.php" class="btn btn-success">Payment Reference Search</a>
</div>

<!-- Recent Pending Purchases -->
<?php
$recent_pending = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}reset_purchases 
     WHERE payment_status = 'pending' 
     ORDER BY created_at DESC 
     LIMIT 10", 
    ARRAY_A
);
?>

<?php if (!empty($recent_pending)): ?>
    <div style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>⏰ Recent Pending Payments</h2>
        <p>These are the most recent pending payments that might need manual fixing:</p>
        
        <?php foreach ($recent_pending as $purchase): ?>
            <div style="background: #fff; padding: 10px; margin: 10px 0; border-radius: 4px; border-left: 3px solid #dc3545;">
                <strong>Purchase #<?php echo $purchase['id']; ?></strong> - 
                <?php echo esc_html($purchase['purchaser_name']); ?> 
                (<?php echo esc_html($purchase['purchaser_email']); ?>) - 
                Rs. <?php echo esc_html($purchase['total_amount'] ?? $purchase['ticket_price']); ?> - 
                <?php echo esc_html($purchase['created_at']); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html> 