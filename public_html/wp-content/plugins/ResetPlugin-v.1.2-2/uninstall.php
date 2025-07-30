<?php
/**
 * RESET Event Ticketing System - Uninstall Script
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It cleans up all database tables and options created by the plugin.
 * 
 * @package ResetTicketing
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check - ensure this is a legitimate uninstall request
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Define table names
$tables = array(
    $wpdb->prefix . 'reset_tokens',
    $wpdb->prefix . 'reset_purchases',
    $wpdb->prefix . 'reset_email_logs',
    $wpdb->prefix . 'reset_ticket_types',
    $wpdb->prefix . 'reset_addons',
    $wpdb->prefix . 'reset_purchase_addons'
);

// Drop database tables
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
}

// Clean up plugin options
$options_to_delete = array(
    'reset_plugin_rewrite_flush_needed',
    'reset_plugin_version',
    'reset_plugin_db_version',
    'reset_plugin_settings',
    'reset_plugin_configuration',
    'reset_capacity_alerts',
    'reset_email_settings',
    'reset_payment_settings',
    'reset_early_bird_threshold',
    'reset_late_bird_threshold',
    'reset_very_late_bird_threshold',
    'reset_addon_settings'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Clean up transients (cached data)
$transients_to_delete = array(
    'reset_token_stats',
    'reset_sales_stats',
    'reset_dashboard_data',
    'reset_capacity_status',
    'reset_addon_stats',
    'reset_ticket_pricing',
    'reset_available_addons'
);

foreach ($transients_to_delete as $transient) {
    delete_transient($transient);
}

// Clean up user meta related to the plugin (if any)
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'reset_%'");

// Clean up post meta related to the plugin (if any)
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'reset_%'");

// Clear any remaining rewrite rules
flush_rewrite_rules();

// Optional: Send notification to admin about successful uninstall
if (function_exists('wp_mail')) {
    $admin_email = get_option('admin_email');
    $site_name = get_option('blogname');
    
    if ($admin_email) {
        $subject = sprintf('[%s] RESET Plugin Uninstalled', $site_name);
        $message = sprintf(
            'The RESET Event Ticketing System plugin has been successfully uninstalled from %s.

All database tables and plugin data have been removed:
- Token records
- Purchase records  
- Email logs
- Ticket type configurations
- Addon configurations
- Purchase addon records
- Plugin settings and options

This is an automated notification.',
            $site_name
        );
        
        wp_mail($admin_email, $subject, $message);
    }
} 