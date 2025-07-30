<?php
/**
 * Plugin Name: RESET Event Ticketing System
 * Plugin URI: https://nooballiance.lk/
 * Description: Krey-based invitation-only ticketing system for the RESET Esports event
 * Version: 1.0.0
 * Author: Noob Alliance
 * Author URI: https://nooballiance.lk/
 * Text Domain: reset-ticketing
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7.2
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RESET_PLUGIN_VERSION', '1.0.0');
define('RESET_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RESET_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RESET_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define database version for migrations
define('RESET_DB_VERSION', '1.3.9');

// Define event constants
define('RESET_EVENT_DATE', '2025-07-27');
define('RESET_TARGET_CAPACITY', 500);
define('RESET_MAX_CAPACITY', 600);
define('RESET_ALERT_THRESHOLD', 450);

/**
 * Main plugin class
 */
class ResetTicketingPlugin {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Hook into WordPress actions
        add_action('init', array($this, 'handle_early_payment_callback'), 5); // Run early
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('init', array($this, 'initialize_plugin'));
        add_action('init', array($this, 'register_email_campaign_cpt'));
        add_action('wp', array($this, 'schedule_cron_jobs'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_exports'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_custom_pages'));
        add_action('wp_ajax_reset_validate_token', array($this, 'ajax_validate_token'));
        add_action('wp_ajax_nopriv_reset_validate_token', array($this, 'ajax_validate_token'));
        add_action('wp_ajax_reset_process_booking', array($this, 'ajax_process_booking'));
        add_action('wp_ajax_nopriv_reset_process_booking', array($this, 'ajax_process_booking'));
        
        // New step-wise booking AJAX actions
        add_action('wp_ajax_reset_save_step_data', array($this, 'ajax_save_step_data'));
        add_action('wp_ajax_nopriv_reset_save_step_data', array($this, 'ajax_save_step_data'));
        
        // Capacity management AJAX actions
        add_action('wp_ajax_reset_preview_capacity_impact', array($this, 'ajax_preview_capacity_impact'));
        
        // Capacity management form handlers
        add_action('admin_post_update_capacity_settings', array($this, 'handle_update_capacity_settings'));
        add_action('admin_post_update_ticket_thresholds', array($this, 'handle_update_ticket_thresholds'));
        add_action('admin_post_reset_to_defaults', array($this, 'handle_reset_to_defaults'));
        add_action('admin_post_recreate_database_tables', array($this, 'handle_recreate_database_tables'));
        
        // Add new migration handlers
        add_action('admin_post_run_database_migration', array($this, 'handle_run_database_migration'));
        add_action('admin_post_force_recreate_tables', array($this, 'handle_force_recreate_tables'));
        
        add_action('wp_ajax_reset_process_free_booking', array($this, 'ajax_process_free_booking'));
        add_action('wp_ajax_nopriv_reset_process_free_booking', array($this, 'ajax_process_free_booking'));
        
        add_action('wp_ajax_reset_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_reset_process_payment', array($this, 'ajax_process_payment'));
        
        add_action('wp_ajax_reset_initialize_wizard', array($this, 'ajax_initialize_wizard'));
        add_action('wp_ajax_nopriv_reset_initialize_wizard', array($this, 'ajax_initialize_wizard'));
        
        // Register cron jobs  
        add_action('reset_reminder_emails', array($this, 'send_reminder_emails'));
        
        // Add temporary debug endpoint
        add_action('wp_ajax_reset_debug_gateway', array($this, 'ajax_debug_gateway'));
        add_action('wp_ajax_nopriv_reset_debug_gateway', array($this, 'ajax_debug_gateway'));
        
        // Bar Management AJAX
        add_action('wp_ajax_reset_update_drink_count', array($this, 'ajax_update_drink_count'));
        add_action('wp_ajax_nopriv_reset_update_drink_count', array($this, 'ajax_update_drink_count'));
        
        // Check-In AJAX
        add_action('wp_ajax_reset_update_check_in', array($this, 'ajax_update_check_in'));
        add_action('wp_ajax_nopriv_reset_update_check_in', array($this, 'ajax_update_check_in'));
        add_action('wp_ajax_reset_get_checkin_stats', array($this, 'ajax_get_checkin_stats'));
        add_action('wp_ajax_nopriv_reset_get_checkin_stats', array($this, 'ajax_get_checkin_stats'));
        
        // Hook the flush rewrite rules on plugin activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('reset-ticketing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Plugin activation - now uses smart migration system
     */
    public function activate() {
        // Clear any opcode cache to ensure fresh code
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Set flag to flush rewrite rules on next load
        update_option('reset_plugin_rewrite_flush_needed', true);
        
        // Load migration class
        require_once(RESET_PLUGIN_PATH . 'includes/class-reset-migration.php');
        
        // Run smart database migration (only creates/updates what's needed)
        $migration = ResetMigration::getInstance();
        $migration->check_and_migrate();
        
        // Fix capacity table constraint issue (for existing installations)
        $migration->fix_capacity_table_constraint();
        
        // Add rewrite rules and flush
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('reset_reminder_emails');
        wp_clear_scheduled_hook('reset_capacity_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin components
     */
    public function initialize_plugin() {
        // Check if rewrite rules need flushing for new payment-return URL
        if (get_option('reset_plugin_rewrite_flush_needed', false)) {
            $this->add_rewrite_rules();
            flush_rewrite_rules();
            delete_option('reset_plugin_rewrite_flush_needed');
        }
        
        // Load dependencies
        $this->load_dependencies();
        
        // Check and run database migrations
        if (class_exists('ResetMigration')) {
            $migration = ResetMigration::getInstance();
            $migration->check_and_migrate();
            
            // Fix capacity table constraint issue (for existing installations)
            $migration->fix_capacity_table_constraint();
        }
        
        // Initialize core services
        if (class_exists('ResetCore')) {
            ResetCore::getInstance();
        }
        
        if (class_exists('ResetDatabase')) {
            ResetDatabase::getInstance();
        }
        
        if (class_exists('ResetTokens')) {
            ResetTokens::getInstance();
        }
        
        if (class_exists('ResetPayments')) {
            ResetPayments::getInstance();
        }
        
        if (class_exists('ResetEmails')) {
            ResetEmails::getInstance();
        }
        
        if (class_exists('ResetEmailCampaigns')) {
            ResetEmailCampaigns::getInstance();
        }
        
        if (class_exists('ResetSampathGateway')) {
            ResetSampathGateway::getInstance();
        }
        
        if (class_exists('ResetAdmin')) {
            ResetAdmin::getInstance();
        }
        
        // Initialize new step-wise booking classes
        if (class_exists('ResetAddons')) {
            ResetAddons::getInstance();
        }
        
        if (class_exists('ResetCapacity')) {
            ResetCapacity::getInstance();
        }
        
        if (class_exists('ResetBookingWizard')) {
            ResetBookingWizard::getInstance();
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        $includes_path = RESET_PLUGIN_PATH . 'includes/';
        
        if (file_exists($includes_path . 'class-reset-core.php')) {
            require_once $includes_path . 'class-reset-core.php';
        }
        if (file_exists($includes_path . 'class-reset-database.php')) {
            require_once $includes_path . 'class-reset-database.php';
        }
        if (file_exists($includes_path . 'class-reset-tokens.php')) {
            require_once $includes_path . 'class-reset-tokens.php';
        }
        if (file_exists($includes_path . 'class-reset-sampath-gateway.php')) {
            require_once $includes_path . 'class-reset-sampath-gateway.php';
        }
        if (file_exists($includes_path . 'class-reset-payments.php')) {
            require_once $includes_path . 'class-reset-payments.php';
        }
        if (file_exists($includes_path . 'class-reset-emails.php')) {
            require_once $includes_path . 'class-reset-emails.php';
        }
        if (file_exists($includes_path . 'class-reset-email-campaigns.php')) {
            require_once $includes_path . 'class-reset-email-campaigns.php';
        }
        
        // Load new step-wise booking classes
        if (file_exists($includes_path . 'class-reset-addons.php')) {
            require_once $includes_path . 'class-reset-addons.php';
        }
        if (file_exists($includes_path . 'class-reset-capacity.php')) {
            require_once $includes_path . 'class-reset-capacity.php';
        }
        if (file_exists($includes_path . 'class-reset-booking-wizard.php')) {
            require_once $includes_path . 'class-reset-booking-wizard.php';
        }
        if (file_exists($includes_path . 'class-reset-carousel.php')) {
            require_once $includes_path . 'class-reset-carousel.php';
        }
        if (file_exists($includes_path . 'class-reset-migration.php')) {
            require_once $includes_path . 'class-reset-migration.php';
        }
        
        if (is_admin() && file_exists($includes_path . 'class-reset-admin.php')) {
            require_once $includes_path . 'class-reset-admin.php';
        }
    }
    
    /**
     * Register Email Campaign Custom Post Type
     */
    public function register_email_campaign_cpt() {
        $labels = array(
            'name'                  => _x('Email Campaigns', 'Post Type General Name', 'reset-ticketing'),
            'singular_name'         => _x('Email Campaign', 'Post Type Singular Name', 'reset-ticketing'),
            'menu_name'             => __('Email Campaigns', 'reset-ticketing'),
            'name_admin_bar'        => __('Email Campaign', 'reset-ticketing'),
            'archives'              => __('Campaign Archives', 'reset-ticketing'),
            'attributes'            => __('Campaign Attributes', 'reset-ticketing'),
            'parent_item_colon'     => __('Parent Campaign:', 'reset-ticketing'),
            'all_items'             => __('All Campaigns', 'reset-ticketing'),
            'add_new_item'          => __('Add New Campaign', 'reset-ticketing'),
            'add_new'               => __('Add New', 'reset-ticketing'),
            'new_item'              => __('New Campaign', 'reset-ticketing'),
            'edit_item'             => __('Edit Campaign', 'reset-ticketing'),
            'update_item'           => __('Update Campaign', 'reset-ticketing'),
            'view_item'             => __('View Campaign', 'reset-ticketing'),
            'view_items'            => __('View Campaigns', 'reset-ticketing'),
            'search_items'          => __('Search Campaign', 'reset-ticketing'),
            'not_found'             => __('Not found', 'reset-ticketing'),
            'not_found_in_trash'    => __('Not found in Trash', 'reset-ticketing'),
            'featured_image'        => __('Featured Image', 'reset-ticketing'),
            'set_featured_image'    => __('Set featured image', 'reset-ticketing'),
            'remove_featured_image' => __('Remove featured image', 'reset-ticketing'),
            'use_featured_image'    => __('Use as featured image', 'reset-ticketing'),
            'insert_into_item'      => __('Insert into campaign', 'reset-ticketing'),
            'uploaded_to_this_item' => __('Uploaded to this campaign', 'reset-ticketing'),
            'items_list'            => __('Campaigns list', 'reset-ticketing'),
            'items_list_navigation' => __('Campaigns list navigation', 'reset-ticketing'),
            'filter_items_list'     => __('Filter campaigns list', 'reset-ticketing'),
        );
        
        $args = array(
            'label'                 => __('Email Campaign', 'reset-ticketing'),
            'description'           => __('Email campaign management for RESET events', 'reset-ticketing'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'custom-fields'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false, // We'll add this to RESET admin menu manually
            'menu_position'         => null,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => false,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => array(
                'create_posts'       => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
                'delete_posts'       => 'manage_options',
                'delete_others_posts'=> 'manage_options',
            ),
            'show_in_rest'          => false,
        );
        
        register_post_type('reset_email_campaign', $args);
    }
    
    /**
     * Create database tables with new key system
     */
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // keys table with new key types
        $table_tokens = $wpdb->prefix . 'reset_tokens';
        $sql_tokens = "CREATE TABLE $table_tokens (
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
        
        // Purchases table with addon support
        $table_purchases = $wpdb->prefix . 'reset_purchases';
        $sql_purchases = "CREATE TABLE $table_purchases (
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
        
        // Email logs table
        $table_email_logs = $wpdb->prefix . 'reset_email_logs';
        $sql_email_logs = "CREATE TABLE $table_email_logs (
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
        
        // Ticket types table
        $table_ticket_types = $wpdb->prefix . 'reset_ticket_types';
        $sql_ticket_types = "CREATE TABLE $table_ticket_types (
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
        
        // Addons table
        $table_addons = $wpdb->prefix . 'reset_addons';
        $sql_addons = "CREATE TABLE $table_addons (
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
        
        // Purchase addons junction table
        $table_purchase_addons = $wpdb->prefix . 'reset_purchase_addons';
        $sql_purchase_addons = "CREATE TABLE $table_purchase_addons (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            purchase_id bigint(20) unsigned NOT NULL,
            addon_id bigint(20) unsigned NOT NULL,
            addon_price decimal(10,2) NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_purchase_id (purchase_id),
            KEY idx_addon_id (addon_id)
        ) $charset_collate;";
        
        // Email campaign recipients table
        $table_email_recipients = $wpdb->prefix . 'reset_email_recipients';
        $sql_email_recipients = "CREATE TABLE $table_email_recipients (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            recipient_email varchar(255) NOT NULL,
            recipient_name varchar(255) NULL,
            token_code varchar(255) NULL,
            source enum('filtered','manual') DEFAULT 'filtered',
            status enum('pending','sent','failed') DEFAULT 'pending',
            sent_at timestamp NULL,
            error_message text NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_campaign_id (campaign_id),
            KEY idx_status (status),
            KEY idx_email (recipient_email),
            KEY idx_campaign_status (campaign_id, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tokens);
        dbDelta($sql_purchases);
        dbDelta($sql_email_logs);
        dbDelta($sql_ticket_types);
        dbDelta($sql_addons);
        dbDelta($sql_purchase_addons);
        dbDelta($sql_email_recipients);
        
        // Run database migrations for existing installations
        $this->migrate_database_schema();
        
        // Migrate initial ticket types and addons (only if tables are empty)
        $this->migrate_initial_ticket_types();
        $this->migrate_initial_addons();
    }
    
    /**
     * Migrate initial ticket types from hardcoded data
     */
    private function migrate_initial_ticket_types() {
        // Load the database class if not already loaded
        if (!class_exists('ResetDatabase')) {
            require_once(RESET_PLUGIN_PATH . 'includes/class-reset-database.php');
        }
        
        $database = ResetDatabase::getInstance();
        
        // Check if tickets already exist
        $existing_tickets = $database->get_all_ticket_types();
        if (!empty($existing_tickets)) {
            return; // Already migrated
        }
        
        // Initial ticket types (main tickets only, addons are separate)
        $initial_tickets = array(
            array(
                'ticket_key' => 'general_early',
                'name' => 'Early Bird',
                'description' => 'Early bird special pricing for general admission',
                'features' => '500/= Off polo, free wristband',
                'ticket_price' => 1500.00,
                'is_enabled' => 1,
                'sort_order' => 10
            ),
            array(
                'ticket_key' => 'general_late',
                'name' => 'Late Bird',
                'description' => 'Late bird pricing for general admission',
                'features' => '500/= off polo & 500 off event activities',
                'ticket_price' => 3000.00,
                'is_enabled' => 1,
                'sort_order' => 20
            ),
            array(
                'ticket_key' => 'general_very_late',
                'name' => 'Very Late Bird',
                'description' => 'Very late bird pricing for general admission',
                'features' => '500/= off polo & 1000 off event activities & DDS photo',
                'ticket_price' => 4500.00,
                'is_enabled' => 1,
                'sort_order' => 30
            )
        );
        
        // Insert initial ticket types
        foreach ($initial_tickets as $ticket) {
            $database->create_ticket_type($ticket);
        }
    }
    
    /**
     * Migrate initial addons (moved from ticket types)
     */
    private function migrate_initial_addons() {
        // Load the database class if not already loaded
        if (!class_exists('ResetDatabase')) {
            require_once(RESET_PLUGIN_PATH . 'includes/class-reset-database.php');
        }
        
        $database = ResetDatabase::getInstance();
        
        // Check if addons already exist
        $existing_addons = $database->get_all_addons();
        if (!empty($existing_addons)) {
            return; // Already migrated
        }
        
        // Initial addons (moved from ticket types)
        $initial_addons = array(
            array(
                'addon_key' => 'afterparty_package_1',
                'name' => 'Afterparty - Package 01',
                'description' => '2 Free Cocktails or Beers',
                'price' => 2500.00,
                'is_enabled' => 1,
                'sort_order' => 10
            ),
            array(
                'addon_key' => 'afterparty_package_2',
                'name' => 'Afterparty - Package 02',
                'description' => '4 Free Cocktails or Beers',
                'price' => 3500.00,
                'is_enabled' => 1,
                'sort_order' => 20
            )
        );
        
        // Insert initial addons
        foreach ($initial_addons as $addon) {
            $database->create_addon($addon);
        }
    }
    
    /**
     * Migrate database schema for existing installations
     */
    private function migrate_database_schema() {
        global $wpdb;
        
        $table_purchases = $wpdb->prefix . 'reset_purchases';
        
        // Check if gaming_name column exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SHOW COLUMNS FROM `$table_purchases` LIKE %s",
                'gaming_name'
            )
        );
        
        // Add gaming_name column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE `$table_purchases` 
                ADD COLUMN `gaming_name` varchar(255) NULL 
                AFTER `purchaser_phone`"
            );
            
