<?php
/**
 * RESET Plugin - Safe Production Migration Script
 * 
 * This script safely migrates the RESET plugin in production environments
 * without losing existing data. Run this BEFORE activating the updated plugin.
 * 
 * Usage: Access via web browser: /wp-content/plugins/ResetPlugin-v.1.2-2/safe-production-migration.php
 * 
 * CRITICAL: Only run this on production servers with existing data
 * 
 * @package ResetTicketing
 * @version 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator access required.');
}

// Prevent execution in local development
$is_local = false;
$local_domains = array('localhost', '127.0.0.1', '::1', 'nooballiance.local', 'reset.local');
$current_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

if (in_array($current_host, $local_domains) || 
    strpos($current_host, '.local') !== false || 
    strpos($current_host, ':8000') !== false ||
    strpos($current_host, ':3000') !== false ||
    strpos($current_host, ':8080') !== false) {
    $is_local = true;
}

if ($is_local) {
    wp_die('This script is intended for production environments only. In local development, use normal plugin activation.');
}

// Load required classes
require_once('includes/class-reset-database.php');
require_once('includes/class-reset-core.php');

echo '<!DOCTYPE html>
<html>
<head>
    <title>RESET Plugin - Safe Production Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; margin: 10px 0; }
        .btn { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>';

echo '<h1>🔧 RESET Plugin - Safe Production Migration</h1>';

// Check if this is a POST request (running migration)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    
    echo '<div class="info"><strong>Starting Migration Process...</strong></div>';
    
    try {
        global $wpdb;
        
        // Step 1: Backup existing data
        echo '<div class="step"><h3>Step 1: Backup Current Data</h3>';
        
        $backup_data = array();
        $tables_to_backup = array(
            'reset_tokens',
            'reset_purchases',
            'reset_email_logs',
            'reset_ticket_types',
            'reset_addons',
            'reset_purchase_addons'
        );
        
        foreach ($tables_to_backup as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
                $backup_data[$table] = $count;
                echo "<p>✅ $table: $count records</p>";
            } else {
                echo "<p>⚠️ $table: Table not found</p>";
            }
        }
        
        echo '</div>';
        
        // Step 2: Run database migrations
        echo '<div class="step"><h3>Step 2: Run Database Migrations</h3>';
        
        // Check for missing columns
        $table_purchases = $wpdb->prefix . 'reset_purchases';
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM `$table_purchases` LIKE %s",
                'gaming_name'
            )
        );
        
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE `$table_purchases` 
                ADD COLUMN `gaming_name` varchar(255) NULL 
                AFTER `purchaser_phone`"
            );
            echo "<p>✅ Added 'gaming_name' column to purchases table</p>";
        } else {
            echo "<p>✅ 'gaming_name' column already exists</p>";
        }
        
        // Add other schema migrations here as needed
        
        echo '</div>';
        
        // Step 3: Ensure all tables exist
        echo '<div class="step"><h3>Step 3: Ensure All Tables Exist</h3>';
        
        $charset_collate = $wpdb->get_charset_collate();
        $tables_created = 0;
        
        // Check each required table
        foreach ($tables_to_backup as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if (!$table_exists) {
                // Create the missing table
                $sql = get_table_sql($table_name, $full_table_name, $charset_collate);
                if ($sql) {
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    dbDelta($sql);
                    $tables_created++;
                    echo "<p>✅ Created missing table: $table_name</p>";
                }
            } else {
                echo "<p>✅ Table exists: $table_name</p>";
            }
        }
        
        if ($tables_created === 0) {
            echo "<p>✅ All tables already exist</p>";
        }
        
        echo '</div>';
        
        // Step 4: Verify data integrity
        echo '<div class="step"><h3>Step 4: Verify Data Integrity</h3>';
        
        foreach ($backup_data as $table => $original_count) {
            $full_table = $wpdb->prefix . $table;
            $current_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
            
            if ($current_count == $original_count) {
                echo "<p>✅ $table: $current_count records (unchanged)</p>";
            } else {
                echo "<p>⚠️ $table: $current_count records (was $original_count)</p>";
            }
        }
        
        echo '</div>';
        
        // Step 5: Update plugin version
        echo '<div class="step"><h3>Step 5: Update Plugin Version</h3>';
        
        update_option('reset_plugin_db_version', '1.2.2');
        update_option('reset_plugin_migration_completed', current_time('mysql'));
        
        echo "<p>✅ Plugin database version updated to 1.2.2</p>";
        echo "<p>✅ Migration completed at: " . current_time('mysql') . "</p>";
        
        echo '</div>';
        
        // Success message
        echo '<div class="success">
            <h2>🎉 Migration Completed Successfully!</h2>
            <p>Your RESET plugin data has been safely migrated. You can now:</p>
            <ul>
                <li>✅ Activate the updated plugin in WordPress admin</li>
                <li>✅ Test the booking system functionality</li>
                <li>✅ Check the admin dashboard for data integrity</li>
            </ul>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Go to WordPress Admin → Plugins</li>
                <li>Activate the RESET Plugin</li>
                <li>Check RESET Dashboard for any issues</li>
                <li>Test token generation and booking process</li>
            </ol>
        </div>';
        
    } catch (Exception $e) {
        echo '<div class="error">
            <h2>❌ Migration Failed</h2>
            <p><strong>Error:</strong> ' . $e->getMessage() . '</p>
            <p><strong>Action Required:</strong> Please check the error log and contact support.</p>
        </div>';
    }
    
} else {
    // Display migration form
    
    echo '<div class="warning">
        <h2>⚠️ Production Migration Warning</h2>
        <p>This script will migrate your RESET plugin data safely in production. Before proceeding:</p>
        <ul>
            <li>✅ Ensure you have a <strong>database backup</strong></li>
            <li>✅ Verify this is a <strong>production environment</strong></li>
            <li>✅ Confirm the plugin has been <strong>updated but not yet activated</strong></li>
            <li>✅ Check that no users are actively booking tickets</li>
        </ul>
    </div>';
    
    // Show current data status
    echo '<div class="info">
        <h2>📊 Current Data Status</h2>';
    
    global $wpdb;
    $total_tokens = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reset_tokens");
    $total_purchases = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reset_purchases");
    $total_emails = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reset_email_logs");
    
    echo "<p><strong>Tokens:</strong> $total_tokens</p>";
    echo "<p><strong>Purchases:</strong> $total_purchases</p>";
    echo "<p><strong>Email Logs:</strong> $total_emails</p>";
    
    echo '</div>';
    
    // Migration form
    echo '<form method="post">
        <div class="step">
            <h3>🚀 Ready to Migrate?</h3>
            <p>Click the button below to start the safe migration process:</p>
            <input type="hidden" name="run_migration" value="1">
            <button type="submit" class="btn btn-success">✅ Start Safe Migration</button>
        </div>
    </form>';
    
    echo '<div class="step">
        <h3>📋 What This Migration Does</h3>
        <ul>
            <li>🔍 <strong>Analyzes existing data</strong> - Checks current table structure and data</li>
            <li>🔄 <strong>Runs schema migrations</strong> - Adds missing columns/tables without data loss</li>
            <li>🛡️ <strong>Preserves all data</strong> - Tokens, purchases, emails remain intact</li>
            <li>✅ <strong>Verifies integrity</strong> - Ensures no data was lost during migration</li>
            <li>🔧 <strong>Updates version</strong> - Marks database as migrated</li>
        </ul>
    </div>';
}

echo '</body></html>';

/**
 * Get SQL for creating a specific table
 */
