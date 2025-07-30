<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core functionality class for RESET ticketing system
 */
class ResetCore {
    
    /**
     * Single instance of the class
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
        // Initialize core functionality
    }
    
    /**
     * Generate secure keys with variable length based on type
     */
    public function generate_token(string $type = 'normal'): string {
        // key type prefixes
        $prefix_map = array(
            'normal' => 'NOR',
            'free_ticket' => 'FTK', 
            'polo_ordered' => 'PLO',
            'sponsor' => 'SPO',
            'invitation' => 'INV'
        );
        
        $prefix = $prefix_map[$type] ?? 'NOR';
        
        // Invitation keys are 8 characters (INV + 5 random)
        // All other keys are 6 characters (PREFIX + 3 random)
        if ($type === 'invitation') {
            return $prefix . $this->generate_random_string(5); // 3 + 5 = 8 chars total
        } else {
            return $prefix . $this->generate_random_string(3); // 3 + 3 = 6 chars total
        }
    }
    
    /**
     * Generate random string with balanced letters and numbers
     */
    private function generate_random_string(int $length): string {
        $numbers = '0123456789';
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';
        
        for ($i = 0; $i < $length; $i++) {
            // 50/50 chance between number and letter for each position
            if (wp_rand(0, 1) === 0) {
                // Choose a random number
                $random_string .= $numbers[wp_rand(0, strlen($numbers) - 1)];
            } else {
                // Choose a random letter
                $random_string .= $letters[wp_rand(0, strlen($letters) - 1)];
            }
        }
        
        return $random_string;
    }
    
    /**
     * Generate payment reference with RESET prefix for tracking
     */
    public function generate_payment_reference(string $token_code = ''): string {
        // Use last 6 digits of timestamp + 2 random digits for uniqueness
        $short_timestamp = substr(time(), -6);
        $random_suffix = wp_rand(10, 99);
        
        // CRITICAL FIX: Always start with RESET to match payment callback validation
        // Format: RESET + TIMESTAMP + RANDOM (this matches the callback expectation)
        $reference = 'RESET' . $short_timestamp . $random_suffix;
        
        // Store the key code separately if needed for tracking
        // The payment reference itself must start with RESET for the gateway
        return $reference;
    }
    
    /**
     * Get ticket pricing
     */
    public function get_ticket_pricing(): array {
        $database = ResetDatabase::getInstance();
        $tickets = $database->get_ticket_types_with_current_pricing();
        
        $formatted_tickets = array();
        
        foreach ($tickets as $ticket) {
            $formatted_tickets[$ticket['ticket_key']] = array(
                'id' => $ticket['id'],
                'name' => $ticket['name'],
                'price' => $ticket['current_price'],
                'benefits' => $ticket['features'],
                'description' => $ticket['description'],
                'available' => $ticket['is_enabled'] == 1,
                'sort_order' => $ticket['sort_order']
            );
        }
        
        return $formatted_tickets;
    }
    

    
    /**
     * Get current ticket price for type
     */
    public function get_current_ticket_price(string $ticket_type): float {
        $database = ResetDatabase::getInstance();
        $ticket = $database->get_ticket_type_by_key($ticket_type);
        
        if (!$ticket || !$ticket['is_enabled']) {
            return 0.00;
        }
        
        return floatval($ticket['ticket_price']);
    }
    
    /**
     * Sanitize and validate phone number
     */
    public function sanitize_phone(string $phone): string {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure Sri Lankan phone number format
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            $phone = '0' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '947') {
            $phone = '0' . substr($phone, 3);
        }
        
