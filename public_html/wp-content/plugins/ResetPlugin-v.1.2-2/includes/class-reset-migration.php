<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database migration class for RESET ticketing system
 * Handles safe database upgrades without data loss
 */
class ResetMigration {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * WordPress database object
     */
    private $wpdb;
    
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
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Check if database needs migration and run if needed
     */
    public function check_and_migrate() {
        $current_version = get_option('reset_plugin_db_version', '0.0.0');
        $target_version = RESET_DB_VERSION;
        
        // If versions match, no migration needed
        if (version_compare($current_version, $target_version, '>=')) {
            return true;
        }
        
        // Log migration start
        error_log("RESET Plugin: Starting database migration from {$current_version} to {$target_version}");
        
        // Run migration
        $result = $this->migrate_from_to($current_version, $target_version);
        
        if ($result) {
            // Update version in database
            update_option('reset_plugin_db_version', $target_version);
            update_option('reset_plugin_last_migration', current_time('mysql'));
            error_log("RESET Plugin: Migration completed successfully to version {$target_version}");
        } else {
            error_log("RESET Plugin: Migration failed from {$current_version} to {$target_version}");
        }
        
        return $result;
    }
    
    /**
     * Run migration from one version to another
     */
    private function migrate_from_to($from_version, $to_version) {
        try {
            // Check if this is truly a fresh install (no tables exist)
            if ($from_version === '0.0.0') {
                $db_validation = $this->validate_database();
                
                // If tables exist but no version recorded, treat as existing installation
                if ($db_validation['existing_tables'] > 0) {
                    error_log("RESET Plugin: Tables exist but no version recorded. Treating as existing installation at 1.3.0");
                    $from_version = '1.3.0'; // Assume current schema
                } else {
                    // True fresh install
                    return $this->fresh_install();
                }
            }
            
            // Run incremental migrations
            $migrations = $this->get_migration_steps($from_version, $to_version);
            
            foreach ($migrations as $migration) {
                $method = $migration['method'];
                if (method_exists($this, $method)) {
                    $result = $this->$method();
                    if (!$result) {
                        error_log("RESET Plugin: Migration step {$method} failed");
                        return false;
                    }
                    error_log("RESET Plugin: Migration step {$method} completed");
                } else {
                    error_log("RESET Plugin: Migration method {$method} not found");
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("RESET Plugin: Migration exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get migration steps needed between versions
     */
    private function get_migration_steps($from_version, $to_version) {
        $migrations = array();
        
        // Define migration steps
        $available_migrations = array(
            '1.0.0' => array('method' => 'migrate_to_1_0_0', 'description' => 'Initial database setup'),
            '1.1.0' => array('method' => 'migrate_to_1_1_0', 'description' => 'Add gaming_name column'),
            '1.2.0' => array('method' => 'migrate_to_1_2_0', 'description' => 'Add addon system tables'),
            '1.3.0' => array('method' => 'migrate_to_1_3_0', 'description' => 'Current schema improvements'),
            '1.3.1' => array('method' => 'migrate_to_1_3_1', 'description' => 'Add sent tracking columns to tokens table'),
            '1.3.2' => array('method' => 'migrate_to_1_3_2', 'description' => 'Fix revenue calculations and ensure total_amount accuracy'),
            '1.3.3' => array('method' => 'migrate_to_1_3_3', 'description' => 'Create dedicated capacity management table'),
            '1.3.4' => array('method' => 'migrate_to_1_3_4', 'description' => 'Add drink count functionality to addons and purchases'),
            '1.3.5' => array('method' => 'migrate_to_1_3_5', 'description' => 'Implement conditional drink count for afterparty_package_2'),
            '1.3.6' => array('method' => 'migrate_to_1_3_6', 'description' => 'Use newest addon only for drink count calculation'),
            '1.3.7' => array('method' => 'migrate_to_1_3_7', 'description' => 'Add email campaigns recipients table'),
            '1.3.8' => array('method' => 'migrate_to_1_3_8', 'description' => 'Add token_code column to email recipients table'),
            '1.3.9' => array('method' => 'migrate_to_1_3_9', 'description' => 'Add check-in functionality columns'),
        );
        
        // Find migrations needed
        foreach ($available_migrations as $version => $migration) {
            if (version_compare($from_version, $version, '<') && version_compare($version, $to_version, '<=')) {
                $migrations[] = $migration;
            }
        }
        
        return $migrations;
    }
    
    /**
     * Fresh installation - create all tables
     */
    private function fresh_install() {
        // Use the existing create_database_tables method
        $plugin = ResetTicketingPlugin::getInstance();
        if (method_exists($plugin, 'create_database_tables')) {
            $plugin->create_database_tables();
            return true;
        }
        return false;
    }
    
    /**
     * Migration to version 1.0.0 - Initial setup
     */
    private function migrate_to_1_0_0() {
        return $this->fresh_install();
    }
    
    /**
     * Migration to version 1.1.0 - Add gaming_name column
     */
    private function migrate_to_1_1_0() {
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        // Check if gaming_name column exists
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'gaming_name'
            )
        );
        
        // Add gaming_name column if it doesn't exist
        if (empty($column_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `gaming_name` varchar(255) NULL 
                AFTER `purchaser_phone`"
            );
            
            if ($result === false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Migration to version 1.2.0 - Add addon system tables
     */
    private function migrate_to_1_2_0() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Create addons table if it doesn't exist
        $table_addons = $this->wpdb->prefix . 'reset_addons';
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_addons}'");
        
        if (!$table_exists) {
            $sql_addons = "CREATE TABLE {$table_addons} (
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
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_addons);
        }
        
        // Create purchase_addons table if it doesn't exist
        $table_purchase_addons = $this->wpdb->prefix . 'reset_purchase_addons';
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_purchase_addons}'");
        
        if (!$table_exists) {
            $sql_purchase_addons = "CREATE TABLE {$table_purchase_addons} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                purchase_id bigint(20) unsigned NOT NULL,
                addon_id bigint(20) unsigned NOT NULL,
                addon_price decimal(10,2) NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_purchase_id (purchase_id),
                KEY idx_addon_id (addon_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_purchase_addons);
        }
        
        // Add addon_total and total_amount columns to purchases table if they don't exist
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        $addon_total_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'addon_total'
            )
        );
        
        if (empty($addon_total_exists)) {
            $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `addon_total` decimal(10,2) DEFAULT 0.00 
                AFTER `ticket_price`"
            );
        }
        
