<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Campaign Management Class for RESET ticketing system
 */
class ResetEmailCampaigns {
    
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
     * Emails instance
     */
    private $emails;
    
    /**
     * Available email templates
     */
    private $templates = array();
    
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
        $this->emails = ResetEmails::getInstance();
        
        $this->init_templates();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Action Scheduler hooks
        add_action('reset_process_email_campaign', array($this, 'process_campaign_batch'));
        add_action('reset_send_test_email', array($this, 'send_test_email_action'));
        
        // AJAX hooks
        add_action('wp_ajax_reset_preview_recipients', array($this, 'ajax_preview_recipients'));
        add_action('wp_ajax_reset_send_test_email', array($this, 'ajax_send_test_email'));
        add_action('wp_ajax_reset_get_campaign_stats', array($this, 'ajax_get_campaign_stats'));
        
        // Handle email failures
        add_action('wp_mail_failed', array($this, 'handle_email_failure'));
    }
    
    /**
     * Initialize available email templates
     */
    private function init_templates() {
        $templates_dir = RESET_PLUGIN_PATH . 'emails/campaign-templates/';
        
        $this->templates = array(
            'reminder-mouse-keyboard' => array(
                'name' => __('Gaming Equipment Reminder', 'reset-ticketing'),
                'description' => __('Remind attendees to bring their mouse and keyboard', 'reset-ticketing'),
                'file' => $templates_dir . 'reminder-mouse-keyboard.php'
            ),
            'purchase-completion' => array(
                'name' => __('Purchase Completion Follow-up', 'reset-ticketing'),
                'description' => __('Thank you message and event preparation details', 'reset-ticketing'),
                'file' => $templates_dir . 'purchase-completion.php'
            ),
            'general-announcement' => array(
                'name' => __('General Announcement', 'reset-ticketing'),
                'description' => __('General event updates and announcements', 'reset-ticketing'),
                'file' => $templates_dir . 'general-announcement.php'
            ),
            'custom' => array(
                'name' => __('Custom Template', 'reset-ticketing'),
                'description' => __('Create a custom email using the WordPress editor', 'reset-ticketing'),
                'file' => null
            )
        );
    }
    
    /**
     * Get available templates
     */
    public function get_templates(): array {
        return $this->templates;
    }
    
    /**
     * Create new email campaign
     */
    public function create_campaign(array $data): int {
        // Validate required data
        if (empty($data['subject']) || empty($data['template'])) {
            error_log('RESET Email Campaign: Missing required fields - subject or template');
            return 0;
        }
        
        // Validate template exists
        if (!isset($this->templates[$data['template']])) {
            error_log('RESET Email Campaign: Invalid template specified: ' . $data['template']);
            return 0;
        }
        
        // Create the CPT
        $campaign_id = wp_insert_post(array(
            'post_type' => 'reset_email_campaign',
            'post_title' => sanitize_text_field($data['subject']),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_status' => 'draft',
            'meta_input' => array(
                '_campaign_template' => sanitize_text_field($data['template']),
                '_campaign_subject' => sanitize_text_field($data['subject']),
                '_campaign_filters' => $data['filters'] ?? array(),
                '_campaign_custom_emails' => sanitize_textarea_field($data['custom_emails'] ?? ''),
                '_campaign_scheduled_date' => $data['scheduled_date'] ?? '',
                '_campaign_scheduled_time' => $data['scheduled_time'] ?? '',
                '_campaign_status' => 'draft',
                '_campaign_created_by' => get_current_user_id()
            )
        ));
        
        if (is_wp_error($campaign_id) || !$campaign_id) {
            return 0;
        }
        
        return $campaign_id;
    }
    
    /**
     * Schedule or send campaign
     */
    public function schedule_campaign(int $campaign_id, string $send_time = ''): bool {
        if (!$this->campaign_exists($campaign_id)) {
            return false;
        }
        
        // Generate recipients first
        $recipients_added = $this->generate_campaign_recipients($campaign_id);
        if (!$recipients_added) {
            return false;
        }
        
        // Update campaign status
        update_post_meta($campaign_id, '_campaign_status', 'scheduled');
        wp_update_post(array(
            'ID' => $campaign_id,
            'post_status' => 'publish'
        ));
        
        // Schedule with Action Scheduler
        if (!empty($send_time)) {
            $scheduled_time = strtotime($send_time);
            if ($scheduled_time && $scheduled_time > time()) {
                as_schedule_single_action($scheduled_time, 'reset_process_email_campaign', array($campaign_id));
                update_post_meta($campaign_id, '_campaign_scheduled_at', $send_time);
                return true;
            }
        }
        
        // Send immediately
        as_enqueue_async_action('reset_process_email_campaign', array($campaign_id));
        update_post_meta($campaign_id, '_campaign_status', 'sending');
        
        return true;
    }
    
    /**
     * Generate recipients for campaign based on filters
     */
    public function generate_campaign_recipients(int $campaign_id): bool {
        $filters = get_post_meta($campaign_id, '_campaign_filters', true) ?: array();
        $custom_emails = get_post_meta($campaign_id, '_campaign_custom_emails', true) ?: '';
        
        $recipients = array();
        
        // Get filtered recipients from database
        if (!empty($filters)) {
            $filtered_recipients = $this->db->get_filtered_recipients($filters);
            foreach ($filtered_recipients as $recipient) {
                $recipients[] = array(
                    'email' => $recipient['email'],
                    'name' => $recipient['name'],
                    'token_code' => $recipient['token_code'],
                    'source' => 'filtered'
                );
            }
        }
        
        // Add manual email addresses
        if (!empty($custom_emails)) {
            $manual_emails = $this->parse_email_list($custom_emails);
            foreach ($manual_emails as $email_data) {
                // Avoid duplicates
                $exists = false;
                foreach ($recipients as $existing) {
                    if ($existing['email'] === $email_data['email']) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $recipients[] = array(
                        'email' => $email_data['email'],
                        'name' => $email_data['name'],
                        'token_code' => '', // Manual recipients don't have tokens
                        'source' => 'manual'
                    );
                }
            }
        }
        
        if (empty($recipients)) {
            return false;
        }
        
        // Clear existing recipients for this campaign
        $this->clear_campaign_recipients($campaign_id);
        
        // Add recipients to database
        return $this->db->add_campaign_recipients($campaign_id, $recipients);
    }
    
    /**
     * Process email campaign in batches
     */
    public function process_campaign_batch(int $campaign_id): void {
        if (!$this->campaign_exists($campaign_id)) {
            return;
        }
        
        // Update campaign status
        update_post_meta($campaign_id, '_campaign_status', 'sending');
        
        // Get pending recipients
        $recipients = $this->db->get_pending_recipients($campaign_id, 50);
        
        if (empty($recipients)) {
            // Campaign completed
            update_post_meta($campaign_id, '_campaign_status', 'completed');
            update_post_meta($campaign_id, '_campaign_completed_at', current_time('mysql'));
            return;
        }
        
        // Get campaign data
        $subject = get_post_meta($campaign_id, '_campaign_subject', true);
        $template = get_post_meta($campaign_id, '_campaign_template', true);
        $content = get_post_field('post_content', $campaign_id);
        
        // Send emails to this batch
        foreach ($recipients as $recipient) {
            $email_content = $this->prepare_email_content($template, $content, $recipient);
            $personalized_subject = $this->personalize_subject($subject, $recipient);
            
            $result = $this->send_single_email(
                $recipient['recipient_email'],
                $personalized_subject,
                $email_content,
                $recipient['recipient_name']
            );
            
            // Update recipient status
            if ($result) {
                $this->db->update_recipient_status($recipient['id'], 'sent');
            } else {
                $this->db->update_recipient_status($recipient['id'], 'failed', 'Email sending failed');
            }
        }
        
        // Schedule next batch if more recipients exist
        if ($this->db->has_pending_recipients($campaign_id)) {
            as_enqueue_async_action('reset_process_email_campaign', array($campaign_id));
        } else {
            // Campaign completed
            update_post_meta($campaign_id, '_campaign_status', 'completed');
            update_post_meta($campaign_id, '_campaign_completed_at', current_time('mysql'));
        }
    }
    
    /**
     * Send single email
     */
    private function send_single_email(string $to, string $subject, string $content, string $name = ''): bool {
        // Validate email address
        if (!is_email($to)) {
            error_log("RESET Email Campaign: Invalid email address: {$to}");
            return false;
        }
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: RESET 2025 <noreply@nooballiance.lk>'
        );
        
        $result = wp_mail($to, $subject, $content, $headers);
        
        if (!$result) {
            error_log("RESET Email Campaign: Failed to send email to {$to} with subject: {$subject}");
        }
        
        return $result;
    }
    
    /**
     * Prepare email content with template and personalization
     */
    private function prepare_email_content(string $template, string $content, array $recipient): string {
        if ($template === 'custom') {
            $email_content = $content;
        } else {
            $template_file = $this->templates[$template]['file'] ?? '';
            if ($template_file && file_exists($template_file)) {
                ob_start();
                include $template_file;
                $email_content = ob_get_clean();
            } else {
                $email_content = $content;
            }
        }
        
        // Personalize content
        return $this->personalize_content($email_content, $recipient);
    }
    
    /**
     * Personalize email content with recipient data
     */
    private function personalize_content(string $content, array $recipient): string {
        $event_details = $this->core->get_event_details();
        
        $replacements = array(
            '{{RECIPIENT_NAME}}' => $recipient['recipient_name'] ?: 'Valued Attendee',
            '{{RECIPIENT_EMAIL}}' => $recipient['recipient_email'],
            '{{Key}}' => $recipient['token_code'] ?: 'N/A',
            '{{EVENT_NAME}}' => $event_details['name'],
            '{{EVENT_DATE}}' => $event_details['date'],
            '{{EVENT_TIME}}' => $event_details['time'],
            '{{VENUE_ADDRESS}}' => $event_details['venue'],
            '{{RESET_LOGO}}' => 'https://nooballiance.lk/wp-content/uploads/2025/07/logo-with-text-white-400.png',
            '{{CURRENT_DATE}}' => date('F j, Y'),
            '{{UNSUBSCRIBE_LINK}}' => '#', // TODO: Implement unsubscribe
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Personalize email subject
     */
    private function personalize_subject(string $subject, array $recipient): string {
        $replacements = array(
            '{{RECIPIENT_NAME}}' => $recipient['recipient_name'] ?: 'Valued Attendee',
            '{{EVENT_NAME}}' => 'RESET 2025'
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }
    
    /**
     * Parse email list from textarea
     */
    private function parse_email_list(string $email_text): array {
        $emails = array();
        $lines = explode("\n", $email_text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Support formats: email@domain.com or "Name <email@domain.com>"
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $line, $matches)) {
                $name = trim($matches[1], '"\'');
                $email = trim($matches[2]);
            } else {
                $name = '';
                $email = $line;
            }
            
            if (is_email($email)) {
                $emails[] = array(
                    'email' => $email,
                    'name' => $name
                );
            }
        }
        
        return $emails;
    }
    
    /**
     * Send test email
     */
    public function send_test_email_action(int $campaign_id): bool {
        $user = wp_get_current_user();
        if (!$user || !$user->user_email) {
            return false;
        }
        
        $subject = get_post_meta($campaign_id, '_campaign_subject', true);
        $template = get_post_meta($campaign_id, '_campaign_template', true);
        $content = get_post_field('post_content', $campaign_id);
        
        $test_recipient = array(
            'recipient_email' => $user->user_email,
            'recipient_name' => $user->display_name
        );
        
        $email_content = $this->prepare_email_content($template, $content, $test_recipient);
        $test_subject = '[TEST] ' . $this->personalize_subject($subject, $test_recipient);
        
        return $this->send_single_email($user->user_email, $test_subject, $email_content, $user->display_name);
    }
    
    /**
     * Clear campaign recipients
     */
    private function clear_campaign_recipients(int $campaign_id): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reset_email_recipients';
        
        $wpdb->delete(
            $table_name,
            array('campaign_id' => $campaign_id),
            array('%d')
        );
    }
    
    /**
     * Check if campaign exists
     */
    private function campaign_exists(int $campaign_id): bool {
        return get_post_type($campaign_id) === 'reset_email_campaign';
    }
    
    /**
     * Get campaign statistics
     */
    public function get_campaign_statistics(int $campaign_id): array {
        if (!$this->campaign_exists($campaign_id)) {
            return array();
        }
        
        $stats = $this->db->get_campaign_stats($campaign_id);
        $campaign_status = get_post_meta($campaign_id, '_campaign_status', true);
        $created_at = get_post_time('mysql', false, $campaign_id);
        $completed_at = get_post_meta($campaign_id, '_campaign_completed_at', true);
        
        return array(
            'total_recipients' => (int)$stats['total'],
            'sent' => (int)$stats['sent'],
            'pending' => (int)$stats['pending'],
            'failed' => (int)$stats['failed'],
            'status' => $campaign_status,
            'created_at' => $created_at,
            'completed_at' => $completed_at,
            'success_rate' => $stats['total'] > 0 ? round(($stats['sent'] / $stats['total']) * 100, 1) : 0
        );
    }
    
    /**
     * Handle email sending failures
     */
    public function handle_email_failure($wp_error): void {
        if (is_wp_error($wp_error)) {
            error_log('RESET Email Campaign Failed: ' . $wp_error->get_error_message());
        }
    }
    
    /**
     * AJAX: Preview recipients based on filters
     */
    public function ajax_preview_recipients(): void {
        check_ajax_referer('reset_email_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'reset-ticketing'));
        }
        
        $filters = $_POST['filters'] ?? array();
        $custom_emails = sanitize_textarea_field($_POST['custom_emails'] ?? '');
        
        $recipients = array();
        $count = 0;
        
        // Get filtered recipients
        if (!empty($filters)) {
            $filtered = $this->db->get_filtered_recipients($filters);
            $recipients = array_merge($recipients, $filtered);
        }
        
        // Add manual emails
        if (!empty($custom_emails)) {
            $manual = $this->parse_email_list($custom_emails);
            foreach ($manual as $email_data) {
                $recipients[] = array(
                    'email' => $email_data['email'],
                    'name' => $email_data['name'],
                    'source' => 'manual'
                );
            }
        }
        
        // Remove duplicates
        $unique_emails = array();
        $unique_recipients = array();
        foreach ($recipients as $recipient) {
            if (!in_array($recipient['email'], $unique_emails)) {
                $unique_emails[] = $recipient['email'];
                $unique_recipients[] = $recipient;
            }
        }
        
        wp_send_json_success(array(
            'count' => count($unique_recipients),
            'recipients' => array_slice($unique_recipients, 0, 10) // Show first 10 for preview
        ));
    }
    
    /**
     * AJAX: Send test email
     */
    public function ajax_send_test_email(): void {
        check_ajax_referer('reset_email_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'reset-ticketing'));
        }
        
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        
        if ($campaign_id && $this->send_test_email_action($campaign_id)) {
            wp_send_json_success(array('message' => __('Test email sent successfully!', 'reset-ticketing')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send test email.', 'reset-ticketing')));
        }
    }
    
    /**
     * AJAX: Get campaign statistics
     */
    public function ajax_get_campaign_stats(): void {
        check_ajax_referer('reset_email_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'reset-ticketing'));
        }
        
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        
        if ($campaign_id) {
            $stats = $this->get_campaign_statistics($campaign_id);
            wp_send_json_success($stats);
        } else {
            wp_send_json_error(array('message' => __('Invalid campaign ID.', 'reset-ticketing')));
        }
    }
} 