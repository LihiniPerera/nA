<?php
if (!defined('ABSPATH')) {
    exit;
}

class ResetPayments {
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
    
    public function process_booking(array $booking_data): array {
        try {
            // Validate booking data
            $validation = $this->validate_booking_data($booking_data);
            if (!$validation['valid']) {
                return $validation;
            }
            
            // Validate keys
            $token_validation = ResetTokens::getInstance()->validate_token($booking_data['token_code']);
            if (!$token_validation['valid']) {
                return $token_validation;
            }
            
            $token = $token_validation['token'];
            
            // Check if there's already a pending purchase for this keys
            $existing_purchase = $this->db->get_purchase_by_token_id((int)$token['id']);
            if ($existing_purchase && $existing_purchase['payment_status'] === 'pending') {
                return array(
                    'success' => false,
                    'message' => __('There is already a pending payment for this keys. Please complete or cancel the existing payment first.', 'reset-ticketing')
                );
            }
            
            // Get ticket price
            $ticket_price = $this->core->get_current_ticket_price($booking_data['ticket_type']);
            
            if ($ticket_price <= 0) {
                return array(
                    'success' => false,
                    'message' => __('Invalid ticket type selected.', 'reset-ticketing')
                );
            }
            
            // Create purchase record
            $purchase_data = array(
                'token_id' => (int)$token['id'],
                'purchaser_name' => $booking_data['name'],
                'purchaser_email' => $booking_data['email'],
                'purchaser_phone' => $this->core->sanitize_phone($booking_data['phone']),
                'ticket_type' => $booking_data['ticket_type'],
                'ticket_price' => $ticket_price,
                'payment_reference' => $this->core->generate_payment_reference($token['token_code']),
                'payment_status' => 'pending'
            );
            
            $purchase_id = $this->db->create_purchase($purchase_data);
            
            if (!$purchase_id) {
                return array(
                    'success' => false,
                    'message' => __('Failed to create purchase record. Please try again.', 'reset-ticketing')
                );
            }
            
            // Check if we're in local development environment
            if ($this->is_local_development()) {
                // Simulate successful payment in local environment
                $success_result = $this->simulate_local_payment($purchase_id, $purchase_data);
                
                $result = array(
                    'success' => true,
                    'purchase_id' => $purchase_id,
                    'payment_url' => site_url('/reset/booking-success?ref=' . $purchase_data['payment_reference'] . '&local=1'),
                    'message' => __('Booking created successfully. [LOCAL MODE: Payment bypassed]', 'reset-ticketing'),
                    'local_mode' => true
                );
            } else {
                // Generate payment URL (Sampath Bank integration)
                // Note: Key will be marked as used only after successful payment
                $payment_url = $this->generate_payment_url($purchase_id, $purchase_data);
                
                $result = array(
                    'success' => true,
                    'purchase_id' => $purchase_id,
                    'payment_url' => $payment_url,
                    'message' => __('Booking created successfully. You will be redirected to payment.', 'reset-ticketing')
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'An error occurred while processing your booking: ' . $e->getMessage()
            );
        }
    }
    
    private function validate_booking_data(array $booking_data): array {
        if (empty($booking_data['name'])) {
            return array(
                'valid' => false,
                'message' => __('Please enter your name.', 'reset-ticketing')
            );
        }
        
        if (!$this->core->validate_email($booking_data['email'])) {
            return array(
                'valid' => false,
                'message' => __('Please enter a valid email address.', 'reset-ticketing')
            );
        }
        
        if (!$this->core->validate_phone($booking_data['phone'])) {
            return array(
                'valid' => false,
                'message' => __('Please enter a valid phone number.', 'reset-ticketing')
            );
        }
        
        if (empty($booking_data['ticket_type'])) {
            return array(
                'valid' => false,
                'message' => __('Please select a ticket type.', 'reset-ticketing')
            );
        }
        
        return array('valid' => true);
    }
    
    private function generate_payment_url(int $purchase_id, array $purchase_data): string {
        // Use Sampath Bank payment gateway integration
        if (class_exists('ResetSampathGateway')) {
            // Clear singleton cache to ensure fresh instance
            ResetSampathGateway::clearInstance();
            $gateway = ResetSampathGateway::getInstance();
            return $gateway->generate_payment_url($purchase_id, $purchase_data);
        } else {
            return site_url('/reset/payment-error?error=gateway_missing');
        }
    }
    
    public function handle_payment_callback(array $payment_data): array {
        // Handle payment gateway callback
        $payment_reference = sanitize_text_field($payment_data['payment_reference'] ?? '');
        
        if (empty($payment_reference)) {
            return array(
                'success' => false,
                'message' => 'Invalid payment reference'
            );
        }
        
        $purchase = $this->db->get_purchase_by_payment_reference($payment_reference);
        
        if (!$purchase) {
            return array(
                'success' => false,
                'message' => 'Purchase not found'
            );
        }
        
        // Update purchase status
        $update_data = array(
            'payment_status' => 'completed',
            'sampath_transaction_id' => $payment_data['transaction_id'] ?? ''
        );
        
        $this->db->update_purchase((int)$purchase['id'], $update_data);
        
        // Mark key as used (only after successful payment)
        $token_used = ResetTokens::getInstance()->use_token((int)$purchase['token_id'], array(
            'name' => $purchase['purchaser_name'],
            'email' => $purchase['purchaser_email'],
            'phone' => $purchase['purchaser_phone']
        ));
        
        // Generate invitation keys
        $invitation_tokens = ResetTokens::getInstance()->generate_invitation_tokens((int)$purchase['token_id']);
        
        // Mark invitation keys as generated
        $this->db->update_purchase((int)$purchase['id'], array('invitation_tokens_generated' => 1));
        

        
        // Send confirmation email
        ResetEmails::getInstance()->send_ticket_confirmation($purchase, $invitation_tokens);
        
        return array(
            'success' => true,
            'purchase' => $purchase,
            'invitation_tokens' => $invitation_tokens
        );
    }
    

    
    public function get_payment_status(string $payment_reference): array {
        $purchase = $this->db->get_purchase_by_payment_reference($payment_reference);
        
        if (!$purchase) {
            return array(
                'found' => false,
                'message' => 'Purchase not found'
            );
        }
        
        return array(
            'found' => true,
            'purchase' => $purchase,
            'status' => $purchase['payment_status']
        );
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
     * Simulate successful payment for local development
     */
    private function simulate_local_payment(int $purchase_id, array $purchase_data): array {
        // Update purchase status to completed
        $update_data = array(
            'payment_status' => 'completed',
            'sampath_transaction_id' => 'LOCAL_DEV_' . time() . '_' . $purchase_id
        );
        
        $this->db->update_purchase($purchase_id, $update_data);
        
        // Get updated purchase data
        $purchase = $this->db->get_purchase_by_id($purchase_id);
        
        // Mark key as used (only after successful payment)
        $token_used = ResetTokens::getInstance()->use_token((int)$purchase['token_id'], array(
            'name' => $purchase['purchaser_name'],
            'email' => $purchase['purchaser_email'],
            'phone' => $purchase['purchaser_phone']
        ));
        
        // Generate invitation keys
        $invitation_tokens = ResetTokens::getInstance()->generate_invitation_tokens((int)$purchase['token_id']);
        
        // Mark invitation keys as generated
        $this->db->update_purchase($purchase_id, array('invitation_tokens_generated' => 1));
        

        
        // Send confirmation email
        $email_sent = ResetEmails::getInstance()->send_ticket_confirmation($purchase, $invitation_tokens);
        
        return array(
            'success' => true,
            'purchase' => $purchase,
            'invitation_tokens' => $invitation_tokens,
            'local_mode' => true
        );
    }
} 