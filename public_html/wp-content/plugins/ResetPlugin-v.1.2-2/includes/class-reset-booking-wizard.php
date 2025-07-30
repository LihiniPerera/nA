<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking wizard controller for RESET ticketing system
 */
class ResetBookingWizard {
    
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
     * Capacity instance
     */
    private $capacity;
    
    /**
     * Addons instance
     */
    private $addons;
    
    /**
     * Step definitions
     */
    private $steps = array(
        1 => 'personal-info',
        2 => 'ticket-selection',
        3 => 'addons',
        4 => 'summary'
    );
    
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
        $this->capacity = ResetCapacity::getInstance();
        $this->addons = ResetAddons::getInstance();
        
        // Define step names mapping
        $this->steps = array(
            1 => 'personal_info',
            2 => 'ticket_selection',
            3 => 'addons',
            4 => 'summary'
        );
        
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * Initialize wizard session with token data
     */
    public function initialize_session(string $token_code): array {
        // Validate token
        $token_validation = ResetTokens::getInstance()->validate_token($token_code);
        if (!$token_validation['valid']) {
            return $token_validation;
        }

        $token = $token_validation['token'];
        // FIXED: Use token_type from database instead of parsing prefix
        $token_type = $token['token_type'] ?? 'normal';
        
        // Initialize session data
        $_SESSION['reset_booking_wizard'] = array(
            'token_code' => $token_code,
            'token_id' => $token['id'],
            'token_type' => $token_type,
            'current_step' => 1,
            'step_data' => array(
                'personal_info' => array(),
                'ticket_selection' => array(),
                'addons' => array(),
                'summary' => array()
            ),
            'started_at' => time()
        );
        
        return array(
            'success' => true,
            'token_type' => $token_type,
            'message' => __('Booking wizard initialized successfully.', 'reset-ticketing')
        );
    }
    
    /**
     * Get current wizard session data
     */
    public function get_session_data(): array {
        return isset($_SESSION['reset_booking_wizard']) ? $_SESSION['reset_booking_wizard'] : array();
    }
    
    /**
     * Update session data for a specific step
     */
    public function update_step_data(int $step, array $data): bool {
        if (!isset($_SESSION['reset_booking_wizard'])) {
            return false;
        }
        
        $step_name = $this->get_step_name($step);
        if (!$step_name) {
            return false;
        }
        
        $_SESSION['reset_booking_wizard']['step_data'][$step_name] = $data;
        $_SESSION['reset_booking_wizard']['current_step'] = $step;
        
        return true;
    }
    
    /**
     * Get step name from number
     */
    private function get_step_name(int $step): string {
        return isset($this->steps[$step]) ? $this->steps[$step] : '';
    }
    
    /**
     * Get step number from name
     */
    private function get_step_number(string $step_name): int {
        return array_search($step_name, $this->steps) ?: 0;
    }
    
    /**
     * Validate step data
     */
    public function validate_step(int $step, array $data): array {
        switch ($step) {
            case 1:
                return $this->validate_personal_info($data);
            case 2:
                return $this->validate_ticket_selection($data);
            case 3:
                return $this->validate_addons($data);
            case 4:
                return $this->validate_summary($data);
            default:
                return array(
                    'valid' => false,
                    'errors' => array(__('Invalid step.', 'reset-ticketing'))
                );
        }
    }
    
    /**
     * Validate personal info step
     */
    private function validate_personal_info(array $data): array {
        $errors = array();
        
        if (empty($data['name'])) {
            $errors[] = __('Name is required.', 'reset-ticketing');
        }
        
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = __('Valid email address is required.', 'reset-ticketing');
        }
        
        if (empty($data['phone'])) {
            $errors[] = __('Phone number is required.', 'reset-ticketing');
        } elseif (!$this->core->validate_phone($data['phone'])) {
            $errors[] = __('Valid Sri Lankan phone number is required.', 'reset-ticketing');
        }
        
