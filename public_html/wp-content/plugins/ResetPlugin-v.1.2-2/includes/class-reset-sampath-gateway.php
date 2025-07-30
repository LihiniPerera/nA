<?php

/**
 * RESET Sampath Bank Payment Gateway Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class ResetSampathGateway {
    
    private static $instance = null;
    private $gateway_config;
    private $gateway_settings;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Clear singleton instance (for debugging/testing)
     */
    public static function clearInstance() {
        self::$instance = null;
    }
    
    private function __construct() {
        $this->init_gateway_config();
    }
    
    /**
     * Initialize gateway configuration
     */
    private function init_gateway_config() {
        // Check if Sampath gateway is available
        if (!class_exists('WC_Sampath')) {
            return;
        }
        
        // Get gateway instance to access settings
        $gateway = new WC_Sampath();
        $this->gateway_settings = $gateway->settings;
        
        // Initialize gateway config if settings are available
        if ($this->is_gateway_configured()) {
            $this->init_gateway_client();
        }
    }
    
    /**
     * Check if gateway is properly configured
     */
    public function is_gateway_configured(): bool {
        return !empty($this->gateway_settings) &&
               !empty($this->gateway_settings['client_id']) &&
               !empty($this->gateway_settings['customer_id']) &&
               !empty($this->gateway_settings['hmac_secret']) &&
               !empty($this->gateway_settings['auth_token']) &&
               !empty($this->gateway_settings['pg_domain']);
    }
    
    /**
     * Initialize gateway client
     */
    private function init_gateway_client() {
        if (!class_exists('GatewayConfig')) {
            $plugin_path = plugin_dir_path(dirname(__FILE__)) . '../paycorp_sampath_ipg/';
            require_once $plugin_path . 'classes/GatewayConfig.php';
        }
        
        $config = array(
            'end_point' => $this->gateway_settings['pg_domain'],
            'auth_token' => $this->gateway_settings['auth_token'],
            'hmac_secret' => $this->gateway_settings['hmac_secret'],
            'client_id' => $this->gateway_settings['client_id']
        );
        
        $this->gateway_config = new GatewayConfig(
            plugin_dir_path(dirname(__FILE__)) . '../paycorp_sampath_ipg/',
            $config
        );
    }
    
    /**
     * Generate payment URL for RESET purchase
     */
    public function generate_payment_url(int $purchase_id, array $purchase_data): string {
        if (!$this->is_gateway_configured()) {
            return site_url('/reset/payment-error?error=config');
        }
        
        if (!$this->gateway_config) {
            return site_url('/reset/payment-error?error=init');
        }
        
        try {
            // Prepare payment data
            $payment_amount = $purchase_data['ticket_price'] * 100; // Convert to cents
            $currency_code = $this->gateway_settings['currency_code'] ?? 'LKR';
            
            // Success and return URLs for the payment gateway
            $return_url = site_url('/reset/payment-return');
            
            $payment_info = array(
                'extra_data' => array(
                    'purchase_id' => $purchase_id,
                    'token_id' => $purchase_data['token_id'],
                    'purchaser_email' => $purchase_data['purchaser_email'],
                    'ticket_type' => $purchase_data['ticket_type'],
                    'reset_payment' => 'true' // Identifier for RESET payments
                ),
                'transaction_data' => array(
                    'total_amount' => 0,
                    'service_fee' => 0,
                    'payment_amount' => $payment_amount,
                    'currency_code' => $currency_code
                ),
                'config_redirect' => array(
                    'url' => $return_url,
                    'method' => 'POST'
                ),
                'client_reference' => $purchase_data['payment_reference']
            );
            
            // Initialize payment and get payment URL
            $payment_url = $this->gateway_config->initialize($payment_info);
            
            return $payment_url;
            
        } catch (Exception $e) {
            return site_url('/reset/payment-error?error=generation&msg=' . urlencode($e->getMessage()));
        }
    }
    
    /**
     * Handle payment callback from Sampath gateway
     */
    public function handle_payment_callback(array $callback_data): array {
        if (!$this->is_gateway_configured() || !$this->gateway_config) {
            return array(
                'success' => false,
                'message' => 'Payment gateway not configured'
            );
        }
        
        try {
            // Complete payment with gateway
            $complete_response = $this->gateway_config->completePayment($callback_data);
            
            if (empty($complete_response->error)) {
                $response_data = $complete_response->responseData;
                $response_code = $response_data->responseCode;
                $success_code = $this->gateway_settings['sucess_responce_code'] ?? '00';
                
                if ($response_code === $success_code) {
                    // Payment successful
                    $payment_reference = $callback_data['clientRef'];
                    
                    return array(
                        'success' => true,
                        'payment_reference' => $payment_reference,
                        'transaction_id' => $response_data->txnReference,
                        'response_code' => $response_code,
                        'response_text' => $response_data->responseText,
                        'settlement_date' => $response_data->settlementDate,
                        'card_info' => array(
                            'holder_name' => $response_data->creditCard->holderName ?? '',
                            'card_number' => $response_data->creditCard->number ?? '',
                            'card_type' => $response_data->creditCard->type ?? ''
                        )
                    );
                } else {
                    // Payment failed
                    return array(
                        'success' => false,
                        'payment_reference' => $callback_data['clientRef'],
                        'response_code' => $response_code,
                        'response_text' => $response_data->responseText,
                        'message' => 'Payment failed: ' . $response_data->responseText
                    );
                }
            } else {
                // Gateway error
                return array(
                    'success' => false,
                    'message' => 'Gateway error: ' . $complete_response->error
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get gateway status for admin dashboard
     */
    public function get_gateway_status(): array {
        return array(
            'configured' => $this->is_gateway_configured(),
            'settings' => array(
                'client_id' => !empty($this->gateway_settings['client_id']) ? 'SET' : 'NOT SET',
                'customer_id' => !empty($this->gateway_settings['customer_id']) ? 'SET' : 'NOT SET',
                'hmac_secret' => !empty($this->gateway_settings['hmac_secret']) ? 'SET' : 'NOT SET',
                'auth_token' => !empty($this->gateway_settings['auth_token']) ? 'SET' : 'NOT SET',
                'pg_domain' => $this->gateway_settings['pg_domain'] ?? 'NOT SET',
                'currency_code' => $this->gateway_settings['currency_code'] ?? 'NOT SET'
            )
        );
    }
    
    /**
     * Get gateway configuration for debugging
     */
    public function get_gateway_config(): array {
        return array(
            'configured' => $this->is_gateway_configured(),
            'callback_url' => site_url('/reset/payment-return'),
            'old_callback_url' => site_url('/reset/payment-callback'),
            'gateway_config_exists' => $this->gateway_config !== null,
            'settings' => $this->gateway_settings
        );
    }
} 