        $total_amount_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'total_amount'
            )
        );
        
        if (empty($total_amount_exists)) {
            $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `total_amount` decimal(10,2) DEFAULT 0.00 
                AFTER `addon_total`"
            );
        }
        
        return true;
    }
    
    /**
     * Migration to version 1.3.0 - Current schema improvements
     */
    private function migrate_to_1_3_0() {
        // Run full dbDelta to ensure all current schema is applied
        $plugin = ResetTicketingPlugin::getInstance();
        if (method_exists($plugin, 'create_database_tables')) {
            $plugin->create_database_tables();
        }
        
        return true;
    }
    
    /**
     * Migration to version 1.3.1 - Add sent tracking columns to tokens table
     */
    private function migrate_to_1_3_1() {
        $table_tokens = $this->wpdb->prefix . 'reset_tokens';
        
        // Array of columns to add
        $columns_to_add = array(
            'sent_to_email' => "varchar(255) NULL AFTER `created_by`",
            'sent_to_name' => "varchar(255) NULL AFTER `sent_to_email`",
            'sent_by' => "varchar(255) NULL AFTER `sent_to_name`",
            'sent_at' => "timestamp NULL AFTER `sent_by`",
            'sent_notes' => "text NULL AFTER `sent_at`"
        );
        
        foreach ($columns_to_add as $column_name => $column_definition) {
            // Check if column exists
            $column_exists = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SHOW COLUMNS FROM `{$table_tokens}` LIKE %s",
                    $column_name
                )
            );
            
            // Add column if it doesn't exist
            if (empty($column_exists)) {
                $result = $this->wpdb->query(
                    "ALTER TABLE `{$table_tokens}` 
                    ADD COLUMN `{$column_name}` {$column_definition}"
                );
                
                if ($result === false) {
                    error_log("RESET Plugin: Failed to add {$column_name} column");
                    return false;
                }
                
                error_log("RESET Plugin: Successfully added {$column_name} column");
            } else {
                error_log("RESET Plugin: Column {$column_name} already exists");
            }
        }
        
        return true;
    }
    
    /**
     * Migration to version 1.3.2 - Fix revenue calculations and ensure total_amount accuracy
     */
    private function migrate_to_1_3_2() {
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        error_log("RESET Plugin: Starting migration to 1.3.2 - Revenue calculation fixes");
        
        // Ensure total_amount column exists (should exist from previous migrations)
        $total_amount_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'total_amount'
            )
        );
        
        if (empty($total_amount_exists)) {
            // Add total_amount column if it doesn't exist
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `total_amount` decimal(10,2) DEFAULT 0.00 
                AFTER `addon_total`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add total_amount column");
                return false;
            }
            
            error_log("RESET Plugin: Successfully added total_amount column");
        }
        
        // Update any records where total_amount is 0 but should be calculated
        // This ensures existing records have correct total_amount values
        $update_result = $this->wpdb->query(
            "UPDATE `{$table_purchases}` 
            SET `total_amount` = (`ticket_price` + `addon_total`) 
            WHERE `total_amount` = 0 
            AND (`ticket_price` > 0 OR `addon_total` > 0)"
        );
        
        if ($update_result !== false) {
            error_log("RESET Plugin: Updated {$update_result} purchase records with correct total_amount values");
        }
        
        // Validate that all completed purchases have consistent total_amount values
        $inconsistent_records = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM `{$table_purchases}` 
            WHERE `payment_status` = 'completed' 
            AND ABS(`total_amount` - (`ticket_price` + `addon_total`)) > 0.01"
        );
        
        if ($inconsistent_records > 0) {
            error_log("RESET Plugin: Warning - {$inconsistent_records} records still have inconsistent total_amount values");
            
            // Auto-fix the inconsistent records
            $fix_result = $this->wpdb->query(
                "UPDATE `{$table_purchases}` 
                SET `total_amount` = (`ticket_price` + `addon_total`) 
                WHERE `payment_status` = 'completed' 
                AND ABS(`total_amount` - (`ticket_price` + `addon_total`)) > 0.01"
            );
            
            if ($fix_result !== false) {
                error_log("RESET Plugin: Auto-fixed {$fix_result} inconsistent total_amount records");
            }
        }
        
        error_log("RESET Plugin: Migration 1.3.2 completed successfully - Revenue calculations now use total_amount");
        return true;
    }
    
    /**
     * Migration to version 1.3.3 - Create dedicated capacity management table
     */
    private function migrate_to_1_3_3() {
        error_log("RESET Plugin: Starting migration to 1.3.3 - Creating capacity management table");
        
        $table_capacity = $this->wpdb->prefix . 'reset_capacity_config';
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Check if table already exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        
        if (!$table_exists) {
            // Create the capacity configuration table
            $sql = "CREATE TABLE {$table_capacity} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                config_name varchar(50) NOT NULL DEFAULT 'default',
                target_capacity int(11) NOT NULL DEFAULT 250,
                max_capacity int(11) NOT NULL DEFAULT 300,
                alert_threshold int(11) NOT NULL DEFAULT 225,
                early_bird_threshold int(11) NOT NULL DEFAULT 100,
                late_bird_threshold int(11) NOT NULL DEFAULT 150,
                very_late_bird_threshold int(11) NOT NULL DEFAULT 200,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by varchar(100) DEFAULT '',
                is_active tinyint(1) DEFAULT 1,
                change_notes text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_config_name (config_name),
                KEY idx_is_active (is_active),
                KEY idx_updated_at (updated_at)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $result = dbDelta($sql);
            
            if ($this->wpdb->last_error) {
                error_log("RESET Plugin: Failed to create capacity table: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully created capacity configuration table");
        }
        
        // Migrate existing WordPress options to the database table
        $existing_capacity_settings = get_option('reset_capacity_settings', array());
        $existing_threshold_settings = get_option('reset_ticket_thresholds', array());
        
        // Set default values from current settings or use defaults
        $target_capacity = !empty($existing_capacity_settings['target_capacity']) ? 
            intval($existing_capacity_settings['target_capacity']) : 250;
        $max_capacity = !empty($existing_capacity_settings['max_capacity']) ? 
            intval($existing_capacity_settings['max_capacity']) : 300;
        $alert_threshold = intval($target_capacity * 0.9);
        
        $early_bird = !empty($existing_threshold_settings['early_bird']) ? 
            intval($existing_threshold_settings['early_bird']) : 100;
        $late_bird = !empty($existing_threshold_settings['late_bird']) ? 
            intval($existing_threshold_settings['late_bird']) : 150;
        $very_late_bird = !empty($existing_threshold_settings['very_late_bird']) ? 
            intval($existing_threshold_settings['very_late_bird']) : 200;
        
        // Check if we already have a default configuration
        $existing_config = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM {$table_capacity} WHERE config_name = %s AND is_active = 1",
                'default'
            )
        );
        
        if (!$existing_config) {
            // Insert the migrated settings as the default active configuration
            $insert_result = $this->wpdb->insert(
                $table_capacity,
                array(
                    'config_name' => 'default',
                    'target_capacity' => $target_capacity,
                    'max_capacity' => $max_capacity,
                    'alert_threshold' => $alert_threshold,
                    'early_bird_threshold' => $early_bird,
                    'late_bird_threshold' => $late_bird,
                    'very_late_bird_threshold' => $very_late_bird,
                    'updated_by' => 'System Migration',
                    'is_active' => 1,
                    'change_notes' => 'Migrated from WordPress options during 1.3.3 upgrade'
                ),
                array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
            );
            
            if ($insert_result === false) {
                error_log("RESET Plugin: Failed to insert default capacity configuration: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully migrated capacity settings to database table");
            error_log("RESET Plugin: Migrated values - Target: {$target_capacity}, Max: {$max_capacity}");
        } else {
            error_log("RESET Plugin: Default capacity configuration already exists, skipping migration");
        }
        
        // Update the validation to include the new table
        $this->update_table_validation_list();
        
        error_log("RESET Plugin: Migration 1.3.3 completed successfully - Capacity management now uses dedicated table");
        return true;
    }
    
    /**
     * Migration to version 1.3.4 - Add drink count functionality to addons and purchases
     */
    private function migrate_to_1_3_4() {
        error_log("RESET Plugin: Starting migration to 1.3.4 - Adding drink count functionality");
        
        // Step 1: Add drink_count column to addons table
        $table_addons = $this->wpdb->prefix . 'reset_addons';
        
        // Check if drink_count column exists in addons table
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_addons}` LIKE %s",
                'drink_count'
            )
        );
        
        if (empty($column_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_addons}` 
                ADD COLUMN `drink_count` int(11) DEFAULT 0 
                AFTER `price`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add drink_count column to addons table");
                return false;
            }
            
            error_log("RESET Plugin: Successfully added drink_count column to addons table");
        }
        
        // Step 2: Add total_drink_count column to purchases table
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        // Check if total_drink_count column exists in purchases table
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'total_drink_count'
            )
        );
        
        if (empty($column_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `total_drink_count` int(11) DEFAULT 0 
                AFTER `total_amount`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add total_drink_count column to purchases table");
                return false;
            }
            
            error_log("RESET Plugin: Successfully added total_drink_count column to purchases table");
        }
        
        // Step 3: Update existing addons with drink counts
        $drink_mapping = array(
            'afterparty_package_1' => 2,
            'afterparty_package_2' => 4
        );
        
        foreach ($drink_mapping as $addon_key => $drink_count) {
            $result = $this->wpdb->update(
                $table_addons,
                array('drink_count' => $drink_count),
                array('addon_key' => $addon_key),
                array('%d'),
                array('%s')
            );
            
            if ($result !== false) {
                error_log("RESET Plugin: Updated {$addon_key} with {$drink_count} drinks");
            } else {
                error_log("RESET Plugin: Failed to update {$addon_key} - addon may not exist yet");
            }
        }
        
        // Step 4: Calculate and update drink counts for existing purchases
        $purchases = $this->wpdb->get_results(
            "SELECT id FROM {$table_purchases} WHERE payment_status = 'completed'",
            ARRAY_A
        );
        
        $updated_count = 0;
        foreach ($purchases as $purchase) {
            $drink_total = $this->calculate_purchase_drink_total($purchase['id']);
            
            $result = $this->wpdb->update(
                $table_purchases,
                array('total_drink_count' => $drink_total),
                array('id' => $purchase['id']),
                array('%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated_count++;
            }
        }
        
        error_log("RESET Plugin: Updated drink counts for {$updated_count} existing purchases");
        error_log("RESET Plugin: Migration 1.3.4 completed successfully - Drink count functionality added");
        return true;
    }
    
    /**
     * Migration to version 1.3.5 - Implement conditional drink count for afterparty_package_2
     */
    private function migrate_to_1_3_5() {
        error_log("RESET Plugin: Starting migration to 1.3.5 - Conditional drink count for afterparty_package_2");
        
        $table_addons = $this->wpdb->prefix . 'reset_addons';
        
        // Step 1: Update afterparty_package_2 to show 3 drinks in admin panel
        $result = $this->wpdb->update(
            $table_addons,
            array('drink_count' => 3),
            array('addon_key' => 'afterparty_package_2'),
            array('%d'),
            array('%s')
        );
        
        if ($result !== false) {
            error_log("RESET Plugin: Updated afterparty_package_2 admin display to 3 drinks");
        } else {
            error_log("RESET Plugin: Failed to update afterparty_package_2 - addon may not exist");
        }
        
        error_log("RESET Plugin: Migration 1.3.5 completed successfully - Conditional drink count implemented");
        return true;
    }
    
    /**
     * Migration to version 1.3.6 - Use newest addon only for drink count calculation
     */
    private function migrate_to_1_3_6() {
        error_log("RESET Plugin: Starting migration to 1.3.6 - Newest addon only for drink count calculation");
        
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        // Get all completed purchases
        $purchases = $this->wpdb->get_results(
            "SELECT id FROM {$table_purchases} WHERE payment_status = 'completed'",
            ARRAY_A
        );
        
        $updated_count = 0;
        $total_purchases = count($purchases);
        
        error_log("RESET Plugin: Found {$total_purchases} completed purchases to recalculate");
        
        foreach ($purchases as $purchase) {
            $purchase_id = $purchase['id'];
            
            // Calculate new drink count using newest addon only logic
            $new_drink_count = $this->calculate_purchase_drink_total_newest_only($purchase_id);
            
            // Update the purchase record
            $result = $this->wpdb->update(
                $table_purchases,
                array('total_drink_count' => $new_drink_count),
                array('id' => $purchase_id),
                array('%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated_count++;
                
                // Log specific cases for debugging
                if ($new_drink_count !== $this->calculate_purchase_drink_total($purchase_id)) {
                    error_log("RESET Plugin: Purchase {$purchase_id} drink count changed from old logic to {$new_drink_count}");
                }
            } else {
                error_log("RESET Plugin: Failed to update drink count for purchase {$purchase_id}");
            }
        }
        
        error_log("RESET Plugin: Migration 1.3.6 completed - Updated drink counts for {$updated_count}/{$total_purchases} purchases");
        return true;
    }
    
    /**
     * Calculate total drink count for a purchase (used in migration) with conditional logic
     */
    private function calculate_purchase_drink_total($purchase_id) {
        $table_purchase_addons = $this->wpdb->prefix . 'reset_purchase_addons';
        $table_addons = $this->wpdb->prefix . 'reset_addons';
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        // Get purchase date for conditional logic
        $purchase = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT created_at FROM {$table_purchases} WHERE id = %d",
                $purchase_id
            ),
            ARRAY_A
        );
        $purchase_date = $purchase['created_at'] ?? '';
        
        // Get addons for this purchase
        $purchase_addons = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT pa.*, a.addon_key, a.drink_count 
                FROM {$table_purchase_addons} pa
                JOIN {$table_addons} a ON pa.addon_id = a.id
                WHERE pa.purchase_id = %d",
                $purchase_id
            ),
            ARRAY_A
        );
        
        $total_drinks = 0;
        
        // Drink count mapping for hardcoded/special addons
        $hardcoded_mapping = array(
            'afterpart_package_0' => 1
        );
        
        foreach ($purchase_addons as $addon) {
            $addon_key = $addon['addon_key'];
            
            if (isset($hardcoded_mapping[$addon_key])) {
                // Use hardcoded mapping for special addons
                $total_drinks += $hardcoded_mapping[$addon_key];
            } elseif ($addon_key === 'afterparty_package_2') {
                // Special conditional logic for afterparty_package_2
                $cutoff_date = '2025-07-23 00:00:00';
                if (strtotime($purchase_date) < strtotime($cutoff_date)) {
                    // Purchased before cutoff: 4 drinks (grandfathered)
                    $total_drinks += 4;
                } else {
                    // Purchased on/after cutoff: 3 drinks (current admin setting)
                    $total_drinks += 3;
                }
            } else {
                // Use database drink_count for other addons
                $total_drinks += intval($addon['drink_count']);
            }
        }
        
        return $total_drinks;
    }
    
    /**
     * Calculate drink count using newest addon only logic (for migration 1.3.6)
     */
    private function calculate_purchase_drink_total_newest_only($purchase_id) {
        $table_purchase_addons = $this->wpdb->prefix . 'reset_purchase_addons';
        $table_addons = $this->wpdb->prefix . 'reset_addons';
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        $table_tokens = $this->wpdb->prefix . 'reset_tokens';
        
        // Get purchase and token info
        $purchase = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT p.*, t.token_type 
                FROM {$table_purchases} p
                LEFT JOIN {$table_tokens} t ON p.token_id = t.id
                WHERE p.id = %d",
                $purchase_id
            ),
            ARRAY_A
        );
        
        if (!$purchase) {
            return 0;
        }
        
        $purchase_date = $purchase['created_at'] ?? '';
        $token_type = $purchase['token_type'] ?? '';
        
        // For polo_ordered users, check if they have paid addons
        if ($token_type === 'polo_ordered') {
            // First check if there are any paid addons (non-afterpart_package_0)
            $has_paid_addons = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$table_purchase_addons} pa
                    JOIN {$table_addons} a ON pa.addon_id = a.id
                    WHERE pa.purchase_id = %d 
                    AND a.addon_key != 'afterpart_package_0'",
                    $purchase_id
                )
            );
            
            if ($has_paid_addons > 0) {
                // Get newest paid addon (exclude free addon)
                $newest_addon = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT pa.*, a.addon_key, a.drink_count 
                        FROM {$table_purchase_addons} pa
                        JOIN {$table_addons} a ON pa.addon_id = a.id
                        WHERE pa.purchase_id = %d 
                        AND a.addon_key != 'afterpart_package_0'
                        ORDER BY pa.created_at DESC 
                        LIMIT 1",
                        $purchase_id
                    ),
                    ARRAY_A
                );
            } else {
                // Only free addon exists, get it
                $newest_addon = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT pa.*, a.addon_key, a.drink_count 
                        FROM {$table_purchase_addons} pa
                        JOIN {$table_addons} a ON pa.addon_id = a.id
                        WHERE pa.purchase_id = %d 
                        ORDER BY pa.created_at DESC 
                        LIMIT 1",
                        $purchase_id
                    ),
                    ARRAY_A
                );
            }
        } else {
            // For non-polo_ordered users, just get the newest addon
            $newest_addon = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT pa.*, a.addon_key, a.drink_count 
                    FROM {$table_purchase_addons} pa
                    JOIN {$table_addons} a ON pa.addon_id = a.id
                    WHERE pa.purchase_id = %d 
                    ORDER BY pa.created_at DESC 
                    LIMIT 1",
                    $purchase_id
                ),
                ARRAY_A
            );
        }
        
        if (!$newest_addon) {
            return 0; // No addons found
        }
        
        $addon_key = $newest_addon['addon_key'];
        
        // Apply the same conditional logic but only to the newest addon
        if ($addon_key === 'afterparty_package_2') {
            $cutoff_date = '2025-07-23 00:00:00';
            if (strtotime($purchase_date) < strtotime($cutoff_date)) {
                return 4; // Purchased before cutoff: 4 drinks (grandfathered)
            } else {
                return 3; // Purchased on/after cutoff: 3 drinks (current admin setting)
            }
        } elseif ($addon_key === 'afterpart_package_0') {
            return 1; // Hardcoded: free addon always gives 1 drink
        } else {
            // Use database drink_count for other addons
            return intval($newest_addon['drink_count']);
        }
        
        return 0;
    }
    
    /**
     * Migration to version 1.3.7 - Add email campaigns recipients table
     */
    private function migrate_to_1_3_7() {
        error_log("RESET Plugin: Starting migration to 1.3.7 - Adding email campaigns recipients table");
        
        $charset_collate = $this->wpdb->get_charset_collate();
        $table_email_recipients = $this->wpdb->prefix . 'reset_email_recipients';
        
        // Check if table already exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_email_recipients}'");
        
        if (!$table_exists) {
            // Create the email recipients table
            $sql_email_recipients = "CREATE TABLE $table_email_recipients (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id bigint(20) unsigned NOT NULL,
                recipient_email varchar(255) NOT NULL,
                recipient_name varchar(255) NULL,
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
            $result = dbDelta($sql_email_recipients);
            
            if ($this->wpdb->last_error) {
                error_log("RESET Plugin: Failed to create email recipients table: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully created email recipients table");
        } else {
            error_log("RESET Plugin: Email recipients table already exists, skipping creation");
        }
        
        // Update the validation to include the new table
        $this->update_table_validation_list();
        
        error_log("RESET Plugin: Migration 1.3.7 completed successfully - Email campaigns recipients table ready");
        return true;
    }
    
    /**
     * Update table validation list to include capacity table
     */
    private function update_table_validation_list() {
        // This method ensures the new table is included in database validation
        // The actual validation happens in validate_database() method
    }
    
    /**
     * Migration to version 1.3.8 - Add token_code column to email recipients table
     */
    private function migrate_to_1_3_8() {
        error_log("RESET Plugin: Starting migration to 1.3.8 - Adding token_code column to email recipients table");
        
        $table_email_recipients = $this->wpdb->prefix . 'reset_email_recipients';
        
        // Check if token_code column exists
        $column_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_email_recipients}` LIKE %s",
                'token_code'
            )
        );
        
        // Add token_code column if it doesn't exist
        if (empty($column_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_email_recipients}` 
                ADD COLUMN `token_code` varchar(255) NULL 
                AFTER `recipient_name`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add token_code column to email recipients table: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully added token_code column to email recipients table");
        } else {
            error_log("RESET Plugin: token_code column already exists in email recipients table, skipping");
        }
        
        error_log("RESET Plugin: Migration 1.3.8 completed successfully - Email recipients table now has token_code column");
        return true;
    }
    
    /**
     * Migration to version 1.3.9 - Add check-in functionality columns
     */
    private function migrate_to_1_3_9() {
        error_log("RESET Plugin: Starting migration to 1.3.9 - Adding check-in functionality columns");
        
        $table_purchases = $this->wpdb->prefix . 'reset_purchases';
        
        // Check and add checked_in column
        $checked_in_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'checked_in'
            )
        );
        
        if (empty($checked_in_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `checked_in` TINYINT(1) DEFAULT 0 
                AFTER `invitation_tokens_generated`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add checked_in column: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully added checked_in column");
        } else {
            error_log("RESET Plugin: checked_in column already exists, skipping");
        }
        
        // Check and add checked_in_at column
        $checked_in_at_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'checked_in_at'
            )
        );
        
        if (empty($checked_in_at_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `checked_in_at` TIMESTAMP NULL 
                AFTER `checked_in`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add checked_in_at column: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully added checked_in_at column");
        } else {
            error_log("RESET Plugin: checked_in_at column already exists, skipping");
        }
        
        // Check and add checked_in_by column
        $checked_in_by_exists = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SHOW COLUMNS FROM `{$table_purchases}` LIKE %s",
                'checked_in_by'
            )
        );
        
        if (empty($checked_in_by_exists)) {
            $result = $this->wpdb->query(
                "ALTER TABLE `{$table_purchases}` 
                ADD COLUMN `checked_in_by` VARCHAR(255) NULL 
                AFTER `checked_in_at`"
            );
            
            if ($result === false) {
                error_log("RESET Plugin: Failed to add checked_in_by column: " . $this->wpdb->last_error);
                return false;
            }
            
            error_log("RESET Plugin: Successfully added checked_in_by column");
        } else {
            error_log("RESET Plugin: checked_in_by column already exists, skipping");
        }
        
        error_log("RESET Plugin: Migration 1.3.9 completed successfully - Check-in functionality columns added");
        return true;
    }

    
    /**
     * Force recreate all tables (development use only)
     */
    public function force_recreate_tables() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return false;
        }
        
        try {
            // Drop all tables
            $tables = array(
                $this->wpdb->prefix . 'reset_tokens',
                $this->wpdb->prefix . 'reset_purchases',
                $this->wpdb->prefix . 'reset_email_logs',
                $this->wpdb->prefix . 'reset_ticket_types',
                $this->wpdb->prefix . 'reset_addons',
                $this->wpdb->prefix . 'reset_purchase_addons',
                $this->wpdb->prefix . 'reset_capacity_config',
                $this->wpdb->prefix . 'reset_email_recipients'
            );
            
            foreach ($tables as $table) {
                $this->wpdb->query("DROP TABLE IF EXISTS `{$table}`");
            }
            
            // Reset version
            delete_option('reset_plugin_db_version');
            
            // Recreate tables
            return $this->fresh_install();
            
        } catch (Exception $e) {
            error_log("RESET Plugin: Force recreate failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get migration status information
     */
    public function get_migration_status() {
        $current_version = get_option('reset_plugin_db_version', '0.0.0');
        $target_version = RESET_DB_VERSION;
        $last_migration = get_option('reset_plugin_last_migration', 'Never');
        
        return array(
            'current_version' => $current_version,
            'target_version' => $target_version,
            'needs_migration' => version_compare($current_version, $target_version, '<'),
            'last_migration' => $last_migration,
            'is_fresh_install' => $current_version === '0.0.0'
        );
    }
    
    /**
     * Validate database integrity
     */
    public function validate_database() {
        $required_tables = array(
            'reset_tokens',
            'reset_purchases', 
            'reset_email_logs',
            'reset_ticket_types',
            'reset_addons',
            'reset_purchase_addons',
            'reset_capacity_config',
            'reset_email_recipients'
        );
        
        $missing_tables = array();
        
        foreach ($required_tables as $table) {
            $full_table = $this->wpdb->prefix . $table;
            $exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table}'");
            
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        
        return array(
            'valid' => empty($missing_tables),
            'missing_tables' => $missing_tables,
            'total_tables' => count($required_tables),
            'existing_tables' => count($required_tables) - count($missing_tables)
        );
    }
    
    /**
     * Fix capacity table unique constraint issue
     * This fixes the problematic unique constraint that prevents multiple inactive records
     */
    public function fix_capacity_table_constraint() {
        $table_capacity = $this->wpdb->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return true; // Table doesn't exist, nothing to fix
        }
        
        // Check if the unique constraint exists before trying to drop it
        $index_exists = $this->wpdb->get_results("SHOW INDEX FROM {$table_capacity} WHERE Key_name = 'unique_active_config'");
        
        if (!empty($index_exists)) {
            // Drop the problematic unique constraint only if it exists
            $this->wpdb->query("ALTER TABLE {$table_capacity} DROP INDEX unique_active_config");
            error_log("RESET Plugin: Dropped unique_active_config constraint");
        } else {
            error_log("RESET Plugin: unique_active_config constraint does not exist, skipping drop");
        }
        
        // Clean up any duplicate active records that might exist
        $this->wpdb->query("
            UPDATE {$table_capacity} 
            SET is_active = 0 
            WHERE config_name = 'default' 
            AND id NOT IN (
                SELECT * FROM (
                    SELECT MAX(id) 
                    FROM {$table_capacity} 
                    WHERE config_name = 'default' 
                    AND is_active = 1
                ) as temp
            )
        ");
        
        error_log("RESET Plugin: Fixed capacity table unique constraint issue");
        return true;
    }
} 