        return $phone;
    }
    
    /**
     * Validate email address
     */
    public function validate_email(string $email): bool {
        return (bool) is_email($email);
    }
    
    /**
     * Validate phone number
     */
    public function validate_phone(string $phone): bool {
        $phone = $this->sanitize_phone($phone);
        return (bool) preg_match('/^0[0-9]{9}$/', $phone);
    }
    
    /**
     * Get event details
     */
    public function get_event_details(): array {
        return array(
            'name' => 'RESET',
            'full_name' => 'RESET - Sri Lanka\'s Premier Esports Event',
            'date' => RESET_EVENT_DATE,
            'formatted_date' => date('F j, Y', strtotime(RESET_EVENT_DATE)),
            'venue' => 'Trace Expert City, Colombo - Bay 07',
            'venue_address' => 'Trace Expert City, Colombo - Bay 07',
            'description' => 'Sri Lanka\'s biggest video game community event',
            'capacity' => RESET_TARGET_CAPACITY,
            'max_capacity' => RESET_MAX_CAPACITY
        );
    }
    
    /**
     * Get time until event
     */
    public function get_time_until_event(): array {
        $event_date = new DateTime(RESET_EVENT_DATE);
        $now = new DateTime();
        
        if ($now > $event_date) {
            return array(
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'expired' => true
            );
        }
        
        $diff = $now->diff($event_date);
        
        return array(
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'expired' => false
        );
    }
    
    /**
     * Check if event is approaching (within 2 days)
     */
    public function is_event_approaching(): bool {
        $time_until = $this->get_time_until_event();
        return !$time_until['expired'] && $time_until['days'] <= 2;
    }
    
    /**
     * Format currency
     */
    public function format_currency(float $amount): string {
        return 'Rs. ' . number_format($amount, 2);
    }
    
    /**
     * Get capacity status
     */
    public function get_capacity_status(): array {
        $db = ResetDatabase::getInstance();
        $stats = $db->get_statistics();
        
        $used_percentage = ($stats['capacity_used'] / RESET_TARGET_CAPACITY) * 100;
        
        $status = 'green';
        if ($used_percentage >= 90) {
            $status = 'red';
        } elseif ($used_percentage >= 75) {
            $status = 'yellow';
        }
        
        return array(
            'used' => $stats['capacity_used'],
            'remaining' => $stats['capacity_remaining'],
            'total' => RESET_TARGET_CAPACITY,
            'percentage' => round($used_percentage, 2),
            'status' => $status,
            'alert' => $used_percentage >= 90
        );
    }
    
    /**
     * Log activity
     */
    public function log_activity(string $activity, array $data = array()): void {
        // Activity logging removed for production
    }
    
    /**
     * Send admin notification
     */
    public function send_admin_notification(string $subject, string $message, array $data = array()): void {
        $admin_email = get_option('admin_email');
        
        $full_message = $message . "\n\n";
        $full_message .= "Event: RESET\n";
        $full_message .= "Time: " . current_time('mysql') . "\n";
        
        if (!empty($data)) {
            $full_message .= "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        
        wp_mail(
            $admin_email,
            '[RESET Ticketing] ' . $subject,
            $full_message
        );
        
        // Log the notification
        if (class_exists('ResetDatabase')) {
            ResetDatabase::getInstance()->log_email(array(
                'email_type' => 'admin_notification',
                'recipient_email' => $admin_email,
                'subject' => $subject,
                'status' => 'sent'
            ));
        }
    }
    
    /**
     * Get allowed HTML for wp_kses
     */
    public function get_allowed_html(): array {
        return array(
            'div' => array(
                'class' => array(),
                'id' => array()
            ),
            'span' => array(
                'class' => array()
            ),
            'p' => array(
                'class' => array()
            ),
            'strong' => array(),
            'em' => array(),
            'br' => array(),
            'a' => array(
                'href' => array(),
                'target' => array(),
                'class' => array()
            )
        );
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt_data(string $data): string {
        if (function_exists('openssl_encrypt')) {
            $key = wp_salt('auth');
            $iv = openssl_random_pseudo_bytes(16);
            $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
            return base64_encode($iv . $encrypted);
        }
        
        // Fallback to base64 (not secure, but better than nothing)
        return base64_encode($data);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt_data(string $encrypted_data): string {
        if (function_exists('openssl_decrypt')) {
            $key = wp_salt('auth');
            $data = base64_decode($encrypted_data);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        }
        
        // Fallback from base64
        return base64_decode($encrypted_data);
    }
    
    /**
     * Generate secure hash
     */
    public function generate_hash(string $data): string {
        return hash('sha256', $data . wp_salt('nonce'));
    }
    
    /**
     * Verify hash
     */
    public function verify_hash(string $data, string $hash): bool {
        return hash_equals($this->generate_hash($data), $hash);
    }
    
    /**
     * Get plugin version
     */
    public function get_version(): string {
        return RESET_PLUGIN_VERSION;
    }
    
    /**
     * Check if plugin is properly configured
     */
    public function is_configured(): bool {
        // Check if tables exist
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'reset_tokens',
            $wpdb->prefix . 'reset_purchases',
            $wpdb->prefix . 'reset_email_logs'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get configuration status
     */
    public function get_configuration_status(): array {
        return array(
            'plugin_version' => $this->get_version(),
            'tables_exist' => $this->is_configured(),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'event_date' => RESET_EVENT_DATE,
            'capacity' => RESET_TARGET_CAPACITY,
            'max_capacity' => RESET_MAX_CAPACITY
        );
    }
    
    /**
     * Generate PDF ticket for a purchase
     */
    public function generate_pdf_ticket(array $purchase): string {
        $event_details = $this->get_event_details();
        
        // Get invitation keys
        $invitation_tokens = array();
        if (class_exists('ResetTokens')) {
            $tokens = ResetTokens::getInstance();
            $invitation_tokens = $tokens->get_invitation_tokens_by_parent_id((int)$purchase['token_id']);
        }
        
        // Create PDF content
        $pdf_content = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESET 2025 - E-Ticket</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #667eea;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .ticket-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }
        
        .ticket-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        
        .ticket-header p {
            margin: 0;
            font-size: 1.2em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .ticket-body {
            padding: 40px;
        }
        
        .ticket-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .ticket-left {
            flex: 1;
            padding-right: 30px;
        }
        
        .ticket-right {
            flex: 0 0 250px;
            text-align: center;
        }
        
        .ticket-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 500;
        }
        
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #fff;
            border: 2px dashed #667eea;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .qr-section h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 1.2em;
        }
        
        .qr-code img {
            max-width: 200px;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .qr-instructions {
            margin-top: 15px;
            color: #666;
            font-size: 0.9em;
        }
        
        .tokens-section {
            background: #e8f4f8;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .tokens-section h3 {
            margin: 0 0 15px 0;
            color: #0c5460;
            font-size: 1.2em;
        }
        
        .tokens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .token-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-family: "Courier New", monospace;
            font-weight: bold;
            font-size: 0.9em;
            border: 1px solid #b8daff;
        }
        
        .important-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .important-info h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 1.1em;
        }
        
        .important-info ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .important-info li {
            margin-bottom: 5px;
            color: #856404;
        }
        
        .ticket-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
        }
        
        .ticket-footer p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .ticket-serial {
            position: absolute;
            top: 10px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-family: "Courier New", monospace;
        }
        
        @media screen {
            body {
                padding: 20px;
                background: #f5f5f5;
            }
            
            .ticket-container {
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border: 2px solid #667eea;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <div class="ticket-serial">TKT-' . $purchase['id'] . '</div>
            <h1>RESET 2025</h1>
            <p>' . esc_html($event_details['description']) . '</p>
        </div>
        
        <div class="ticket-body">
            <div class="ticket-row">
                <div class="ticket-left">
                    <div class="ticket-details">
                        <div class="detail-item">
                            <span class="detail-label">Event:</span>
                            <span class="detail-value">RESET 2025</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">' . date('F j, Y', strtotime($event_details['date'])) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">10:00 AM - 11:00 PM</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Venue:</span>
                            <span class="detail-value">' . esc_html($event_details['venue']) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ticket Holder:</span>
                            <span class="detail-value">' . esc_html($purchase['purchaser_name']) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">' . esc_html($purchase['purchaser_email']) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ticket Type:</span>
                            <span class="detail-value">' . esc_html($purchase['ticket_type']) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount Paid:</span>
                            <span class="detail-value">Rs. ' . number_format($purchase['ticket_price'], 2) . '</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Reference:</span>
                            <span class="detail-value">' . esc_html($purchase['payment_reference']) . '</span>
                        </div>
                    </div>
                </div>
                
                <div class="ticket-right">
                    <div class="qr-section">
                        <h3>🎫 Your Digital Ticket</h3>
                        <div class="qr-code">
                            <div style="width: 200px; height: 150px; background: #f8f9fa; border: 2px solid #667eea; display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 8px; flex-direction: column; text-align: center;">
                                <div style="font-size: 48px; margin-bottom: 10px;">🎫</div>
                                <span style="color: #667eea; font-weight: bold;">Digital Ticket</span>
                            </div>
                        </div>
                        <div class="qr-instructions">
                            <strong>Present this ticket at the event entrance</strong><br>
                            <small>Show your email confirmation or this printout!</small>
                        </div>
                    </div>
                </div>
            </div>';
            
        // Add invitation keys if available
        if (!empty($invitation_tokens)) {
            $pdf_content .= '
            <div class="tokens-section">
                <h3>Your 5 Invitation Keys</h3>
                <p>Share these keys with your friends so they can join the event too!</p>
                <div class="tokens-grid">';
                
            foreach ($invitation_tokens as $token) {
                $pdf_content .= '<div class="token-item">' . esc_html($token['token_code']) . '</div>';
            }
            
            $pdf_content .= '</div>
                <p><strong>Important:</strong> Each keys allows one person to book a ticket. Share them wisely!</p>
            </div>';
        }
        
        $pdf_content .= '
            <div class="important-info">
                <h4>Important Reminders</h4>
                <ul>
                    <li>Please arrive at the venue 30 minutes before the event</li>
                    <li>Keep your ticket safe - lost tickets cannot be replaced</li>
                    <li>Entry is subject to capacity limits</li>
                </ul>
            </div>
        </div>
        
        <div class="ticket-footer">
            <p>Generated on ' . date('F j, Y \a\t g:i A') . ' | For support, contact: support@nooballiance.lk</p>
        </div>
    </div>
    

</body>
</html>';
        
        return $pdf_content;
    }
    
    /**
     * Get available token types
     */
    public function get_token_types(): array {
        return array(
            'normal' => array(
                'name' => 'Normal Key',
                'prefix' => 'NOR',
                'description' => 'Standard access keys for general attendees'
            ),
            'free_ticket' => array(
                'name' => 'Free Ticket Key',
                'prefix' => 'FTK',
                'description' => 'Free ticket access keys'
            ),
            'polo_ordered' => array(
                'name' => 'Polo Ordered Key',
                'prefix' => 'PLO',
                'description' => 'Keys for attendees who pre-ordered polo shirts'
            ),
            'sponsor' => array(
                'name' => 'Sponsor Key',
                'prefix' => 'SPO',
                'description' => 'Special access keys for sponsors'
            ),
            'invitation' => array(
                'name' => 'Invitation Key',
                'prefix' => 'INV',
                'description' => 'Generated invitation keys (auto-generated after purchase)'
            )
        );
    }
    
    /**
     * Get logo URL with fallback system (for web pages - uses plugin assets)
     * 
     * @param string $type Logo type ('black' or 'white')
     * @param string $size Logo size ('400' or other sizes)
     * @return array Array containing logo URL and fallback HTML
     */
    public function get_logo_with_fallback(string $type = 'black', string $size = '400'): array {
        $logo_filename = "logo-with-text-{$type}-{$size}.png";
        
        // Plugin assets URL (for web pages)
        $logo_url = RESET_PLUGIN_URL . 'assets/images/' . $logo_filename;
        
        // Generate fallback HTML based on logo type
        $fallback_color = ($type === 'white') ? '#ffffff' : '#f9c613';
        $fallback_html = "<h1 style='color: {$fallback_color}; font-size: 32px; font-weight: bold; margin: 0;'>RESET 2025</h1><p style='color: " . (($type === 'white') ? '#cccccc' : '#666') . "; font-size: 18px; margin: 10px 0 0 0;'>Reunion of Sri Lankan Esports</p>";
        
        return array(
            'url' => $logo_url,
            'fallback' => $fallback_html,
            'source' => 'plugin_assets'
        );
    }
    
    /**
     * Generate complete logo HTML with fallback
     * 
     * @param string $type Logo type ('black' or 'white')
     * @param string $size Logo size ('400' or other sizes)
     * @param string $css_class CSS class for the image
     * @param string $max_width Maximum width for the logo
     * @return string Complete HTML for logo with fallback
     */
    public function get_logo_html(string $type = 'black', string $size = '400', string $css_class = '', string $max_width = '300px'): string {
        $logo_data = $this->get_logo_with_fallback($type, $size);
        
        $class_attr = !empty($css_class) ? ' class="' . esc_attr($css_class) . '"' : '';
        $style_attr = !empty($max_width) ? ' style="max-width: ' . esc_attr($max_width) . '; height: auto; transition: transform 0.3s ease;"' : '';
        
        return sprintf(
            '<img src="%s" alt="RESET 2025 Logo"%s%s onerror="this.parentElement.innerHTML=\'%s\'">',
            esc_url($logo_data['url']),
            $class_attr,
            $style_attr,
            esc_js($logo_data['fallback'])
        );
    }
    
    /**
     * Get logo URL specifically for emails (uses site URL for public access)
     * 
     * @param string $type Logo type ('black' or 'white')
     * @param string $size Logo size ('400' or other sizes)
     * @return array Array containing publicly accessible logo URL and fallback HTML
     */
    public function get_email_logo_data(string $type = 'white', string $size = '400'): array {
        $logo_filename = "logo-with-text-{$type}-{$size}.png";
        $logo_url = site_url() . '/wp-content/uploads/2025/07/' . $logo_filename;
        
        // Generate fallback HTML for emails
        $fallback_color = ($type === 'white') ? '#ffffff' : '#f9c613';
        $fallback_text_color = ($type === 'white') ? '#cccccc' : '#666666';
        $fallback_html = '<h1 style="color: ' . $fallback_color . '; font-size: 32px; font-weight: bold; margin: 0;">RESET 2025</h1><p style="color: ' . $fallback_text_color . '; font-size: 18px; margin: 10px 0 0 0;">Sri Lanka\'s Premier Esports Event</p>';
        
        return array(
            'url' => $logo_url,
            'fallback' => $fallback_html,
            'source' => 'email_public_url'
        );
    }
    
    /**
     * Check if token type is a master type (can generate invitations)
     */
    public function is_master_token_type(string $type): bool {
        return in_array($type, array('normal', 'free_ticket', 'polo_ordered', 'sponsor', 'invitation'));
    }
} 