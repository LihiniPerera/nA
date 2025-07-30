<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Addon management class for RESET ticketing system
 */
class ResetAddons {
    
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
     * Get all available addons for public display
     */
    public function get_available_addons(): array {
        $addons = $this->db->get_all_addons(true); // Only enabled addons
        
        $formatted_addons = array();
        foreach ($addons as $addon) {
            $formatted_addons[$addon['addon_key']] = array(
                'id' => $addon['id'],
                'key' => $addon['addon_key'],
                'name' => $addon['name'],
                'description' => $addon['description'],
                'price' => floatval($addon['price']),
                'drink_count' => intval($addon['drink_count'] ?? 0),
                'formatted_price' => $this->core->format_currency(floatval($addon['price'])),
                'sort_order' => intval($addon['sort_order'])
            );
        }
        
        // Sort by sort_order
        uasort($formatted_addons, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        
        return $formatted_addons;
    }
    
    /**
     * Validate addon selection
     */
    public function validate_addon_selection(array $selected_addon_keys): array {
        $validation_result = array(
            'valid' => true,
            'errors' => array(),
            'addons' => array(),
            'total' => 0.00
        );
        
        if (empty($selected_addon_keys)) {
            // No addons selected is valid
            return $validation_result;
        }
        
        // Check that only one addon is selected
        if (count($selected_addon_keys) > 1) {
            $validation_result['valid'] = false;
            $validation_result['errors'][] = __('Only one add-on can be selected at a time.', 'reset-ticketing');
            return $validation_result;
        }
        
        $available_addons = $this->get_available_addons();
        $total = 0.00;
        $valid_addons = array();
        
        foreach ($selected_addon_keys as $addon_key) {
            if (!isset($available_addons[$addon_key])) {
                $validation_result['valid'] = false;
                $validation_result['errors'][] = sprintf(__('Invalid addon: %s', 'reset-ticketing'), $addon_key);
                continue;
            }
            
            $addon = $available_addons[$addon_key];
            $valid_addons[] = $addon;
            $total += $addon['price'];
        }
        
        $validation_result['addons'] = $valid_addons;
        $validation_result['total'] = $total;
        
        return $validation_result;
    }
    
    /**
     * Calculate addon total from selection
     */
    public function calculate_addon_total(array $selected_addon_keys, string $token_type = ''): float {
        if (empty($selected_addon_keys)) {
            return 0.00;
        }
        
        $available_addons = $this->get_available_addons();
        $total = 0.00;
        
        foreach ($selected_addon_keys as $addon_key) {
            if (isset($available_addons[$addon_key])) {
                // For polo_ordered tokens, afterpart_package_0 is free
                if ($token_type === 'polo_ordered' && $addon_key === 'afterpart_package_0') {
                    continue; // Skip adding cost for free addon
                }
                $total += $available_addons[$addon_key]['price'];
            }
        }
        
        return $total;
    }
    
    /**
     * Get addon details by keys
     */
    public function get_addons_by_keys(array $addon_keys): array {
        $available_addons = $this->get_available_addons();
        $result = array();
        
        foreach ($addon_keys as $addon_key) {
            if (isset($available_addons[$addon_key])) {
                $result[] = $available_addons[$addon_key];
            }
        }
        
        return $result;
    }
    
    /**
     * Save addons to purchase
     */
    public function save_addons_to_purchase(int $purchase_id, array $selected_addon_keys, string $token_type = ''): bool {
        if (empty($selected_addon_keys)) {
            return true; // No addons to save
        }
        
        $available_addons = $this->get_available_addons();
        $success = true;
        
        foreach ($selected_addon_keys as $addon_key) {
            if (!isset($available_addons[$addon_key])) {
                continue;
            }
            
            $addon = $available_addons[$addon_key];
            
            // Determine the actual price to save
            $addon_price = $addon['price'];
            
            // For polo_ordered tokens, afterpart_package_0 is free
            if ($token_type === 'polo_ordered' && $addon_key === 'afterpart_package_0') {
                $addon_price = 0.00;
            }
            
            $result = $this->db->add_addon_to_purchase($purchase_id, $addon['id'], $addon_price);
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get default selected addons (first addon by sort_order)
     */
    public function get_default_addons(string $token_type = ''): array {
        $available_addons = $this->get_available_addons();
        
        if (empty($available_addons)) {
            return array();
        }
        
        // For polo_ordered tokens, always include afterpart_package_0 as free
        if ($token_type === 'polo_ordered') {
            return array('afterpart_package_0');
        }
        
        // Get first addon by sort_order (already sorted in get_available_addons)
        $first_addon = reset($available_addons);
        return array($first_addon['key']);
    }
    
    /**
     * Format addons for display in summary
     */
    public function format_addons_for_summary(array $selected_addon_keys, string $token_type = ''): array {
        $addons = $this->get_addons_by_keys($selected_addon_keys);
        $formatted = array();
        
        // For polo_ordered users: if they selected a paid addon, hide the free addon
        $is_polo_ordered = ($token_type === 'polo_ordered');
        $has_paid_addon = false;
        
        if ($is_polo_ordered) {
            // Check if any paid addons are selected
            foreach ($selected_addon_keys as $addon_key) {
                if ($addon_key !== 'afterpart_package_0') {
                    $has_paid_addon = true;
                    break;
                }
            }
        }
        
        foreach ($addons as $addon) {
            $is_free_addon = ($is_polo_ordered && $addon['key'] === 'afterpart_package_0');
            
            // Skip free addon if polo user selected a paid addon
            if ($is_free_addon && $has_paid_addon) {
                continue;
            }
            
            $formatted[] = array(
                'key' => $addon['key'],
                'name' => $addon['name'],
                'description' => $addon['description'],
                'price' => $addon['price'],
                'formatted_price' => $is_free_addon ? $this->core->format_currency(0) : $addon['formatted_price'],
                'is_free' => $is_free_addon
            );
        }
        
        return $formatted;
    }
    
    /**
     * Admin methods for addon management
     */
    
    /**
     * Create new addon (admin)
     */
    public function create_addon(array $addon_data): array {
        // Validate addon data
        $validation = $this->validate_addon_data($addon_data);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Prepare sanitized data for database
        $sanitized_data = array(
            'addon_key' => sanitize_text_field($addon_data['addon_key']),
            'name' => sanitize_text_field($addon_data['name']),
            'description' => sanitize_textarea_field($addon_data['description'] ?? ''),
            'price' => floatval($addon_data['price']),
            'drink_count' => intval($addon_data['drink_count'] ?? 0),
            'sort_order' => intval($addon_data['sort_order'] ?? 0),
            'is_enabled' => isset($addon_data['is_enabled']) ? 1 : 0
        );
        
        // Create addon
        $addon_id = $this->db->create_addon($sanitized_data);
        
        if ($addon_id) {
            return array(
                'success' => true,
                'addon_id' => $addon_id,
                'message' => __('Add-on created successfully.', 'reset-ticketing')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to create add-on. Please try again.', 'reset-ticketing')
            );
        }
    }
    
    /**
     * Update addon (admin)
     */
    public function update_addon(int $addon_id, array $addon_data): array {
        // Validate addon data
        $validation = $this->validate_addon_data($addon_data, $addon_id);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Prepare sanitized data for database (exclude addon_key for updates)
        $sanitized_data = array(
            'name' => sanitize_text_field($addon_data['name']),
            'description' => sanitize_textarea_field($addon_data['description'] ?? ''),
            'price' => floatval($addon_data['price']),
            'drink_count' => intval($addon_data['drink_count'] ?? 0),
            'sort_order' => intval($addon_data['sort_order'] ?? 0),
            'is_enabled' => isset($addon_data['is_enabled']) ? 1 : 0
        );
        
        // Update addon
        $success = $this->db->update_addon($addon_id, $sanitized_data);
        
        if ($success) {
            return array(
                'success' => true,
                'message' => __('Add-on updated successfully.', 'reset-ticketing')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to update add-on. Please try again.', 'reset-ticketing')
            );
        }
    }
    
    /**
     * Delete addon (admin)
     */
    public function delete_addon(int $addon_id): array {
        // Check if addon exists
        $addon = $this->db->get_addon_by_id($addon_id);
        if (!$addon) {
            return array(
                'success' => false,
                'message' => __('Add-on not found.', 'reset-ticketing')
            );
        }
        
        $success = $this->db->delete_addon($addon_id);
        
        if ($success) {
            return array(
                'success' => true,
                'message' => __('Add-on deleted successfully.', 'reset-ticketing')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to delete add-on. Please try again.', 'reset-ticketing')
            );
        }
    }
    
    /**
     * Toggle addon enabled/disabled status (admin)
     */
    public function toggle_addon_status(int $addon_id): array {
        $addon = $this->db->get_addon_by_id($addon_id);
        
        if (!$addon) {
            return array(
                'success' => false,
                'message' => __('Addon not found.', 'reset-ticketing')
            );
        }
        
        $new_status = $addon['is_enabled'] ? 0 : 1;
        
        $success = $this->db->update_addon($addon_id, array('is_enabled' => $new_status));
        
        if ($success) {
            $status_text = $new_status ? __('enabled', 'reset-ticketing') : __('disabled', 'reset-ticketing');
            return array(
                'success' => true,
                'message' => sprintf(__('Addon %s successfully.', 'reset-ticketing'), $status_text)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to toggle addon status. Please try again.', 'reset-ticketing')
            );
        }
    }
    
    /**
     * Validate addon data
     */
    private function validate_addon_data(array $data, int $exclude_id = 0): array {
        $errors = array();
        
        // Sanitize input data
        $data = array_map('sanitize_text_field', $data);
        if (isset($data['description'])) {
            $data['description'] = sanitize_textarea_field($data['description']);
        }
        
        // Validate addon key
        if (empty($data['addon_key'])) {
            $errors[] = __('Add-on key is required.', 'reset-ticketing');
        } elseif (strlen($data['addon_key']) < 3) {
            $errors[] = __('Add-on key must be at least 3 characters long.', 'reset-ticketing');
        } elseif (strlen($data['addon_key']) > 50) {
            $errors[] = __('Add-on key cannot be longer than 50 characters.', 'reset-ticketing');
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['addon_key'])) {
            $errors[] = __('Add-on key must contain only lowercase letters, numbers, and underscores.', 'reset-ticketing');
        } elseif (preg_match('/^[0-9]/', $data['addon_key'])) {
            $errors[] = __('Add-on key cannot start with a number.', 'reset-ticketing');
        } else {
            // Check uniqueness
            $existing = $this->db->get_addon_by_key($data['addon_key']);
            if ($existing && intval($existing['id']) !== $exclude_id) {
                $errors[] = __('Add-on key already exists. Please choose a different key.', 'reset-ticketing');
            }
        }
        
        // Validate name
        if (empty($data['name'])) {
            $errors[] = __('Add-on name is required.', 'reset-ticketing');
        } elseif (strlen($data['name']) < 3) {
            $errors[] = __('Add-on name must be at least 3 characters long.', 'reset-ticketing');
        } elseif (strlen($data['name']) > 255) {
            $errors[] = __('Add-on name cannot be longer than 255 characters.', 'reset-ticketing');
        }
        
        // Validate description
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = __('Add-on description cannot be longer than 1000 characters.', 'reset-ticketing');
        }
        
        // Validate price
        if (!isset($data['price']) || $data['price'] === '') {
            $errors[] = __('Add-on price is required.', 'reset-ticketing');
        } elseif (!is_numeric($data['price'])) {
            $errors[] = __('Add-on price must be a valid number.', 'reset-ticketing');
        } elseif (floatval($data['price']) < 0) {
            $errors[] = __('Add-on price cannot be negative.', 'reset-ticketing');
        } elseif (floatval($data['price']) > 999999.99) {
            $errors[] = __('Add-on price cannot exceed Rs. 999,999.99.', 'reset-ticketing');
        }
        
        // Validate drink count
        if (isset($data['drink_count']) && $data['drink_count'] !== '') {
            if (!is_numeric($data['drink_count'])) {
                $errors[] = __('Drink count must be a valid number.', 'reset-ticketing');
            } elseif (intval($data['drink_count']) < 0) {
                $errors[] = __('Drink count cannot be negative.', 'reset-ticketing');
            } elseif (intval($data['drink_count']) > 99) {
                $errors[] = __('Drink count cannot exceed 99.', 'reset-ticketing');
            }
        }
        
        // Validate sort order
        if (isset($data['sort_order']) && $data['sort_order'] !== '') {
            if (!is_numeric($data['sort_order'])) {
                $errors[] = __('Sort order must be a valid number.', 'reset-ticketing');
            } elseif (intval($data['sort_order']) < 0) {
                $errors[] = __('Sort order cannot be negative.', 'reset-ticketing');
            } elseif (intval($data['sort_order']) > 9999) {
                $errors[] = __('Sort order cannot exceed 9999.', 'reset-ticketing');
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? '' : __('Please fix the validation errors above.', 'reset-ticketing')
        );
    }
    
    /**
     * Get addon statistics
     */
    public function get_addon_statistics(): array {
        $total_addons = count($this->db->get_all_addons());
        $enabled_addons = count($this->db->get_all_addons(true));
        
        return array(
            'total_addons' => $total_addons,
            'enabled_addons' => $enabled_addons,
            'disabled_addons' => $total_addons - $enabled_addons
        );
    }
} 