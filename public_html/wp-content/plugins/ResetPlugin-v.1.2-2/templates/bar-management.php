<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is admin
if (!is_user_logged_in()) {
    // Redirect to login page with return URL
    $login_url = wp_login_url(site_url('/reset/bar/'));
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

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'reset_bar_management_action')) {
        $message = 'Security check failed.';
        $message_type = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_drink_count':
                $purchase_id = intval($_POST['purchase_id']);
                $drink_count = intval($_POST['drink_count']);
                
                if ($purchase_id > 0) {
                    $result = $db->update_purchase_drink_count($purchase_id, $drink_count);
                    if ($result) {
                        $message = 'Drink count updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update drink count.';
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
$total_drinks = 0;
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
    $total_drinks = array_sum(array_column($purchases, 'total_drink_count'));
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drinks Management - RESET Event</title>
    
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
        
        .bar-management-container {
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
        
        .purchase-card.has-mismatch {
            border-color: #ffc107;
            background: #fff;
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
        
        .mismatch-warning {
            background: #ffc107;
            color: #000;
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
        
        .addons-section {
            margin-bottom: 25px;
        }
        
        .addons-section h4 {
            color: #000;
            margin: 0 0 10px 0;
            font-weight: 700;
            font-size: 1.2em;
        }
        
        .addon-item {
            color: #000;
            margin: 5px 0;
            font-size: 1.1em;
            line-height: 1.4;
        }
        
        .drinks-section {
            background: rgba(249, 198, 19, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #f9c613;
        }
        
        .drinks-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
        }
        
        .drinks-label {
            color: #000;
            font-weight: 700;
            font-size: 1.1em;
        }
        
        .drinks-value {
            color: #000;
            font-weight: 600;
            font-size: 1.2em;
        }
        
        .drinks-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-control {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
        }
        
        .btn-minus {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-minus:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
            transform: scale(1.1);
        }
        
        .btn-plus {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-plus:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            transform: scale(1.1);
        }
        
        .drinks-count {
            background: #000;
            color: #f9c613;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.3em;
            border: 2px solid #f9c613;
            min-width: 50px;
            text-align: center;
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
        
        /* Drinks Summary in Table */
        .drinks-summary {
            font-size: 0.9em;
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
        
        /* Prevent row click on interactive elements */
        .purchase-row .btn-control,
        .purchase-row .btn-expand,
        .purchase-row input,
        .purchase-row button {
            cursor: pointer;
        }
        
        /* Visual hint for clickable rows */
        .purchase-row td:first-child::before {
            content: "👆 ";
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 0.8em;
        }
        
        .purchase-row:hover td:first-child::before {
            opacity: 0.6;
        }
        
        /* Card Modal */
        .card-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            border: 2px solid #f9c613;
            border-radius: 15px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: flex-end;
        }
        
        .modal-close {
            color: #f9c613;
            font-size: 2em;
            cursor: pointer;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #ffdd44;
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
        
        .drink-edit-form {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .drink-count-input {
            width: 70px;
            padding: 8px 10px;
            border: 2px solid #444;
            border-radius: 6px;
            text-align: center;
            background: #000;
            color: #f9c613;
            font-weight: bold;
            font-size: 14px;
        }
        
        .drink-count-input:focus {
            outline: none;
            border-color: #f9c613;
            box-shadow: 0 0 0 2px rgba(249, 198, 19, 0.2);
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
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
            color: white;
            border: 2px solid #28a745;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #34ce57 0%, #28a745 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .mismatch {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.05) 100%) !important;
            border-left: 4px solid #ffc107;
        }
        
        .mismatch-indicator {
            color: #ffc107;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .back-to-sales-report {
            margin-top: 20px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1200px) {
            .bar-management-container {
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
            
            .bar-management-container {
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
            
            .drink-edit-form {
                flex-direction: column;
                gap: 8px;
            }
            
            .drink-count-input {
                width: 80px;
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
            
            .drinks-controls {
                gap: 10px;
            }
            
            .btn-control {
                width: 35px;
                height: 35px;
                font-size: 1.3em;
            }
            
            .drinks-count {
                padding: 6px 12px;
                font-size: 1.2em;
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
            
            .modal-content {
                margin: 10px;
                max-height: 95vh;
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
    <div class="bar-management-container">
        <div class="header">
            <h1>🍺 Drinks Management System</h1>
            <div class="actions">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('reset_bar_management_action'); ?>
                    <input type="hidden" name="action" value="recalculate_all">
                    <button type="submit" class="btn btn-secondary" 
                            onclick="return confirm('Recalculate drink counts for all purchases? This will overwrite manual changes.')">
                        🔄 Recalculate All
                    </button>
                </form>
                <a href="<?php echo admin_url('admin.php?page=reset-sales-report'); ?>" class="btn btn-primary">
                    📊 Back to Sales Report
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-form">
            <h3>🔍  Search Add-ons</h3>
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
                        <a href="<?php echo site_url('/reset/bar'); ?>" class="btn btn-secondary">Clear</a>
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
                $current_drinks = intval($purchase['total_drink_count'] ?? 0);
                $calculated_drinks = intval($purchase['calculated_drinks'] ?? 0);
                $has_mismatch = $current_drinks !== $calculated_drinks;
                ?>
                <div class="purchase-card <?php echo $has_mismatch ? 'has-mismatch' : ''; ?>">
                    <div class="card-header">
                        <div class="key-display">
                            <span class="key-label">Key:</span>
                            <code class="key-code"><?php echo esc_html($purchase['token_code']); ?></code>
                        </div>
                        <?php if ($has_mismatch): ?>
                            <div class="mismatch-warning">⚠️ Mismatch</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="customer-name"><?php echo esc_html($purchase['purchaser_name']); ?></h3>
                        <p class="customer-email"><?php echo esc_html($purchase['purchaser_email']); ?></p>
                        
                        <div class="addons-section">
                            <h4>Add-ons:</h4>
                            <?php if (!empty($purchase['addon_details'])): ?>
                                <?php 
                                $addon_list = explode(', ', $purchase['addon_details']);
                                foreach ($addon_list as $addon): ?>
                                    <div class="addon-item">• <?php echo esc_html($addon); ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="addon-item">• No add-ons</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="drinks-section">
                            <div class="drinks-row">
                                <span class="drinks-label">Current Drinks:</span>
                                <div class="drinks-controls">
                                    <button type="button" class="btn-control btn-minus" 
                                            data-purchase-id="<?php echo esc_attr($purchase['id']); ?>" 
                                            data-action="decrease">−</button>
                                    <span class="drinks-count" id="drinks-<?php echo esc_attr($purchase['id']); ?>"><?php echo $current_drinks; ?></span>
                                    <button type="button" class="btn-control btn-plus" 
                                            data-purchase-id="<?php echo esc_attr($purchase['id']); ?>" 
                                            data-action="increase">+</button>
                                </div>
                            </div>
                            <div class="drinks-row">
                                <span class="drinks-label">Calculated Drinks:</span>
                                <span class="drinks-value"><?php echo $calculated_drinks; ?></span>
                            </div>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $index => $purchase): ?>
                                <?php
                                $current_drinks = intval($purchase['total_drink_count'] ?? 0);
                                $calculated_drinks = intval($purchase['calculated_drinks'] ?? 0);
                                $has_mismatch = $current_drinks !== $calculated_drinks;
                                ?>
                                <tr class="purchase-row <?php echo $has_mismatch ? 'mismatch' : ''; ?>" 
                                    id="row-<?php echo $index; ?>"
                                    data-index="<?php echo $index; ?>"
                                    data-purchase='<?php echo htmlspecialchars(json_encode($purchase), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <td>
                                        <strong><?php echo esc_html($purchase['purchaser_name']); ?></strong><br>
                                        <small style="color: #666;"><?php echo esc_html($purchase['purchaser_email']); ?></small>
                                    </td>
                                    <td><code class="key-code-small"><?php echo esc_html($purchase['token_code']); ?></code></td>
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
                            $current_drinks = intval($purchase['total_drink_count'] ?? 0);
                            $calculated_drinks = intval($purchase['calculated_drinks'] ?? 0);
                            $has_mismatch = $current_drinks !== $calculated_drinks;
                            ?>
                            <div class="purchase-card <?php echo $has_mismatch ? 'has-mismatch' : ''; ?>">
                                <div class="card-header">
                                    <div class="key-display">
                                        <span class="key-label">Key:</span>
                                        <code class="key-code"><?php echo esc_html($purchase['token_code']); ?></code>
                                    </div>
                                    <?php if ($has_mismatch): ?>
                                        <div class="mismatch-warning">⚠️ Mismatch</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-content">
                                    <h3 class="customer-name"><?php echo esc_html($purchase['purchaser_name']); ?></h3>
                                    <p class="customer-email"><?php echo esc_html($purchase['purchaser_email']); ?></p>
                                    
                                    <div class="addons-section">
                                        <h4>Add-ons:</h4>
                                        <?php if (!empty($purchase['addon_details'])): ?>
                                            <?php 
                                            $addon_list = explode(', ', $purchase['addon_details']);
                                            foreach ($addon_list as $addon): ?>
                                                <div class="addon-item">• <?php echo esc_html($addon); ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="addon-item">• No add-ons</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="drinks-section">
                                        <div class="drinks-row">
                                            <span class="drinks-label">Current Drinks:</span>
                                            <div class="drinks-controls">
                                                <button type="button" class="btn-control btn-minus" 
                                                        data-purchase-id="<?php echo esc_attr($purchase['id']); ?>" 
                                                        data-action="decrease">−</button>
                                                <span class="drinks-count" id="drinks-<?php echo esc_attr($purchase['id']); ?>"><?php echo $current_drinks; ?></span>
                                                <button type="button" class="btn-control btn-plus" 
                                                        data-purchase-id="<?php echo esc_attr($purchase['id']); ?>" 
                                                        data-action="increase">+</button>
                                            </div>
                                        </div>
                                        <div class="drinks-row">
                                            <span class="drinks-label">Calculated Drinks:</span>
                                            <span class="drinks-value"><?php echo $calculated_drinks; ?></span>
                                        </div>
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
                    <div class="welcome-icon">🔍</div>
                    <h3>Welcome to Drinks Management</h3>
                    <p>Enter a customer name or key above to search for purchases and manage drink counts.</p>
                    <div class="quick-tips">
                        <h4>Quick Tips:</h4>
                        <ul>
                            <li>🔑 Search by key for exact matches</li>
                            <li>👤 Search by name for multiple results</li>
                            <li>➕➖ Use +/- buttons to adjust drink counts</li>
                            <li>⚠️ Yellow highlighting shows mismatches</li>
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
    // AJAX functionality for plus/minus buttons and row expansion
    document.addEventListener('DOMContentLoaded', function() {
        // Handle clicks with event delegation
        document.addEventListener('click', function(e) {
            // Handle plus/minus button clicks
            if (e.target.classList.contains('btn-control')) {
                e.stopPropagation(); // Prevent row expansion
                
                const purchaseId = e.target.getAttribute('data-purchase-id');
                const action = e.target.getAttribute('data-action');
                const countElement = document.getElementById('drinks-' + purchaseId);
                
                if (!purchaseId || !countElement) return;
                
                let currentCount = parseInt(countElement.textContent);
                let newCount = currentCount;
                
                if (action === 'increase') {
                    newCount = Math.min(currentCount + 1, 99);
                } else if (action === 'decrease') {
                    newCount = Math.max(currentCount - 1, 0);
                }
                
                if (newCount !== currentCount) {
                    updateDrinkCount(purchaseId, newCount, countElement);
                }
                return;
            }
            
            // Handle row expansion clicks
            const row = e.target.closest('.purchase-row');
            if (row && !e.target.closest('.btn-expand, .btn-control, input, button')) {
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
    
    // AJAX function to update drink count
    function updateDrinkCount(purchaseId, newCount, countElement) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'reset_update_drink_count');
        formData.append('purchase_id', purchaseId);
        formData.append('drink_count', newCount);
        formData.append('nonce', '<?php echo wp_create_nonce('reset_ajax_nonce'); ?>');
        
        // Show loading state
        const originalText = countElement.textContent;
        countElement.textContent = '...';
        countElement.style.opacity = '0.6';
        
        // Send AJAX request
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                countElement.textContent = newCount;
                countElement.style.opacity = '1';
                
                // Show success feedback
                showNotification('Drink count updated!', 'success');
                
                // Update mismatch styling if needed
                updateMismatchStyling(purchaseId, newCount, data.calculated_drinks);
            } else {
                // Revert on error
                countElement.textContent = originalText;
                countElement.style.opacity = '1';
                showNotification('Error updating drink count: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            // Revert on error
            countElement.textContent = originalText;
            countElement.style.opacity = '1';
            showNotification('Network error updating drink count', 'error');
            console.error('Error:', error);
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
    
    // Update mismatch styling
    function updateMismatchStyling(purchaseId, currentDrinks, calculatedDrinks) {
        const card = document.querySelector('.purchase-card');
        if (card) {
            const hasMismatch = currentDrinks !== calculatedDrinks;
            if (hasMismatch) {
                card.classList.add('has-mismatch');
            } else {
                card.classList.remove('has-mismatch');
            }
        }
    }
    
    // Switch between table and card views
    function switchView(viewType) {
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');
        const tableBtnOld = document.querySelector('.view-btn[data-view="table"]');
        const cardBtnOld = document.querySelector('.view-btn[data-view="cards"]');
        
        // Remove active class from all buttons
        document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
        
        if (viewType === 'table') {
            tableView.style.display = 'block';
            cardView.style.display = 'none';
            tableBtnOld.classList.add('active');
            
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
            cardBtnOld.classList.add('active');
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
    
    // Populate inline card content
    function populateInlineCard(container, purchaseData) {
        const currentDrinks = parseInt(purchaseData.total_drink_count || 0);
        const calculatedDrinks = parseInt(purchaseData.calculated_drinks || 0);
        const hasMismatch = currentDrinks !== calculatedDrinks;
        
        // Create add-ons HTML
        let addonsHtml = '';
        if (purchaseData.addon_details) {
            const addonList = purchaseData.addon_details.split(', ');
            addonsHtml = addonList.map(addon => `<div class="addon-item">• ${addon}</div>`).join('');
        } else {
            addonsHtml = '<div class="addon-item">• No add-ons</div>';
        }
        
        container.innerHTML = `
            <div class="card-header">
                <div class="key-display">
                    <span class="key-label">Key:</span>
                    <code class="key-code">${purchaseData.token_code}</code>
                </div>
                ${hasMismatch ? '<div class="mismatch-warning">⚠️ Mismatch</div>' : ''}
            </div>
            
            <div class="card-content">
                <h3 class="customer-name">${purchaseData.purchaser_name}</h3>
                <p class="customer-email">${purchaseData.purchaser_email}</p>
                
                <div class="addons-section">
                    <h4>Add-ons:</h4>
                    ${addonsHtml}
                </div>
                
                <div class="drinks-section">
                    <div class="drinks-row">
                        <span class="drinks-label">Current Drinks:</span>
                        <div class="drinks-controls">
                            <button type="button" class="btn-control btn-minus" 
                                    data-purchase-id="${purchaseData.id}" 
                                    data-action="decrease">−</button>
                            <span class="drinks-count" id="drinks-${purchaseData.id}">${currentDrinks}</span>
                            <button type="button" class="btn-control btn-plus" 
                                    data-purchase-id="${purchaseData.id}" 
                                    data-action="increase">+</button>
                        </div>
                    </div>
                    <div class="drinks-row">
                        <span class="drinks-label">Calculated Drinks:</span>
                        <span class="drinks-value">${calculatedDrinks}</span>
                    </div>
                </div>
            </div>
        `;
    }
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 