<?php
if (!defined('ABSPATH')) {
    exit;
}

class ResetAdmin {
    private static $instance = null;
    private $db;
    private $core;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = ResetDatabase::getInstance();
        $this->core = ResetCore::getInstance();
        
        // Add admin AJAX handlers
        add_action('wp_ajax_reset_generate_tokens', array($this, 'ajax_generate_tokens'));
        add_action('wp_ajax_reset_cancel_token', array($this, 'ajax_cancel_token'));
        add_action('wp_ajax_reset_mark_token_as_sent', array($this, 'ajax_mark_token_as_sent'));
        add_action('wp_ajax_reset_quick_mark_sent', array($this, 'ajax_quick_mark_sent'));
        add_action('wp_ajax_reset_get_token_row', array($this, 'ajax_get_token_row'));
        add_action('wp_ajax_reset_search_tokens', array($this, 'ajax_search_tokens'));
        add_action('wp_ajax_reset_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_reset_rollback_capacity', array($this, 'ajax_rollback_capacity'));
        add_action('wp_ajax_reset_save_default_filters', array($this, 'ajax_save_default_filters'));
        add_action('wp_ajax_reset_reset_default_filters', array($this, 'ajax_reset_default_filters'));
        add_action('wp_ajax_reset_set_current_as_default', array($this, 'ajax_set_current_as_default'));
    }
    
    public function get_dashboard_data(): array {
        return $this->db->get_dashboard_data();
    }
    
    public function generate_tokens(string $token_type = 'normal', int $count = 1): array {
        $current_user = wp_get_current_user();
        $created_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        $tokens = ResetTokens::getInstance()->generate_tokens($token_type, $count, $created_by);
        
        // Get token type name for response
        $token_type_names = $this->core->get_token_types();
        $token_type_name = $token_type_names[$token_type]['name'] ?? $token_type;
        
        return array(
            'success' => !empty($tokens),
            'count' => count($tokens),
            'tokens' => $tokens,
            'token_type' => $token_type,
            'token_type_name' => $token_type_name,
            'message' => sprintf(__('Successfully generated %d %s keys.', 'reset-ticketing'), count($tokens), $token_type_name)
        );
    }
    
    public function generate_master_tokens(int $count): array {
        return $this->generate_tokens('normal', $count);
    }
    
    public function cancel_token(int $token_id, string $reason): array {
        $current_user = wp_get_current_user();
        $cancelled_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        $success = ResetTokens::getInstance()->cancel_token($token_id, $reason, $cancelled_by);
        
        if ($success) {
            return array(
                'success' => true,
                'message' => __('Key cancelled successfully.', 'reset-ticketing')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to cancel key.', 'reset-ticketing')
            );
        }
    }
    
    public function bulk_cancel_tokens(array $token_ids, string $reason): array {
        $current_user = wp_get_current_user();
        $cancelled_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($token_ids as $token_id) {
            if (ResetTokens::getInstance()->cancel_token($token_id, $reason, $cancelled_by)) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }
        
        return array(
            'success' => $success_count > 0,
            'count' => $success_count,
            'failed' => $failed_count,
            'message' => sprintf(__('Successfully cancelled %d keys, %d failed.', 'reset-ticketing'), $success_count, $failed_count)
        );
    }
    
    public function mark_token_as_sent(int $token_id, array $sent_data): array {
        $current_user = wp_get_current_user();
        $sent_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        $sent_data['sent_by'] = $sent_by;
        
        $success = $this->db->mark_token_as_sent($token_id, $sent_data);
        
        if ($success) {
            return array(
                'success' => true,
                'message' => __('Token marked as sent successfully.', 'reset-ticketing')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to mark token as sent.', 'reset-ticketing')
            );
        }
    }
    
    public function bulk_mark_tokens_as_sent(array $token_ids, array $sent_data): array {
        $current_user = wp_get_current_user();
        $sent_by = $current_user->display_name . ' (' . $current_user->user_email . ')';
        
        $sent_data['sent_by'] = $sent_by;
        
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($token_ids as $token_id) {
            if ($this->db->mark_token_as_sent($token_id, $sent_data)) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }
        
        return array(
            'success' => $success_count > 0,
            'count' => $success_count,
            'failed' => $failed_count,
            'message' => sprintf(__('Successfully marked %d tokens as sent, %d failed.', 'reset-ticketing'), $success_count, $failed_count)
        );
    }
    
    public function search_tokens(string $search = '', string $status = '', string $type = '', string $used = '', string $sent = '', int $per_page = 20, int $offset = 0): array {
        $tokens = $this->db->search_tokens_advanced($search, $status, $type, $used, $sent, $per_page, $offset);
        $total = $this->db->get_search_tokens_count($search, $status, $type, $used, $sent);
        
        return array(
            'tokens' => $tokens,
            'total' => $total
        );
    }
    
    public function get_token_statistics(): array {
        return ResetTokens::getInstance()->get_token_statistics();
    }
    
    public function get_sales_statistics(): array {
        return array(
            'total_revenue' => $this->db->get_total_revenue(),
            'completed_purchases' => $this->db->get_completed_purchases_count(),
            'sales_by_type' => $this->db->get_sales_by_ticket_type(),
            'recent_purchases' => $this->db->get_recent_purchases(10)
        );
    }
    
    public function get_capacity_status(): array {
        return $this->core->get_capacity_status();
    }
    
    public function get_email_statistics(): array {
        return ResetEmails::getInstance()->get_email_statistics();
    }
    
    // AJAX Handlers
    
    public function ajax_generate_tokens(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $count = intval($_POST['count'] ?? 0);
        $token_type = sanitize_text_field($_POST['token_type'] ?? 'normal');
        
        if ($count < 1) {
            wp_send_json_error('Invalid key count. Must be at least 1.');
        }
        
        // Validate token type
        $valid_types = array('normal', 'free_ticket', 'polo_ordered', 'sponsor');
        if (!in_array($token_type, $valid_types)) {
            wp_send_json_error('Invalid key type selected.');
        }
        
        $result = $this->generate_tokens($token_type, $count);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_cancel_token(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $token_id = intval($_POST['token_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if ($token_id <= 0) {
            wp_send_json_error('Invalid key ID');
        }
        
        $result = $this->cancel_token($token_id, $reason);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_mark_token_as_sent(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $token_id = intval($_POST['token_id'] ?? 0);
        $recipient_name = sanitize_text_field($_POST['recipient_name'] ?? '');
        $recipient_email = sanitize_email($_POST['recipient_email'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if ($token_id <= 0) {
            wp_send_json_error('Invalid token ID');
        }
        
        if (empty($recipient_name)) {
            wp_send_json_error('Recipient name is required');
        }
        
        $sent_data = array(
            'name' => $recipient_name,
            'email' => $recipient_email,
            'notes' => $notes
        );
        
        $result = $this->mark_token_as_sent($token_id, $sent_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_search_tokens(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $used = sanitize_text_field($_POST['used'] ?? '');
        $sent = sanitize_text_field($_POST['sent'] ?? ''); // Added sent parameter
        $per_page = intval($_POST['per_page'] ?? 20);
        $offset = intval($_POST['offset'] ?? 0);
        
        $result = $this->search_tokens($search_term, $status, $type, $used, $sent, $per_page, $offset);
        
        wp_send_json_success(array(
            'tokens' => $result['tokens'],
            'total' => $result['total'],
            'count' => count($result['tokens'])
        ));
    }
    
    public function ajax_get_dashboard_data(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $capacity_manager = ResetCapacity::getInstance();
        
        $data = array(
            'dashboard' => $this->get_dashboard_data(),
            'capacity' => $capacity_manager->get_capacity_status(),
            'token_stats' => $this->get_token_statistics(),
            'email_stats' => $this->get_email_statistics()
        );
        
        wp_send_json_success($data);
    }
    
    public function export_tokens(string $type = 'all'): string {
        $tokens = array();
        
        if ($type === 'all') {
            // Get all token types
            $token_types = array('normal', 'free_ticket', 'polo_ordered', 'sponsor', 'invitation');
            foreach ($token_types as $token_type) {
                $type_tokens = $this->db->get_tokens_by_type($token_type);
                $tokens = array_merge($tokens, $type_tokens);
            }
        } else {
            // Get specific token type
            $tokens = $this->db->get_tokens_by_type($type);
        }
        
        // Create CSV content
        $csv_content = "Key Code,key Type,Status,Used,Created By,Created At,Used At,Used By Email\n";
        
        foreach ($tokens as $token) {
            $csv_content .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $token['token_code'],
                $token['token_type'],
                $token['status'],
                $token['is_used'] ? 'Yes' : 'No',
                $token['created_by'] ?? '',
                $token['created_at'],
                $token['used_at'] ?? '',
                $token['used_by_email'] ?? ''
            );
        }
        
        return $csv_content;
    }
    
    public function get_system_info(): array {
        return array(
            'plugin_version' => $this->core->get_version(),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'mysql_version' => $this->get_mysql_version(),
            'event_date' => RESET_EVENT_DATE,
            'capacity' => RESET_TARGET_CAPACITY,
            'max_capacity' => RESET_MAX_CAPACITY,
            'tables_exist' => $this->core->is_configured(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
    }
    
    private function get_mysql_version(): string {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }
    
    public function validate_system(): array {
        $issues = array();
        
        // Check PHP version
        if (version_compare(phpversion(), '7.4', '<')) {
            $issues[] = 'PHP version 7.4 or higher is required';
        }
        
        // Check if tables exist
        if (!$this->core->is_configured()) {
            $issues[] = 'Database tables are not properly created';
        }
        
        // Check if wp_mail function works
        if (!function_exists('wp_mail')) {
            $issues[] = 'WordPress mail function is not available';
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && intval($memory_limit) < 128) {
            $issues[] = 'Memory limit should be at least 128MB';
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues
        );
    }
    
    /**
     * AJAX handler for rolling back capacity settings
     */
    public function ajax_rollback_capacity(): void {
        check_ajax_referer('reset_rollback_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $config_id = intval($_POST['config_id'] ?? 0);
        
        if ($config_id <= 0) {
            wp_send_json_error('Invalid configuration ID');
        }
        
        $capacity_manager = ResetCapacity::getInstance();
        $result = $capacity_manager->rollback_to_configuration($config_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Successfully rolled back capacity settings',
                'config_id' => $config_id
            ));
        } else {
            wp_send_json_error('Failed to rollback capacity settings');
        }
    }
    
    public function ajax_quick_mark_sent(): void {
        try {
            check_ajax_referer('reset_admin_nonce', 'nonce');
        } catch (Exception $e) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $token_id = intval($_POST['token_id'] ?? 0);
        $recipient_name = sanitize_text_field($_POST['recipient_name'] ?? '');
        $recipient_email = sanitize_email($_POST['recipient_email'] ?? '');
        
        if ($token_id <= 0) {
            wp_send_json_error('Invalid token ID');
        }
        
        if (empty($recipient_name)) {
            wp_send_json_error('Recipient name is required');
        }
        
        // Use default email if not provided or empty
        if (empty($recipient_email)) {
            $recipient_email = 'devnooballiance@gmail.com';
        }
        
        $sent_data = array(
            'name' => $recipient_name,
            'email' => $recipient_email,
            'notes' => 'Quick marked via inline edit'
        );
        
        $result = $this->mark_token_as_sent($token_id, $sent_data);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Token marked as sent successfully',
                'name' => $recipient_name,
                'email' => $recipient_email
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_get_token_row(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $token_id = intval($_POST['token_id'] ?? 0);
        
        if ($token_id <= 0) {
            wp_send_json_error('Invalid token ID');
        }
        
        // Get the updated token data
        $token = $this->db->get_token_by_id($token_id);
        
        if (!$token) {
            wp_send_json_error('Token not found');
        }
        
        // Generate the HTML for this token row
        ob_start();
        ?>
        <tr>
            <td class="check-column">
                <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                    <input type="checkbox" name="token_ids[]" value="<?php echo esc_attr($token['id']); ?>">
                <?php endif; ?>
            </td>
            <td>
                <code class="token-code-copy" 
                      data-token="<?php echo esc_attr($token['token_code']); ?>" 
                      title="<?php echo esc_attr__('Click to copy token code', 'reset-ticketing'); ?>">
                    <?php echo esc_html($token['token_code']); ?>
                </code>
            </td>
            <td class="sent-to-column">
                <?php if (!empty($token['sent_to_name'])): ?>
                    <div class="sent-display" data-token-id="<?php echo esc_attr($token['id']); ?>" title="<?php echo esc_attr__('Click to edit', 'reset-ticketing'); ?>">
                        <div class="sent-info">
                            <strong><?php echo esc_html($token['sent_to_name']); ?></strong>
                            <?php if (!empty($token['sent_to_email']) && $token['sent_to_email'] !== 'devnooballiance@gmail.com'): ?>
                                <br><small><?php echo esc_html($token['sent_to_email']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($token['sent_to_phone'])): ?>
                                <br><small><?php echo esc_html($token['sent_to_phone']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                        <input type="text" 
                               class="sent-to-input" 
                               data-token-id="<?php echo esc_attr($token['id']); ?>"
                               placeholder="<?php echo esc_attr__('Type recipient name...', 'reset-ticketing'); ?>"
                               data-original-value="">
                    <?php else: ?>
                        <span class="reset-not-sent"><?php echo esc_html__('Not sent', 'reset-ticketing'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>
                <span class="reset-token-type-<?php echo esc_attr($token['token_type']); ?>">
                    <?php echo esc_html(ucfirst($token['token_type'])); ?>
                </span>
            </td>
            <td>
                <span class="reset-status-<?php echo esc_attr($token['status']); ?>">
                    <?php echo esc_html(ucfirst($token['status'])); ?>
                    <?php if ($token['is_used']): ?>
                        <br><small><?php echo esc_html__('Used', 'reset-ticketing'); ?></small>
                    <?php endif; ?>
                </span>
            </td>
            <td>
                <?php if ($token['used_by_email']): ?>
                    <strong><?php echo esc_html($token['used_by_name']); ?></strong><br>
                    <small><?php echo esc_html($token['used_by_email']); ?></small><br>
                    <small><?php echo esc_html($token['used_by_phone']); ?></small>
                <?php else: ?>
                    <span class="reset-not-used"><?php echo esc_html__('Not used', 'reset-ticketing'); ?></span>
                <?php endif; ?>
            </td>
            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($token['created_at']))); ?></td>
            <td>
                <?php if ($token['used_at']): ?>
                    <?php echo esc_html(date('Y-m-d H:i', strtotime($token['used_at']))); ?>
                <?php else: ?>
                    <span class="reset-not-used">-</span>
                <?php endif; ?>
            </td>
            <td class="actions-column">
                <div class="action-buttons">
                    <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                        <?php if (empty($token['sent_to_email'])): ?>
                            <button type="button" class="reset-button action-btn icon-only primary mark-sent mark-sent-btn" 
                                    data-token-id="<?php echo esc_attr($token['id']); ?>" 
                                    data-token-code="<?php echo esc_attr($token['token_code']); ?>"
                                    data-tooltip="<?php echo esc_attr__('Mark as Sent', 'reset-ticketing'); ?>">✓
                            </button>
                        <?php endif; ?>
                        <button type="button" class="reset-button action-btn icon-only danger cancel cancel-token-btn" 
                                data-token-id="<?php echo esc_attr($token['id']); ?>" 
                                data-token-code="<?php echo esc_attr($token['token_code']); ?>"
                                data-tooltip="<?php echo esc_attr__('Cancel Token', 'reset-ticketing'); ?>">X
                        </button>
                    <?php elseif ($token['status'] === 'cancelled'): ?>
                        <small class="status-text"><?php echo esc_html__('Cancelled', 'reset-ticketing'); ?></small>
                        <?php if ($token['cancellation_reason']): ?>
                            <small class="reason-text" title="<?php echo esc_attr($token['cancellation_reason']); ?>">
                                <?php echo esc_html(wp_trim_words($token['cancellation_reason'], 5)); ?>
                            </small>
                        <?php endif; ?>
                    <?php elseif (!empty($token['sent_to_email'])): ?>
                        <small class="status-text"><?php echo esc_html__('Sent', 'reset-ticketing'); ?></small>
                        <?php if ($token['sent_at']): ?>
                            <small class="date-text"><?php echo esc_html(date('M j, Y', strtotime($token['sent_at']))); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
    }
    
    /**
     * Get default filter settings
     */
    public function get_default_filter_settings(): array {
        $defaults = array(
            'status' => 'active',
            'type' => 'free_ticket',
            'used' => 'unused',
            'sent' => '',
            'search' => ''
        );
        
        $saved_settings = get_option('reset_token_filter_defaults', array());
        error_log('RESET Plugin: get_default_filter_settings - Saved settings: ' . print_r($saved_settings, true));
        
        $result = wp_parse_args($saved_settings, $defaults);
        error_log('RESET Plugin: get_default_filter_settings - Final result: ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * Save default filter settings
     */
    public function save_default_filter_settings(array $settings): bool {
        $valid_settings = array();
        
        // Validate and sanitize each setting
        $valid_statuses = array('', 'active', 'cancelled', 'expired');
        $valid_types = array('', 'normal', 'free_ticket', 'polo_ordered', 'sponsor', 'invitation');
        $valid_used = array('', 'used', 'unused');
        $valid_sent = array('', 'sent', 'not_sent');
        
        $valid_settings['status'] = in_array($settings['status'] ?? '', $valid_statuses) ? $settings['status'] : '';
        $valid_settings['type'] = in_array($settings['type'] ?? '', $valid_types) ? $settings['type'] : '';
        $valid_settings['used'] = in_array($settings['used'] ?? '', $valid_used) ? $settings['used'] : '';
        $valid_settings['sent'] = in_array($settings['sent'] ?? '', $valid_sent) ? $settings['sent'] : '';
        $valid_settings['search'] = sanitize_text_field($settings['search'] ?? '');
        
        error_log('RESET Plugin: save_default_filter_settings - Valid settings: ' . print_r($valid_settings, true));
        
        $result = update_option('reset_token_filter_defaults', $valid_settings);
        error_log('RESET Plugin: save_default_filter_settings - Update result: ' . ($result ? 'Success' : 'Failed'));
        
        return $result;
    }
    
    /**
     * Apply default filters when no filters are set
     */
    public function apply_default_filters(array $current_filters): array {
        // Only apply defaults if no filters are currently set
        $has_filters = !empty($current_filters['search']) || 
                      !empty($current_filters['status']) || 
                      !empty($current_filters['type']) || 
                      !empty($current_filters['used']) || 
                      !empty($current_filters['sent']);
        
        // Debug: Log what's happening
        error_log('RESET Plugin: apply_default_filters - Current filters: ' . print_r($current_filters, true));
        error_log('RESET Plugin: apply_default_filters - Has filters: ' . ($has_filters ? 'Yes' : 'No'));
        
        if ($has_filters) {
            error_log('RESET Plugin: apply_default_filters - Returning current filters (has filters)');
            return $current_filters;
        }
        
        $defaults = $this->get_default_filter_settings();
        error_log('RESET Plugin: apply_default_filters - Defaults: ' . print_r($defaults, true));
        
        $result = array(
            'search' => $defaults['search'],
            'status' => $defaults['status'],
            'type' => $defaults['type'],
            'used' => $defaults['used'],
            'sent' => $defaults['sent']
        );
        
        error_log('RESET Plugin: apply_default_filters - Returning: ' . print_r($result, true));
        return $result;
    }
    
    /**
     * Check if current filters match defaults
     */
    public function are_filters_default(array $current_filters): bool {
        $defaults = $this->get_default_filter_settings();
        
        return $current_filters['search'] === $defaults['search'] &&
               $current_filters['status'] === $defaults['status'] &&
               $current_filters['type'] === $defaults['type'] &&
               $current_filters['used'] === $defaults['used'] &&
               $current_filters['sent'] === $defaults['sent'];
    }
    
    /**
     * Reset default filters to system defaults
     */
    public function reset_default_filters_to_system(): bool {
        $system_defaults = array(
            'status' => 'active',
            'type' => 'free_ticket',
            'used' => 'unused',
            'sent' => '',
            'search' => ''
        );
        
        return update_option('reset_token_filter_defaults', $system_defaults);
    }
    
    /**
     * AJAX handler for saving default filter settings
     */
    public function ajax_save_default_filters(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'used' => sanitize_text_field($_POST['used'] ?? ''),
            'sent' => sanitize_text_field($_POST['sent'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? '')
        );
        
        $success = $this->save_default_filter_settings($settings);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Default filter settings saved successfully.', 'reset-ticketing')
            ));
        } else {
            wp_send_json_error(__('Failed to save default filter settings.', 'reset-ticketing'));
        }
    }
    
    /**
     * AJAX handler for resetting default filters to system defaults
     */
    public function ajax_reset_default_filters(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $success = $this->reset_default_filters_to_system();
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Default filters reset to system defaults.', 'reset-ticketing'),
                'settings' => $this->get_default_filter_settings()
            ));
        } else {
            wp_send_json_error(__('Failed to reset default filters.', 'reset-ticketing'));
        }
    }
    
    /**
     * AJAX handler for setting current filters as default
     */
    public function ajax_set_current_as_default(): void {
        check_ajax_referer('reset_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'used' => sanitize_text_field($_POST['used'] ?? ''),
            'sent' => sanitize_text_field($_POST['sent'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? '')
        );
        
        // Debug: Log the settings being saved
        error_log('RESET Plugin: Saving default filters: ' . print_r($settings, true));
        
        $success = $this->save_default_filter_settings($settings);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Current filters set as default.', 'reset-ticketing')
            ));
        } else {
            wp_send_json_error(__('Failed to set current filters as default.', 'reset-ticketing'));
        }
    }
} 