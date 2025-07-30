<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Key management class for RESET ticketing system
 */
class ResetTokens {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Core instance
     */
    private $core;
    
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
        $this->db = ResetDatabase::getInstance();
        $this->core = ResetCore::getInstance();
    }
    
    /**
     * Validate key
     */
    public function validate_token(string $token_code): array {
        // Sanitize keys code
        $token_code = sanitize_text_field($token_code);
        
        if (empty($token_code)) {
            return array(
                'valid' => false,
                'message' => __('Please enter a key.', 'reset-ticketing')
            );
        }
        
        // Get keys from database
        $token = $this->db->get_token_by_code($token_code);
        
        if (!$token) {
            return array(
                'valid' => false,
                'message' => __('Invalid key code. Please check and try again.', 'reset-ticketing')
            );
        }
        
        // Check if key is cancelled
        if ($token['status'] === 'cancelled') {
            return array(
                'valid' => false,
                'message' => __('This key has been cancelled. Please contact support for assistance.', 'reset-ticketing'),
                'cancelled' => true,
                'cancellation_reason' => $token['cancellation_reason']
            );
        }
        
        // Check if key is expired
        if ($token['status'] === 'expired') {
            return array(
                'valid' => false,
                'message' => __('This key has expired.', 'reset-ticketing')
            );
        }
        
        // Check if key is already used
        if ($token['is_used']) {
            return array(
                'valid' => false,
                'message' => __('This key has already been used.', 'reset-ticketing'),
                'used' => true,
                'used_at' => $token['used_at']
            );
        }
        
        // Check if key is active
        if ($token['status'] !== 'active') {
            return array(
                'valid' => false,
                'message' => __('This key is not active.', 'reset-ticketing')
            );
        }
        
        // Check if key has expired based on date
        if ($token['expires_at'] && strtotime($token['expires_at']) < time()) {
            // Mark as expired
            $this->db->update_token($token['id'], array('status' => 'expired'));
            
            return array(
                'valid' => false,
                'message' => __('This key has expired.', 'reset-ticketing')
            );
        }
        
        // key is valid
        $message = __('Key is valid, hold on.', 'reset-ticketing');
        $result = array(
            'valid' => true,
            'message' => $message,
            'token' => $token
        );
        
        // Add special polo message for polo_ordered tokens
        if ($token['token_type'] === 'polo_ordered') {
            $result['polo_message'] = __('Thank you for helping<br> build Sri Lankan Esports', 'reset-ticketing');
        }
        
        return $result;
    }
    
    /**
     * Generate keys of specified type
     */
    public function generate_tokens(string $token_type = 'normal', int $count = 1, string $created_by = ''): array {
        $tokens = array();
        
        // Validate key type
        $valid_types = array('normal', 'free_ticket', 'polo_ordered', 'sponsor', 'invitation');
        if (!in_array($token_type, $valid_types)) {
            $token_type = 'normal';
        }
        
        for ($i = 0; $i < $count; $i++) {
            $token_code = $this->generate_unique_token($token_type);
            
            $token_data = array(
                'token_code' => $token_code,
                'token_type' => $token_type,
                'created_by' => $created_by,
                'expires_at' => RESET_EVENT_DATE . ' 23:59:59'
            );
            
            $token_id = $this->db->create_token($token_data);
            
            if ($token_id) {
                $tokens[] = array(
                    'id' => $token_id,
                    'code' => $token_code,
                    'type' => $token_type
                );
            }
        }
        
        // Log activity
        $this->core->log_activity('Generated tokens', array(
            'type' => $token_type,
            'count' => count($tokens),
            'created_by' => $created_by
        ));
        
        // Send admin notification
        $token_type_names = $this->core->get_token_types();
        $token_type_name = $token_type_names[$token_type]['name'] ?? $token_type;
        
        $this->core->send_admin_notification(
            'Keys Generated',
            sprintf('%d %s keys have been generated.', count($tokens), $token_type_name),
            array('tokens' => $tokens, 'type' => $token_type)
        );
        
        return $tokens;
    }
    
    /**
     * Generate master keys (backwards compatibility)
     */
    public function generate_master_tokens(int $count, string $created_by = ''): array {
        return $this->generate_tokens('normal', $count, $created_by);
    }
    
    /**
     * Generate invitation keys for a purchase
     */
    public function generate_invitation_tokens(int $parent_token_id, int $count = 5): array {
        $tokens = array();
        
        for ($i = 0; $i < $count; $i++) {
            $token_code = $this->generate_unique_token('invitation');
            
            $token_data = array(
                'token_code' => $token_code,
                'token_type' => 'invitation',
                'parent_token_id' => $parent_token_id,
                'expires_at' => RESET_EVENT_DATE . ' 23:59:59'
            );
            
            $token_id = $this->db->create_token($token_data);
            
            if ($token_id) {
                $tokens[] = array(
                    'id' => $token_id,
                    'code' => $token_code,
                    'type' => 'invitation'
                );
            }
        }
        
        // Log activity
        $this->core->log_activity('Generated invitation keys', array(
            'count' => count($tokens),
            'parent_token_id' => $parent_token_id
        ));
        
        return $tokens;
    }
    
    /**
     * Generate unique token code
     */
    private function generate_unique_token(string $type): string {
        $max_attempts = 10;
        $attempts = 0;
        
        do {
            $token_code = $this->core->generate_token($type);
            $existing = $this->db->get_token_by_code($token_code);
            $attempts++;
        } while ($existing && $attempts < $max_attempts);
        
        if ($attempts >= $max_attempts) {
            // Fallback with timestamp
            $token_code .= substr(time(), -4);
        }
        
        return $token_code;
    }
    
    /**
     * Use token for purchase
     */
    public function use_token(int $token_id, array $user_data): bool {
        $token = $this->db->get_token_by_id($token_id);
        
        if (!$token || $token['is_used'] || $token['status'] !== 'active') {
            return false;
        }
        
        $success = $this->db->mark_token_as_used($token_id, $user_data);
        
        if ($success) {
            // Log activity
            $this->core->log_activity('Key used', array(
                'token_id' => $token_id,
                'token_code' => $token['token_code'],
                'user_email' => $user_data['email'] ?? ''
            ));
        }
        
        return $success;
    }
    
    /**
     * Cancel token
     */
    public function cancel_token(int $token_id, string $reason = '', string $cancelled_by = ''): bool {
        $token = $this->db->get_token_by_id($token_id);
        
        if (!$token) {
            return false;
        }
        
        // Don't cancel already used tokens
        if ($token['is_used']) {
            return false;
        }
        
        $success = $this->db->cancel_token($token_id, $reason, $cancelled_by);
        
        if ($success) {
            // Log activity
            $this->core->log_activity('Key cancelled', array(
                'token_id' => $token_id,
                'token_code' => $token['token_code'],
                'reason' => $reason,
                'cancelled_by' => $cancelled_by
            ));
            
            // Send cancellation email if user has used the key for purchase
            if (!empty($token['used_by_email'])) {
                $this->send_cancellation_email($token, $reason);
            }
        }
        
        return $success;
    }
    
    /**
     * Cancel multiple keys
     */
    public function cancel_tokens(array $token_ids, string $reason = '', string $cancelled_by = ''): array {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'emails_sent' => 0
        );
        
        foreach ($token_ids as $token_id) {
            if ($this->cancel_token($token_id, $reason, $cancelled_by)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }
        
        // Log bulk cancellation
        $this->core->log_activity('Bulk key cancellation', array(
            'token_count' => count($token_ids),
            'success' => $results['success'],
            'failed' => $results['failed'],
            'reason' => $reason,
            'cancelled_by' => $cancelled_by
        ));
        
        return $results;
    }
    
    /**
     * Check capacity and auto-cancel keys if needed
     */
    public function check_capacity_and_cancel_tokens(): void {
        $capacity_status = $this->core->get_capacity_status();
        
        // If we're at 90% capacity, start cancelling unused invitation keys
        if ($capacity_status['percentage'] >= 90) {
            $tokens_to_cancel = $this->db->get_unused_tokens_for_cancellation(50);
            
            if (!empty($tokens_to_cancel)) {
                $token_ids = array_column($tokens_to_cancel, 'id');
                
                $results = $this->cancel_tokens(
                    $token_ids,
                    'Automatically cancelled due to capacity limits',
                    'System'
                );
                
                // Send admin notification
                $this->core->send_admin_notification(
                    'Automatic key Cancellation',
                    sprintf(
                        'Automatically cancelled %d keys due to capacity limits. Current capacity: %d%%',
                        $results['success'],
                        $capacity_status['percentage']
                    ),
                    $capacity_status
                );
            }
        }
    }
    
    /**
     * Get key statistics for all key types
     */
    public function get_token_statistics(): array {
        $stats = array(
            'total_tokens' => $this->db->get_used_tokens_count() + $this->db->get_active_tokens_count() + $this->db->get_cancelled_tokens_count(),
            'active_tokens' => $this->db->get_active_tokens_count(),
            'used_tokens' => $this->db->get_used_tokens_count(),
            'cancelled_tokens' => $this->db->get_cancelled_tokens_count(),
            'normal_tokens' => count($this->db->get_tokens_by_type('normal')),
            'free_ticket_tokens' => count($this->db->get_tokens_by_type('free_ticket')),
            'polo_ordered_tokens' => count($this->db->get_tokens_by_type('polo_ordered')),
            'sponsor_tokens' => count($this->db->get_tokens_by_type('sponsor')),
            'invitation_tokens' => count($this->db->get_tokens_by_type('invitation'))
        );
        
        return $stats;
    }
    
    /**
     * Search keys
     */
    public function search_tokens(string $search_term): array {
        return $this->db->search_tokens($search_term);
    }
    
    /**
     * Get keys by type with pagination
     */
    public function get_tokens_by_type(string $type, int $page = 1, int $per_page = 20): array {
        $offset = ($page - 1) * $per_page;
        return $this->db->get_tokens_by_type($type, $per_page, $offset);
    }
    
    /**
     * Send cancellation email
     */
    private function send_cancellation_email(array $token, string $reason): bool {
        if (empty($token['used_by_email'])) {
            return false;
        }
        
        $event_details = $this->core->get_event_details();
        
        $subject = sprintf(
            __('Important: Your %s key has been cancelled', 'reset-ticketing'),
            $event_details['name']
        );
        
        $message = sprintf(
            __('Dear %s,

We regret to inform you that your key (%s) for the %s event has been cancelled.

Reason: %s

We sincerely apologize for any inconvenience this may cause. 

If you have any questions or concerns, please don\'t hesitate to contact us.

Thank you for your understanding.

Best regards,
%s Team', 'reset-ticketing'),
            $token['used_by_name'] ?: 'Participant',
            $token['token_code'],
            $event_details['full_name'],
            $reason ?: 'Capacity limitations',
            $event_details['name']
        );
        
        $sent = wp_mail($token['used_by_email'], $subject, $message);
        
        // Log the email
        if (class_exists('ResetDatabase')) {
            ResetDatabase::getInstance()->log_email(array(
                'email_type' => 'cancellation',
                'recipient_email' => $token['used_by_email'],
                'subject' => $subject,
                'status' => $sent ? 'sent' : 'failed'
            ));
        }
        
        return $sent;
    }
    
    /**
     * Expire old keys
     */
    public function expire_old_tokens(): int {
        return $this->db->cleanup_expired_tokens();
    }
    
    /**
     * Get key analytics for all key types
     */
    public function get_token_analytics(): array {
        $stats = $this->get_token_statistics();
        $capacity_status = $this->core->get_capacity_status();
        
        return array(
            'total_generated' => $stats['total_tokens'],
            'usage_rate' => $stats['total_tokens'] > 0 ? round(($stats['used_tokens'] / $stats['total_tokens']) * 100, 2) : 0,
            'capacity_used' => $capacity_status['percentage'],
            'token_types' => array(
                'normal' => $stats['normal_tokens'],
                'free_ticket' => $stats['free_ticket_tokens'],
                'polo_ordered' => $stats['polo_ordered_tokens'],
                'sponsor' => $stats['sponsor_tokens'],
                'invitation' => $stats['invitation_tokens']
            ),
            'status_breakdown' => array(
                'active' => $stats['active_tokens'],
                'used' => $stats['used_tokens'],
                'cancelled' => $stats['cancelled_tokens']
            )
        );
    }
    
    /**
     * Validate key for admin operations
     */
    public function admin_validate_token(string $token_code): array {
        $token = $this->db->get_token_by_code($token_code);
        
        if (!$token) {
            return array(
                'found' => false,
                'message' => 'Token not found'
            );
        }
        
        return array(
            'found' => true,
            'token' => $token,
            'status' => $token['status'],
            'is_used' => (bool) $token['is_used'],
            'can_cancel' => $token['status'] === 'active' && !$token['is_used']
        );
    }
    
    /**
     * Get key history
     */
    public function get_token_history(int $token_id): array {
        $token = $this->db->get_token_by_id($token_id);
        
        if (!$token) {
            return array();
        }
        
        $history = array();
        
        // Creation event
        $history[] = array(
            'event' => 'created',
            'timestamp' => $token['created_at'],
            'details' => 'Token created'
        );
        
        // Usage event
        if ($token['is_used']) {
            $history[] = array(
                'event' => 'used',
                'timestamp' => $token['used_at'],
                'details' => sprintf('Used by %s (%s)', $token['used_by_name'], $token['used_by_email'])
            );
        }
        
        // Cancellation event
        if ($token['status'] === 'cancelled') {
            $history[] = array(
                'event' => 'cancelled',
                'timestamp' => $token['cancelled_at'],
                'details' => sprintf('Cancelled by %s. Reason: %s', $token['cancelled_by'], $token['cancellation_reason'])
            );
        }
        
        return $history;
    }
    
    /**
     * Get invitation keys by parent key ID
     */
    public function get_invitation_tokens_by_parent_id(int $parent_token_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'reset_tokens';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE parent_token_id = %d AND token_type = 'invitation' ORDER BY created_at ASC",
            $parent_token_id
        );
        
        $tokens = $wpdb->get_results($query, ARRAY_A);
        
        return $tokens ? $tokens : array();
    }
} 