            // Log the migration
            error_log('RESET Plugin: Added gaming_name column to purchases table');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('RESET Ticketing', 'reset-ticketing'),
            __('RESET Ticketing', 'reset-ticketing'),
            'manage_options',
            'reset-ticketing',
            array($this, 'admin_dashboard_page'),
            'dashicons-tickets-alt',
            30
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Token Management', 'reset-ticketing'),
            __('Token Management', 'reset-ticketing'),
            'manage_options',
            'reset-token-management',
            array($this, 'admin_token_management_page')
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Sales Report', 'reset-ticketing'),
            __('Sales Report', 'reset-ticketing'),
            'manage_options',
            'reset-sales-report',
            array($this, 'admin_sales_report_page')
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Ticket Management', 'reset-ticketing'),
            __('Ticket Management', 'reset-ticketing'),
            'manage_options',
            'reset-ticket-management',
            array($this, 'admin_ticket_management_page')
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Add-on Management', 'reset-ticketing'),
            __('Add-on Management', 'reset-ticketing'),
            'manage_options',
            'reset-addon-management',
            array($this, 'admin_addon_management_page')
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Capacity Management', 'reset-ticketing'),
            __('Capacity Management', 'reset-ticketing'),
            'manage_options',
            'reset-capacity-management',
            array($this, 'admin_capacity_management_page')
        );
        
        add_submenu_page(
            'reset-ticketing',
            __('Email Campaigns', 'reset-ticketing'),
            __('Email Campaigns', 'reset-ticketing'),
            'manage_options',
            'reset-email-campaigns',
            array($this, 'admin_email_campaigns_page')
        );
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        $dashboard_file = RESET_PLUGIN_PATH . 'admin/dashboard.php';
        if (file_exists($dashboard_file)) {
            include $dashboard_file;
        } else {
            echo '<div class="notice notice-error"><p>Dashboard file not found.</p></div>';
        }
    }
    
    /**
     * Admin key management page
     */
    public function admin_token_management_page() {
        $token_file = RESET_PLUGIN_PATH . 'admin/token-management.php';
        if (file_exists($token_file)) {
            include $token_file;
        } else {
            echo '<div class="notice notice-error"><p>Token management file not found.</p></div>';
        }
    }
    
    /**
     * Admin sales report page
     */
    public function admin_sales_report_page() {
        $sales_file = RESET_PLUGIN_PATH . 'admin/sales-report.php';
        if (file_exists($sales_file)) {
            include $sales_file;
        } else {
            echo '<div class="notice notice-error"><p>Sales report file not found.</p></div>';
        }
    }
    
    /**
     * Admin ticket management page
     */
    public function admin_ticket_management_page() {
        $ticket_file = RESET_PLUGIN_PATH . 'admin/ticket-management.php';
        if (file_exists($ticket_file)) {
            include $ticket_file;
        } else {
            echo '<div class="notice notice-error"><p>Ticket management file not found.</p></div>';
        }
    }
    
    /**
     * Admin addon management page
     */
    public function admin_addon_management_page() {
        $addon_file = RESET_PLUGIN_PATH . 'admin/addon-management.php';
        if (file_exists($addon_file)) {
            include $addon_file;
        } else {
            echo '<div class="notice notice-error"><p>Add-on management file not found.</p></div>';
        }
    }
    
    /**
     * Admin capacity management page
     */
    public function admin_capacity_management_page() {
        $capacity_file = RESET_PLUGIN_PATH . 'admin/capacity-management.php';
        if (file_exists($capacity_file)) {
            include $capacity_file;
        } else {
            echo '<div class="notice notice-error"><p>Capacity management file not found.</p></div>';
        }
    }
    
    /**
     * Email campaigns page
     */
    public function admin_email_campaigns_page() {
        $campaigns_file = RESET_PLUGIN_PATH . 'admin/email-campaigns.php';
        if (file_exists($campaigns_file)) {
            include $campaigns_file;
        } else {
            echo '<div class="notice notice-error"><p>Email campaigns file not found.</p></div>';
        }
    }
    
    /**
     * Handle admin exports (runs early to prevent HTML output)
     */
    public function handle_admin_exports() {
        // Only run on admin pages
        if (!is_admin()) {
            return;
        }
        
        // Check if this is a sales report export request
        if (isset($_GET['page']) && $_GET['page'] === 'reset-sales-report' && 
            isset($_GET['export']) && $_GET['export'] === 'purchases') {
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to export purchases.'));
            }
            
            // Load dependencies
            $this->load_dependencies();
            
            // Get DB instance
            global $wpdb;
            $table = $wpdb->prefix . 'reset_purchases';
            
            // Get all columns dynamically
            $columns = $wpdb->get_col("DESC $table", 0);
            
            // Build WHERE clause based on filters
            $where_conditions = array();
            $where_values = array();
            
            // Status filter
            if (!empty($_GET['export_status'])) {
                $where_conditions[] = "payment_status = %s";
                $where_values[] = sanitize_text_field($_GET['export_status']);
            }
            
            // Date range filters
            if (!empty($_GET['export_start_date'])) {
                $where_conditions[] = "DATE(created_at) >= %s";
                $where_values[] = sanitize_text_field($_GET['export_start_date']);
            }
            
            if (!empty($_GET['export_end_date'])) {
                $where_conditions[] = "DATE(created_at) <= %s";
                $where_values[] = sanitize_text_field($_GET['export_end_date']);
            }
            
            // Build the SQL query
            $sql = "SELECT * FROM $table";
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            $sql .= " ORDER BY created_at ASC";
            
            // Get filtered rows
            if (!empty($where_values)) {
                $rows = $wpdb->get_results($wpdb->prepare($sql, ...$where_values), ARRAY_A);
            } else {
                $rows = $wpdb->get_results($sql, ARRAY_A);
            }
            
            // Generate filename with filter info
            $filename_parts = array('reset_purchases_export');
            if (!empty($_GET['export_status'])) {
                $filename_parts[] = sanitize_text_field($_GET['export_status']);
            }
            if (!empty($_GET['export_start_date']) || !empty($_GET['export_end_date'])) {
                $date_part = '';
                if (!empty($_GET['export_start_date'])) {
                    $date_part .= sanitize_text_field($_GET['export_start_date']);
                }
                $date_part .= '_to_';
                if (!empty($_GET['export_end_date'])) {
                    $date_part .= sanitize_text_field($_GET['export_end_date']);
                } else {
                    $date_part .= date('Y-m-d');
                }
                $filename_parts[] = $date_part;
            }
            $filename_parts[] = date('Ymd_His');
            $filename = implode('_', $filename_parts) . '.csv';
            
            // Output headers for CSV download
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output UTF-8 BOM for Excel compatibility
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            // Output header row
            fputcsv($output, $columns);
            // Output data rows
            foreach ($rows as $row) {
                // Ensure order matches columns
                $data = [];
                foreach ($columns as $col) {
                    $data[] = $row[$col];
                }
                fputcsv($output, $data);
            }
            fclose($output);
            exit;
        }
        
        // Check if this is a check-in export request
        if (isset($_GET['page']) && $_GET['page'] === 'reset-sales-report' && 
            isset($_GET['export']) && $_GET['export'] === 'checkin') {
            
            // Check permissions
            if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
                wp_die(__('You do not have sufficient permissions to export check-in data.'));
            }
            
            // Load dependencies
            $this->load_dependencies();
            
            // Get DB instance
            $db = ResetDatabase::getInstance();
            
            // Get filter parameters
            $checkin_filter = isset($_GET['checkin_filter']) ? sanitize_text_field($_GET['checkin_filter']) : 'all';
            $start_date = isset($_GET['checkin_start_date']) ? sanitize_text_field($_GET['checkin_start_date']) : '';
            $end_date = isset($_GET['checkin_end_date']) ? sanitize_text_field($_GET['checkin_end_date']) : '';
            
            // Get check-in export data with filters
            $rows = $db->get_check_in_export_data($checkin_filter, $start_date, $end_date);
            
            // Define columns for check-in export
            $columns = array(
                'purchaser_name' => 'Customer Name',
                'purchaser_email' => 'Email',
                'token_code' => 'Key',
                'ticket_type' => 'Ticket Type',
                'addon_details' => 'Add-ons',
                'total_drink_count' => 'Drinks Count',
                'checked_in' => 'Checked In',
                'checked_in_at' => 'Check-in Time',
                'checked_in_by' => 'Checked In By',
                'created_at' => 'Purchase Date'
            );
            
            // Generate filename with filter info
            $filename_parts = array('reset_checkin_export');
            
            if ($checkin_filter === 'checked_in') {
                $filename_parts[] = 'checked_in_only';
            } elseif ($checkin_filter === 'not_checked_in') {
                $filename_parts[] = 'not_checked_in';
            } else {
                $filename_parts[] = 'all_users';
            }
            
            if (!empty($start_date) || !empty($end_date)) {
                $date_range = array();
                if (!empty($start_date)) $date_range[] = 'from_' . str_replace('-', '', $start_date);
                if (!empty($end_date)) $date_range[] = 'to_' . str_replace('-', '', $end_date);
                $filename_parts[] = implode('_', $date_range);
            }
            
            $filename_parts[] = date('Ymd_His');
            $filename = implode('_', $filename_parts) . '.csv';
            
            // Output headers for CSV download
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output UTF-8 BOM for Excel compatibility
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            
            // Output header row
            fputcsv($output, array_values($columns));
            
            // Output data rows
            foreach ($rows as $row) {
                $data = array();
                foreach (array_keys($columns) as $col) {
                    if ($col === 'checked_in') {
                        $data[] = intval($row[$col]) ? 'Yes' : 'No';
                    } elseif ($col === 'checked_in_at') {
                        $data[] = $row[$col] ? date('Y-m-d H:i:s', strtotime($row[$col])) : '';
                    } elseif ($col === 'created_at') {
                        $data[] = date('Y-m-d H:i:s', strtotime($row[$col]));
                    } else {
                        $data[] = $row[$col] ?? '';
                    }
                }
                fputcsv($output, $data);
            }
            fclose($output);
            exit;
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('reset-frontend', RESET_PLUGIN_URL . 'assets/css/frontend.css', array(), RESET_PLUGIN_VERSION);
        wp_enqueue_script('reset-frontend', RESET_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), RESET_PLUGIN_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('reset-frontend', 'resetAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reset_nonce'),
            'messages' => array(
                'validating' => __('Validating token...', 'reset-ticketing'),
                'processing' => __('Processing booking...', 'reset-ticketing'),
                'error' => __('An error occurred. Please try again.', 'reset-ticketing')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'reset-ticketing') === false) {
            return;
        }
        
        wp_enqueue_style('reset-admin', RESET_PLUGIN_URL . 'assets/css/admin.css', array(), RESET_PLUGIN_VERSION);
        $admin_js_path = RESET_PLUGIN_PATH . 'assets/js/admin.js';
        $admin_js_version = file_exists($admin_js_path) ? filemtime($admin_js_path) : RESET_PLUGIN_VERSION;
        wp_enqueue_script('reset-admin', RESET_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), $admin_js_version, true);
        
        // Localize script for AJAX
        wp_localize_script('reset-admin', 'resetAdminAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reset_admin_nonce')
        ));
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^reset/?$', 'index.php?reset_page=token_entry', 'top');
        add_rewrite_rule('^reset/booking/?$', 'index.php?reset_page=booking_form', 'top');
        
        // New event details route
        add_rewrite_rule('^reset/event-details/?$', 'index.php?reset_page=event_details', 'top');
        
        // New step-wise booking routes
        add_rewrite_rule('^reset/booking/step/([1-4])/?$', 'index.php?reset_page=booking_wizard&step=$matches[1]', 'top');
        add_rewrite_rule('^reset/booking-success/?$', 'index.php?reset_page=booking_success', 'top');
        
        add_rewrite_rule('^reset/success/?$', 'index.php?reset_page=payment_success', 'top');
        add_rewrite_rule('^reset/payment-callback/?$', 'index.php?reset_page=payment_callback', 'top');
        add_rewrite_rule('^reset/payment-return/?$', 'index.php?reset_page=payment_return', 'top');
        add_rewrite_rule('^reset/payment-error/?$', 'index.php?reset_page=payment_error', 'top');
        add_rewrite_rule('^reset/ticket/([^/]+)/?$', 'index.php?reset_page=e_ticket&ticket_id=$matches[1]', 'top');
        add_rewrite_rule('^reset/pdf/([^/]+)/?$', 'index.php?reset_page=pdf_ticket&purchase_id=$matches[1]', 'top');
        add_rewrite_rule('^reset/bar/?$', 'index.php?reset_page=bar_management', 'top');
        add_rewrite_rule('^reset/check-in/?$', 'index.php?reset_page=check_in', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'reset_page';
        $vars[] = 'ticket_id';
        $vars[] = 'purchase_id';
        $vars[] = 'step';
        return $vars;
    }
    
    /**
     * Handle custom pages
     */
    public function handle_custom_pages() {
        $reset_page = get_query_var('reset_page');
        
        if (!empty($reset_page)) {
            switch ($reset_page) {
                case 'token_entry':
                    $template = RESET_PLUGIN_PATH . 'templates/token-entry.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'booking_form':
                    $template = RESET_PLUGIN_PATH . 'templates/booking-form.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'event_details':
                    $template = RESET_PLUGIN_PATH . 'templates/event-details.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'booking_wizard':
                    $template = RESET_PLUGIN_PATH . 'templates/booking-wizard.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'booking_success':
                    $template = RESET_PLUGIN_PATH . 'templates/payment-success.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'payment_success':
                    $template = RESET_PLUGIN_PATH . 'templates/payment-success.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'payment_callback':
                    $this->handle_payment_callback();
                    break;
                
                case 'payment_return':
                    $this->handle_payment_callback();
                    break;
                
                case 'payment_error':
                    $template = RESET_PLUGIN_PATH . 'templates/payment-error.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'e_ticket':
                    $template = RESET_PLUGIN_PATH . 'templates/e-ticket.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'pdf_ticket':
                    $this->generate_pdf_ticket();
                    break;
                
                case 'bar_management':
                    $template = RESET_PLUGIN_PATH . 'templates/bar-management.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
                
                case 'check_in':
                    $template = RESET_PLUGIN_PATH . 'templates/check-in.php';
                    if (file_exists($template)) {
                        include $template;
                        exit;
                    }
                    break;
            }
        }
    }
    
    /**
     * AJAX key validation
     */
    public function ajax_validate_token() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');
            
            $token_code = sanitize_text_field($_POST['token_code']);
            
            if (empty($token_code)) {
                wp_send_json_error('No token code provided');
                return;
            }
            
            if (!class_exists('ResetTokens')) {
                wp_send_json_error('Token validation service not available - ResetTokens class missing');
                return;
            }
            
            if (!class_exists('ResetDatabase')) {
                wp_send_json_error('Database service not available');
                return;
            }
            
            // Get the validation result
            $result = ResetTokens::getInstance()->validate_token($token_code);
            
            // Send the result - wp_send_json automatically wraps in success/data structure
            if (isset($result['valid']) && $result['valid']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX booking processing
     */
    public function ajax_process_booking() {
        try {
            // Check nonce
            check_ajax_referer('reset_nonce', 'nonce');
            
            // Collect booking data
            $booking_data = array(
                'token_code' => sanitize_text_field($_POST['token_code'] ?? ''),
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                'ticket_type' => sanitize_text_field($_POST['ticket_type'] ?? '')
            );
            
            // Validate required fields
            if (empty($booking_data['token_code'])) {
                wp_send_json_error('Token code is required');
                return;
            }
            
            if (empty($booking_data['name'])) {
                wp_send_json_error('Name is required');
                return;
            }
            
            if (empty($booking_data['email'])) {
                wp_send_json_error('Email is required');
                return;
            }
            
            if (empty($booking_data['phone'])) {
                wp_send_json_error('Phone is required');
                return;
            }
            
            if (empty($booking_data['ticket_type'])) {
                wp_send_json_error('Ticket type is required');
                return;
            }
            
            // Check if classes exist
            if (!class_exists('ResetPayments')) {
                wp_send_json_error('Payment processing service not available - ResetPayments class missing');
                return;
            }
            
            $result = ResetPayments::getInstance()->process_booking($booking_data);
            
            // Send appropriate response
            if (isset($result['success']) && $result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle payment callback from Sampath gateway
     */
    public function handle_payment_callback() {
        try {
            // Check if we have the required callback data
            if (!isset($_POST['clientRef']) || !isset($_POST['reqid'])) {
                wp_die('Invalid payment callback', 'Payment Error', array('response' => 400));
                return;
            }
            
            // Validate that this is a RESET payment by checking the clientRef pattern
            $client_ref = $_POST['clientRef'];
            if (strpos($client_ref, 'RESET') !== 0) {
                wp_die('Invalid payment reference', 'Payment Error', array('response' => 400));
                return;
            }
            
            // ENHANCED ERROR HANDLING: Try to load dependencies if not already loaded
            if (!class_exists('ResetSampathGateway')) {
                // Try to load dependencies
                $this->load_dependencies();
                
                // Check again after loading
                if (!class_exists('ResetSampathGateway')) {
                    wp_die('Payment gateway not available - class loading failed', 'Payment Error', array('response' => 500));
                    return;
                }
            }
            
            // Get the Sampath gateway instance
            $gateway = ResetSampathGateway::getInstance();
            $callback_result = $gateway->handle_payment_callback($_POST);
            
            if ($callback_result['success']) {
                // Payment successful - update purchase record
                $payment_reference = $callback_result['payment_reference'];
                $transaction_id = $callback_result['transaction_id'];
                
                // ENHANCED ERROR HANDLING: Check if ResetPayments class is available
                if (!class_exists('ResetPayments')) {
                    $this->load_dependencies();
                    
                    if (!class_exists('ResetPayments')) {
                        wp_die('Payment processing service not available - class loading failed', 'Payment Error', array('response' => 500));
                        return;
                    }
                }
                
                $payment_data = array(
                    'payment_reference' => $payment_reference,
                    'transaction_id' => $transaction_id,
                    'response_code' => $callback_result['response_code'],
                    'response_text' => $callback_result['response_text'],
                    'settlement_date' => $callback_result['settlement_date'],
                    'card_info' => $callback_result['card_info']
                );
                
                $result = ResetPayments::getInstance()->handle_payment_callback($payment_data);
                
                if ($result['success']) {
                    // Redirect to booking success page
                    wp_redirect(site_url('/reset/booking-success?ref=' . $payment_reference));
                    exit;
                } else {
                    wp_die('Payment processing failed: ' . $result['message'], 'Payment Error', array('response' => 500));
                }
            } else {
                // Payment failed - provide detailed error information
                $error_message = $callback_result['message'];
                $payment_reference = $callback_result['payment_reference'] ?? 'N/A';
                $response_code = $callback_result['response_code'] ?? 'N/A';
                
                // Log detailed error information
                error_log("RESET Payment Failed - Reference: {$payment_reference}, Code: {$response_code}, Message: {$error_message}");
                
                // Show user-friendly error with helpful information
                if (stripos($error_message, 'expired') !== false) {
                    $friendly_message = "
                        <h2>Payment Failed: Card Issue</h2>
                        <p><strong>Error:</strong> {$error_message}</p>
                        <p><strong>Payment Reference:</strong> {$payment_reference}</p>
                        
                        <h3>What to do next:</h3>
                        <ul>
                            <li><strong>Check your card expiry date</strong> - Ensure it's valid and not expired</li>
                            <li><strong>Try a different card</strong> - Use another valid credit/debit card</li>
                            <li><strong>Contact your bank</strong> - Your card might be blocked for online transactions</li>
                            <li><strong>Contact us</strong> - If the issue persists, please contact support with reference: {$payment_reference}</li>
                        </ul>
                        
                        <p><a href='javascript:history.back()' style='padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px;'>← Try Again</a></p>
                    ";
                } else {
                    $friendly_message = "
                        <h2>Payment Failed</h2>
                        <p><strong>Error:</strong> {$error_message}</p>
                        <p><strong>Payment Reference:</strong> {$payment_reference}</p>
                        
                        <p>Please try again or contact support if the issue persists.</p>
                        <p><a href='javascript:history.back()' style='padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px;'>← Try Again</a></p>
                    ";
                }
                
                wp_die($friendly_message, 'Payment Failed', array('response' => 400));
            }
            
        } catch (Exception $e) {
            wp_die('Payment callback error: ' . $e->getMessage(), 'Payment Error', array('response' => 500));
        }
    }
    
    /**
     * Handle early payment callback before WC_Sampath's parse_request
     */
    public function handle_early_payment_callback() {
        // Check if this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Get the request URI
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // ENHANCED: Check for RESET payment callbacks in multiple ways
        $is_reset_callback = false;
        $debug_info = array();
        
        // Method 1: Check for RESET payment-return URL
        if (strpos($request_uri, '/reset/payment-return') !== false) {
            $is_reset_callback = true;
            $debug_info[] = 'Matched RESET payment-return URL';
        }
        
        // Method 2: Check for RESET clientRef in POST data
        if (isset($_POST['clientRef']) && strpos($_POST['clientRef'], 'RESET') === 0) {
            $is_reset_callback = true;
            $debug_info[] = 'Found RESET clientRef: ' . $_POST['clientRef'];
        }
        
        // Method 3: Check for any POST request with RESET payment parameters to any URL
        if (isset($_POST['clientRef']) && isset($_POST['reqid'])) {
            $client_ref = $_POST['clientRef'];
            if (strpos($client_ref, 'RESET') === 0) {
                $is_reset_callback = true;
                $debug_info[] = 'RESET payment callback detected on URL: ' . $request_uri;
                $debug_info[] = 'ClientRef: ' . $client_ref;
                $debug_info[] = 'ReqID: ' . $_POST['reqid'];
            }
        }
        
        // If this is a RESET payment callback, handle it
        if ($is_reset_callback) {
            // Log for debugging
            error_log('RESET Payment Callback Detected: ' . implode(' | ', $debug_info));
            
            // CRITICAL FIX: Load dependencies before calling payment callback
            $this->load_dependencies();
            
            // Initialize required services
            if (class_exists('ResetCore')) {
                ResetCore::getInstance();
            }
            if (class_exists('ResetDatabase')) {
                ResetDatabase::getInstance();
            }
            if (class_exists('ResetTokens')) {
                ResetTokens::getInstance();
            }
            if (class_exists('ResetPayments')) {
                ResetPayments::getInstance();
            }
            if (class_exists('ResetEmails')) {
                ResetEmails::getInstance();
            }
            if (class_exists('ResetSampathGateway')) {
                ResetSampathGateway::getInstance();
            }
            
            // Handle the payment callback
            $this->handle_payment_callback();
            exit; // Stop further processing to prevent WC_Sampath interference
        }
    }

    /**
     * Flush rewrite rules for RESET plugin
     */
    public function flush_reset_rewrite_rules() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    /**
     * Schedule cron jobs
     */
    public function schedule_cron_jobs() {
        if (!wp_next_scheduled('reset_reminder_emails')) {
            wp_schedule_event(time(), 'daily', 'reset_reminder_emails');
        }
        
        if (!wp_next_scheduled('reset_capacity_check')) {
            wp_schedule_event(time(), 'hourly', 'reset_capacity_check');
        }
    }
    
    /**
     * Generate PDF ticket
     */
    public function generate_pdf_ticket() {
        // Clear any output buffering to prevent interference
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $purchase_id = get_query_var('purchase_id');
        
        if (empty($purchase_id)) {
            $this->send_pdf_error('Invalid purchase ID', 'INVALID_ID');
            return;
        }
        
        // Validate purchase ID is numeric
        if (!is_numeric($purchase_id)) {
            $this->send_pdf_error('Purchase ID must be numeric', 'INVALID_FORMAT');
            return;
        }
        
        // Get purchase data
        if (!class_exists('ResetDatabase')) {
            $this->send_pdf_error('Database service not available', 'SERVICE_UNAVAILABLE');
            return;
        }
        
        $db = ResetDatabase::getInstance();
        $purchase = $db->get_purchase_by_id((int)$purchase_id);
        
        if (!$purchase) {
            error_log("RESET PDF: Purchase not found for ID: " . $purchase_id);
            $this->send_pdf_error('Purchase not found', 'PURCHASE_NOT_FOUND');
            return;
        }
        
        // Log PDF generation attempt
        error_log("RESET PDF: Generating PDF for purchase ID: " . $purchase_id . ", Customer: " . $purchase['purchaser_name']);
        
        // Generate PDF content
        if (!class_exists('ResetCore')) {
            $this->send_pdf_error('Core service not available', 'SERVICE_UNAVAILABLE');
            return;
        }
        
        try {
            $core = ResetCore::getInstance();
            $pdf_content = $core->generate_pdf_ticket($purchase);
            
            // Get key info for filename
            $token_info = '';
            $token = $db->get_token_by_id($purchase['token_id']);
            if ($token) {
                $token_info = $token['token_code'];
            }
            
            // Generate actual PDF content
            $filename = 'RESET_2025_Ticket_' . ($token_info ? $token_info : $purchase['id']) . '.pdf';
            
            // Log successful PDF generation
            error_log("RESET PDF: Successfully generated PDF: " . $filename);
            
            $this->generate_actual_pdf($purchase, $filename);
            
        } catch (Exception $e) {
            error_log("RESET PDF: Exception during PDF generation: " . $e->getMessage());
            $this->send_pdf_error('PDF generation failed: ' . $e->getMessage(), 'GENERATION_ERROR');
            return;
        }
    }
    
    /**
     * Send PDF error response
     */
    private function send_pdf_error($message, $error_code = 'UNKNOWN_ERROR') {
        // Log the error
        error_log("RESET PDF Error: " . $error_code . " - " . $message);
        
        // Clear any output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set appropriate HTTP status
        $status_code = 500;
        switch ($error_code) {
            case 'INVALID_ID':
            case 'INVALID_FORMAT':
                $status_code = 400;
                break;
            case 'PURCHASE_NOT_FOUND':
                $status_code = 404;
                break;
            case 'SERVICE_UNAVAILABLE':
                $status_code = 503;
                break;
            default:
                $status_code = 500;
                break;
        }
        
        // Send error response that JavaScript can detect
        http_response_code($status_code);
        header('Content-Type: text/html; charset=utf-8');
        
        // Create a simple error page that JavaScript can detect
        echo '<!DOCTYPE html>
<html>
<head>
    <title>PDF Generation Error</title>
    <meta name="pdf-error" content="' . esc_attr($error_code) . '">
</head>
<body>
    <h1>PDF Generation Error</h1>
    <p><strong>Error Code:</strong> ' . esc_html($error_code) . '</p>
    <p><strong>Message:</strong> ' . esc_html($message) . '</p>
    <p>Please try again or contact support if the problem persists.</p>
    <script>
        // Try to communicate with parent if in iframe
        if (window.parent !== window) {
            window.parent.postMessage({
                type: "pdf_error",
                error_code: "' . esc_js($error_code) . '",
                message: "' . esc_js($message) . '"
            }, "*");
        }
    </script>
</body>
</html>';
        exit;
    }
    
    /**
     * Generate actual PDF file
     */
    private function generate_actual_pdf($purchase, $filename) {
        // Try to use available PDF libraries first
        if (class_exists('TCPDF')) {
            $this->generate_pdf_with_tcpdf($purchase, $filename);
        } elseif (class_exists('mPDF')) {
            $this->generate_pdf_with_mpdf($purchase, $filename);
        } else {
            // Fallback: Generate basic PDF using simple PHP approach
            $this->generate_simple_pdf($purchase, $filename);
        }
    }
    
    /**
     * Generate simple PDF without external libraries
     */
    private function generate_simple_pdf($purchase, $filename) {
        try {
            // Ensure clean output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Get purchase details
            $token_info = '';
            if (class_exists('ResetDatabase')) {
                $db = ResetDatabase::getInstance();
                $token = $db->get_token_by_id($purchase['token_id']);
                if ($token) {
                    $token_info = $token['token_code'];
                }
            }
            
            // Get invitation keys
            $invitation_tokens = array();
            if (class_exists('ResetTokens')) {
                $invitation_tokens = ResetTokens::getInstance()->get_invitation_tokens_by_parent_id((int)$purchase['token_id']);
            }
            
            // Create PDF content using basic PDF format
            $pdf_content = $this->create_basic_pdf_content($purchase, $token_info, $invitation_tokens);
            
            // Validate PDF content was generated
            if (empty($pdf_content) || strlen($pdf_content) < 100) {
                throw new Exception('PDF content generation failed or produced invalid output');
            }
            
            // Log successful PDF creation
            error_log("RESET PDF: PDF content generated successfully. Size: " . strlen($pdf_content) . " bytes");
            
            // Set PDF headers with proper error handling
            if (headers_sent($file, $line)) {
                throw new Exception("Headers already sent in $file on line $line");
            }
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output PDF content
            echo $pdf_content;
            
            // Log successful download
            error_log("RESET PDF: PDF successfully sent to browser: " . $filename);
            
            exit;
            
        } catch (Exception $e) {
            error_log("RESET PDF: Error in generate_simple_pdf: " . $e->getMessage());
            $this->send_pdf_error('PDF generation failed: ' . $e->getMessage(), 'GENERATION_ERROR');
        }
    }
    
    /**
     * Create basic PDF content
     */
    private function create_basic_pdf_content($purchase, $token_info, $invitation_tokens) {
        // Simple PDF structure - this creates a minimal valid PDF
        $pdf_header = "%PDF-1.4\n";
        
        // Object 1: Catalog
        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        
        // Create ticket content
        $ticket_content = $this->format_ticket_text($purchase, $token_info, $invitation_tokens);
        
        // Object 3: Page with multiple font resources
        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>\nendobj\n";
        
        // Object 4: Regular Font
        $obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Object 5: Bold Font
        $obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        
        // Object 6: Styled content stream with beautiful design
        $content_stream = $this->create_styled_pdf_content($ticket_content);
        $obj6 = "6 0 obj\n<< /Length " . strlen($content_stream) . " >>\nstream\n" . $content_stream . "\nendstream\nendobj\n";
        
        // Cross-reference table
        $xref_pos = strlen($pdf_header . $obj1 . $obj2 . $obj3 . $obj4 . $obj5 . $obj6);
        $xref = "xref\n0 7\n0000000000 65535 f \n";
        
        $pos = strlen($pdf_header);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        $pos += strlen($obj1);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        $pos += strlen($obj2);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        $pos += strlen($obj3);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        $pos += strlen($obj4);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        $pos += strlen($obj5);
        $xref .= sprintf("%010d 00000 n \n", $pos);
        
        // Trailer
        $trailer = "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xref_pos . "\n%%EOF";
        
        return $pdf_header . $obj1 . $obj2 . $obj3 . $obj4 . $obj5 . $obj6 . $xref . $trailer;
    }
    
    /**
     * Format ticket text for PDF
     */
    private function format_ticket_text($purchase, $token_info, $invitation_tokens) {
        $text = "RESET 2025 - E-TICKET";
        $text .= "\n\n";
        $text .= "Event: RESET 2025";
        $text .= "\n";
        $text .= "Date: " . date('F j, Y', strtotime('2025-07-27'));
        $text .= "\n";
        $text .= "Time: 10:00 AM - 11:00 PM";
        $text .= "\n";
        $text .= "Venue: Trace Expert City, Colombo - Bay 07";
        $text .= "\n\n";
        
        $text .= "TICKET HOLDER DETAILS:";
        $text .= "\n";
        $text .= "Name: " . $purchase['purchaser_name'];
        $text .= "\n";
        $text .= "Email: " . $purchase['purchaser_email'];
        $text .= "\n";
        $text .= "Phone: " . $purchase['purchaser_phone'];
        $text .= "\n";
        // FIXED: Use get_ticket_name_from_key method instead of raw ticket_type
        $text .= "Ticket Type: " . $this->get_ticket_name_from_key($purchase['ticket_type']);
        $text .= "\n";
        
        // Get addon details for this purchase
        $purchase_addons = array();
        if (class_exists('ResetDatabase')) {
            $db = ResetDatabase::getInstance();
            $purchase_addons = $db->get_addons_for_purchase((int)$purchase['id']);
            
            // Apply same filtering logic - hide free addon for polo_ordered users if paid addon selected
            if (!empty($purchase_addons)) {
                $token = $db->get_token_by_id($purchase['token_id']);
                $token_type = $token['token_type'] ?? '';
                
                if ($token_type === 'polo_ordered') {
                    $has_paid_addon = false;
                    foreach ($purchase_addons as $addon) {
                        if ($addon['addon_key'] !== 'afterpart_package_0') {
                            $has_paid_addon = true;
                            break;
                        }
                    }
                    
                    // If polo_ordered user has paid addon, hide the free addon
                    if ($has_paid_addon) {
                        $purchase_addons = array_filter($purchase_addons, function($addon) {
                            return $addon['addon_key'] !== 'afterpart_package_0';
                        });
                    }
                }
            }
        }
        
        // FIXED: Correct amount calculation for free keys with add-ons
        $total_amount = 0;
        
        // First, try to use total_amount from purchase if it exists and is > 0
        if (isset($purchase['total_amount']) && $purchase['total_amount'] > 0) {
            $total_amount = floatval($purchase['total_amount']);
        } else {
            // Fallback: Calculate from ticket price + addon total
            $ticket_price = floatval($purchase['ticket_price'] ?? 0);
            $addon_total = floatval($purchase['addon_total'] ?? 0);
            
            // Also calculate from individual addons as fallback
            if ($addon_total == 0 && !empty($purchase_addons)) {
                foreach ($purchase_addons as $addon) {
                    $addon_total += floatval($addon['addon_price']);
                }
            }
            
            $total_amount = $ticket_price + $addon_total;
        }
        
        // Display addon details if any
        if (!empty($purchase_addons)) {
            foreach ($purchase_addons as $addon) {
                $text .= "Add-on: " . $addon['name'] . " - Rs. " . number_format($addon['addon_price'], 2);
                $text .= "\n";
            }
        }
        
        $text .= "Amount Paid: Rs. " . number_format($total_amount, 2);
        $text .= "\n";
        
        if ($token_info) {
            $text .= "Key: " . $token_info;
            $text .= "\n";
        }
        
        $text .= "\n";
        
        if (!empty($invitation_tokens)) {
            $text .= "INVITATION KEYS:";
            $text .= "\n";
            foreach ($invitation_tokens as $token) {
                $text .= "- " . $token['token_code'];
                $text .= "\n";
            }
            $text .= "\n";
        }
        
        $text .= "IMPORTANT REMINDERS:";
        $text .= "\n";
        $text .= "- Arrive 30 minutes before the event";
        $text .= "\n";
        $text .= "- Bring valid ID for verification";
        $text .= "\n";
        $text .= "- Keep this ticket safe";
        $text .= "\n";
        
        $text .= "Generated: " . date('F j, Y g:i A');
        $text .= "\n";
        $text .= "Support: support@nooballiance.lk";
        
        return $text;
    }
    
    /**
     * Create professionally styled PDF content (Black & Yellow Theme)
     */
    private function create_styled_pdf_content($ticket_content) {
        $content_lines = explode("\n", $ticket_content);
        $stream = "";
        
        // Page dimensions
        $page_width = 612;
        $page_height = 792;
        $margin = 40;
        $content_width = $page_width - (2 * $margin);
        
        // Brand colors: Black and Yellow (#f9c613)
        // Black: 0 0 0 rg
        // Yellow: 0.976 0.776 0.075 rg (RGB: 249, 198, 19)
        
        // Main background - white
        $stream .= "q\n";
        $stream .= "1 1 1 rg\n"; // White background
        $stream .= "0 0 {$page_width} {$page_height} re\n";
        $stream .= "f\n";
        $stream .= "Q\n";
        
        // Header section - Black background with yellow accent
        $stream .= "q\n";
        $stream .= "0 0 0 rg\n"; // Black background
        $stream .= "0 720 {$page_width} 72 re\n"; // Header rectangle
        $stream .= "f\n";
        
        // Yellow accent strip
        $stream .= "0.976 0.776 0.075 rg\n"; // Yellow color
        $stream .= "0 715 {$page_width} 5 re\n"; // Yellow strip
        $stream .= "f\n";
        $stream .= "Q\n";
        
        // Start text
        $stream .= "BT\n";
        
        // Header: RESET 2025 - E-TICKET (centered, clean design)
        $stream .= "1 1 1 rg\n"; // White text
        $stream .= "/F2 24 Tf\n"; // Large bold font
        $stream .= "1 0 0 1 50 750 Tm\n";
        $stream .= "(RESET 2025 - E-TICKET) Tj\n";
        
        // Subtitle
        $stream .= "0.976 0.776 0.075 rg\n"; // Yellow text
        $stream .= "/F1 12 Tf\n";
        $stream .= "1 0 0 1 50 730 Tm\n";
        $stream .= "(Reunion of Sri Lankan Esports) Tj\n";
        
        // Main content area
        $y_pos = 680;
        
        // Event details section
        $stream .= "0 0 0 rg\n"; // Black text
        $stream .= "/F2 16 Tf\n"; // Bold font
        $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
        $stream .= "(Event: RESET 2025) Tj\n";
        $y_pos -= 30;
        
        // Event info in two columns
        $stream .= "0.3 0.3 0.3 rg\n"; // Dark gray
        $stream .= "/F1 11 Tf\n";
        $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
        $stream .= "(Date: July 27, 2025) Tj\n";
        $stream .= "1 0 0 1 250 {$y_pos} Tm\n";
        $stream .= "(Time: 10:00 AM - 11:00 PM) Tj\n";
        $y_pos -= 25;
        
        $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
        $stream .= "(Venue: Trace Expert City, Colombo - Bay 07) Tj\n";
        $y_pos -= 40;
        
        // Divider line
        $stream .= "ET\n";
        $stream .= "q\n";
        $stream .= "0.9 0.9 0.9 rg\n"; // Light gray
        $stream .= "50 {$y_pos} " . ($content_width) . " 1 re\n";
        $stream .= "f\n";
        $stream .= "Q\n";
        $stream .= "BT\n";
        $y_pos -= 30;
        
        // Ticket Holder Details
        $stream .= "0 0 0 rg\n"; // Black text
        $stream .= "/F2 14 Tf\n";
        $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
        $stream .= "(TICKET HOLDER DETAILS) Tj\n";
        $y_pos -= 30;
        
        // Extract ticket holder info
        $holder_info = $this->extract_ticket_info($content_lines);
        
        // Two column layout for ticket details
        $left_col = 50;
        $right_col = 300;
        
        // Left column
        $stream .= "0.4 0.4 0.4 rg\n"; // Medium gray
        $stream .= "/F2 10 Tf\n";
        $stream .= "1 0 0 1 {$left_col} {$y_pos} Tm\n";
        $stream .= "(Name:) Tj\n";
        $stream .= "0.1 0.1 0.1 rg\n"; // Almost black
        $stream .= "/F1 10 Tf\n";
        $stream .= "1 0 0 1 " . ($left_col + 50) . " {$y_pos} Tm\n";
        $stream .= "(" . $this->escape_pdf_text($holder_info['name']) . ") Tj\n";
        
        // Right column
        $stream .= "0.4 0.4 0.4 rg\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "1 0 0 1 {$right_col} {$y_pos} Tm\n";
        $stream .= "(Email:) Tj\n";
        $stream .= "0.1 0.1 0.1 rg\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "1 0 0 1 " . ($right_col + 50) . " {$y_pos} Tm\n";
        $stream .= "(" . $this->escape_pdf_text($holder_info['email']) . ") Tj\n";
        $y_pos -= 22;
        
        // Phone and Ticket Type
        $stream .= "0.4 0.4 0.4 rg\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "1 0 0 1 {$left_col} {$y_pos} Tm\n";
        $stream .= "(Phone:) Tj\n";
        $stream .= "0.1 0.1 0.1 rg\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "1 0 0 1 " . ($left_col + 50) . " {$y_pos} Tm\n";
        $stream .= "(" . $this->escape_pdf_text($holder_info['phone']) . ") Tj\n";
        
        // Ticket Type - clean styling
        $stream .= "0.4 0.4 0.4 rg\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "1 0 0 1 {$right_col} {$y_pos} Tm\n";
        $stream .= "(Ticket Type:) Tj\n";
        $stream .= "0.1 0.1 0.1 rg\n"; // Almost black
        $stream .= "/F1 10 Tf\n";
        $stream .= "1 0 0 1 " . ($right_col + 70) . " {$y_pos} Tm\n";
        $stream .= "(" . $this->escape_pdf_text($holder_info['ticket_name']) . ") Tj\n";
        $y_pos -= 22;
        
        // Add-ons section (if any) and Amount Paid - balanced layout
        $addon_y_start = $y_pos; // Remember starting position
        
        if (!empty($holder_info['addons'])) {
            foreach ($holder_info['addons'] as $addon) {
                $stream .= "0.4 0.4 0.4 rg\n";
                $stream .= "/F2 10 Tf\n";
                $stream .= "1 0 0 1 {$right_col} {$y_pos} Tm\n";
                $stream .= "(Add-on:) Tj\n";
                
                // Simple addon name without background - on right side
                $stream .= "0.1 0.1 0.1 rg\n"; // Almost black
                $stream .= "/F1 10 Tf\n";
                $stream .= "1 0 0 1 " . ($right_col + 50) . " {$y_pos} Tm\n";
                $stream .= "(" . $this->escape_pdf_text($addon['name']) . ") Tj\n";
                
                $y_pos -= 22;
            }
        }
        
        // Amount Paid - positioned to align with add-ons if they exist
        $amount_y_pos = !empty($holder_info['addons']) ? $addon_y_start : $y_pos;
        $stream .= "0.4 0.4 0.4 rg\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "1 0 0 1 {$left_col} {$amount_y_pos} Tm\n";
        $stream .= "(Amount Paid:) Tj\n";
        $stream .= "0.1 0.6 0.1 rg\n"; // Green for amount
        $stream .= "/F2 11 Tf\n";
        $stream .= "1 0 0 1 " . ($left_col + 75) . " {$amount_y_pos} Tm\n";
        $stream .= "(" . $this->escape_pdf_text($holder_info['amount']) . ") Tj\n";
        
        // Ensure we advance y_pos properly
        if (empty($holder_info['addons'])) {
            $y_pos -= 22;
        }
        $y_pos -= 15; // Add some extra spacing before next section
        
        
        
        // Invitation keys section (if any)
        if (!empty($holder_info['invitation_tokens'])) {
            $y_pos -= 10;
            $stream .= "0 0 0 rg\n";
            $stream .= "/F2 12 Tf\n";
            $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
            $stream .= "(INVITATION KEYS) Tj\n";
            
            $token_y = $y_pos - 40;
            $tokens_per_row = 5;
            $token_width = 80;
            $token_spacing = 5;
            
            $token_number = 1;
                         foreach (array_chunk($holder_info['invitation_tokens'], $tokens_per_row) as $token_row) {
                 $x_pos = 50;
                 foreach ($token_row as $token) {
                     // Draw small circle with number
                     $stream .= "ET\n";
                     $stream .= "q\n";
                     $stream .= "0.976 0.776 0.075 rg\n"; // Yellow circle
                     $stream .= ($x_pos - 15) . " " . ($token_y + 2) . " 12 12 re\n";
                     $stream .= "f\n";
                     $stream .= "Q\n";
                     $stream .= "BT\n";
                     
                     // Number in circle
                     $stream .= "0 0 0 rg\n"; // Black text
                     $stream .= "/F2 8 Tf\n";
                     $stream .= "1 0 0 1 " . ($x_pos - 11) . " " . ($token_y + 5) . " Tm\n";
                     $stream .= "({$token_number}) Tj\n";
                     
                     // White background with yellow border for tokens
                     $stream .= "ET\n";
                     $stream .= "q\n";
                     $stream .= "1 1 1 rg\n"; // White background
                     $stream .= "{$x_pos} " . ($token_y - 8) . " {$token_width} 20 re\n";
                     $stream .= "f\n";
                     $stream .= "0.976 0.776 0.075 RG\n"; // Yellow border (#f9c613)
                     $stream .= "1.5 w\n"; // Slightly thicker border
                     $stream .= "{$x_pos} " . ($token_y - 8) . " {$token_width} 20 re\n";
                     $stream .= "S\n";
                     $stream .= "Q\n";
                     $stream .= "BT\n";
                     
                     $stream .= "0 0 0 rg\n"; // Black text
                     $stream .= "/F2 9 Tf\n";
                     $stream .= "1 0 0 1 " . ($x_pos + 8) . " " . ($token_y - 2) . " Tm\n"; // Added top space inside
                     $stream .= "(" . $this->escape_pdf_text($token) . ") Tj\n";
                     
                     $x_pos += $token_width + $token_spacing;
                     $token_number++;
                 }
                 $token_y -= 28; // Adjusted spacing for taller keys
             }
            $y_pos = $token_y - 20;
        }
        
        // Important Reminders section
        $y_pos -= 30;
        $stream .= "0 0 0 rg\n";
        $stream .= "/F2 12 Tf\n";
        $stream .= "1 0 0 1 50 {$y_pos} Tm\n";
        $stream .= "(IMPORTANT REMINDERS) Tj\n";
        $y_pos -= 25;
        
        $reminders = [
            "Arrive 30 minutes before the event",
            "Bring valid ID for verification", 
            "Keep this ticket safe",
            "Entry subject to capacity limits"
        ];
        
        $reminder_number = 1;
        foreach ($reminders as $reminder) {
            // Draw numbered circle for each reminder
            $stream .= "ET\n";
            $stream .= "q\n";
            $stream .= "0.976 0.776 0.075 rg\n"; // Yellow circle
            $stream .= "50 " . ($y_pos - 2) . " 12 12 re\n"; // Circle position and size
            $stream .= "f\n";
            $stream .= "Q\n";
            $stream .= "BT\n";
            
            // Number in circle
            $stream .= "0 0 0 rg\n"; // Black text
            $stream .= "/F2 8 Tf\n";
            $stream .= "1 0 0 1 54 " . ($y_pos + 1) . " Tm\n";
            $stream .= "({$reminder_number}) Tj\n";
            
            // Reminder text
            $stream .= "0.3 0.3 0.3 rg\n";
            $stream .= "/F1 10 Tf\n";
            $stream .= "1 0 0 1 70 {$y_pos} Tm\n";
            $stream .= "(" . $this->escape_pdf_text($reminder) . ") Tj\n";
            
            $y_pos -= 20;
            $reminder_number++;
        }
        
        // Removed valid entry stamp per user request
        
        // Footer
        $stream .= "0.6 0.6 0.6 rg\n";
        $stream .= "/F1 8 Tf\n";
        $stream .= "1 0 0 1 50 50 Tm\n";
        $stream .= "(Generated: " . date('F j, Y g:i A') . ") Tj\n";
        $stream .= "1 0 0 1 350 50 Tm\n";
        $stream .= "(Support: support@nooballiance.lk) Tj\n";
        
        $stream .= "ET\n";
        
        return $stream;
    }
    
    /**
     * Convert ticket key to ticket name
     */
    private function get_ticket_name_from_key($ticket_key) {
        // IMPROVED: Handle empty/null values
        if (empty($ticket_key)) {
            error_log("DEBUG: PDF get_ticket_name_from_key called with empty ticket_key");
            return 'Free Ticket'; // Default for empty values (likely free keys)
        }
        
        // DEBUG: Log what we're receiving (remove this after testing)
        error_log("DEBUG: PDF get_ticket_name_from_key called with: " . $ticket_key);
        
        // Try to get from database first (for dynamic ticket types)
        if (class_exists('ResetCore')) {
            $core = ResetCore::getInstance();
            $all_tickets = $core->get_ticket_pricing();
            if (isset($all_tickets[$ticket_key])) {
                return $all_tickets[$ticket_key]['name'];
            }
        }
        
        // Fallback to static mapping for ticket types
        $ticket_names = [
            'general_early' => 'General Admission - Early Bird',
            'general_late' => 'General Admission - Late Bird',
            'general_very_late' => 'General Admission - Very Late Bird',
            'afterparty_package_1' => 'Afterparty - Package 01',
            'afterparty_package_2' => 'Afterparty - Package 02'
        ];
        
        // FIXED: Handle key types (these are used for free keys)
        $token_type_names = [
            'free_ticket' => 'Free Ticket',
            'polo_ordered' => 'FREE Ticket',
            'sponsor' => 'FREE Ticket',
            'normal' => 'Regular Ticket'
        ];
        
        if (isset($ticket_names[$ticket_key])) {
            return $ticket_names[$ticket_key];
        } elseif (isset($token_type_names[$ticket_key])) {
            return $token_type_names[$ticket_key];
        }
        
        // IMPROVED: Better fallback for unrecognized values
        error_log("DEBUG: PDF Unrecognized ticket_key: " . $ticket_key . " - using fallback");
        return ucfirst(str_replace('_', ' ', $ticket_key)) ?: 'Free Ticket';
    }
    
    /**
     * Extract ticket information from content lines
     */
    private function extract_ticket_info($content_lines) {
        $info = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'ticket_type' => '',
            'ticket_name' => '',
            'amount' => '',
            'addons' => array(),
            'invitation_tokens' => []
        ];
        
        $collecting_tokens = false;
        
        foreach ($content_lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'Name: ') !== false) {
                $info['name'] = str_replace('Name: ', '', $line);
            } elseif (strpos($line, 'Email: ') !== false) {
                $info['email'] = str_replace('Email: ', '', $line);
            } elseif (strpos($line, 'Phone: ') !== false) {
                $info['phone'] = str_replace('Phone: ', '', $line);
            } elseif (strpos($line, 'Ticket Type: ') !== false) {
                $ticket_key = str_replace('Ticket Type: ', '', $line);
                $info['ticket_type'] = $ticket_key;
                $info['ticket_name'] = $this->get_ticket_name_from_key($ticket_key);
            } elseif (strpos($line, 'Add-on: ') !== false) {
                // Extract addon information
                $addon_line = str_replace('Add-on: ', '', $line);
                if (strpos($addon_line, ' - Rs. ') !== false) {
                    $addon_parts = explode(' - Rs. ', $addon_line);
                    if (count($addon_parts) == 2) {
                        $info['addons'][] = array(
                            'name' => trim($addon_parts[0]),
                            'price' => trim($addon_parts[1])
                        );
                    }
                }
            } elseif (strpos($line, 'Amount Paid: ') !== false) {
                $info['amount'] = str_replace('Amount Paid: ', '', $line);
            } elseif (strpos($line, 'INVITATION KEYS:') !== false) {
                $collecting_tokens = true;
            } elseif ($collecting_tokens && strpos($line, '- ') !== false) {
                $token = str_replace('- ', '', $line);
                if (!empty($token)) {
                    $info['invitation_tokens'][] = $token;
                }
            } elseif ($collecting_tokens && strpos($line, 'IMPORTANT REMINDERS:') !== false) {
                $collecting_tokens = false;
            }
        }
        
        return $info;
    }

    /**
     * Escape text for PDF format
     */
    private function escape_pdf_text($text) {
        // Basic PDF text escaping
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        $text = str_replace("\r", '', $text);
        $text = str_replace("\n", '', $text);
        $text = str_replace("\t", ' ', $text);
        return $text;
    }
    
    /**
     * Generate PDF with TCPDF (if available)
     */
    private function generate_pdf_with_tcpdf($purchase, $filename) {
        // Fallback to simple PDF if TCPDF implementation is complex
        $this->generate_simple_pdf($purchase, $filename);
    }
    
    /**
     * Generate PDF with mPDF (if available)
     */
    private function generate_pdf_with_mpdf($purchase, $filename) {
        // Fallback to simple PDF if mPDF implementation is complex
        $this->generate_simple_pdf($purchase, $filename);
    }

    /**
     * Save step data to session
     */
    public function ajax_save_step_data() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');
            
            $step = intval($_POST['step'] ?? 0);
            $data = json_decode(stripslashes($_POST['data'] ?? ''), true);
            
            if (!$step || !is_array($data)) {
                wp_send_json_error('Invalid step data');
                return;
            }
            
            $wizard = ResetBookingWizard::getInstance();
            $success = $wizard->update_step_data($step, $data);
            
            if ($success) {
                wp_send_json_success(array(
                    'message' => 'Step data saved successfully',
                    'step' => $step
                ));
            } else {
                wp_send_json_error('Failed to save step data');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Process free booking confirmation
     */
    public function ajax_process_free_booking() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');
            
            $wizard = ResetBookingWizard::getInstance();
            $booking_data = $wizard->get_complete_booking_data();
            
            if (empty($booking_data)) {
                wp_send_json_error('No booking data found');
                return;
            }
            
            // FIXED: Check both key type AND total amount
            // Only process if it's a free key with zero total amount
            if (!$booking_data['is_free_token'] || $booking_data['pricing']['total_amount'] > 0) {
                wp_send_json_error('This booking requires payment processing');
                return;
            }
            
            // Process the free booking
            $result = $this->process_wizard_booking($booking_data, true);
            
            if ($result['success']) {
                $wizard->clear_session();
                wp_send_json_success(array(
                    'message' => 'Free ticket confirmed successfully',
                    'redirect_url' => site_url('/reset/booking-success?ref=' . $result['purchase_id'])
                ));
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Process payment booking
     */
    public function ajax_process_payment() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');
            
            $wizard = ResetBookingWizard::getInstance();
            $booking_data = $wizard->get_complete_booking_data();
            
            if (empty($booking_data)) {
                wp_send_json_error('No booking data found');
                return;
            }
            
            // FIXED: Check both key type AND total amount
            // Free keys with paid add-ons should go through payment flow
            if ($booking_data['is_free_token'] && $booking_data['pricing']['total_amount'] == 0) {
                wp_send_json_error('Free tokens with zero amount should use free confirmation');
                return;
            }
            
            // Process the paid booking
            $result = $this->process_wizard_booking($booking_data, false);
            
            if ($result['success']) {
                if ($result['redirect_to_gateway']) {
                    wp_send_json_success(array(
                        'message' => 'Redirecting to payment gateway',
                        'redirect_url' => $result['gateway_url']
                    ));
                } else {
                    // Local development or zero amount
                    $wizard->clear_session();
                    wp_send_json_success(array(
                        'message' => 'Payment processed successfully',
                        'redirect_url' => site_url('/reset/booking-success?ref=' . $result['purchase_id'])
                    ));
                }
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Process booking from wizard data
     */
    private function process_wizard_booking($booking_data, $is_free = false) {
        $core = ResetCore::getInstance();
        $tokens = ResetTokens::getInstance();
        $payments = ResetPayments::getInstance();
        $emails = ResetEmails::getInstance();
        $addons = ResetAddons::getInstance();
        
        // Extract data
        $personal_info = $booking_data['personal_info'];
        $ticket_selection = $booking_data['ticket_selection'];
        $selected_addons = $booking_data['selected_addons'];
        $pricing = $booking_data['pricing'];
        
        // Validate key is still available
        $token_validation = $tokens->validate_token($booking_data['token_code']);
        if (!$token_validation['valid']) {
            return array(
                'success' => false,
                'message' => 'Key is no longer valid: ' . $token_validation['message']
            );
        }
        
        // Create purchase record
        $ticket_type = $booking_data['is_free_token'] ? $booking_data['token_type'] : ($ticket_selection['ticket_type'] ?? '');
        $ticket_price = $pricing['ticket_price'] ?? 0;
        $addon_total = $pricing['addon_total'] ?? 0;
        $total_amount = $pricing['total_amount'] ?? 0;
        
        // CRITICAL FIX: Always generate payment reference upfront for any booking requiring payment
        $payment_reference = '';
        if ($total_amount > 0) {
            $payment_reference = $core->generate_payment_reference($booking_data['token_code']);
        }
        
        $purchase_data = array(
            'token_id' => $booking_data['token_id'],
            'purchaser_name' => $personal_info['name'],
            'purchaser_email' => $personal_info['email'],
            'purchaser_phone' => $personal_info['phone'],
            'gaming_name' => $personal_info['gaming_name'] ?? '',
            'ticket_type' => $ticket_type,
            'ticket_price' => $ticket_price,
            'addon_total' => $addon_total,
            'total_amount' => $total_amount,
            'payment_status' => $is_free || $total_amount == 0 ? 'completed' : 'pending',
            'payment_reference' => $payment_reference
        );
        
        $purchase_id = ResetDatabase::getInstance()->create_purchase($purchase_data);
        if (!$purchase_id) {
            return array(
                'success' => false,
                'message' => 'Failed to create purchase record'
            );
        }
        
        // Save addons to purchase
        if (!empty($selected_addons)) {
            $addons->save_addons_to_purchase($purchase_id, $selected_addons, $booking_data['token_type']);
            // Update purchase totals
            ResetDatabase::getInstance()->update_purchase_totals($purchase_id, $addon_total, $total_amount);
            // Calculate and update drink count
            $drink_count = ResetDatabase::getInstance()->calculate_purchase_drink_count($purchase_id);
            ResetDatabase::getInstance()->update_purchase_drink_count($purchase_id, $drink_count);
        } else {
            // No addons selected, set drink count to 0
            ResetDatabase::getInstance()->update_purchase_drink_count($purchase_id, 0);
        }
        
        // CRITICAL FIX: Do NOT mark key as used here - wait for payment confirmation
        // key will be marked as used in the payment callback handler after successful payment
        
                if ($is_free || $total_amount == 0) {
            // Complete free booking immediately
            ResetDatabase::getInstance()->update_purchase($purchase_id, array('payment_status' => 'completed'));
            
            // For free bookings, mark key as used immediately since no payment is required
            $tokens->use_token($booking_data['token_id'], array(
                'name' => $personal_info['name'],
                'email' => $personal_info['email'],
                'phone' => $personal_info['phone']
            ));
            
            // Generate invitation keys for free users too
            $invitation_tokens = $tokens->generate_invitation_tokens($booking_data['token_id']);
            
            // Get updated purchase data
            $purchase = ResetDatabase::getInstance()->get_purchase_by_id($purchase_id);
            
            // Send confirmation email
            $emails->send_ticket_confirmation($purchase, $invitation_tokens);
            
            return array(
                'success' => true,
                'purchase_id' => $purchase_id,
                'redirect_to_gateway' => false
            );
        } else {
            // Check if local development mode
            if ($this->is_local_development()) {
                // Complete payment immediately in local mode
                ResetDatabase::getInstance()->update_purchase($purchase_id, array('payment_status' => 'completed'));
                
                // For local development, mark key as used immediately since payment is bypassed
                $tokens->use_token($booking_data['token_id'], array(
                    'name' => $personal_info['name'],
                    'email' => $personal_info['email'],
                    'phone' => $personal_info['phone']
                ));
                
                $invitation_tokens = $tokens->generate_invitation_tokens($booking_data['token_id']);
                
                // Get updated purchase data
                $purchase = ResetDatabase::getInstance()->get_purchase_by_id($purchase_id);
                
                // Send confirmation email
                $emails->send_ticket_confirmation($purchase, $invitation_tokens);
                
                return array(
                    'success' => true,
                    'purchase_id' => $purchase_id,
                    'redirect_to_gateway' => false
                );
            } else {
                // Use the already-generated payment reference
                $gateway = ResetSampathGateway::getInstance();
                
                // Prepare purchase data for payment gateway
                $gateway_purchase_data = array(
                    'ticket_price' => $total_amount, // Use total amount (includes addons)
                    'token_id' => $booking_data['token_id'],
                    'purchaser_email' => $personal_info['email'],
                    'ticket_type' => $ticket_type,
                    'payment_reference' => $payment_reference
                );
                
                $gateway_url = $gateway->generate_payment_url($purchase_id, $gateway_purchase_data);
                
                return array(
                    'success' => true,
                    'purchase_id' => $purchase_id,
                    'redirect_to_gateway' => true,
                    'gateway_url' => $gateway_url
                );
            }
        }
     }
     
    /**
     * Check if we're in local development environment
     */
    private function is_local_development(): bool {
        $local_domains = array(
            'localhost',
            '127.0.0.1',
            '::1',
            'nooballiance.local',
            'reset.local'
        );
        
        $current_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        
        // Check for common local development indicators
        if (in_array($current_host, $local_domains)) {
            return true;
        }
        
        // Check if it's a .local domain
        if (strpos($current_host, '.local') !== false) {
            return true;
        }
        
        // Check for development ports
        if (strpos($current_host, ':8000') !== false || 
            strpos($current_host, ':3000') !== false || 
            strpos($current_host, ':8080') !== false) {
            return true;
        }
        
        // Check WordPress debug constants
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_ENV') && WP_ENV === 'development') {
            return true;
        }
        
        return false;
    }
     
    /**
     * Initialize wizard session
     */
    public function ajax_initialize_wizard() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');
            
            $token_code = sanitize_text_field($_POST['token_code'] ?? '');
            
            if (empty($token_code)) {
                wp_send_json_error('Key code is required');
                return;
            }
            
            $wizard = ResetBookingWizard::getInstance();
            $result = $wizard->initialize_session($token_code);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => 'Wizard session initialized successfully',
                    'token_type' => $result['token_type']
                ));
            } else {
                wp_send_json_error($result['message'] ?? 'Failed to initialize wizard session');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Debug gateway configuration and callback URL
     */
    public function ajax_debug_gateway() {
        try {
            check_ajax_referer('reset_nonce', 'nonce');

            $gateway = ResetSampathGateway::getInstance();
            $config = $gateway->get_gateway_config();
            $callback_url = site_url('/reset/payment-return');

            wp_send_json_success(array(
                'gateway_config' => $config,
                'callback_url' => $callback_url
            ));
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for capacity impact preview
     */
    public function ajax_preview_capacity_impact() {
        try {
            // Check nonce
            check_ajax_referer('reset_admin_nonce', 'nonce');
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            // Get the new settings from the request
            $new_settings = array(
                'target_capacity' => intval($_POST['target_capacity'] ?? 0),
                'max_capacity' => intval($_POST['max_capacity'] ?? 0),
                'alert_threshold' => intval($_POST['alert_threshold'] ?? 0),
                'early_bird_threshold' => intval($_POST['early_bird_threshold'] ?? 0),
                'late_bird_threshold' => intval($_POST['late_bird_threshold'] ?? 0),
                'very_late_bird_threshold' => intval($_POST['very_late_bird_threshold'] ?? 0)
            );
            
            // Get capacity manager instance
            $capacity_manager = ResetCapacity::getInstance();
            
            // Get impact analysis
            $impact = $capacity_manager->get_capacity_impact_analysis($new_settings);
            
            wp_send_json_success(array(
                'impact' => $impact,
                'new_settings' => $new_settings
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for updating drink counts in bar management
     */
    public function ajax_update_drink_count() {
        try {
            // Check nonce
            check_ajax_referer('reset_ajax_nonce', 'nonce');
            
            // Get and validate parameters
            $purchase_id = intval($_POST['purchase_id'] ?? 0);
            $drink_count = intval($_POST['drink_count'] ?? 0);
            
            if ($purchase_id <= 0) {
                wp_send_json_error('Invalid purchase ID');
                return;
            }
            
            if ($drink_count < 0 || $drink_count > 99) {
                wp_send_json_error('Drink count must be between 0 and 99');
                return;
            }
            
            // Get database instance
            $db = ResetDatabase::getInstance();
            
            // Update the drink count
            $result = $db->update_purchase_drink_count($purchase_id, $drink_count);
            
            if ($result) {
                // Get calculated drinks for comparison
                $calculated_drinks = $db->calculate_purchase_drink_count($purchase_id);
                
                wp_send_json_success(array(
                    'purchase_id' => $purchase_id,
                    'new_count' => $drink_count,
                    'calculated_drinks' => $calculated_drinks,
                    'message' => 'Drink count updated successfully'
                ));
            } else {
                wp_send_json_error('Failed to update drink count');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for updating check-in status
     */
    public function ajax_update_check_in() {
        try {
            // Check nonce
            check_ajax_referer('reset_ajax_nonce', 'nonce');
            
            // Check user permissions
            if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            // Get and validate parameters
            $purchase_id = intval($_POST['purchase_id'] ?? 0);
            
            if ($purchase_id <= 0) {
                wp_send_json_error('Invalid purchase ID');
                return;
            }
            
            // Get database instance
            $db = ResetDatabase::getInstance();
            
            // Get current purchase data
            $purchase = $db->get_purchase_by_id($purchase_id);
            if (!$purchase) {
                wp_send_json_error('Purchase not found');
                return;
            }
            
            // Check if already checked in
            if (intval($purchase['checked_in'])) {
                wp_send_json_error('Customer is already checked in');
                return;
            }
            
            // Get current user info
            $current_user = wp_get_current_user();
            $user_info = array(
                'user_login' => $current_user->user_login
            );
            
            // Update check-in status
            $result = $db->update_purchase_check_in($purchase_id, true, $user_info);
            
            if ($result) {
                wp_send_json_success(array(
                    'purchase_id' => $purchase_id,
                    'checked_in' => true,
                    'checked_in_by' => $current_user->user_login,
                    'checked_in_at' => current_time('mysql'),
                    'message' => 'Customer checked in successfully'
                ));
            } else {
                wp_send_json_error('Failed to update check-in status');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for getting check-in statistics
     */
    public function ajax_get_checkin_stats() {
        try {
            // Check nonce
            check_ajax_referer('reset_ajax_nonce', 'nonce');
            
            // Check user permissions
            if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            // Get database instance
            $db = ResetDatabase::getInstance();
            
            // Get statistics
            $stats = $db->get_total_attendees_vs_checked_in();
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle capacity settings update
     */
    public function handle_update_capacity_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['capacity_nonce'] ?? '', 'reset_capacity_action')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $capacity_manager = ResetCapacity::getInstance();
        
        // Get form data
        $capacity_settings = array(
            'target_capacity' => intval($_POST['target_capacity'] ?? 0),
            'max_capacity' => intval($_POST['max_capacity'] ?? 0),
            'alert_threshold' => intval($_POST['alert_threshold'] ?? 0)
        );
        
        $threshold_settings = array(
            'early_bird' => intval($_POST['early_bird_threshold'] ?? 0),
            'late_bird' => intval($_POST['late_bird_threshold'] ?? 0),
            'very_late_bird' => intval($_POST['very_late_bird_threshold'] ?? 0)
        );
        
        // Validate settings
        $capacity_errors = $capacity_manager->validate_capacity_settings($capacity_settings);
        
        if (!empty($capacity_errors)) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => urlencode(implode(' ', $capacity_errors))
            ), admin_url('admin.php')));
            exit;
        }
        
        // Update capacity settings
        $capacity_result = $capacity_manager->update_capacity_settings($capacity_settings);
        
        // Update thresholds
        $threshold_result = $capacity_manager->update_capacity_thresholds($threshold_settings);
        
        // Check if the update was successful by verifying the settings are now in place
        // Note: update_option() returns false if the value doesn't change, so we need to verify actual values
        $current_settings = $capacity_manager->get_capacity_settings();
        
        $capacity_success = (
            $current_settings['main_capacity']['target_capacity'] == $capacity_settings['target_capacity'] &&
            $current_settings['main_capacity']['max_capacity'] == $capacity_settings['max_capacity'] &&
            $current_settings['main_capacity']['alert_threshold'] == $capacity_settings['alert_threshold']
        );
        
        $threshold_success = (
            $current_settings['ticket_thresholds']['early_bird'] == $threshold_settings['early_bird'] &&
            $current_settings['ticket_thresholds']['late_bird'] == $threshold_settings['late_bird'] &&
            $current_settings['ticket_thresholds']['very_late_bird'] == $threshold_settings['very_late_bird']
        );
        
        if ($capacity_success && $threshold_success) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'success' => 'settings_updated'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Failed to update capacity settings.'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle ticket thresholds update
     */
    public function handle_update_ticket_thresholds() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['threshold_nonce'] ?? '', 'update_ticket_thresholds')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $capacity_manager = ResetCapacity::getInstance();
        
        // Get form data
        $threshold_settings = array(
            'early_bird' => intval($_POST['early_bird'] ?? 0),
            'regular' => intval($_POST['regular'] ?? 0),
            'late_bird' => intval($_POST['late_bird'] ?? 0)
        );
        
        // Update thresholds
        $result = $capacity_manager->update_capacity_thresholds($threshold_settings);
        
        if ($result) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'success' => 'thresholds_updated'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Failed to update ticket thresholds.'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle reset to defaults
     */
    public function handle_reset_to_defaults() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['capacity_nonce'] ?? '', 'reset_capacity_action')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $capacity_manager = ResetCapacity::getInstance();
        $result = $capacity_manager->reset_capacity_settings_to_defaults();
        
        if ($result) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'success' => 'settings_reset'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Failed to reset settings to defaults.'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle database tables recreation
     */
    public function handle_recreate_database_tables() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['capacity_nonce'] ?? '', 'reset_capacity_action')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Only allow in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            wp_die(__('This function is only available in debug mode.'));
        }
        
        try {
            $this->create_database_tables();
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'success' => 'database_recreated'
            ), admin_url('admin.php')));
        } catch (Exception $e) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Failed to recreate database tables: ' . $e->getMessage()
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle manual database migration
     */
    public function handle_run_database_migration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['migration_nonce'] ?? '', 'reset_migration_action')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        try {
            require_once(RESET_PLUGIN_PATH . 'includes/class-reset-migration.php');
            $migration = ResetMigration::getInstance();
            $result = $migration->check_and_migrate();
            
            if ($result) {
                wp_redirect(add_query_arg(array(
                    'page' => 'reset-capacity-management',
                    'success' => 'migration_completed'
                ), admin_url('admin.php')));
            } else {
                wp_redirect(add_query_arg(array(
                    'page' => 'reset-capacity-management',
                    'error' => 'Migration failed. Check error logs for details.'
                ), admin_url('admin.php')));
            }
        } catch (Exception $e) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Migration exception: ' . $e->getMessage()
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Handle force recreate tables (development only)
     */
    public function handle_force_recreate_tables() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['migration_nonce'] ?? '', 'reset_migration_action')) {
            wp_die(__('Security check failed. Please try again.'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Only allow in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            wp_die(__('This function is only available in debug mode.'));
        }
        
        try {
            require_once(RESET_PLUGIN_PATH . 'includes/class-reset-migration.php');
            $migration = ResetMigration::getInstance();
            $result = $migration->force_recreate_tables();
            
            if ($result) {
                wp_redirect(add_query_arg(array(
                    'page' => 'reset-capacity-management',
                    'success' => 'tables_recreated'
                ), admin_url('admin.php')));
            } else {
                wp_redirect(add_query_arg(array(
                    'page' => 'reset-capacity-management',
                    'error' => 'Failed to recreate tables. Check error logs for details.'
                ), admin_url('admin.php')));
            }
        } catch (Exception $e) {
            wp_redirect(add_query_arg(array(
                'page' => 'reset-capacity-management',
                'error' => 'Recreate tables exception: ' . $e->getMessage()
            ), admin_url('admin.php')));
        }
        exit;
    }
    

    


}

// Initialize plugin
ResetTicketingPlugin::getInstance();

// Add cron job handlers
add_action('reset_reminder_emails', function() {
    if (class_exists('ResetEmails')) {
        ResetEmails::getInstance()->send_reminder_emails();
    }
});

add_action('reset_capacity_check', function() {
    if (class_exists('ResetTokens')) {
        ResetTokens::getInstance()->check_capacity_and_cancel_tokens();
    }
}); 