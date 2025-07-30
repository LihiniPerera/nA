<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Capacity management class for RESET ticketing system
 */
class ResetCapacity {
    private static $instance = null;
    private $db;
    
    // Single set of capacity settings (no environment-specific scaling)
    private $target_capacity = 500;
    private $max_capacity = 600;
    private $current_tickets = 0;
    
    private function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->load_capacity_settings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function load_capacity_settings() {
        // Load settings from database table
        $settings = $this->get_capacity_settings_from_database();
        
        if ($settings) {
            $this->target_capacity = intval($settings['target_capacity']);
            $this->max_capacity = intval($settings['max_capacity']);
        } else {
            // Fallback to default values if no database settings
            $this->target_capacity = 250;
            $this->max_capacity = 300;
            
            // Try to create default settings
            $this->create_default_capacity_settings();
        }
        
        // Ensure database tables exist
        $this->ensure_database_tables();
        
        $this->current_tickets = $this->get_current_ticket_count();
    }
    
    private function ensure_database_tables() {
        // Check if tables exist, if not trigger plugin activation
        $purchases_table = $this->db->prefix . 'reset_purchases';
        $tokens_table = $this->db->prefix . 'reset_tokens';
        
        $purchases_exists = $this->db->get_var("SHOW TABLES LIKE '{$purchases_table}'");
        $tokens_exists = $this->db->get_var("SHOW TABLES LIKE '{$tokens_table}'");
        
        if (!$purchases_exists || !$tokens_exists) {
            // Tables don't exist, try to create them
            $plugin = ResetTicketingPlugin::getInstance();
            if (method_exists($plugin, 'create_database_tables')) {
                $plugin->create_database_tables();
            }
        }
    }
    
    public function get_capacity_status() {
        return array(
            'current_tickets' => $this->current_tickets,
            'target_capacity' => $this->target_capacity,
            'max_capacity' => $this->max_capacity,
            'remaining_capacity' => $this->max_capacity - $this->current_tickets,
            'capacity_percentage' => ($this->current_tickets / $this->max_capacity) * 100,
            'is_near_capacity' => $this->current_tickets >= ($this->target_capacity * 0.9), // 90% of target
            'is_at_capacity' => $this->current_tickets >= $this->max_capacity,
            'warning_threshold' => $this->target_capacity * 0.9
        );
        }
        
    public function can_accept_booking() {
        return $this->current_tickets < $this->max_capacity;
    }
    
        public function get_current_ticket_count() {
        // Ensure database connection exists
        if (!$this->db) {
            global $wpdb;
            $this->db = $wpdb;
        }
        
        // Check if table exists before querying
        $table_name = $this->db->prefix . 'reset_purchases';
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            // If table doesn't exist, return 0 and maybe log a warning
            error_log("RESET Plugin: reset_purchases table does not exist");
            return 0;
        }
        
        $count = $this->db->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE payment_status = 'completed'
        ");
        
