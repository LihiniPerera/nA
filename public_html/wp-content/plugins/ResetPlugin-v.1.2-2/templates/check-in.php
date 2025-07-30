<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is admin
if (!is_user_logged_in()) {
    // Redirect to login page with return URL
    $login_url = wp_login_url(site_url('/reset/check-in/'));
    wp_redirect($login_url);
    exit;
}

// Check if logged-in user has admin or shop manager privileges
if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
    wp_die('Access denied. This page is for administrators and shop managers only.');
}

// Initialize required classes
$db = ResetDatabase::getInstance();
$core = ResetCore::getInstance();

// Helper function to get user-friendly ticket type names
function get_friendly_ticket_type($ticket_key) {
    if (empty($ticket_key)) {
        return 'Free Ticket';
    }
    
    // Mapping for ticket types (from purchases table)
    $ticket_names = [
        'general_early' => 'Early Bird - 1500',
        'general_late' => 'Late Bird - 2500', 
        'general_very_late' => 'Very Late Bird - 4,500',
        'afterparty_package_1' => 'Afterparty - Package 01',
        'afterparty_package_2' => 'Afterparty - Package 02'
    ];
    
    // Mapping for token types (from tokens table)
    $token_type_names = [
        'normal' => 'Normal Key',
        'free_ticket' => 'Free Ticket Key',
        'polo_ordered' => 'Polo Ordered Key',
        'sponsor' => 'Sponsor Key',
        'invitation' => 'Invitation Key'
    ];
    
    // Check ticket types first, then token types
    if (isset($ticket_names[$ticket_key])) {
        return $ticket_names[$ticket_key];
    } elseif (isset($token_type_names[$ticket_key])) {
        return $token_type_names[$ticket_key];
    }
    
    // Fallback for unrecognized values
    return ucfirst(str_replace('_', ' ', $ticket_key)) ?: 'Free Ticket';
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'reset_checkin_management_action')) {
        $message = 'Security check failed.';
        $message_type = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_check_in':
                $purchase_id = intval($_POST['purchase_id']);
                
                if ($purchase_id > 0) {
                    $current_user = wp_get_current_user();
                    $user_info = array('user_login' => $current_user->user_login);
                    $result = $db->update_purchase_check_in($purchase_id, true, $user_info);
                    if ($result) {
                        $message = 'Customer checked in successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to check in customer.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Invalid purchase ID.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get search parameters
$search_token = isset($_GET['search_token']) ? sanitize_text_field($_GET['search_token']) : '';
$search_name = isset($_GET['search_name']) ? sanitize_text_field($_GET['search_name']) : '';

// Initialize variables
$purchases = array();
$total_purchases = 0;
$show_results = false;

// Only fetch data if user has entered search criteria
if (!empty($search_token) || !empty($search_name)) {
    $show_results = true;
    
    // Build search conditions
    $where_conditions = array("p.payment_status = 'completed'");
    $search_params = array();

    if (!empty($search_token)) {
        $where_conditions[] = "t.token_code LIKE %s";
        $search_params[] = '%' . $search_token . '%';
    }

    if (!empty($search_name)) {
        $where_conditions[] = "p.purchaser_name LIKE %s";
        $search_params[] = '%' . $search_name . '%';
    }

    $where_clause = implode(' AND ', $where_conditions);

    global $wpdb;
    $query = "SELECT p.*, 
                     t.token_code,
                     newest_addon.addon_name,
                     CASE 
                         WHEN newest_addon.addon_key = 'afterparty_package_2' AND p.created_at < '2025-07-23 00:00:00' THEN 4
                         WHEN newest_addon.addon_key = 'afterparty_package_2' AND p.created_at >= '2025-07-23 00:00:00' THEN 3
                         WHEN newest_addon.addon_key = 'afterpart_package_0' THEN 1
                         ELSE COALESCE(newest_addon.drink_count, 0) 
                     END as calculated_drinks,
                     CASE 
                         WHEN newest_addon.addon_key = 'afterparty_package_2' AND p.created_at < '2025-07-23 00:00:00' THEN CONCAT(newest_addon.addon_name, ' (4 drinks)')
                         WHEN newest_addon.addon_key = 'afterparty_package_2' AND p.created_at >= '2025-07-23 00:00:00' THEN CONCAT(newest_addon.addon_name, ' (3 drinks)')
                         WHEN newest_addon.addon_key = 'afterpart_package_0' THEN CONCAT(newest_addon.addon_name, ' (1 drink)')
                         ELSE CONCAT(COALESCE(newest_addon.addon_name, 'No addons'), ' (', COALESCE(newest_addon.drink_count, 0), ' drinks)')
                     END as addon_details
              FROM {$wpdb->prefix}reset_purchases p
              LEFT JOIN {$wpdb->prefix}reset_tokens t ON p.token_id = t.id
              LEFT JOIN (
                  SELECT pa1.purchase_id, 
                         a.name as addon_name, 
                         a.addon_key, 
                         a.drink_count
                  FROM {$wpdb->prefix}reset_purchase_addons pa1
                  JOIN {$wpdb->prefix}reset_addons a ON pa1.addon_id = a.id
                  WHERE pa1.id = (
                      SELECT pa2.id 
                      FROM {$wpdb->prefix}reset_purchase_addons pa2
                      JOIN {$wpdb->prefix}reset_addons a2 ON pa2.addon_id = a2.id
                      JOIN {$wpdb->prefix}reset_purchases p2 ON pa2.purchase_id = p2.id
                      JOIN {$wpdb->prefix}reset_tokens t2 ON p2.token_id = t2.id
                      WHERE pa2.purchase_id = pa1.purchase_id
                      AND NOT (t2.token_type = 'polo_ordered' AND a2.addon_key = 'afterpart_package_0' 
                               AND EXISTS (SELECT 1 FROM {$wpdb->prefix}reset_purchase_addons pa3 
                                          JOIN {$wpdb->prefix}reset_addons a3 ON pa3.addon_id = a3.id 
                                          WHERE pa3.purchase_id = pa2.purchase_id 
                                          AND a3.addon_key != 'afterpart_package_0'))
                      ORDER BY pa2.created_at DESC 
                      LIMIT 1
                  )
              ) newest_addon ON p.id = newest_addon.purchase_id
              WHERE {$where_clause}
              ORDER BY p.created_at DESC";

    if (!empty($search_params)) {
        $purchases = $wpdb->get_results($wpdb->prepare($query, $search_params), ARRAY_A);
    } else {
        $purchases = $wpdb->get_results($query, ARRAY_A);
    }

    // Get counts for search results
    $total_purchases = count($purchases);
}

// Get check-in statistics
$checkin_stats = $db->get_total_attendees_vs_checked_in();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Check-In - RESET Event</title>
    
    <!-- WordPress styles -->
    <?php wp_head(); ?>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #000;
            color: #fff;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .checkin-container {
            max-width: 1600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(249, 198, 19, 0.1);
            border: 1px solid #333;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f9c613;
            background: linear-gradient(90deg, rgba(249, 198, 19, 0.1) 0%, transparent 100%);
            padding: 20px;
            border-radius: 8px;
            margin: -10px -10px 30px -10px;
        }
        
        .header h1 {
            color: #000;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            font-size: 2.2em;
            font-weight: 700;
        }
        
        .capacity-display {
            display: flex;
            gap: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #f9c613;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(249, 198, 19, 0.3);
        }
        
        .stat-card h3 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            color: #000;
            font-weight: 800;
            text-shadow: none;
        }
        
        .stat-card p {
            margin: 0;
            color: #333;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .search-form {
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 2px solid #f9c613;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.1);
        }
        
        /* Card Styles */
        .purchase-card {
            background: #fff;
            border: 2px solid #f9c613;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 8px 25px rgba(249, 198, 19, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .purchase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(249, 198, 19, 0.25);
        }
        
        .purchase-card.checked-in {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, transparent 100%);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        
        .key-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .key-label {
            color: #000;
            font-weight: 700;
            font-size: 1.1em;
        }
        
        .key-code {
            background: #000;
            color: #f9c613;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2em;
            border: 2px solid #f9c613;
        }
        
        .key-code-small {
            background: #000;
            color: #f9c613;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .checkin-status {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .checkin-mobile {
            background: transparent;
        }

        .not-check {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .customer-name {
            color: #000;
            margin: 0 0 8px 0;
            font-size: 1.8em;
            font-weight: 700;
        }
        
        .customer-email {
            color: #000;
            margin: 0 0 25px 0;
            font-size: 1.1em;
        }
        
        .customer-details-grid {
            background: rgba(249, 198, 19, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #f9c613;
            margin-bottom: 25px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid rgba(249, 198, 19, 0.2);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row .label {
            color: #000;
            font-weight: 700;
            font-size: 1.1em;
        }
        
        .detail-row .value {
            color: #000;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .checkin-section {
            background: rgba(40, 167, 69, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #28a745;
            text-align: center;
        }
        
        .btn-checkin {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-checkin:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .checked-in-status {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
        }
        
        .checked-in-status small {
            display: block;
            margin-top: 5px;
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        /* Welcome Message */
        .welcome-message {
            text-align: center;
            padding: 50px 30px;
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            border: 2px dashed #f9c613;
            border-radius: 15px;
            margin: 30px 0;
        }
        
        .welcome-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .welcome-message h3 {
            color: #f9c613;
            font-size: 1.8em;
            margin-bottom: 15px;
        }
        
        .welcome-message p {
            color: #aaa;
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        
        .quick-tips {
            background: rgba(249, 198, 19, 0.05);
            padding: 20px;
            border-radius: 10px;
            text-align: left;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .quick-tips h4 {
            color: #f9c613;
            margin-top: 0;
        }
        
        .quick-tips ul {
            color: #fff;
            margin: 0;
            padding-left: 20px;
        }
        
        .quick-tips li {
            margin: 8px 0;
            line-height: 1.4;
        }
        
        /* Results Header */
        .results-header {
            margin: 25px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .results-header h3 {
            color: #000;
            font-weight: 700;
            margin: 0;
        }
        
        /* View Toggle */
        .view-toggle {
            display: flex;
            background: #1a1a1a;
            border-radius: 8px;
            padding: 4px;
            border: 2px solid #f9c613;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: #f9c613;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .view-btn:hover {
            background: rgba(249, 198, 19, 0.1);
        }
        
        .view-btn.active {
            background: #f9c613;
            color: #000;
        }
        
        /* View Containers */
        .view-container {
            transition: opacity 0.3s ease;
        }
        
        /* Inline Card Styles */
        .inline-card {
            background: linear-gradient(135deg, #f9f9f9 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 10px 0;
            border: 2px solid #f9c613;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.15);
        }
        
        .inline-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .inline-card .customer-name {
            font-size: 1.4em;
            margin-bottom: 5px;
        }
        
        .inline-card .customer-email {
            margin-bottom: 15px;
        }
        
        /* Expandable Row */
        .expanded-row {
            background: rgba(249, 198, 19, 0.05);
        }
        
        .expanded-row td {
            padding: 0 !important;
        }
        
        /* Expand Button */
        .btn-expand {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-expand:hover {
            background: linear-gradient(135deg, #ffdd44 0%, #f9c613 100%);
            transform: translateY(-1px);
        }
        
        .expand-icon {
            transition: transform 0.2s;
        }
        
        .expand-icon.rotated {
            transform: rotate(180deg);
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .cards-grid .purchase-card {
            margin: 0;
        }
        
        /* Table Row Click */
        .purchase-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .purchase-row:hover {
            background: rgba(249, 198, 19, 0.15) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 198, 19, 0.2);
        }
        
        .purchase-row:active {
            transform: translateY(0);
        }
        
        .search-form h3 {
            margin-top: 0;
            color: #000;
            font-weight: 700;
            font-size: 1.4em;
            margin-bottom: 20px;
        }
        
        .search-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }
        
        .search-field {
            display: flex;
            flex-direction: column;
        }
        
        .search-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #000;
            font-size: 0.95em;
        }
        
        .search-field input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f9c613;
            border-radius: 6px;
            font-size: 15px;
            background: #fff;
            color: #000 !important;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        
        .search-field input:focus {
            outline: none;
            border-color: #f9c613;
            box-shadow: 0 0 0 3px rgba(249, 198, 19, 0.1);
        }
        
        .search-field input::placeholder {
            color: #888;
        }
        
        .search-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .notice {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 5px solid;
            font-weight: 500;
        }
        
        .notice-success {
            background: linear-gradient(135deg, rgba(249, 198, 19, 0.1) 0%, rgba(249, 198, 19, 0.05) 100%);
            border-color: #f9c613;
            color: #f9c613;
        }
        
        .notice-error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
            border-color: #dc3545;
            color: #ff6b7d;
        }
        
        .purchases-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .purchases-table th,
        .purchases-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #333;
            width: 100%;
        }
        
        .purchases-table td {
            color: #000;
        }

        .purchases-table th {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        .purchases-table tbody tr {
            transition: background-color 0.2s;
        }
        
        .purchases-table tbody tr:hover {
            background: rgba(249, 198, 19, 0.05);
        }
        
        .purchases-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .purchases-table tbody tr:nth-child(even):hover {
            background: rgba(249, 198, 19, 0.05);
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000;
            border: 2px solid #f9c613;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ffdd44 0%, #f9c613 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 198, 19, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #444 0%, #666 100%);
            color: #fff;
            border: 2px solid #555;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #666 0%, #444 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .back-to-sales-report {
            margin-top: 20px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1200px) {
            .checkin-container {
                padding: 20px;
            }
            
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .checkin-container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                text-align: center;
                margin: -10px -10px 25px -10px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .capacity-display {
                width: 100%;
                justify-content: center;
            }
            
            .stats {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-card h3 {
                font-size: 2em;
            }
            
            .search-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .search-actions {
                justify-content: center;
            }
            
            .purchases-table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .purchases-table th,
            .purchases-table td {
                padding: 8px 6px;
            }
            
            /* Mobile Card Styles */
            .purchase-card {
                padding: 20px;
                margin: 20px 0;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .customer-name {
                font-size: 1.5em;
            }
            
            .welcome-message {
                padding: 30px 20px;
            }
            
            .welcome-icon {
                font-size: 3em;
            }
            
            .quick-tips {
                padding: 15px;
            }
            
            /* Mobile View Toggle */
            .results-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .view-toggle {
                width: 100%;
                justify-content: center;
            }
            
            .view-btn {
                flex: 1;
                text-align: center;
            }
            
            /* Mobile Cards Grid */
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            /* Mobile Inline Cards */
            .inline-card {
                padding: 15px;
                margin: 5px 0;
            }
            
            .inline-card .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .inline-card .customer-name {
                font-size: 1.3em;
            }
            
            .btn-expand {
                width: 100%;
                justify-content: center;
                padding: 10px;
            }

            .detail-row {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.5em;
            }
            
            .stat-card h3 {
                font-size: 1.8em;
            }
            
            .search-form {
                padding: 15px;
            }
            
            .purchases-table th,
            .purchases-table td {
                padding: 6px 4px;
                font-size: 12px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 11px;
            }
        }
        
        /* Dark theme scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #f9c613;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #ffdd44;
        }
    </style>
</head>

<body>
    <div class="checkin-container">
        <div class="header">
            <h1>📋 Event Check-In System</h1>
            <div class="capacity-display">
                <div class="stat-card">
                    <h3 id="checkin-count"><?php echo $checkin_stats['checked_in_count']; ?></h3>
                    <p>Checked In</p>
                </div>
                <div class="stat-card">
                    <h3 id="total-attendees"><?php echo $checkin_stats['total_attendees']; ?></h3>
                    <p>Total Attendees</p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-form">
            <h3>🔍 Search Attendees</h3>
            <form method="get">
                <div class="search-row">
                    <div class="search-field">
                        <label for="search_name">Customer Name</label>
                        <input type="text" id="search_name" name="search_name" 
                               value="<?php echo esc_attr($search_name); ?>" 
                               placeholder="Enter customer name..." style="text-transform: uppercase;">
                    </div>
                    <div class="search-field">
                        <label for="search_token">Key</label>
                        <input type="text" id="search_token" name="search_token" 
                               value="<?php echo esc_attr($search_token); ?>" 
                               placeholder="Enter key..." 
                               style="text-transform: uppercase;">
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="<?php echo site_url('/reset/check-in'); ?>" class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($show_results): ?>
            <?php if (empty($purchases)): ?>
                <div class="notice notice-error">
                    <p>No completed purchases found. Try adjusting your search criteria.</p>
                </div>
            <?php elseif (count($purchases) === 1): ?>
                <!-- Single result: Show as card -->
                <?php 
                $purchase = $purchases[0];
                $is_checked_in = intval($purchase['checked_in'] ?? 0);
                ?>
                <div class="purchase-card <?php echo $is_checked_in ? 'checked-in' : ''; ?>">
                    <div class="card-header">
                        <div class="key-display">
                            <span class="key-label">Key:</span>
                            <code class="key-code"><?php echo esc_html($purchase['token_code']); ?></code>
                        </div>
                        <?php if ($is_checked_in): ?>
                            <div class="checkin-status">✅ Checked In</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="customer-name"><?php echo esc_html($purchase['purchaser_name']); ?></h3>
                        <p class="customer-email"><?php echo esc_html($purchase['purchaser_email']); ?></p>
                        
                        <div class="customer-details-grid">
                            <div class="detail-row">
                                <span class="label">Ticket Type:</span>
                                <span class="value"><?php echo esc_html(get_friendly_ticket_type($purchase['ticket_type'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Add-ons:</span>
                                <span class="value"><?php echo esc_html($purchase['addon_details'] ?: 'No add-ons'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Drinks Count:</span>
                                <span class="value"><?php echo intval($purchase['total_drink_count']); ?></span>
                            </div>
                        </div>
                        
                        <div class="checkin-section">
                            <?php if (!$is_checked_in): ?>
                                <button type="button" class="btn-checkin" data-purchase-id="<?php echo esc_attr($purchase['id']); ?>">
                                    📋 Check In Customer
                                </button>
                            <?php else: ?>
                                <div class="checked-in-status">
                                    ✅ Customer Checked In
                                    <small>
                                        by <?php echo esc_html($purchase['checked_in_by']); ?> 
                                        at <?php echo date('M j, g:i A', strtotime($purchase['checked_in_at'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Multiple results: Show as table -->
                <div class="results-header">
                    <h3>Search Results (<?php echo count($purchases); ?> found)</h3>
                    <div class="view-toggle">
                        <button type="button" class="view-btn active" data-view="table" onclick="switchView('table')">📋 Table View</button>
                        <button type="button" class="view-btn" data-view="cards" onclick="switchView('cards')">🎴 Card View</button>
                    </div>
                </div>
                
                <!-- Table View -->
                <div id="tableView" class="view-container">
                    <table class="purchases-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Key</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $index => $purchase): ?>
                                <?php
                                $is_checked_in = intval($purchase['checked_in'] ?? 0);
                                ?>
                                <tr class="purchase-row <?php echo $is_checked_in ? 'checked-in' : ''; ?>" 
                                    id="row-<?php echo $index; ?>"
                                    data-index="<?php echo $index; ?>"
                                    data-purchase='<?php echo htmlspecialchars(json_encode($purchase), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <td>
                                        <strong><?php echo esc_html($purchase['purchaser_name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo esc_html($purchase['purchaser_email']); ?></small>
                                    </td>
                                    <td><code class="key-code-small"><?php echo esc_html($purchase['token_code']); ?></code></td>
                                    <td>
                                        <?php if ($is_checked_in): ?>
                                            <span class="checkin-status checkin-mobile">✅</span>
                                        <?php else: ?>
                                            <span class="not-check">❌</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-expand" data-index="<?php echo $index; ?>">
                                            <span class="expand-icon">▼</span> Manage
                                        </button>
                                    </td>
                                </tr>
                                <!-- Expandable Row Content -->
                                <tr class="expanded-row" id="expanded-<?php echo $index; ?>" style="display: none;">
                                    <td colspan="4">
                                        <div class="inline-card">
                                            <div class="inline-card-content" id="inline-card-<?php echo $index; ?>">
                                                <!-- Content will be injected here -->
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Card View -->
                <div id="cardView" class="view-container" style="display: none;">
                    <div class="cards-grid">
                        <?php foreach ($purchases as $purchase): ?>
                            <?php
                            $is_checked_in = intval($purchase['checked_in'] ?? 0);
                            ?>
                            <div class="purchase-card <?php echo $is_checked_in ? 'checked-in' : ''; ?>">
                                <div class="card-header">
                                    <div class="key-display">
                                        <span class="key-label">Key:</span>
                                        <code class="key-code"><?php echo esc_html($purchase['token_code']); ?></code>
                                    </div>
                                    <?php if ($is_checked_in): ?>
                                        <div class="checkin-status">✅ Checked In</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-content">
                                    <h3 class="customer-name"><?php echo esc_html($purchase['purchaser_name']); ?></h3>
                                    <p class="customer-email"><?php echo esc_html($purchase['purchaser_email']); ?></p>
                                    
                                    <div class="customer-details-grid">
                                        <div class="detail-row">
                                            <span class="label">Ticket Type:</span>
                                            <span class="value"><?php echo esc_html(get_friendly_ticket_type($purchase['ticket_type'])); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Add-ons:</span>
                                            <span class="value"><?php echo esc_html($purchase['addon_details'] ?: 'No add-ons'); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Drinks Count:</span>
                                            <span class="value"><?php echo intval($purchase['total_drink_count']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="checkin-section">
                                        <?php if (!$is_checked_in): ?>
                                            <button type="button" class="btn-checkin" data-purchase-id="<?php echo esc_attr($purchase['id']); ?>">
                                                📋 Check In Customer
                                            </button>
                                        <?php else: ?>
                                            <div class="checked-in-status">
                                                ✅ Customer Checked In
                                                <small>
                                                    by <?php echo esc_html($purchase['checked_in_by']); ?> 
                                                    at <?php echo date('M j, g:i A', strtotime($purchase['checked_in_at'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- No search entered: Show welcome message -->
            <div style="display: flex; justify-content: center;"> 
                <div class="welcome-message">
                    <div class="welcome-icon">📋</div>
                    <h3>Welcome to Event Check-In</h3>
                    <p>Enter a customer name or key above to search for attendees and manage check-in status.</p>
                    <div class="quick-tips">
                        <h4>Quick Tips:</h4>
                        <ul>
                            <li>🔑 Search by key for exact matches</li>
                            <li>👤 Search by name for multiple results</li>
                            <li>📋 Use Check-In button to mark attendance</li>
                            <li>✅ Green highlighting shows checked-in customers</li>
                            <li>📊 Capacity counter updates in real-time</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="actions back-to-sales-report">
            <a href="<?php echo admin_url('admin.php?page=reset-sales-report'); ?>" class="btn btn-primary">
                📊 Back to Sales Report
            </a>
        </div>
    </div>
    
    <script>
    // Check-in functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle clicks with event delegation
        document.addEventListener('click', function(e) {
            // Handle check-in button clicks
            if (e.target.classList.contains('btn-checkin')) {
                e.stopPropagation(); // Prevent row expansion
                
                const purchaseId = e.target.getAttribute('data-purchase-id');
                
                if (!purchaseId) return;
                
                updateCheckInStatus(purchaseId, e.target);
                return;
            }
            
            // Handle row expansion clicks
            const row = e.target.closest('.purchase-row');
            if (row && !e.target.closest('.btn-expand, .btn-checkin, input, button')) {
                const index = row.getAttribute('data-index');
                if (index !== null) {
                    toggleRowExpansion(parseInt(index));
                }
                return;
            }
            
            // Handle expand button clicks (keep existing functionality)
            if (e.target.closest('.btn-expand')) {
                e.stopPropagation(); // Prevent row click
                const button = e.target.closest('.btn-expand');
                const index = button.getAttribute('data-index');
                if (index !== null) {
                    toggleRowExpansion(parseInt(index));
                }
                return;
            }
        });
        
        // Auto-uppercase search inputs
        const searchInputs = document.querySelectorAll('#search_name, #search_token');
        searchInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
    });
    
    // AJAX function to update check-in status
    function updateCheckInStatus(purchaseId, buttonElement) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'reset_update_check_in');
        formData.append('purchase_id', purchaseId);
        formData.append('nonce', '<?php echo wp_create_nonce('reset_ajax_nonce'); ?>');
        
        // Show loading state
        const originalText = buttonElement.innerHTML;
        buttonElement.innerHTML = '⏳ Checking In...';
        buttonElement.disabled = true;
        buttonElement.style.opacity = '0.6';
        
        // Send AJAX request
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to show checked-in status
                updateCheckInUI(purchaseId, data.data);
                updateCapacityCounter();
                showNotification('Customer checked in successfully!', 'success');
            } else {
                // Revert button on error
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
                buttonElement.style.opacity = '1';
                showNotification('Error: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            // Revert button on error
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
            buttonElement.style.opacity = '1';
            showNotification('Network error during check-in', 'error');
            console.error('Error:', error);
        });
    }
    
    // Update UI to show checked-in status
    function updateCheckInUI(purchaseId, data) {
        // Find all check-in buttons for this purchase
        const buttons = document.querySelectorAll(`[data-purchase-id="${purchaseId}"]`);
        
        buttons.forEach(button => {
            const section = button.closest('.checkin-section');
            if (section) {
                section.innerHTML = `
                    <div class="checked-in-status">
                        ✅ Customer Checked In
                        <small>
                            by ${data.checked_in_by} 
                            at ${new Date(data.checked_in_at).toLocaleString()}
                        </small>
                    </div>
                `;
            }
        });
        
        // Update table row status if exists
        const tableRows = document.querySelectorAll('.purchase-row');
        tableRows.forEach(row => {
            const purchaseData = JSON.parse(row.getAttribute('data-purchase'));
            if (purchaseData.id == purchaseId) {
                row.classList.add('checked-in');
                const statusCell = row.querySelector('td:nth-child(3)');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="checkin-status checkin-mobile">✅</span>';
                }
            }
        });
        
        // Update card styling if exists
        const cards = document.querySelectorAll('.purchase-card');
        cards.forEach(card => {
            const button = card.querySelector(`[data-purchase-id="${purchaseId}"]`);
            if (button) {
                card.classList.add('checked-in');
                const header = card.querySelector('.card-header');
                if (header && !header.querySelector('.checkin-status')) {
                    header.innerHTML += '<div class="checkin-status">✅ Checked In</div>';
                }
            }
        });
    }
    
    // Update capacity counter
    function updateCapacityCounter() {
        const formData = new FormData();
        formData.append('action', 'reset_get_checkin_stats');
        formData.append('nonce', '<?php echo wp_create_nonce('reset_ajax_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const checkinCount = document.getElementById('checkin-count');
                const totalAttendees = document.getElementById('total-attendees');
                
                if (checkinCount) checkinCount.textContent = data.data.checked_in_count;
                if (totalAttendees) totalAttendees.textContent = data.data.total_attendees;
            }
        })
        .catch(error => {
            console.error('Error updating capacity counter:', error);
        });
    }
    
    // Show notification
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transition: all 0.3s ease;
        `;
        
        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        } else {
            notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Switch between table and card views
    function switchView(viewType) {
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');
        const tableBtn = document.querySelector('.view-btn[data-view="table"]');
        const cardBtn = document.querySelector('.view-btn[data-view="cards"]');
        
        // Remove active class from all buttons
        document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
        
        if (viewType === 'table') {
            tableView.style.display = 'block';
            cardView.style.display = 'none';
            tableBtn.classList.add('active');
            
            // Collapse any expanded rows when switching to table view
            document.querySelectorAll('.expanded-row').forEach(row => {
                row.style.display = 'none';
            });
            document.querySelectorAll('.expand-icon').forEach(icon => {
                icon.classList.remove('rotated');
            });
            
        } else if (viewType === 'cards') {
            tableView.style.display = 'none';
            cardView.style.display = 'block';
            cardBtn.classList.add('active');
        }
    }
    
    // Toggle row expansion in table view
    function toggleRowExpansion(index) {
        const expandedRow = document.getElementById(`expanded-${index}`);
        const mainRow = document.getElementById(`row-${index}`);
        const button = mainRow ? mainRow.querySelector('.btn-expand') : null;
        const expandIcon = button ? button.querySelector('.expand-icon') : null;
        const cardContent = document.getElementById(`inline-card-${index}`);
        
        if (!expandedRow || !mainRow) return;
        
        const isExpanded = expandedRow.style.display !== 'none';
        
        if (isExpanded) {
            // Collapse
            expandedRow.style.display = 'none';
            if (expandIcon) expandIcon.classList.remove('rotated');
            if (button) button.innerHTML = '<span class="expand-icon">▼</span> Manage';
        } else {
            // Expand
            expandedRow.style.display = 'table-row';
            if (expandIcon) expandIcon.classList.add('rotated');
            if (button) button.innerHTML = '<span class="expand-icon rotated">▼</span> Close';
            
            // Populate the inline card content
            const purchaseData = JSON.parse(mainRow.getAttribute('data-purchase'));
            populateInlineCard(cardContent, purchaseData);
            
            // Collapse other rows
            document.querySelectorAll('.expanded-row').forEach((row, i) => {
                if (row.id !== `expanded-${index}`) {
                    row.style.display = 'none';
                    const otherMainRow = document.getElementById(`row-${i}`);
                    const otherButton = otherMainRow ? otherMainRow.querySelector('.btn-expand') : null;
                    if (otherButton) {
                        const otherIcon = otherButton.querySelector('.expand-icon');
                        if (otherIcon) otherIcon.classList.remove('rotated');
                        otherButton.innerHTML = '<span class="expand-icon">▼</span> Manage';
                    }
                }
            });
        }
    }
    
    // JavaScript version of ticket type mapping
    function getFriendlyTicketType(ticketKey) {
        if (!ticketKey) {
            return 'Free Ticket';
        }
        
        // Mapping for ticket types (from purchases table)
        const ticketNames = {
            'general_early': 'Early Bird',
            'general_late': 'Late Bird',
            'general_very_late': 'Very Late Bird',
            'afterparty_package_1': 'Afterparty - Package 01',
            'afterparty_package_2': 'Afterparty - Package 02'
        };
        
        // Mapping for token types (from tokens table)
        const tokenTypeNames = {
            'normal': 'Normal Key',
            'free_ticket': 'Free Ticket Key',
            'polo_ordered': 'Polo Ordered Key',
            'sponsor': 'Sponsor Key',
            'invitation': 'Invitation Key'
        };
        
        // Check ticket types first, then token types
        if (ticketNames[ticketKey]) {
            return ticketNames[ticketKey];
        } else if (tokenTypeNames[ticketKey]) {
            return tokenTypeNames[ticketKey];
        }
        
        // Fallback for unrecognized values
        return ticketKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Free Ticket';
    }

    // Populate inline card content
    function populateInlineCard(container, purchaseData) {
        const isCheckedIn = parseInt(purchaseData.checked_in || 0);
        
        // Create add-ons HTML
        let addonsHtml = purchaseData.addon_details || 'No add-ons';
        
        container.innerHTML = `
            <div class="card-header">
                <div class="key-display">
                    <span class="key-label">Key:</span>
                    <code class="key-code">${purchaseData.token_code}</code>
                </div>
                ${isCheckedIn ? '<div class="checkin-status t">✅ Checked In</div>' : ''}
            </div>
            
            <div class="card-content">
                <h3 class="customer-name">${purchaseData.purchaser_name}</h3>
                <p class="customer-email">${purchaseData.purchaser_email}</p>
                
                <div class="customer-details-grid">
                    <div class="detail-row">
                        <span class="label">Ticket Type:</span>
                        <span class="value">${getFriendlyTicketType(purchaseData.ticket_type)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Add-ons:</span>
                        <span class="value">${addonsHtml}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Drinks Count:</span>
                        <span class="value">${purchaseData.total_drink_count || 0}</span>
                    </div>
                </div>
                
                <div class="checkin-section">
                    ${!isCheckedIn ? `
                        <button type="button" class="btn-checkin" data-purchase-id="${purchaseData.id}">
                            📋 Check In Customer
                        </button>
                    ` : `
                        <div class="checked-in-status">
                            ✅ Customer Checked In
                            <small>
                                by ${purchaseData.checked_in_by || 'Unknown'} 
                                at ${purchaseData.checked_in_at ? new Date(purchaseData.checked_in_at).toLocaleString() : 'Unknown time'}
                            </small>
                        </div>
                    `}
                </div>
            </div>
        `;
    }
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 