        // Gaming name validation (optional field)
        if (!empty($data['gaming_name'])) {
            if (strlen($data['gaming_name']) > 50) {
                $errors[] = __('Gaming name must be 50 characters or less.', 'reset-ticketing');
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Validate ticket selection step
     */
    private function validate_ticket_selection(array $data): array {
        $session_data = $this->get_session_data();
        $token_type = $session_data['token_type'] ?? '';
        
        // Check if this is a free token
        $is_free_token = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));
        
        if ($is_free_token) {
            // Free tokens don't need to select a ticket, they automatically get a free ticket
            return array('valid' => true, 'errors' => array());
        }
        
        $errors = array();
        
        if (empty($data['ticket_type'])) {
            $errors[] = __('Please select a ticket type.', 'reset-ticketing');
        } else {
            // Validate ticket availability
            if (!$this->capacity->is_ticket_type_available($data['ticket_type'], $token_type)) {
                $errors[] = __('Selected ticket type is not currently available.', 'reset-ticketing');
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Validate addons step
     */
    private function validate_addons(array $data): array {
        $selected_addons = $data['selected_addons'] ?? array();
        
        if (empty($selected_addons)) {
            // No addons selected is valid
            return array('valid' => true, 'errors' => array());
        }
        
        return $this->addons->validate_addon_selection($selected_addons);
    }
    
    /**
     * Validate summary step
     */
    private function validate_summary(array $data): array {
        // Summary step validation - ensure all previous steps are completed
        $session_data = $this->get_session_data();
        $errors = array();
        
        // Validate personal info is complete
        $personal_info = $session_data['step_data']['personal_info'] ?? array();
        if (empty($personal_info['name']) || empty($personal_info['email']) || empty($personal_info['phone'])) {
            $errors[] = __('Personal information is incomplete.', 'reset-ticketing');
        }
        
        // Validate ticket selection if required (only for paying keys)
        $token_type = $session_data['token_type'] ?? '';
        $is_free_token = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));
        
        if (!$is_free_token) {
            $ticket_selection = $session_data['step_data']['ticket_selection'] ?? array();
            if (empty($ticket_selection['ticket_type'])) {
                $errors[] = __('Ticket selection is incomplete.', 'reset-ticketing');
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Get complete booking data from session
     */
    public function get_complete_booking_data(): array {
        $session_data = $this->get_session_data();
        
        if (empty($session_data)) {
            return array();
        }
        
        $personal_info = $session_data['step_data']['personal_info'] ?? array();
        $ticket_selection = $session_data['step_data']['ticket_selection'] ?? array();
        $addons_data = $session_data['step_data']['addons'] ?? array();
        
        // Calculate pricing
        $ticket_price = 0.00;
        $token_type = $session_data['token_type'] ?? '';
        $is_free_token = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));
        
        if (!$is_free_token && !empty($ticket_selection['ticket_type'])) {
            $ticket_price = $this->core->get_current_ticket_price($ticket_selection['ticket_type']);
        }
        
        $selected_addons = $addons_data['selected_addons'] ?? array();
        $addon_total = $this->addons->calculate_addon_total($selected_addons, $token_type);
        $total_amount = $ticket_price + $addon_total;
        
        return array(
            'token_code' => $session_data['token_code'],
            'token_id' => $session_data['token_id'],
            'token_type' => $token_type,
            'is_free_token' => $is_free_token,
            'personal_info' => $personal_info,
            'ticket_selection' => $ticket_selection,
            'selected_addons' => $selected_addons,
            'pricing' => array(
                'ticket_price' => $ticket_price,
                'addon_total' => $addon_total,
                'total_amount' => $total_amount,
                'is_free' => $total_amount == 0
            )
        );
    }
    
    /**
     * Clear wizard session
     */
    public function clear_session(): void {
        unset($_SESSION['reset_booking_wizard']);
    }
    
    /**
     * Get token type from token code prefix
     */
    private function get_token_type_from_code(string $token_code): string {
        $prefix = substr($token_code, 0, 3);
        
        $prefix_map = array(
            'NOR' => 'normal',
            'FTK' => 'free_ticket',
            'PLO' => 'polo_ordered',
            'SPO' => 'sponsor',
            'INV' => 'invitation'
        );
        
        return $prefix_map[$prefix] ?? 'normal';
    }
    
    /**
     * Check if wizard session is expired
     */
    public function is_session_expired(): bool {
        $session_data = $this->get_session_data();
        
        if (empty($session_data)) {
            return true;
        }
        
        $started_at = $session_data['started_at'] ?? 0;
        $expire_time = 30 * 60; // 30 minutes
        
        return (time() - $started_at) > $expire_time;
    }
    
    /**
     * Get step progress data for UI
     */
    public function get_step_progress(): array {
        $session_data = $this->get_session_data();
        $current_step = $session_data['current_step'] ?? 1;
        
        // Check if each step has valid data
        $step1_completed = $this->is_step_completed(1, $session_data);
        $step2_completed = $this->is_step_completed(2, $session_data);
        $step3_completed = $this->is_step_completed(3, $session_data);
        
        $steps = array(
            1 => array(
                'number' => 1,
                'name' => 'personal-info',
                'title' => __('Personal Info', 'reset-ticketing'),
                'completed' => $step1_completed && $current_step > 1,
                'current' => $current_step == 1,
                'skipped' => false
            ),
            2 => array(
                'number' => 2,
                'name' => 'ticket-selection',
                'title' => __('Ticket', 'reset-ticketing'),
                'completed' => $step2_completed && $current_step > 2,
                'current' => $current_step == 2,
                'skipped' => false
            ),
            3 => array(
                'number' => 3,
                'name' => 'addons',
                'title' => __('Add-ons', 'reset-ticketing'),
                'completed' => $step3_completed && $current_step > 3,
                'current' => $current_step == 3,
                'skipped' => false
            ),
            4 => array(
                'number' => 4,
                'name' => 'summary',
                'title' => __('Summary', 'reset-ticketing'),
                'completed' => false,
                'current' => $current_step == 4,
                'skipped' => false
            )
        );
        
        return array(
            'current_step' => $current_step,
            'total_steps' => 4,
            'steps' => $steps
        );
    }
    
    /**
     * Check if a step is completed (has valid data)
     */
    private function is_step_completed(int $step, array $session_data): bool {
        $step_data = $session_data['step_data'] ?? array();
        
        switch ($step) {
            case 1:
                // Personal info step
                $personal_info = $step_data['personal_info'] ?? array();
                return !empty($personal_info['name']) && 
                       !empty($personal_info['email']) && 
                       !empty($personal_info['phone']);
                       
            case 2:
                // Ticket selection step
                $token_type = $session_data['token_type'] ?? '';
                $is_free_token = in_array($token_type, array('free_ticket', 'polo_ordered', 'sponsor'));
                
                if ($is_free_token) {
                    // Free tokens automatically have this step completed
                    return true;
                } else {
                    // Regular tokens need to select a ticket
                    $ticket_selection = $step_data['ticket_selection'] ?? array();
                    return !empty($ticket_selection['ticket_type']);
                }
                
            case 3:
                // Addons step - always considered completed (selection is optional)
                return true;
                
            case 4:
                // Summary step - never marked as completed
                return false;
                
            default:
                return false;
        }
    }

    /**
     * Get next step number
     */
    public function get_next_step(int $current_step): int {
        // Always proceed to the next step sequentially
        return min($current_step + 1, 4);
    }
    
    /**
     * Get previous step number
     */
    public function get_previous_step(int $current_step): int {
        // Always go to the previous step sequentially
        return max($current_step - 1, 1);
    }
} 