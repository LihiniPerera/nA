<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database management class for RESET ticketing system
 */
class ResetDatabase {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * WordPress database object
     */
    private $wpdb;
    
    /**
     * Database table names
     */
    private $table_tokens;
    private $table_purchases;
    private $table_email_logs;
    private $table_ticket_types;
    private $table_email_recipients;
    
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
        $this->table_tokens = $wpdb->prefix . 'reset_tokens';
        $this->table_purchases = $wpdb->prefix . 'reset_purchases';
        $this->table_email_logs = $wpdb->prefix . 'reset_email_logs';
        $this->table_ticket_types = $wpdb->prefix . 'reset_ticket_types';
        $this->table_email_recipients = $wpdb->prefix . 'reset_email_recipients';
    }
    
    /**
     * Get token by code
     */
    public function get_token_by_code(string $token_code) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_tokens} WHERE token_code = %s",
                $token_code
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get token by ID
     */
    public function get_token_by_id(int $token_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_tokens} WHERE id = %d",
                $token_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Create new token
     */
    public function create_token(array $token_data): int {
        $defaults = array(
            'token_code' => '',
            'token_type' => 'master',
            'parent_token_id' => null,
            'created_by' => '',
            'status' => 'active',
            'expires_at' => null
        );
        
        $token_data = wp_parse_args($token_data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->table_tokens,
            $token_data,
            array('%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update token
     */
    public function update_token(int $token_id, array $token_data): bool {
        $result = $this->wpdb->update(
            $this->table_tokens,
            $token_data,
            array('id' => $token_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Mark token as used
     */
    public function mark_token_as_used(int $token_id, array $user_data): bool {
        $update_data = array(
            'is_used' => 1,
            'used_at' => current_time('mysql'),
            'used_by_email' => $user_data['email'] ?? '',
            'used_by_phone' => $user_data['phone'] ?? '',
            'used_by_name' => $user_data['name'] ?? ''
        );
        
        return $this->update_token($token_id, $update_data);
    }
    
    /**
     * Cancel token
     */
    public function cancel_token(int $token_id, string $reason = '', string $cancelled_by = ''): bool {
        $update_data = array(
            'status' => 'cancelled',
            'cancelled_at' => current_time('mysql'),
            'cancelled_by' => $cancelled_by,
            'cancellation_reason' => $reason
        );
        
        return $this->update_token($token_id, $update_data);
    }
    
    /**
     * Mark token as sent
     */
    public function mark_token_as_sent(int $token_id, array $sent_data): bool {
        $update_data = array(
            'sent_to_email' => $sent_data['email'] ?? '',
            'sent_to_name' => $sent_data['name'] ?? '',
            'sent_by' => $sent_data['sent_by'] ?? '',
            'sent_at' => current_time('mysql'),
            'sent_notes' => $sent_data['notes'] ?? ''
        );
        
        return $this->update_token($token_id, $update_data);
    }
    
    /**
     * Get active tokens count
     */
    public function get_active_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} WHERE status = 'active' AND is_used = 0"
        );
    }
    
    /**
     * Get used tokens count
     */
    public function get_used_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} WHERE is_used = 1"
        );
    }
    
    /**
     * Get cancelled tokens count
     */
    public function get_cancelled_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} WHERE status = 'cancelled'"
        );
    }
    
    /**
     * Get sent tokens count
     */
    public function get_sent_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} WHERE sent_at IS NOT NULL"
        );
    }
    
    /**
     * Get unsent tokens count (active and unused)
     */
    public function get_unsent_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} 
            WHERE status = 'active' AND is_used = 0 AND sent_at IS NULL"
        );
    }
    
    /**
     * Get tokens by type
     */
    public function get_tokens_by_type(string $token_type, int $limit = -1, int $offset = 0): array {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_tokens} WHERE token_type = %s ORDER BY created_at DESC",
            $token_type
        );
        
        if ($limit > 0) {
            $query .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get unused tokens for cancellation
     */
    public function get_unused_tokens_for_cancellation(int $limit = 50): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_tokens} 
                WHERE status = 'active' AND is_used = 0 
                ORDER BY token_type DESC, created_at ASC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Bulk cancel tokens
     */
    public function bulk_cancel_tokens(array $token_ids, string $reason = '', string $cancelled_by = ''): int {
        if (empty($token_ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($token_ids), '%d'));
        
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_tokens} 
                SET status = 'cancelled', 
                    cancelled_at = %s, 
                    cancelled_by = %s, 
                    cancellation_reason = %s 
                WHERE id IN ({$placeholders})",
                current_time('mysql'),
                $cancelled_by,
                $reason,
                ...$token_ids
            )
        );
        
        return $result !== false ? $result : 0;
    }
    
    /**
     * Create purchase record
     */
    public function create_purchase(array $purchase_data): int {
        $defaults = array(
            'token_id' => 0,
            'purchaser_name' => '',
            'purchaser_email' => '',
            'purchaser_phone' => '',
            'gaming_name' => '',
            'ticket_type' => '',
            'ticket_price' => 0.00,
            'addon_total' => 0.00,
            'total_amount' => 0.00,
            'total_drink_count' => 0,
            'payment_status' => 'pending',
            'payment_reference' => '',
            'sampath_transaction_id' => '',
            'invitation_tokens_generated' => 0
        );
        
        $purchase_data = wp_parse_args($purchase_data, $defaults);
        
        // Validation: Ensure total_amount is calculated correctly
        $calculated_total = floatval($purchase_data['ticket_price']) + floatval($purchase_data['addon_total']);
        if (abs(floatval($purchase_data['total_amount']) - $calculated_total) > 0.01) {
            error_log("RESET Plugin: Warning - total_amount mismatch in create_purchase. Provided: " . $purchase_data['total_amount'] . ", Calculated: " . $calculated_total . ". Auto-correcting.");
            $purchase_data['total_amount'] = $calculated_total;
        }
        
        $result = $this->wpdb->insert(
            $this->table_purchases,
            $purchase_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get purchase by ID
     */
    public function get_purchase_by_id(int $purchase_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_purchases} WHERE id = %d",
                $purchase_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get purchase by payment reference
     */
    public function get_purchase_by_payment_reference(string $payment_reference) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_purchases} WHERE payment_reference = %s",
                $payment_reference
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get purchase by token ID
     */
    public function get_purchase_by_token_id(int $token_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_purchases} WHERE token_id = %d ORDER BY created_at DESC LIMIT 1",
                $token_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Update purchase
     */
    public function update_purchase(int $purchase_id, array $purchase_data): bool {
        // Validation: If updating price-related fields, ensure total_amount is correct
        if (isset($purchase_data['ticket_price']) || isset($purchase_data['addon_total']) || isset($purchase_data['total_amount'])) {
            // Get current purchase data to fill in missing values
            $current_purchase = $this->get_purchase_by_id($purchase_id);
            if ($current_purchase) {
                $ticket_price = isset($purchase_data['ticket_price']) ? floatval($purchase_data['ticket_price']) : floatval($current_purchase['ticket_price']);
                $addon_total = isset($purchase_data['addon_total']) ? floatval($purchase_data['addon_total']) : floatval($current_purchase['addon_total']);
                $calculated_total = $ticket_price + $addon_total;
                
                if (isset($purchase_data['total_amount'])) {
                    if (abs(floatval($purchase_data['total_amount']) - $calculated_total) > 0.01) {
                        error_log("RESET Plugin: Warning - total_amount mismatch in update_purchase. Provided: " . $purchase_data['total_amount'] . ", Calculated: " . $calculated_total . ". Auto-correcting.");
                        $purchase_data['total_amount'] = $calculated_total;
                    }
                } else {
                    // If total_amount not provided but other amounts are updated, calculate it
                    $purchase_data['total_amount'] = $calculated_total;
                }
            }
        }
        
        $result = $this->wpdb->update(
            $this->table_purchases,
            $purchase_data,
            array('id' => $purchase_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get completed purchases count
     */
    public function get_completed_purchases_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_purchases} WHERE payment_status = 'completed'"
        );
    }
    
    /**
     * Get total revenue
     */
    public function get_total_revenue(): float {
        return (float) $this->wpdb->get_var(
            "SELECT SUM(total_amount) FROM {$this->table_purchases} WHERE payment_status = 'completed'"
        );
    }
    
    /**
     * Get sales by ticket type
     */
    public function get_sales_by_ticket_type(): array {
        return $this->wpdb->get_results(
            "SELECT 
                p.ticket_type as ticket_key,
                COALESCE(tt.name, p.ticket_type) as ticket_type,
                COUNT(*) as count, 
                SUM(p.total_amount) as revenue 
            FROM {$this->table_purchases} p
            LEFT JOIN {$this->table_ticket_types} tt ON p.ticket_type = tt.ticket_key
            WHERE p.payment_status = 'completed' 
            GROUP BY p.ticket_type 
            ORDER BY count DESC",
            ARRAY_A
        );
    }
    
    /**
     * Get recent purchases
     */
    public function get_recent_purchases(int $limit = 10): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_purchases} 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get purchases for reminder emails
     */
    public function get_purchases_for_reminder(): array {
        return $this->wpdb->get_results(
            "SELECT p.*, t.token_code 
            FROM {$this->table_purchases} p 
            LEFT JOIN {$this->table_tokens} t ON p.token_id = t.id 
            WHERE p.payment_status = 'completed' 
            AND p.invitation_tokens_generated = 1",
            ARRAY_A
        );
    }
    
    /**
     * Get unused invitation keys count for purchase
     */
    public function get_unused_invitation_tokens_count_for_purchase(int $purchase_id): int {
        $purchase = $this->get_purchase_by_id($purchase_id);
        if (!$purchase) {
            return 0;
        }
        
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_tokens} 
                WHERE parent_token_id = %d 
                AND token_type = 'invitation' 
                AND is_used = 0 
                AND status = 'active'",
                $purchase['token_id']
            )
        );
    }
    
    /**
     * Log email
     */
    public function log_email(array $email_data): int {
        $defaults = array(
            'purchase_id' => null,
            'email_type' => 'confirmation',
            'recipient_email' => '',
            'subject' => '',
            'status' => 'sent'
        );
        
        $email_data = wp_parse_args($email_data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->table_email_logs,
            $email_data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get email logs
     */
    public function get_email_logs(int $limit = 50, int $offset = 0): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_email_logs} 
                ORDER BY sent_at DESC 
                LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get database statistics
     */
    public function get_statistics(): array {
        return array(
            'total_tokens' => $this->get_total_tokens_count(),
            'active_tokens' => $this->get_active_tokens_count(),
            'used_tokens' => $this->get_used_tokens_count(),
            'cancelled_tokens' => $this->get_cancelled_tokens_count(),
            'sent_tokens' => $this->get_sent_tokens_count(),
            'unsent_tokens' => $this->get_unsent_tokens_count(),
            'completed_purchases' => $this->get_completed_purchases_count(),
            'total_revenue' => $this->get_total_revenue(),
            'capacity_used' => $this->get_completed_purchases_count(),
            'capacity_remaining' => RESET_TARGET_CAPACITY - $this->get_completed_purchases_count()
        );
    }
    
    /**
     * Get total tokens count
     */
    private function get_total_tokens_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens}"
        );
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens(): int {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_tokens} 
                SET status = 'expired' 
                WHERE expires_at < %s 
                AND status = 'active'",
                current_time('mysql')
            )
        );
        
        return $result !== false ? $result : 0;
    }
    
    /**
     * Search tokens with filters
     */
    public function search_tokens(string $search_term = '', int $limit = 50, int $offset = 0): array {
        $where_conditions = array();
        $prepare_values = array();
        
        // Search term filter
        if (!empty($search_term)) {
            $where_conditions[] = "(token_code LIKE %s OR used_by_email LIKE %s OR used_by_name LIKE %s OR created_by LIKE %s)";
            $search_like = '%' . $search_term . '%';
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
        }
        
        // Build the WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Add limit and offset
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;
        
        $query = "SELECT * FROM {$this->table_tokens} 
                 {$where_clause} 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d";
        
        if (!empty($prepare_values)) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($query, ...$prepare_values),
                ARRAY_A
            );
        } else {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($query, $limit, $offset),
                ARRAY_A
            );
        }
    }
    
    /**
     * Search tokens with advanced filters
     */
    public function search_tokens_advanced(string $search_term = '', string $status = '', string $type = '', string $used = '', string $sent = '', int $limit = 50, int $offset = 0): array {
        $where_conditions = array();
        $prepare_values = array();
        
        // Search term filter (include sent_to fields)
        if (!empty($search_term)) {
            $where_conditions[] = "(token_code LIKE %s OR used_by_email LIKE %s OR used_by_name LIKE %s OR created_by LIKE %s OR sent_to_email LIKE %s OR sent_to_name LIKE %s)";
            $search_like = '%' . $search_term . '%';
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
        }
        
        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $prepare_values[] = $status;
        }
        
        // Type filter
        if (!empty($type)) {
            $where_conditions[] = "token_type = %s";
            $prepare_values[] = $type;
        }
        
        // Used filter
        if (!empty($used)) {
            if ($used === 'used') {
                $where_conditions[] = "is_used = 1";
            } elseif ($used === 'unused') {
                $where_conditions[] = "is_used = 0";
            }
        }
        
        // Sent filter
        if (!empty($sent)) {
            if ($sent === 'sent') {
                $where_conditions[] = "sent_at IS NOT NULL";
            } elseif ($sent === 'not_sent') {
                $where_conditions[] = "sent_at IS NULL";
            }
        }
        
        // Build the WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Add limit and offset
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;
        
        $query = "SELECT * FROM {$this->table_tokens} 
                 {$where_clause} 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d";
        
        if (!empty($prepare_values)) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($query, ...$prepare_values),
                ARRAY_A
            );
        } else {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($query, $limit, $offset),
                ARRAY_A
            );
        }
    }
    
    /**
     * Get total count for search results
     */
    public function get_search_tokens_count(string $search_term = '', string $status = '', string $type = '', string $used = '', string $sent = ''): int {
        $where_conditions = array();
        $prepare_values = array();
        
        // Search term filter (include sent_to fields)
        if (!empty($search_term)) {
            $where_conditions[] = "(token_code LIKE %s OR used_by_email LIKE %s OR used_by_name LIKE %s OR created_by LIKE %s OR sent_to_email LIKE %s OR sent_to_name LIKE %s)";
            $search_like = '%' . $search_term . '%';
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
        }
        
        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $prepare_values[] = $status;
        }
        
        // Type filter
        if (!empty($type)) {
            $where_conditions[] = "token_type = %s";
            $prepare_values[] = $type;
        }
        
        // Used filter
        if (!empty($used)) {
            if ($used === 'used') {
                $where_conditions[] = "is_used = 1";
            } elseif ($used === 'unused') {
                $where_conditions[] = "is_used = 0";
            }
        }
        
        // Sent filter
        if (!empty($sent)) {
            if ($sent === 'sent') {
                $where_conditions[] = "sent_at IS NOT NULL";
            } elseif ($sent === 'not_sent') {
                $where_conditions[] = "sent_at IS NULL";
            }
        }
        
        // Build the WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table_tokens} {$where_clause}";
        
        if (!empty($prepare_values)) {
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, ...$prepare_values)
            );
        } else {
            return (int) $this->wpdb->get_var($query);
        }
    }
    
    /**
     * Get dashboard data
     */
    public function get_dashboard_data(): array {
        $stats = $this->get_statistics();
        $recent_purchases = $this->get_recent_purchases(5);
        $sales_by_type = $this->get_sales_by_ticket_type();
        
        return array(
            'statistics' => $stats,
            'recent_purchases' => $recent_purchases,
            'sales_by_type' => $sales_by_type,
            'capacity_percentage' => round(($stats['capacity_used'] / RESET_TARGET_CAPACITY) * 100, 2)
        );
    }
    
    // ====================================================================
    // TICKET TYPES MANAGEMENT
    // ====================================================================
    
    /**
     * Create ticket type
     */
    public function create_ticket_type(array $ticket_data): int {
        $defaults = array(
            'ticket_key' => '',
            'name' => '',
            'description' => '',
            'features' => '',
            'ticket_price' => 0.00,
            'is_enabled' => 1,
            'sort_order' => 0
        );
        
        $ticket_data = wp_parse_args($ticket_data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->table_ticket_types,
            $ticket_data,
            array('%s', '%s', '%s', '%s', '%f', '%d', '%d')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get ticket type by ID
     */
    public function get_ticket_type_by_id(int $ticket_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_ticket_types} WHERE id = %d",
                $ticket_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get ticket type by key
     */
    public function get_ticket_type_by_key(string $ticket_key) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_ticket_types} WHERE ticket_key = %s",
                $ticket_key
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get all ticket types
     */
    public function get_all_ticket_types(bool $enabled_only = false): array {
        $query = "SELECT * FROM {$this->table_ticket_types}";
        
        if ($enabled_only) {
            $query .= " WHERE is_enabled = 1";
        }
        
        $query .= " ORDER BY sort_order ASC, name ASC";
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Update ticket type
     */
    public function update_ticket_type(int $ticket_id, array $ticket_data): bool {
        $result = $this->wpdb->update(
            $this->table_ticket_types,
            $ticket_data,
            array('id' => $ticket_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete ticket type
     */
    public function delete_ticket_type(int $ticket_id): bool {
        $result = $this->wpdb->delete(
            $this->table_ticket_types,
            array('id' => $ticket_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get enabled ticket types with pricing
     */
    public function get_enabled_ticket_types(): array {
        $query = "SELECT * FROM {$this->table_ticket_types} WHERE is_enabled = 1 ORDER BY sort_order ASC, name ASC";
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Update ticket type sort order
     */
    public function update_ticket_type_sort_order(int $ticket_id, int $sort_order): bool {
        return $this->update_ticket_type($ticket_id, array('sort_order' => $sort_order));
    }
    
    /**
     * Toggle ticket type enabled status
     */
    public function toggle_ticket_type_status(int $ticket_id): bool {
        $current_status = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT is_enabled FROM {$this->table_ticket_types} WHERE id = %d",
                $ticket_id
            )
        );
        
        if ($current_status === null) {
            return false;
        }
        
        $new_status = $current_status ? 0 : 1;
        return $this->update_ticket_type($ticket_id, array('is_enabled' => $new_status));
    }
    
    /**
     * Get ticket type price
     */
    public function get_ticket_type_current_price(int $ticket_id): float {
        $ticket = $this->get_ticket_type_by_id($ticket_id);
        if (!$ticket) {
            return 0.00;
        }
        
        return floatval($ticket['ticket_price']);
    }
    
    /**
     * Get ticket types with pricing
     */
    public function get_ticket_types_with_current_pricing(): array {
        $tickets = $this->get_enabled_ticket_types();
        
        foreach ($tickets as &$ticket) {
            $ticket['current_price'] = floatval($ticket['ticket_price']);
        }
        
        return $tickets;
    }
    
    /**
     * Get revenue by date range
     */
    public function get_revenue_by_date_range(string $start_date, string $end_date): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as purchases,
                    SUM(total_amount) as revenue
                FROM {$this->table_purchases} 
                WHERE payment_status = 'completed'
                AND DATE(created_at) BETWEEN %s AND %s
                GROUP BY DATE(created_at)
                ORDER BY date DESC",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );
    }
    
    // ================================
    // ADDON MANAGEMENT METHODS
    // ================================
    
    /**
     * Create addon
     */
    public function create_addon(array $addon_data): int {
        $defaults = array(
            'addon_key' => '',
            'name' => '',
            'description' => '',
            'price' => 0.00,
            'drink_count' => 0,
            'is_enabled' => 1,
            'sort_order' => 0
        );
        
        $addon_data = wp_parse_args($addon_data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'reset_addons',
            $addon_data,
            array('%s', '%s', '%s', '%f', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get addon by ID
     */
    public function get_addon_by_id(int $addon_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}reset_addons WHERE id = %d",
                $addon_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get addon by key
     */
    public function get_addon_by_key(string $addon_key) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}reset_addons WHERE addon_key = %s",
                $addon_key
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get all addons
     */
    public function get_all_addons(bool $enabled_only = false): array {
        $query = "SELECT * FROM {$this->wpdb->prefix}reset_addons";
        
        if ($enabled_only) {
            $query .= " WHERE is_enabled = 1";
        }
        
        $query .= " ORDER BY sort_order ASC";
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Update addon
     */
    public function update_addon(int $addon_id, array $addon_data): bool {
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'reset_addons',
            $addon_data,
            array('id' => $addon_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete addon
     */
    public function delete_addon(int $addon_id): bool {
        // First delete any purchase addon references
        $this->wpdb->delete(
            $this->wpdb->prefix . 'reset_purchase_addons',
            array('addon_id' => $addon_id),
            array('%d')
        );
        
        // Then delete the addon
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'reset_addons',
            array('id' => $addon_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Add addon to purchase
     */
    public function add_addon_to_purchase(int $purchase_id, int $addon_id, float $addon_price): int {
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'reset_purchase_addons',
            array(
                'purchase_id' => $purchase_id,
                'addon_id' => $addon_id,
                'addon_price' => $addon_price
            ),
            array('%d', '%d', '%f')
        );
        
        if ($result === false) {
            return 0;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get addons for purchase
     */
    public function get_addons_for_purchase(int $purchase_id): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT pa.*, a.name, a.description, a.addon_key, a.drink_count 
                FROM {$this->wpdb->prefix}reset_purchase_addons pa
                JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
                WHERE pa.purchase_id = %d
                ORDER BY a.sort_order ASC",
                $purchase_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get newest relevant addon for purchase (for drink count calculation)
     * Excludes free addon for polo_ordered users if paid addons exist
     */
    public function get_newest_relevant_addon_for_purchase(int $purchase_id): ?array {
        // Get purchase and token info
        $purchase = $this->get_purchase_by_id($purchase_id);
        if (!$purchase) {
            return null;
        }
        
        $token = $this->get_token_by_id($purchase['token_id']);
        $token_type = $token['token_type'] ?? '';
        
        // For polo_ordered users, check if they have paid addons
        if ($token_type === 'polo_ordered') {
            // First check if there are any paid addons (non-afterpart_package_0)
            $has_paid_addons = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$this->wpdb->prefix}reset_purchase_addons pa
                    JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
                    WHERE pa.purchase_id = %d 
                    AND a.addon_key != 'afterpart_package_0'",
                    $purchase_id
                )
            );
            
            if ($has_paid_addons > 0) {
                // Get newest paid addon (exclude free addon)
                $newest_addon = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT pa.*, a.name, a.description, a.addon_key, a.drink_count 
                        FROM {$this->wpdb->prefix}reset_purchase_addons pa
                        JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
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
                        "SELECT pa.*, a.name, a.description, a.addon_key, a.drink_count 
                        FROM {$this->wpdb->prefix}reset_purchase_addons pa
                        JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
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
                    "SELECT pa.*, a.name, a.description, a.addon_key, a.drink_count 
                    FROM {$this->wpdb->prefix}reset_purchase_addons pa
                    JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
                    WHERE pa.purchase_id = %d 
                    ORDER BY pa.created_at DESC 
                    LIMIT 1",
                    $purchase_id
                ),
                ARRAY_A
            );
        }
        
        return $newest_addon;
    }
    
    /**
     * Get count of purchases using specific addon
     */
    public function get_addons_for_purchase_count(int $addon_id): int {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}reset_purchase_addons 
                WHERE addon_id = %d",
                $addon_id
            )
        );
    }

    /**
     * Get total attendees count (paid + free)
     */
    public function get_total_attendees_count(): int {
        // Count completed purchases (paid attendees)
        $paid_attendees = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_purchases} WHERE payment_status = 'completed'"
        );
        
        // Count used free tokens (free attendees)
        $free_attendees = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_tokens} 
            WHERE token_type IN ('free_ticket', 'polo_ordered', 'sponsor') 
            AND is_used = 1"
        );
        
        return $paid_attendees + $free_attendees;
    }
    
    /**
     * Update purchase with addon totals
     */
    public function update_purchase_totals(int $purchase_id, float $addon_total, float $total_amount): bool {
        return $this->update_purchase($purchase_id, array(
            'addon_total' => $addon_total,
            'total_amount' => $total_amount
        ));
    }
    
    /**
     * Calculate total drink count for a purchase with conditional logic
     * NEW: Only uses the newest relevant addon (not sum of all addons)
     */
    public function calculate_purchase_drink_count(int $purchase_id): int {
        // Get the newest relevant addon (handles polo_ordered logic)
        $newest_addon = $this->get_newest_relevant_addon_for_purchase($purchase_id);
        
        if (!$newest_addon) {
            return 0; // No addons found
        }
        
        // Get purchase date for conditional logic
        $purchase = $this->get_purchase_by_id($purchase_id);
        $purchase_date = $purchase['created_at'] ?? '';
        
        $addon_key = $newest_addon['addon_key'] ?? '';
        
        // Apply the same conditional logic but only to the newest addon
        if ($addon_key === 'afterparty_package_2') {
            return $this->get_conditional_drink_count_for_addon($addon_key, $purchase_date);
        } elseif ($addon_key === 'afterpart_package_0') {
            // Hardcoded: free addon always gives 1 drink
            return 1;
        } else {
            // Regular logic for other addons
            if (isset($newest_addon['drink_count'])) {
                return intval($newest_addon['drink_count']);
            } else {
                // Fallback: get drink count from addon table
                $addon_details = $this->get_addon_by_id($newest_addon['addon_id']);
                if ($addon_details) {
                    return intval($addon_details['drink_count']);
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Get conditional drink count for specific addon based on purchase date
     */
    public function get_conditional_drink_count_for_addon(string $addon_key, string $purchase_date): int {
        // Conditional logic for afterparty_package_2
        if ($addon_key === 'afterparty_package_2') {
            $cutoff_date = '2025-07-23 00:00:00';
            
            // Compare purchase date with cutoff date
            if (strtotime($purchase_date) < strtotime($cutoff_date)) {
                // Purchased before cutoff: 4 drinks (grandfathered)
                return 4;
            } else {
                // Purchased on/after cutoff: 3 drinks (current admin setting)
                return 3;
            }
        }
        
        // For other addons, get from database
        $addon = $this->get_addon_by_key($addon_key);
        return $addon ? intval($addon['drink_count']) : 0;
    }
    
    /**
     * Update purchase drink count
     */
    public function update_purchase_drink_count(int $purchase_id, int $drink_count): bool {
        return $this->update_purchase($purchase_id, array(
            'total_drink_count' => $drink_count
        ));
    }
    
    /**
     * Update purchase with all totals including drink count
     */
    public function update_purchase_all_totals(int $purchase_id, float $addon_total, float $total_amount, int $drink_count): bool {
        return $this->update_purchase($purchase_id, array(
            'addon_total' => $addon_total,
            'total_amount' => $total_amount,
            'total_drink_count' => $drink_count
        ));
    }
    
    /**
     * Validate and fix drink counts for all purchases (admin utility)
     */
    public function validate_and_fix_drink_counts(): array {
        $results = array(
            'total_checked' => 0,
            'mismatches_found' => 0,
            'fixes_applied' => 0,
            'errors' => array()
        );
        
        // Get all completed purchases
        $purchases = $this->wpdb->get_results(
            "SELECT id, total_drink_count FROM {$this->table_purchases} WHERE payment_status = 'completed'",
            ARRAY_A
        );
        
        $results['total_checked'] = count($purchases);
        
        foreach ($purchases as $purchase) {
            $purchase_id = $purchase['id'];
            $stored_count = intval($purchase['total_drink_count']);
            $calculated_count = $this->calculate_purchase_drink_count($purchase_id);
            
            if ($stored_count !== $calculated_count) {
                $results['mismatches_found']++;
                
                // Fix the mismatch
                $update_result = $this->update_purchase_drink_count($purchase_id, $calculated_count);
                
                if ($update_result) {
                    $results['fixes_applied']++;
                    error_log("RESET: Fixed drink count mismatch for purchase {$purchase_id}: {$stored_count} -> {$calculated_count}");
                } else {
                    $results['errors'][] = "Failed to update purchase {$purchase_id}";
                }
            }
        }
        
        return $results;
    }

    /**
     * Get recent purchases for display badges (formatted for UI)
     * Orders from oldest to newest to show early supporters first
     */
    public function get_recent_purchases_for_display(int $limit = 21): array {
        $purchases = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT p.purchaser_name, 
                        COALESCE(p.gaming_name, '') as gaming_name, 
                        p.created_at, 
                        p.ticket_type,
                        p.id as purchase_id,
                        CASE WHEN pa.purchase_id IS NOT NULL THEN 1 ELSE 0 END as has_addons
                FROM {$this->table_purchases} p 
                LEFT JOIN {$this->wpdb->prefix}reset_purchase_addons pa ON p.id = pa.purchase_id
                WHERE p.payment_status = 'completed' 
                GROUP BY p.id
                ORDER BY p.created_at ASC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        // Format the purchases for display
        $formatted_purchases = array();
        foreach ($purchases as $purchase) {
            $formatted_name = $this->format_display_name($purchase['purchaser_name'], $purchase['gaming_name']);
            $time_ago = $this->get_time_ago($purchase['created_at']);
            
            // Format created_at for JavaScript with proper timezone handling
            $wp_timezone = wp_timezone();
            $created_date = new DateTime($purchase['created_at'], $wp_timezone);
            $created_at_iso = $created_date->format('Y-m-d\TH:i:s');
            
            $formatted_purchases[] = array(
                'display_name' => $formatted_name,
                'time_ago' => $time_ago,
                'created_at' => $created_at_iso,
                'token_type' => $purchase['ticket_type'],
                'has_addons' => (bool) $purchase['has_addons']
            );
        }
        
        return $formatted_purchases;
    }
    
    /**
     * Format display name for badges (similar to inviter info logic)
     */
    private function format_display_name(string $real_name, ?string $gaming_name): string {
        $real_name = trim($real_name);
        $gaming_name = trim($gaming_name ?? '');
        
        // Format the name according to privacy requirements
        if (!empty($real_name) && !empty($gaming_name)) {
            // Split real name into words
            $name_parts = explode(' ', $real_name);
            
            if (count($name_parts) >= 2) {
                // Two or more names: "FirstName .LastInitial - GamingName"
                $first_name = $name_parts[0];
                $last_initial = strtoupper(substr($name_parts[1], 0, 1));
                return $first_name . ' .' . $last_initial . ' - ' . $gaming_name;
            } else {
                // One name: "Name - GamingName"
                return $real_name . ' - ' . $gaming_name;
            }
        } elseif (!empty($gaming_name)) {
            // Only gaming name available
            return $gaming_name;
        } elseif (!empty($real_name)) {
            // Only real name available - show first name and last initial for privacy
            $name_parts = explode(' ', $real_name);
            if (count($name_parts) >= 2) {
                $first_name = $name_parts[0];
                $last_initial = strtoupper(substr($name_parts[1], 0, 1));
                return $first_name . ' .' . $last_initial;
            } else {
                return $real_name;
            }
        }
        
        return 'Anonymous';
    }
    
    /**
     * Email Recipients Management Methods
     */
    
    /**
     * Add recipients to email campaign
     */
    public function add_campaign_recipients(int $campaign_id, array $recipients): bool {
        if (empty($recipients)) {
            return false;
        }
        
        $values = array();
        $placeholders = array();
        
        foreach ($recipients as $recipient) {
            $values[] = $campaign_id;
            $values[] = sanitize_email($recipient['email']);
            $values[] = sanitize_text_field($recipient['name'] ?? '');
            $values[] = sanitize_text_field($recipient['token_code'] ?? '');
            $values[] = sanitize_text_field($recipient['source'] ?? 'filtered');
            $placeholders[] = '(%d, %s, %s, %s, %s)';
        }
        
        if (empty($placeholders)) {
            return false;
        }
        
        $sql = "INSERT INTO {$this->table_email_recipients} 
                (campaign_id, recipient_email, recipient_name, token_code, source) 
                VALUES " . implode(', ', $placeholders);
        
        $result = $this->wpdb->query(
            $this->wpdb->prepare($sql, $values)
        );
        
        return $result !== false;
    }
    
    /**
     * Get pending recipients for campaign
     */
    public function get_pending_recipients(int $campaign_id, int $limit = 50): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_email_recipients} 
                 WHERE campaign_id = %d AND status = 'pending' 
                 ORDER BY id ASC LIMIT %d",
                $campaign_id,
                $limit
            ),
            ARRAY_A
        );
    }
    
    /**
     * Update recipient status
     */
    public function update_recipient_status(int $recipient_id, string $status, string $error_message = ''): bool {
        $update_data = array(
            'status' => $status,
            'sent_at' => current_time('mysql')
        );
        
        if (!empty($error_message)) {
            $update_data['error_message'] = $error_message;
        }
        
        $result = $this->wpdb->update(
            $this->table_email_recipients,
            $update_data,
            array('id' => $recipient_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get campaign recipient stats
     */
    public function get_campaign_stats(int $campaign_id): array {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM {$this->table_email_recipients} 
                WHERE campaign_id = %d",
                $campaign_id
            ),
            ARRAY_A
        );
        
        return $stats ?: array('total' => 0, 'pending' => 0, 'sent' => 0, 'failed' => 0);
    }
    
    /**
     * Check if campaign has pending recipients
     */
    public function has_pending_recipients(int $campaign_id): bool {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_email_recipients} 
                 WHERE campaign_id = %d AND status = 'pending'",
                $campaign_id
            )
        );
        
        return (int)$count > 0;
    }
    
    /**
     * Get recipients for filtering (supports complex filters)
     */
    public function get_filtered_recipients(array $filters): array {
        $where_conditions = array("p.payment_status = 'completed'");
        $params = array();
        
        // Token type filter
        if (!empty($filters['token_types']) && is_array($filters['token_types'])) {
            $token_placeholders = array_fill(0, count($filters['token_types']), '%s');
            $where_conditions[] = "t.token_type IN (" . implode(',', $token_placeholders) . ")";
            $params = array_merge($params, $filters['token_types']);
        }
        
        // Payment status filter
        if (!empty($filters['payment_status']) && is_array($filters['payment_status'])) {
            $status_placeholders = array_fill(0, count($filters['payment_status']), '%s');
            $where_conditions[] = "p.payment_status IN (" . implode(',', $status_placeholders) . ")";
            $params = array_merge($params, $filters['payment_status']);
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "p.created_at >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "p.created_at <= %s";
            $params[] = $filters['date_to'];
        }
        
        // Add-ons filter
        if (!empty($filters['has_addons'])) {
            if ($filters['has_addons'] === 'yes') {
                $where_conditions[] = "p.addon_total > 0";
            } elseif ($filters['has_addons'] === 'no') {
                $where_conditions[] = "p.addon_total = 0";
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT DISTINCT 
                    p.purchaser_email as email,
                    p.purchaser_name as name,
                    t.token_code,
                    t.token_type,
                    p.payment_status,
                    p.created_at
                FROM {$this->table_purchases} p
                LEFT JOIN {$this->table_tokens} t ON p.token_id = t.id
                WHERE {$where_clause}
                ORDER BY p.created_at DESC";
        
        if (empty($params)) {
            return $this->wpdb->get_results($sql, ARRAY_A);
        } else {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $params),
                ARRAY_A
            );
        }
    }
    
    /**
     * Get relative time ago string
     */
    private function get_time_ago(string $datetime): string {
        // Use WordPress timezone for consistent calculations
        $wp_timezone = wp_timezone();
        
        // Create DateTime objects with proper timezone handling
        $created_date = new DateTime($datetime, $wp_timezone);
        $current_date = new DateTime('now', $wp_timezone);
        
        $time = $current_date->getTimestamp() - $created_date->getTimestamp();
        
        // Debug logging for all calculations when WP_DEBUG is on
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RESET Debug: Time calculation - Created: ' . $datetime . ' (' . $created_date->format('Y-m-d H:i:s T') . ' timestamp: ' . $created_date->getTimestamp() . '), Current: ' . $current_date->format('Y-m-d H:i:s T') . ' (timestamp: ' . $current_date->getTimestamp() . '), Diff: ' . $time . ' seconds');
        }
        
        // Handle future dates (test data)
        if ($time < 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RESET Debug: Returning "Just Now" due to future date - Time diff: ' . $time);
            }
            return 'Just Now';
        }
        
        if ($time < 60) {
            return min($time, 99) . ' sec ago';  // Limit to 2 digits max
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' min ago';  // Show exact minutes
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', strtotime($datetime));
        }
    }
    
    // ================================
    // CHECK-IN MANAGEMENT METHODS
    // ================================
    
    /**
     * Update purchase check-in status
     */
    public function update_purchase_check_in(int $purchase_id, bool $checked_in = true, array $user_info = array()): bool {
        $update_data = array('checked_in' => $checked_in ? 1 : 0);
        
        if ($checked_in) {
            $update_data['checked_in_at'] = current_time('mysql');
            $current_user = wp_get_current_user();
            $update_data['checked_in_by'] = $user_info['user_login'] ?? $current_user->user_login ?? 'system';
        } else {
            $update_data['checked_in_at'] = null;
            $update_data['checked_in_by'] = null;
        }
        
        $result = $this->wpdb->update(
            $this->table_purchases,
            $update_data,
            array('id' => $purchase_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get checked-in count
     */
    public function get_checked_in_count(): int {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_purchases} 
            WHERE payment_status = 'completed' AND checked_in = 1"
        );
    }
    
    /**
     * Get total attendees vs checked-in statistics
     */
    public function get_total_attendees_vs_checked_in(): array {
        $total_attendees = $this->get_total_attendees_count();
        $checked_in_count = $this->get_checked_in_count();
        
        return array(
            'total_attendees' => $total_attendees,
            'checked_in_count' => $checked_in_count,
            'not_checked_in' => $total_attendees - $checked_in_count,
            'percentage_checked_in' => $total_attendees > 0 ? round(($checked_in_count / $total_attendees) * 100, 2) : 0
        );
    }
    
    /**
     * Get check-in export data
     */
    public function get_check_in_export_data(string $checkin_filter = 'all', string $start_date = '', string $end_date = ''): array {
        // Build WHERE conditions based on filters
        $where_conditions = array("p.payment_status = 'completed'");
        
        // Check-in status filter
        if ($checkin_filter === 'checked_in') {
            $where_conditions[] = "p.checked_in = 1";
        } elseif ($checkin_filter === 'not_checked_in') {
            $where_conditions[] = "p.checked_in = 0";
        }
        
        // Date range filters
        if (!empty($start_date)) {
            $where_conditions[] = $this->wpdb->prepare("DATE(p.created_at) >= %s", $start_date);
        }
        if (!empty($end_date)) {
            $where_conditions[] = $this->wpdb->prepare("DATE(p.created_at) <= %s", $end_date);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get all completed purchases with their basic info
        $purchases = $this->wpdb->get_results(
            "SELECT 
                p.id,
                p.purchaser_name,
                p.purchaser_email,
                t.token_code,
                p.ticket_type,
                p.total_drink_count,
                p.checked_in,
                p.checked_in_at,
                p.checked_in_by,
                p.created_at
            FROM {$this->table_purchases} p
            LEFT JOIN {$this->table_tokens} t ON p.token_id = t.id
            {$where_clause}
            ORDER BY p.checked_in DESC, p.created_at DESC",
            ARRAY_A
        );

        // For each purchase, get addon details and friendly ticket type
        foreach ($purchases as &$purchase) {
            $purchase['addon_details'] = $this->get_purchase_addon_details_for_export($purchase['id']);
            $purchase['ticket_type'] = $this->get_friendly_ticket_type_for_export($purchase['ticket_type']);
        }

        return $purchases;
    }

    /**
     * Helper method to get addon details for export
     */
    private function get_purchase_addon_details_for_export($purchase_id): string {
        // Get all addons for this purchase
        $addons = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT a.addon_key, a.name, a.drink_count, pa.created_at
                FROM {$this->wpdb->prefix}reset_purchase_addons pa
                JOIN {$this->wpdb->prefix}reset_addons a ON pa.addon_id = a.id
                WHERE pa.purchase_id = %d
                ORDER BY pa.created_at DESC",
                $purchase_id
            ),
            ARRAY_A
        );

        if (empty($addons)) {
            return 'No addons';
        }

        // Get token type for conditional logic
        $token_info = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT t.token_type, p.created_at as purchase_date
                FROM {$this->table_purchases} p
                LEFT JOIN {$this->table_tokens} t ON p.token_id = t.id
                WHERE p.id = %d",
                $purchase_id
            ),
            ARRAY_A
        );

        $token_type = $token_info['token_type'] ?? '';
        $purchase_date = $token_info['purchase_date'] ?? '';

        // Apply the newest addon logic
        $newest_addon = null;
        if ($token_type === 'polo_ordered') {
            // For polo_ordered, prefer paid addons
            foreach ($addons as $addon) {
                if ($addon['addon_key'] !== 'afterpart_package_0') {
                    $newest_addon = $addon;
                    break;
                }
            }
            // If no paid addon found, use the free one
            if (!$newest_addon && !empty($addons)) {
                $newest_addon = $addons[0];
            }
        } else {
            // For other token types, just use the newest
            $newest_addon = $addons[0];
        }

        if (!$newest_addon) {
            return 'No addons';
        }

        // Format addon details with conditional drink count
        $addon_name = $newest_addon['name'];
        $addon_key = $newest_addon['addon_key'];
        
        if ($addon_key === 'afterparty_package_2') {
            $cutoff_date = '2025-07-23 00:00:00';
            if (strtotime($purchase_date) < strtotime($cutoff_date)) {
                return $addon_name . ' (4 drinks - grandfathered)';
            } else {
                return $addon_name . ' (3 drinks)';
            }
        } elseif ($addon_key === 'afterpart_package_0') {
            return $addon_name . ' (1 drink)';
        } else {
            $drink_count = intval($newest_addon['drink_count']);
            return $addon_name . ' (' . $drink_count . ' drinks)';
        }
    }

    /**
     * Helper method to get friendly ticket type names for export
     */
    private function get_friendly_ticket_type_for_export($ticket_key): string {
        if (empty($ticket_key)) {
            return 'Free Ticket';
        }
        
        // Mapping for ticket types (from purchases table)
        $ticket_names = array(
            'general_early' => 'Early Bird',
            'general_late' => 'Late Bird', 
            'general_very_late' => 'Very Late Bird',
            'afterparty_package_1' => 'Afterparty - Package 01',
            'afterparty_package_2' => 'Afterparty - Package 02'
        );
        
        // Mapping for token types (from tokens table)
        $token_type_names = array(
            'normal' => 'Normal Key',
            'free_ticket' => 'Free Ticket Key',
            'polo_ordered' => 'Polo Ordered Key',
            'sponsor' => 'Sponsor Key',
            'invitation' => 'Invitation Key'
        );
        
        // Check ticket types first, then token types
        if (isset($ticket_names[$ticket_key])) {
            return $ticket_names[$ticket_key];
        } elseif (isset($token_type_names[$ticket_key])) {
            return $token_type_names[$ticket_key];
        }
        
        // Fallback for unrecognized values
        return ucfirst(str_replace('_', ' ', $ticket_key)) ?: 'Free Ticket';
    }
    
    /**
     * Get check-in statistics for dashboard
     */
    public function get_check_in_statistics(): array {
        $stats = $this->get_total_attendees_vs_checked_in();
        
        // Get recent check-ins (last 10)
        $recent_checkins = $this->wpdb->get_results(
            "SELECT 
                p.purchaser_name,
                p.checked_in_at,
                p.checked_in_by
            FROM {$this->table_purchases} p
            WHERE p.checked_in = 1 AND p.checked_in_at IS NOT NULL
            ORDER BY p.checked_in_at DESC
            LIMIT 10",
            ARRAY_A
        );
        
        $stats['recent_checkins'] = $recent_checkins;
        return $stats;
    }
} 