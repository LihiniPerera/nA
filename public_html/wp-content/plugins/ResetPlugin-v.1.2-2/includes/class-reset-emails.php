<?php
if (!defined('ABSPATH')) {
    exit;
}

class ResetEmails {
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
    }
    
    public function send_ticket_confirmation(array $purchase, array $invitation_tokens): bool {
        $event_details = $this->core->get_event_details();
        
        $subject = sprintf(
            __('🎉 Your %s Ticket Confirmation', 'reset-ticketing'),
            $event_details['name']
        );
        
        // Load HTML template
        $template_path = plugin_dir_path(__FILE__) . '../emails/ticket-confirmation.php';
        if (!file_exists($template_path)) {
            return $this->send_ticket_confirmation_fallback($purchase, $invitation_tokens);
        }
        
        $template = file_get_contents($template_path);
        
        // Generate invitation keys HTML with inline styles for email client compatibility
        $tokens_html = '';
        foreach ($invitation_tokens as $token) {
            $tokens_html .= '<div class="invitaion-keys" style="background: #ffffff; color: #000000; border: 2px solid #f9c613; padding: 15px 10px; border-radius: 12px; text-align: center; font-family: \'Courier New\', monospace; font-weight: 700; font-size: 0.9rem; letter-spacing: 1px; min-height: 50px; display: inline-block; margin: 8px; word-break: break-all; vertical-align: top; box-sizing: border-box; width: 140px; -webkit-text-fill-color: #000000 !important;">' . esc_html($token['code']) . '</div>';
        }
        
        // Get ticket type name - handle both regular tickets and key types
        $ticket_type_name = $this->get_ticket_type_name($purchase['ticket_type']);
        
        // Get addons for this purchase
        $purchase_addons = $this->db->get_addons_for_purchase((int)$purchase['id']);
        
        // Apply same filtering logic - hide free addon for polo_ordered users if paid addon selected
        if (!empty($purchase_addons)) {
            $token = $this->db->get_token_by_id($purchase['token_id']);
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
        
        // Generate addons table rows
        $addons_html = '';
        if (!empty($purchase_addons)) {
            foreach ($purchase_addons as $addon) {
                // Check if this addon should be shown as FREE for polo_ordered users
                $is_free_for_user = ($token_type === 'polo_ordered' && $addon['addon_key'] === 'afterpart_package_0');
                $addon_price_display = $is_free_for_user ? 'FREE' : 'Rs. ' . number_format($addon['addon_price'], 2);
                
                $addons_html .= '<tr style="border-bottom: 1px solid #e9ecef;">';
                $addons_html .= '<td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000;">Add-on:</td>';
                $addons_html .= '<td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333;">';
                
                $addons_html .= '<span style="background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%); color: #000000; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; -webkit-text-fill-color: #000000;">' . esc_html($addon['name']) . '</span>';
                
                $addons_html .= '</td>';
                $addons_html .= '</tr>';
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
                    // Don't add price for free addons (polo_ordered users get Package 0 free)
                    if ($token_type === 'polo_ordered' && $addon['addon_key'] === 'afterpart_package_0') {
                        continue; // Skip adding this addon's price
                    }
                    $addon_total += floatval($addon['addon_price']);
                }
            }
            
            $total_amount = $ticket_price + $addon_total;
        }
        
        // Special case: if polo_ordered user with only free addon, ensure total is 0
        if ($token_type === 'polo_ordered' && $ticket_price == 0) {
            $has_only_free_addon = true;
            if (!empty($purchase_addons)) {
                foreach ($purchase_addons as $addon) {
                    if ($addon['addon_key'] !== 'afterpart_package_0') {
                        $has_only_free_addon = false;
                        break;
                    }
                }
            }
            if ($has_only_free_addon) {
                $total_amount = 0;
            }
        }
        
        // Replace template placeholders
        $replacements = array(
            '{{TICKET_TYPE}}' => esc_html($purchase['ticket_type']),
            '{{TICKET_TYPE_NAME}}' => esc_html($ticket_type_name),
            '{{TICKET_PRICE}}' => esc_html($this->core->format_currency($purchase['ticket_price'])),
            '{{ADDONS_TABLE_ROWS}}' => $addons_html,
            '{{TOTAL_AMOUNT}}' => esc_html($this->core->format_currency($total_amount)),
            '{{PURCHASER_NAME}}' => esc_html($purchase['purchaser_name']),
            '{{PAYMENT_REFERENCE}}' => esc_html($purchase['payment_reference']),
            '{{QR_CODE_IMAGE}}' => '<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; color: #666;">Digital ticket access via email confirmation</div>',
            '{{INVITATION_TOKENS}}' => $tokens_html,
            '{{SITE_URL}}' => esc_url(home_url()),
        );
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($purchase['purchaser_email'], $subject, $message, $headers);
        
        // Log email
        $this->db->log_email(array(
            'purchase_id' => $purchase['id'],
            'email_type' => 'confirmation',
            'recipient_email' => $purchase['purchaser_email'],
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed'
        ));
        
        return $sent;
    }
    
    private function send_ticket_confirmation_fallback(array $purchase, array $invitation_tokens): bool {
        $event_details = $this->core->get_event_details();
        
        $subject = sprintf(
            __('Your %s Ticket Confirmation', 'reset-ticketing'),
            $event_details['name']
        );
        
        $tokens_list = '';
        foreach ($invitation_tokens as $token) {
            $tokens_list .= $token['code'] . "\n";
        }
        
        // Get addons for this purchase
        $purchase_addons = $this->db->get_addons_for_purchase((int)$purchase['id']);
        
        // Apply same filtering logic - hide free addon for polo_ordered users if paid addon selected
        if (!empty($purchase_addons)) {
            $token = $this->db->get_token_by_id($purchase['token_id']);
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
        
        // FIXED: Correct amount calculation for free keys with add-ons
        $total_amount = 0;
        $addons_text = '';
        
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
                    // Don't add price for free addons (polo_ordered users get Package 0 free)
                    if ($token_type === 'polo_ordered' && $addon['addon_key'] === 'afterpart_package_0') {
                        continue; // Skip adding this addon's price
                    }
                    $addon_total += floatval($addon['addon_price']);
                }
            }
            
            $total_amount = $ticket_price + $addon_total;
        }
        
        // Special case: if polo_ordered user with only free addon, ensure total is 0
        if ($token_type === 'polo_ordered' && $ticket_price == 0) {
            $has_only_free_addon = true;
            if (!empty($purchase_addons)) {
                foreach ($purchase_addons as $addon) {
                    if ($addon['addon_key'] !== 'afterpart_package_0') {
                        $has_only_free_addon = false;
                        break;
                    }
                }
            }
            if ($has_only_free_addon) {
                $total_amount = 0;
            }
        }
        
        // Generate addons text
        if (!empty($purchase_addons)) {
            $addons_text = "\n\nAdd-ons:\n";
            foreach ($purchase_addons as $addon) {
                $is_free_for_user = ($token_type === 'polo_ordered' && $addon['addon_key'] === 'afterpart_package_0');
                $price_display = $is_free_for_user ? 'FREE' : $this->core->format_currency($addon['addon_price']);
                $addons_text .= "- " . $addon['name'] . ": " . $price_display . "\n";
            }
        }
        
        $message = sprintf(
            __('Dear %s,

Congratulations! Your ticket for %s has been confirmed.

Event Details:
- Event: %s
- Date: %s
- Ticket Type: %s
- Ticket Price: %s%s
- Total Amount: %s

Your 5 invitation keys:
%s

Please share these keys with your friends to join the event!

Important Note: We cannot guarantee all keys will be eligible. If we reach our capacity, some keys may be cancelled.

Thank you for joining us!

Best regards,
%s Team', 'reset-ticketing'),
            $purchase['purchaser_name'],
            $event_details['full_name'],
            $event_details['full_name'],
            $event_details['formatted_date'],
            $purchase['ticket_type'],
            $this->core->format_currency($purchase['ticket_price']),
            $addons_text,
            $this->core->format_currency($total_amount),
            $tokens_list,
            $event_details['name']
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail($purchase['purchaser_email'], $subject, $message, $headers);
        
        // Log email
        $this->db->log_email(array(
            'purchase_id' => $purchase['id'],
            'email_type' => 'confirmation',
            'recipient_email' => $purchase['purchaser_email'],
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed'
        ));
        
        return $sent;
    }
    
    public function send_reminder_emails(): int {
        $event_details = $this->core->get_event_details();
        
        // Only send if event is within 2 days
        if (!$this->core->is_event_approaching()) {
            return 0;
        }
        
        $purchases = $this->db->get_purchases_for_reminder();
        $emails_sent = 0;
        
        foreach ($purchases as $purchase) {
            $unused_tokens_count = $this->db->get_unused_invitation_tokens_count_for_purchase($purchase['id']);
            
            if ($unused_tokens_count > 0) {
                $subject = sprintf(
                    __('Reminder: %s is in 2 days - You have %d unused invitation keys!', 'reset-ticketing'),
                    $event_details['name'],
                    $unused_tokens_count
                );
                
                $message = sprintf(
                    __('Dear %s,

%s is just 2 days away!

We noticed you still have %d unused invitation keys. Why not invite your friends to join the fun?

Event Details:
- Event: %s
- Date: %s

Don\'t let your friends miss out on this amazing event!

See you there!

Best regards,
%s Team', 'reset-ticketing'),
                    $purchase['purchaser_name'],
                    $event_details['full_name'],
                    $unused_tokens_count,
                    $event_details['full_name'],
                    $event_details['formatted_date'],
                    $event_details['name']
                );
                
                $sent = wp_mail($purchase['purchaser_email'], $subject, $message);
                
                if ($sent) {
                    $emails_sent++;
                }
                
                // Log email
                $this->db->log_email(array(
                    'purchase_id' => $purchase['id'],
                    'email_type' => 'reminder',
                    'recipient_email' => $purchase['purchaser_email'],
                    'subject' => $subject,
                    'status' => $sent ? 'sent' : 'failed'
                ));
            }
        }
        
        return $emails_sent;
    }
    
    public function send_token_cancellation_email(array $token, string $reason): bool {
        if (empty($token['used_by_email'])) {
            return false;
        }
        
        $event_details = $this->core->get_event_details();
        
        $subject = sprintf(
            __('⚠️ Important: Your %s keys has been cancelled', 'reset-ticketing'),
            $event_details['name']
        );
        
        // Load HTML template
        $template_path = plugin_dir_path(__FILE__) . '../emails/token-cancellation.php';
        if (!file_exists($template_path)) {
            return $this->send_token_cancellation_fallback($token, $reason);
        }
        
        $template = file_get_contents($template_path);
        
        // Replace template placeholders
        $replacements = array(
            '{{TOKEN_CODE}}' => esc_html($token['token_code']),
            '{{TOKEN_TYPE}}' => esc_html(ucfirst($token['token_type'])),
            '{{CANCELLED_DATE}}' => esc_html(date('F j, Y g:i A')),
            '{{CANCELLED_BY}}' => esc_html('System Administrator'),
            '{{CANCELLATION_REASON}}' => esc_html($reason ?: 'Due to capacity limitations to ensure the best experience for all attendees.'),
        );
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($token['used_by_email'], $subject, $message, $headers);
        
        // Log email
        $this->db->log_email(array(
            'email_type' => 'cancellation',
            'recipient_email' => $token['used_by_email'],
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed'
        ));
        
        return $sent;
    }
    
    private function send_token_cancellation_fallback(array $token, string $reason): bool {
        $event_details = $this->core->get_event_details();
        
        $subject = sprintf(
            __('Important: Your %s keys has been cancelled', 'reset-ticketing'),
            $event_details['name']
        );
        
        $message = sprintf(
            __('Dear %s,

We regret to inform you that your keys (%s) for the %s event has been cancelled.

Reason: %s

We sincerely apologize for any inconvenience this may cause. This decision was made due to capacity limitations to ensure the best experience for all attendees.

If you have any questions or concerns, please don\'t hesitate to contact us at %s.

Thank you for your understanding.

Best regards,
%s Team', 'reset-ticketing'),
            $token['used_by_name'] ?: 'Participant',
            $token['token_code'],
            $event_details['full_name'],
            $reason ?: 'Capacity limitations',
            get_option('admin_email'),
            $event_details['name']
        );
        
        $sent = wp_mail($token['used_by_email'], $subject, $message);
        
        // Log email
        $this->db->log_email(array(
            'email_type' => 'cancellation',
            'recipient_email' => $token['used_by_email'],
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed'
        ));
        
        return $sent;
    }
    
    public function send_admin_notification(string $type, string $message, array $data = array()): bool {
        $admin_email = get_option('admin_email');
        
        $subject = sprintf('[RESET Ticketing] %s', ucfirst($type));
        
        $full_message = $message . "\n\n";
        $full_message .= "Event: RESET\n";
        $full_message .= "Time: " . current_time('mysql') . "\n";
        
        if (!empty($data)) {
            $full_message .= "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        
        $sent = wp_mail($admin_email, $subject, $full_message);
        
        // Log email
        $this->db->log_email(array(
            'email_type' => 'admin_notification',
            'recipient_email' => $admin_email,
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed'
        ));
        
        return $sent;
    }
    
    public function get_email_templates(): array {
        return array(
            'ticket_confirmation' => array(
                'name' => __('Ticket Confirmation', 'reset-ticketing'),
                'description' => __('Sent when a ticket is purchased successfully', 'reset-ticketing')
            ),
            'reminder' => array(
                'name' => __('Event Reminder', 'reset-ticketing'),
                'description' => __('Sent 2 days before the event', 'reset-ticketing')
            ),
            'cancellation' => array(
                'name' => __('keys Cancellation', 'reset-ticketing'),
                'description' => __('Sent when a keys is cancelled', 'reset-ticketing')
            ),
            'admin_notification' => array(
                'name' => __('Admin Notification', 'reset-ticketing'),
                'description' => __('Sent to admin for various events', 'reset-ticketing')
            )
        );
    }
    
    public function get_email_statistics(): array {
        $logs = $this->db->get_email_logs(1000);
        
        $stats = array(
            'total_sent' => 0,
            'total_failed' => 0,
            'by_type' => array(),
            'recent' => array_slice($logs, 0, 10)
        );
        
        foreach ($logs as $log) {
            if ($log['status'] === 'sent') {
                $stats['total_sent']++;
            } else {
                $stats['total_failed']++;
            }
            
            if (!isset($stats['by_type'][$log['email_type']])) {
                $stats['by_type'][$log['email_type']] = array('sent' => 0, 'failed' => 0);
            }
            
            $stats['by_type'][$log['email_type']][$log['status']]++;
        }
        
        return $stats;
    }
    
    /**
     * Get proper ticket type name - handles both regular tickets and keys types
     */
    private function get_ticket_type_name(string $ticket_key): string {
        // IMPROVED: Handle empty/null values
        if (empty($ticket_key)) {
            error_log("DEBUG: Email get_ticket_type_name called with empty ticket_key");
            return 'Free Ticket'; // Default for empty values (likely free keys)
        }
        
        // DEBUG: Log what we're receiving (remove this after testing)
        error_log("DEBUG: Email get_ticket_type_name called with: " . $ticket_key);
        
        // Try to get from database first (for dynamic ticket types)
        $ticket_types = $this->core->get_ticket_pricing();
        if (isset($ticket_types[$ticket_key])) {
            return $ticket_types[$ticket_key]['name'];
        }
        
        // FIXED: Handle keys types (these are used for free tickets)
        $token_type_names = array(
            'free_ticket' => 'Free Ticket',
            'polo_ordered' => 'FREE Ticket',
            'sponsor' => 'FREE Ticket',
            'normal' => 'Regular Ticket'
        );
        
        if (isset($token_type_names[$ticket_key])) {
            return $token_type_names[$ticket_key];
        }
        
        // IMPROVED: Better fallback for unrecognized values
        error_log("DEBUG: Email Unrecognized ticket_key: " . $ticket_key . " - using fallback");
        return ucfirst(str_replace('_', ' ', $ticket_key)) ?: 'Free Ticket';
    }
} 