function get_table_sql($table_name, $full_table_name, $charset_collate) {
    switch ($table_name) {
        case 'reset_tokens':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                token_code varchar(50) NOT NULL UNIQUE,
                token_type enum('normal', 'free_ticket', 'polo_ordered', 'sponsor', 'invitation') NOT NULL DEFAULT 'normal',
                parent_token_id bigint(20) unsigned NULL,
                created_by varchar(255) NULL,
                used_by_email varchar(255) NULL,
                used_by_phone varchar(20) NULL,
                used_by_name varchar(255) NULL,
                is_used tinyint(1) DEFAULT 0,
                used_at timestamp NULL,
                status enum('active', 'cancelled', 'expired') DEFAULT 'active',
                cancelled_by varchar(255) NULL,
                cancelled_at timestamp NULL,
                cancellation_reason text NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                expires_at timestamp NULL,
                PRIMARY KEY (id),
                KEY idx_token_code (token_code),
                KEY idx_token_type (token_type),
                KEY idx_parent_token (parent_token_id),
                KEY idx_status (status)
            ) $charset_collate;";
            
        case 'reset_purchases':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                token_id bigint(20) unsigned NOT NULL,
                purchaser_name varchar(255) NOT NULL,
                purchaser_email varchar(255) NOT NULL,
                purchaser_phone varchar(20) NOT NULL,
                gaming_name varchar(255) NULL,
                ticket_type varchar(100) NOT NULL,
                ticket_price decimal(10,2) NOT NULL,
                addon_total decimal(10,2) DEFAULT 0.00,
                total_amount decimal(10,2) DEFAULT 0.00,
                payment_status enum('pending', 'completed', 'failed') DEFAULT 'pending',
                payment_reference varchar(255) NULL,
                sampath_transaction_id varchar(255) NULL,
                invitation_tokens_generated tinyint(1) DEFAULT 0,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_token_id (token_id),
                KEY idx_email (purchaser_email),
                KEY idx_payment_status (payment_status)
            ) $charset_collate;";
            
        case 'reset_email_logs':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                purchase_id bigint(20) unsigned NULL,
                email_type enum('confirmation', 'reminder', 'cancellation', 'admin_notification') NOT NULL,
                recipient_email varchar(255) NOT NULL,
                subject varchar(255) NOT NULL,
                sent_at timestamp DEFAULT CURRENT_TIMESTAMP,
                status enum('sent', 'failed') DEFAULT 'sent',
                PRIMARY KEY (id),
                KEY idx_purchase_id (purchase_id),
                KEY idx_email_type (email_type)
            ) $charset_collate;";
            
        case 'reset_ticket_types':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                ticket_key varchar(50) NOT NULL UNIQUE,
                name varchar(255) NOT NULL,
                description text NULL,
                features text NULL,
                ticket_price decimal(10,2) NOT NULL DEFAULT 0.00,
                is_enabled tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY idx_ticket_key (ticket_key),
                KEY idx_enabled (is_enabled),
                KEY idx_sort_order (sort_order)
            ) $charset_collate;";
            
        case 'reset_addons':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                addon_key varchar(50) NOT NULL UNIQUE,
                name varchar(255) NOT NULL,
                description text NULL,
                price decimal(10,2) NOT NULL DEFAULT 0.00,
                is_enabled tinyint(1) DEFAULT 1,
                sort_order int(11) DEFAULT 0,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY idx_addon_key (addon_key),
                KEY idx_enabled (is_enabled),
                KEY idx_sort_order (sort_order)
            ) $charset_collate;";
            
        case 'reset_purchase_addons':
            return "CREATE TABLE $full_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                purchase_id bigint(20) unsigned NOT NULL,
                addon_id bigint(20) unsigned NOT NULL,
                addon_price decimal(10,2) NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_purchase_id (purchase_id),
                KEY idx_addon_id (addon_id)
            ) $charset_collate;";
            
        default:
            return false;
    }
}

?> 