        return intval($count ?? 0);
    }
    
    /**
     * Get capacity settings from database table
     */
    private function get_capacity_settings_from_database() {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return false;
        }
        
        // Get active configuration
        $settings = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$table_capacity} WHERE config_name = %s AND is_active = 1 ORDER BY updated_at DESC LIMIT 1",
                'default'
            ),
            ARRAY_A
        );
        
        return $settings;
    }
    
    /**
     * Create default capacity settings in database
     */
    private function create_default_capacity_settings() {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return false;
        }
        
        // Insert default configuration
        $result = $this->db->insert(
            $table_capacity,
            array(
                'config_name' => 'default',
                'target_capacity' => 250,
                'max_capacity' => 300,
                'alert_threshold' => 225,
                'early_bird_threshold' => 100,
                'late_bird_threshold' => 150,
                'very_late_bird_threshold' => 200,
                'updated_by' => 'System Default',
                'is_active' => 1,
                'change_notes' => 'Default capacity configuration created automatically'
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update capacity settings in database with change tracking
     */
    private function update_capacity_settings_in_database($settings) {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return false;
        }
        
        // Get current user info
        $current_user = wp_get_current_user();
        $updated_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        // Get current settings for change tracking
        $current_settings = $this->get_capacity_settings_from_database();
        
        // Use provided alert threshold or calculate default
        $alert_threshold = isset($settings['alert_threshold']) ? intval($settings['alert_threshold']) : intval($settings['target_capacity'] * 0.9);
        
        // Prepare change notes
        $change_notes = '';
        if ($current_settings) {
            $changes = array();
            if (intval($current_settings['target_capacity']) !== $settings['target_capacity']) {
                $changes[] = "Target: {$current_settings['target_capacity']} → {$settings['target_capacity']}";
            }
            if (intval($current_settings['max_capacity']) !== $settings['max_capacity']) {
                $changes[] = "Max: {$current_settings['max_capacity']} → {$settings['max_capacity']}";
            }
            if (intval($current_settings['alert_threshold']) !== $alert_threshold) {
                $changes[] = "Alert: {$current_settings['alert_threshold']} → {$alert_threshold}";
            }
            $change_notes = !empty($changes) ? implode(', ', $changes) : 'Settings updated';
        } else {
            $change_notes = 'Initial capacity settings created';
        }
        
        // Deactivate ALL existing configurations first
        $this->db->update(
            $table_capacity,
            array('is_active' => 0),
            array('config_name' => 'default'),
            array('%d'),
            array('%s')
        );
        
        // Insert new configuration
        $result = $this->db->insert(
            $table_capacity,
            array(
                'config_name' => 'default',
                'target_capacity' => $settings['target_capacity'],
                'max_capacity' => $settings['max_capacity'],
                'alert_threshold' => $alert_threshold,
                'early_bird_threshold' => $current_settings['early_bird_threshold'] ?? 100,
                'late_bird_threshold' => $current_settings['late_bird_threshold'] ?? 150,
                'very_late_bird_threshold' => $current_settings['very_late_bird_threshold'] ?? 200,
                'updated_by' => $updated_by,
                'is_active' => 1,
                'change_notes' => $change_notes
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update thresholds in database with change tracking
     */
    private function update_thresholds_in_database($thresholds) {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return false;
        }
        
        // Get current user info
        $current_user = wp_get_current_user();
        $updated_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        // Get current settings for change tracking
        $current_settings = $this->get_capacity_settings_from_database();
        
        if (!$current_settings) {
            return false;
        }
        
        // Prepare change notes
        $changes = array();
        if (intval($current_settings['early_bird_threshold']) !== $thresholds['early_bird']) {
            $changes[] = "Early Bird: {$current_settings['early_bird_threshold']} → {$thresholds['early_bird']}";
        }
        if (intval($current_settings['late_bird_threshold']) !== $thresholds['late_bird']) {
            $changes[] = "Late Bird: {$current_settings['late_bird_threshold']} → {$thresholds['late_bird']}";
        }
        if (intval($current_settings['very_late_bird_threshold']) !== $thresholds['very_late_bird']) {
            $changes[] = "Very Late Bird: {$current_settings['very_late_bird_threshold']} → {$thresholds['very_late_bird']}";
        }
        $change_notes = !empty($changes) ? 'Thresholds updated: ' . implode(', ', $changes) : 'Thresholds updated';
        
        // Deactivate ALL existing configurations first
        $this->db->update(
            $table_capacity,
            array('is_active' => 0),
            array('config_name' => 'default'),
            array('%d'),
            array('%s')
        );
        
        // Insert new configuration with updated thresholds
        $result = $this->db->insert(
            $table_capacity,
            array(
                'config_name' => 'default',
                'target_capacity' => intval($current_settings['target_capacity']),
                'max_capacity' => intval($current_settings['max_capacity']),
                'alert_threshold' => intval($current_settings['alert_threshold']),
                'early_bird_threshold' => $thresholds['early_bird'],
                'late_bird_threshold' => $thresholds['late_bird'],
                'very_late_bird_threshold' => $thresholds['very_late_bird'],
                'updated_by' => $updated_by,
                'is_active' => 1,
                'change_notes' => $change_notes
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get capacity change history
     */
    public function get_change_history($limit = 10) {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return array();
        }
        
        $history = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$table_capacity} WHERE config_name = %s ORDER BY updated_at DESC LIMIT %d",
                'default',
                $limit
            ),
            ARRAY_A
        );
        
        return $history;
    }
    
    /**
     * Rollback to a previous capacity configuration
     */
    public function rollback_to_configuration($config_id) {
        $table_capacity = $this->db->prefix . 'reset_capacity_config';
        
        // Check if table exists
        $table_exists = $this->db->get_var("SHOW TABLES LIKE '{$table_capacity}'");
        if (!$table_exists) {
            return false;
        }
        
        // Get the configuration to rollback to
        $rollback_config = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$table_capacity} WHERE id = %d AND config_name = %s",
                $config_id,
                'default'
            ),
            ARRAY_A
        );
        
        if (!$rollback_config) {
            return false;
        }
        
        // Get current user info
        $current_user = wp_get_current_user();
        $updated_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        // Deactivate ALL existing configurations first
        $this->db->update(
            $table_capacity,
            array('is_active' => 0),
            array('config_name' => 'default'),
            array('%d'),
            array('%s')
        );
        
        // Insert the rollback configuration as new active config
        $result = $this->db->insert(
            $table_capacity,
            array(
                'config_name' => 'default',
                'target_capacity' => intval($rollback_config['target_capacity']),
                'max_capacity' => intval($rollback_config['max_capacity']),
                'alert_threshold' => intval($rollback_config['alert_threshold']),
                'early_bird_threshold' => intval($rollback_config['early_bird_threshold']),
                'late_bird_threshold' => intval($rollback_config['late_bird_threshold']),
                'very_late_bird_threshold' => intval($rollback_config['very_late_bird_threshold']),
                'updated_by' => $updated_by,
                'is_active' => 1,
                'change_notes' => 'Rolled back to configuration from ' . $rollback_config['updated_at']
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s')
        );
        
        if ($result !== false) {
            $this->load_capacity_settings(); // Reload settings
        }
        
        return $result !== false;
    }
        
    public function load_thresholds() {
        // Load thresholds from database table
        $settings = $this->get_capacity_settings_from_database();
        
        if ($settings) {
            return array(
                'early_bird' => intval($settings['early_bird_threshold']),
                'late_bird' => intval($settings['late_bird_threshold']),
                'very_late_bird' => intval($settings['very_late_bird_threshold'])
            );
        }
        
        // Fallback to default thresholds
        return array(
            'early_bird' => 100,
            'late_bird' => 150,
            'very_late_bird' => 200
        );
    }
    
    public function get_current_ticket_tier() {
        $thresholds = $this->load_thresholds();
        
        if ($this->current_tickets < $thresholds['early_bird']) {
            return 'early_bird';
        } elseif ($this->current_tickets < $thresholds['late_bird']) {
            return 'late_bird';
        } elseif ($this->current_tickets < $thresholds['very_late_bird']) {
            return 'very_late_bird';
        } else {
            return 'final';
        }
    }
    
    public function update_capacity_thresholds($thresholds) {
        // Validate thresholds
        $validated = array();
        foreach ($thresholds as $key => $value) {
            $validated[$key] = max(0, intval($value));
        }
        
        // Ensure logical order
        if ($validated['early_bird'] >= $validated['late_bird']) {
            $validated['late_bird'] = $validated['early_bird'] + 50;
        }
        if ($validated['late_bird'] >= $validated['very_late_bird']) {
            $validated['very_late_bird'] = $validated['late_bird'] + 50;
        }
        
        return $this->update_thresholds_in_database($validated);
    }
        
    public function get_threshold_recommendations() {
        $capacity = $this->target_capacity;
        
        return array(
            'early_bird' => floor($capacity * 0.4),     // 40% of capacity
            'late_bird' => floor($capacity * 0.6),      // 60% of capacity
            'very_late_bird' => floor($capacity * 0.9)  // 90% of capacity
        );
    }
    
    // Admin interface methods
    public function get_capacity_settings() {
        $thresholds = $this->load_thresholds();
        $db_settings = $this->get_capacity_settings_from_database();
        
            return array(
            'main_capacity' => array(
                'target_capacity' => $this->target_capacity,
                'max_capacity' => $this->max_capacity,
                'alert_threshold' => $db_settings ? intval($db_settings['alert_threshold']) : intval($this->target_capacity * 0.9)
            ),
            'ticket_thresholds' => array(
                'early_bird' => $thresholds['early_bird'] ?? 200,
                'late_bird' => $thresholds['late_bird'] ?? 300,
                'very_late_bird' => $thresholds['very_late_bird'] ?? $this->target_capacity
            ),
            'current_tickets' => $this->current_tickets
        );
    }
    
    public function update_capacity_settings($settings) {
        $validated_settings = array(
            'target_capacity' => max(100, intval($settings['target_capacity'] ?? 250)),
            'max_capacity' => max(200, intval($settings['max_capacity'] ?? 300)),
            'alert_threshold' => max(50, intval($settings['alert_threshold'] ?? 225))
        );
        
        // Ensure max >= target
        if ($validated_settings['max_capacity'] < $validated_settings['target_capacity']) {
            $validated_settings['max_capacity'] = $validated_settings['target_capacity'] + 50;
        }
        
        // Ensure alert_threshold is reasonable (between 50% and 100% of target)
        if ($validated_settings['alert_threshold'] > $validated_settings['target_capacity']) {
            $validated_settings['alert_threshold'] = intval($validated_settings['target_capacity'] * 0.9);
        }
        
        $result = $this->update_capacity_settings_in_database($validated_settings);
        
        if ($result) {
            $this->load_capacity_settings(); // Reload settings
        }
        
        return $result;
    }
    
    public function validate_capacity_settings($settings) {
        $errors = array();
        
        $target = intval($settings['target_capacity'] ?? 0);
        $max = intval($settings['max_capacity'] ?? 0);
        $alert = intval($settings['alert_threshold'] ?? 0);
        
        if ($target < 100) {
            $errors[] = 'Target capacity must be at least 100';
        }
        
        if ($max < 200) {
            $errors[] = 'Maximum capacity must be at least 200';
        }
        
        if ($max < $target) {
            $errors[] = 'Maximum capacity must be greater than or equal to target capacity';
        }
        
        if ($alert < 50) {
            $errors[] = 'Alert threshold must be at least 50';
        }
        
        if ($alert > $target) {
            $errors[] = 'Alert threshold cannot be greater than target capacity';
        }
        
        return $errors;
    }
    
    public function get_capacity_impact_analysis($new_settings) {
        $current = $this->get_capacity_settings();
        $current_tier = $this->get_current_ticket_tier();
        
        // Simulate new settings
        $temp_target = intval($new_settings['target_capacity'] ?? $current['target_capacity']);
        $temp_max = intval($new_settings['max_capacity'] ?? $current['max_capacity']);
        
        $analysis = array(
            'current_settings' => $current,
            'new_settings' => array(
                'target_capacity' => $temp_target,
                'max_capacity' => $temp_max,
                'thresholds' => $current['thresholds']
            ),
            'impact' => array(
                'capacity_change' => $temp_max - $current['max_capacity'],
                'target_change' => $temp_target - $current['target_capacity'],
                'current_tier' => $current_tier,
                'tickets_sold' => $this->current_tickets
            )
        );
        
        return $analysis;
    }
    
    public function reset_capacity_settings_to_defaults() {
        $defaults = array(
            'target_capacity' => 500,
            'max_capacity' => 600
        );
        
        $default_thresholds = array(
            'early_bird' => 200,
            'late_bird' => 300,
            'very_late_bird' => 500
        );
        
        update_option('reset_capacity_settings', $defaults);
        update_option('reset_ticket_thresholds', $default_thresholds);
        
        $this->load_capacity_settings();
        
        return true;
    }
    
    public function get_capacity_statistics() {
        $status = $this->get_capacity_status();
        $thresholds = $this->load_thresholds();
        
        // Get detailed attendee breakdown
        $attendee_breakdown = $this->get_attendee_breakdown();
        
        return array(
            // Main capacity metrics (expected by admin page)
            'current_attendees' => $this->current_tickets,
            'target_capacity' => $this->target_capacity,
            'max_capacity' => $this->max_capacity,
            'percentage_used' => round($status['capacity_percentage'], 1),
            'remaining_slots' => $status['remaining_capacity'],
            
            // Status flags
            'is_approaching_capacity' => $status['is_near_capacity'],
            'is_at_capacity' => $status['is_at_capacity'],
            
            // Attendee breakdown
            'paid_attendees' => $attendee_breakdown['paid'],
            'free_attendees' => $attendee_breakdown['free'],
            
            // Additional metrics
            'total_tickets_sold' => $this->current_tickets,
            'capacity_used_percentage' => round($status['capacity_percentage'], 1),
            'current_tier' => $this->get_current_ticket_tier(),
            'next_tier_threshold' => $this->get_next_tier_threshold(),
            'settings' => array(
                'target_capacity' => $this->target_capacity,
                'max_capacity' => $this->max_capacity,
                'thresholds' => $thresholds
            )
        );
    }
    
    private function get_next_tier_threshold() {
        $thresholds = $this->load_thresholds();
        $current = $this->current_tickets;
        
        if ($current < $thresholds['early_bird']) {
            return $thresholds['early_bird'];
        } elseif ($current < $thresholds['late_bird']) {
            return $thresholds['late_bird'];
        } elseif ($current < $thresholds['very_late_bird']) {
            return $thresholds['very_late_bird'];
        } else {
            return $this->max_capacity;
        }
    }
    
    private function get_attendee_breakdown() {
        // Ensure database connection exists
        if (!$this->db) {
            global $wpdb;
            $this->db = $wpdb;
        }
        
        // Check if required tables exist
        $purchases_table = $this->db->prefix . 'reset_purchases';
        $tokens_table = $this->db->prefix . 'reset_tokens';
        
        $purchases_exists = $this->db->get_var("SHOW TABLES LIKE '{$purchases_table}'");
        $tokens_exists = $this->db->get_var("SHOW TABLES LIKE '{$tokens_table}'");
        
        if (!$purchases_exists || !$tokens_exists) {
            error_log("RESET Plugin: Required database tables do not exist");
            return array('paid' => 0, 'free' => 0);
        }
        
        // Get paid vs free ticket breakdown from database
        $paid_count = $this->db->get_var("
            SELECT COUNT(*) 
            FROM {$purchases_table} p
            INNER JOIN {$tokens_table} t ON p.token_id = t.id
            WHERE p.payment_status = 'completed' 
            AND t.token_type != 'free_ticket'
        ");
        
        $free_count = $this->db->get_var("
            SELECT COUNT(*) 
            FROM {$purchases_table} p
            INNER JOIN {$tokens_table} t ON p.token_id = t.id
            WHERE p.payment_status = 'completed' 
            AND t.token_type = 'free_ticket'
        ");
        
        return array(
            'paid' => intval($paid_count ?? 0),
            'free' => intval($free_count ?? 0)
        );
    }
    
    public function get_environment_info() {
        $environment = $this->detect_environment();
        $thresholds = $this->load_thresholds();
        
        return array(
            'environment' => $environment,
            'is_local_development' => $environment === 'local',
            'current_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'description' => 'Database-driven capacity settings with change tracking',
            'testing_note' => $environment === 'local' ? 'Development environment - settings changes are tracked' : 'Production environment - all changes are logged',
            'capacity_limits' => array(
                'current_attendees' => $this->current_tickets,
                'target_capacity' => $this->target_capacity,
                'max_capacity' => $this->max_capacity
            ),
            'threshold_description' => sprintf(
                'Early Bird: %d, Late Bird: %d, Very Late: %d',
                $thresholds['early_bird'],
                $thresholds['late_bird'],
                $thresholds['very_late_bird']
            ),
            'capacity_settings' => array(
                'target_capacity' => $this->target_capacity,
                'max_capacity' => $this->max_capacity
            )
        );
    }
    
    private function detect_environment() {
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        
        if (strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false || 
            strpos($host, '.local') !== false ||
            (defined('WP_DEBUG') && WP_DEBUG && defined('WP_ENV') && WP_ENV === 'development')) {
            return 'local';
        }
        
        return 'production';
    }
    
    /**
     * Debug method to check system health
     */
    public function get_system_health() {
        if (!$this->db) {
            global $wpdb;
            $this->db = $wpdb;
        }
        
        $purchases_table = $this->db->prefix . 'reset_purchases';
        $tokens_table = $this->db->prefix . 'reset_tokens';
        
        return array(
            'database_connection' => $this->db ? 'OK' : 'FAILED',
            'purchases_table_exists' => $this->db->get_var("SHOW TABLES LIKE '{$purchases_table}'") ? 'YES' : 'NO',
            'tokens_table_exists' => $this->db->get_var("SHOW TABLES LIKE '{$tokens_table}'") ? 'YES' : 'NO',
            'current_tickets' => $this->current_tickets,
            'settings_loaded' => array(
                'target_capacity' => $this->target_capacity,
                'max_capacity' => $this->max_capacity
            ),
            'environment' => $this->detect_environment(),
            'wp_prefix' => $this->db->prefix
        );
    }
    
    /**
     * Get ticket availability information for the booking wizard
     */
    public function get_ticket_availability($token_type) {
        $status = $this->get_capacity_status();
        $current_tier = $this->get_current_ticket_tier();
        
        // Free token types should bypass capacity restrictions
        $free_token_types = array('free_ticket', 'polo_ordered', 'sponsor');
        $is_free_token = in_array($token_type, $free_token_types);
        
        // Check if capacity is reached - only for paying users (normal and invitation)
        $capacity_reached = false;
        if (!$is_free_token) {
            $capacity_reached = $status['is_at_capacity'] || !$this->can_accept_booking() || $current_tier === 'final';
        }
        
        // Generate capacity message
        $capacity_message = '';
        if ($capacity_reached) {
            if ($current_tier === 'final') {
                $capacity_message = "We are closed the ticket booking. All ticket tiers have been sold out.";
            } else {
                $capacity_message = "Event capacity has been reached. No more tickets are available.";
            }
        } elseif ($status['is_near_capacity'] && !$is_free_token) {
            $remaining = $status['remaining_capacity'];
            $capacity_message = "Only {$remaining} tickets remaining!";
        }
        
        // Generate current pricing note
        $current_pricing_note = $this->get_current_pricing_note($current_tier);
        
        return array(
            'capacity_reached' => $capacity_reached,
            'capacity_message' => $capacity_message,
            'current_pricing_note' => $current_pricing_note,
            'current_tier' => $current_tier,
            'tickets_sold' => $this->current_tickets,
            'remaining_capacity' => $status['remaining_capacity']
        );
    }
    
    /**
     * Get available ticket types for the booking wizard
     */
    public function get_available_ticket_types($token_type) {
        // Get core instance for ticket data
        $core = ResetCore::getInstance();
        $all_tickets = $core->get_ticket_pricing();
        
        // Check capacity status
        $can_accept_booking = $this->can_accept_booking();
        $current_tier = $this->get_current_ticket_tier();
        
        // Free token types get different treatment
        $free_token_types = array('free_ticket', 'polo_ordered', 'sponsor');
        $is_free_token = in_array($token_type, $free_token_types);
        
        // Determine which ticket should be available based on current tier
        $active_ticket_key = $this->get_active_ticket_for_tier($current_tier);
        
        $available_tickets = array();
        
        foreach ($all_tickets as $ticket_key => $ticket_data) {
            // Skip afterparty tickets for general admission flow
            if (strpos($ticket_key, 'afterparty_') === 0) {
                continue;
            }
            
            $available = true;
            $availability_reason = '';
            
            // TIER-BASED LOGIC: Only enable the ticket for the current tier
            if (!$can_accept_booking) {
                // Capacity reached - disable all tickets
                $available = false;
                $availability_reason = 'Event capacity reached';
            } elseif ($current_tier === 'final') {
                // Final tier - all tickets disabled
                $available = false;
                $availability_reason = 'Event capacity reached';
            } elseif ($ticket_key !== $active_ticket_key) {
                // Not the active tier ticket - disable with explanation
                $available = false;
                $availability_reason = $this->get_tier_unavailable_reason($ticket_key, $current_tier);
            } elseif ($is_free_token && $ticket_data['price'] > 0) {
                // Free keys can't purchase paid tickets
                $available = false;
                $availability_reason = 'Not available with free pass';
            }
            
            $available_tickets[$ticket_key] = array(
                'name' => $ticket_data['name'],
                'price' => $ticket_data['price'],
                'benefits' => $ticket_data['benefits'],
                'available' => $available,
                'availability_reason' => $availability_reason
            );
        }
        
        return $available_tickets;
    }
    
    /**
     * Get current pricing note based on ticket tier
     */
    private function get_current_pricing_note($current_tier) {
        $notes = array(
            'early_bird' => 'Early Bird pricing is currently active. Book now to save!',
            'late_bird' => 'Late Bird pricing is now active. Early Bird pricing has ended.',
            'very_late_bird' => 'Very Late Bird pricing is now active. This is the final pricing tier.'
        );
        
        return $notes[$current_tier] ?? 'Standard pricing is active.';
    }
    
    /**
     * Get the active ticket type for the current tier
     */
    private function get_active_ticket_for_tier($current_tier) {
        $tier_to_ticket = array(
            'early_bird' => 'general_early',
            'late_bird' => 'general_late',
            'very_late_bird' => 'general_very_late',
            'final' => null  // No tickets available
        );
        
        return $tier_to_ticket[$current_tier] ?? null;
    }
    
    /**
     * Get explanation for why a ticket is unavailable in the current tier
     */
    private function get_tier_unavailable_reason($ticket_key, $current_tier) {
        $thresholds = $this->load_thresholds();
        $current_count = $this->current_tickets;
        
        // Map ticket keys to their tiers
        $ticket_tier_map = array(
            'general_early' => 'early_bird',
            'general_late' => 'late_bird',
            'general_very_late' => 'very_late_bird'
        );
        
        $ticket_tier = $ticket_tier_map[$ticket_key] ?? null;
        
        if (!$ticket_tier) {
            return 'Not available at this time';
        }
        
        // Generate specific explanations based on current tier and ticket tier
        switch ($current_tier) {
            case 'early_bird':
                if ($ticket_tier === 'late_bird') {
                    return "Available after {$thresholds['early_bird']} tickets sold (currently {$current_count})";
                } elseif ($ticket_tier === 'very_late_bird') {
                    return "Available after {$thresholds['late_bird']} tickets sold (currently {$current_count})";
                }
                break;
                
            case 'late_bird':
                if ($ticket_tier === 'early_bird') {
                    return "Early Bird period has ended ({$thresholds['early_bird']} tickets sold)";
                } elseif ($ticket_tier === 'very_late_bird') {
                    return "Available after {$thresholds['late_bird']} tickets sold (currently {$current_count})";
                }
                break;
                
            case 'very_late_bird':
                if ($ticket_tier === 'early_bird') {
                    return "Early Bird period has ended ({$thresholds['early_bird']} tickets sold)";
                } elseif ($ticket_tier === 'late_bird') {
                    return "Late Bird period has ended ({$thresholds['late_bird']} tickets sold)";
                }
                break;
                
            case 'final':
                return 'Event capacity reached';
        }
        
        return 'Not available at this time';
    }
} 