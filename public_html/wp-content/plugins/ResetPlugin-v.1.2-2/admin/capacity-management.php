<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Initialize required classes
$capacity_manager = ResetCapacity::getInstance();
$admin = ResetAdmin::getInstance();

// Handle form submissions - this page now uses admin-post.php handlers
$message = '';
$message_type = '';
$validation_errors = array();

// Handle URL parameters for success messages
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = 'Capacity settings updated successfully.';
    $message_type = 'success';
}

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $message = 'Capacity settings reset to defaults successfully.';
    $message_type = 'success';
}

// Handle success/error messages from form submissions
if (isset($_GET['success'])) {
    $message_type = 'success';
    switch ($_GET['success']) {
        case 'settings_updated':
            $message = 'Capacity settings updated successfully!';
            break;
        case 'thresholds_updated':
            $message = 'Ticket thresholds updated successfully!';
            break;
        case 'settings_reset':
            $message = 'Capacity settings reset to defaults successfully!';
            break;
        case 'database_recreated':
            $message = 'Database tables recreated successfully!';
            break;
        default:
            $message = 'Settings updated successfully!';
            break;
    }
}

if (isset($_GET['error'])) {
    $message_type = 'error';
    $message = urldecode($_GET['error']);
}

// Handle migration-specific messages
if (isset($_GET['success'])) {
    $message_type = 'success';
    switch ($_GET['success']) {
        case 'migration_completed':
            $message = 'Database migration completed successfully!';
            break;
        case 'tables_recreated':
            $message = 'Database tables recreated successfully! All data has been reset.';
            break;

        // ... existing cases remain the same
    }
}

// Get current capacity settings and statistics
$current_settings = $capacity_manager->get_capacity_settings();
$capacity_stats = $capacity_manager->get_capacity_statistics();
$environment_info = $capacity_manager->get_environment_info();

// Get migration status
require_once(RESET_PLUGIN_PATH . 'includes/class-reset-migration.php');
$migration = ResetMigration::getInstance();
$migration_status = $migration->get_migration_status();
$db_validation = $migration->validate_database();

// Debug information has been removed for cleaner interface

// Prepare form data (use posted values if validation failed, otherwise use current settings)
$form_data = array(
    'target_capacity' => $_POST['target_capacity'] ?? $current_settings['main_capacity']['target_capacity'],
    'max_capacity' => $_POST['max_capacity'] ?? $current_settings['main_capacity']['max_capacity'],
    'alert_threshold' => $_POST['alert_threshold'] ?? $current_settings['main_capacity']['alert_threshold'],
    'early_bird_threshold' => $_POST['early_bird_threshold'] ?? $current_settings['ticket_thresholds']['early_bird'],
    'late_bird_threshold' => $_POST['late_bird_threshold'] ?? $current_settings['ticket_thresholds']['late_bird'],
    'very_late_bird_threshold' => $_POST['very_late_bird_threshold'] ?? $current_settings['ticket_thresholds']['very_late_bird']
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Capacity Management</h1>
    <hr class="wp-header-end">
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($validation_errors)): ?>
        <div class="notice notice-error">
            <p><strong>Please fix the following errors:</strong></p>
            <ul>
                <?php foreach ($validation_errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Environment Info -->
    <div class="capacity-environment-info">
        <div class="environment-badge <?php echo $environment_info['environment'] === 'local' ? 'local' : 'production'; ?>">
            <?php if ($environment_info['environment'] === 'local'): ?>
                🧪 LOCAL DEVELOPMENT MODE
            <?php else: ?>
                🚀 PRODUCTION MODE
            <?php endif; ?>
        </div>
        <div class="environment-details">
            <p><strong>Environment:</strong> <?php echo esc_html($environment_info['environment']); ?></p>
            <p><strong>Description:</strong> <?php echo esc_html($environment_info['description']); ?></p>
        </div>
    </div>
    
    <!-- Capacity Statistics Dashboard -->
    <div class="capacity-stats-dashboard">
        <h2>Current Capacity Status</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Current Attendees</h3>
                <div class="stat-number"><?php echo $capacity_stats['current_attendees']; ?></div>
                <div class="stat-label">Total registered</div>
            </div>
            <div class="stat-card">
                <h3>Target Capacity</h3>
                <div class="stat-number"><?php echo $capacity_stats['target_capacity']; ?></div>
                <div class="stat-label">Target limit</div>
            </div>
            <div class="stat-card">
                <h3>Maximum Capacity</h3>
                <div class="stat-number"><?php echo $capacity_stats['max_capacity']; ?></div>
                <div class="stat-label">Hard limit</div>
            </div>
            <div class="stat-card">
                <h3>Utilization</h3>
                <div class="stat-number"><?php echo $capacity_stats['percentage_used']; ?>%</div>
                <div class="stat-label">Capacity used</div>
            </div>
        </div>
        
        <!-- Capacity Progress Bar -->
        <div class="capacity-progress-section">
            <h3>Capacity Progress</h3>
            <div class="capacity-progress-bar">
                <div class="progress-fill" style="width: <?php echo $capacity_stats['percentage_used']; ?>%"></div>
                <div class="progress-text">
                    <?php echo $capacity_stats['current_attendees']; ?> / <?php echo $capacity_stats['max_capacity']; ?> attendees
                </div>
            </div>
            <?php if ($capacity_stats['is_approaching_capacity']): ?>
                <div class="capacity-warning">
                    ⚠️ Warning: Approaching capacity! Only <?php echo $capacity_stats['remaining_slots']; ?> slots remaining.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Attendee Breakdown -->
        <div class="attendee-breakdown">
            <h3>Attendee Breakdown</h3>
            <div class="breakdown-grid">
                <div class="breakdown-item">
                    <span class="breakdown-label">Paid Tickets:</span>
                    <span class="breakdown-value"><?php echo $capacity_stats['paid_attendees']; ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="breakdown-label">Free Tickets:</span>
                    <span class="breakdown-value"><?php echo $capacity_stats['free_attendees']; ?></span>
                </div>
                <div class="breakdown-item">
                    <span class="breakdown-label">Total:</span>
                    <span class="breakdown-value"><strong><?php echo $capacity_stats['current_attendees']; ?></strong></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Capacity Settings Form -->
    <div class="capacity-settings-form">
        <h2>Capacity Settings</h2>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="capacity-settings-form">
            <?php wp_nonce_field('reset_capacity_action', 'capacity_nonce'); ?>
            <input type="hidden" name="action" value="update_capacity_settings">
            
            <div class="form-sections">
                <!-- Main Capacity Settings -->
                <div class="form-section">
                    <h3>Main Capacity Settings</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="target_capacity">Target Capacity *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="target_capacity" 
                                       name="target_capacity" 
                                       value="<?php echo esc_attr($form_data['target_capacity']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    The target number of attendees for the event. This is your main capacity goal.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_capacity">Maximum Capacity *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_capacity" 
                                       name="max_capacity" 
                                       value="<?php echo esc_attr($form_data['max_capacity']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    The absolute maximum number of attendees allowed. Must be greater than target capacity.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="alert_threshold">Alert Threshold *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="alert_threshold" 
                                       name="alert_threshold" 
                                       value="<?php echo esc_attr($form_data['alert_threshold']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    When to start showing capacity warnings. Should be less than target capacity.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Ticket Pricing Thresholds -->
                <div class="form-section">
                    <h3>Ticket Pricing Thresholds</h3>
                    <p class="section-description">
                        These thresholds determine when ticket prices change based on the number of attendees.
                        They must be in ascending order.
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="early_bird_threshold">Early Bird Threshold *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="early_bird_threshold" 
                                       name="early_bird_threshold" 
                                       value="<?php echo esc_attr($form_data['early_bird_threshold']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    Early bird pricing is available until this many attendees are registered.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="late_bird_threshold">Late Bird Threshold *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="late_bird_threshold" 
                                       name="late_bird_threshold" 
                                       value="<?php echo esc_attr($form_data['late_bird_threshold']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    Late bird pricing starts after early bird and continues until this threshold.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="very_late_bird_threshold">Very Late Bird Threshold *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="very_late_bird_threshold" 
                                       name="very_late_bird_threshold" 
                                       value="<?php echo esc_attr($form_data['very_late_bird_threshold']); ?>" 
                                       min="1" 
                                       max="10000"
                                       class="regular-text" 
                                       required>
                                <p class="description">
                                    Very late bird pricing starts after late bird. Usually equals target capacity.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Capacity Settings">
                    <button type="button" class="button" id="preview-changes">Preview Changes</button>
                </p>
            </div>
        </form>
        
        <!-- Change History Section -->
        <div class="change-history-section">
            <h3>Change History & Rollback</h3>
            <p>Track all capacity changes and rollback to previous configurations if needed.</p>
            
            <?php 
            $change_history = $capacity_manager->get_change_history(10);
            if (!empty($change_history)): 
            ?>
                <div class="history-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Target Capacity</th>
                                <th>Max Capacity</th>
                                <th>Changed By</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($change_history as $index => $change): ?>
                                <tr class="<?php echo $index === 0 ? 'current-config' : ''; ?>">
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($change['updated_at'])); ?>
                                        <?php if ($index === 0): ?>
                                            <span class="current-badge">CURRENT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($change['target_capacity']); ?></td>
                                    <td><?php echo esc_html($change['max_capacity']); ?></td>
                                    <td><?php echo esc_html($change['updated_by'] ?: 'System'); ?></td>
                                    <td>
                                        <span class="change-notes" title="<?php echo esc_attr($change['change_notes']); ?>">
                                            <?php 
                                            $notes = $change['change_notes'];
                                            echo esc_html(strlen($notes) > 50 ? substr($notes, 0, 50) . '...' : $notes); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($index > 0): ?>
                                            <button type="button" 
                                                    class="button button-small rollback-btn" 
                                                    data-config-id="<?php echo $change['id']; ?>"
                                                    data-target="<?php echo $change['target_capacity']; ?>"
                                                    data-max="<?php echo $change['max_capacity']; ?>"
                                                    data-date="<?php echo date('M j, Y g:i A', strtotime($change['updated_at'])); ?>">
                                                Rollback
                                            </button>
                                        <?php else: ?>
                                            <span class="current-text">Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><em>No change history available yet.</em></p>
            <?php endif; ?>
        </div>
        
        <!-- Reset to Defaults Form -->
        <div class="reset-defaults-section">
            <h3>Reset to Defaults</h3>
            <p>Reset all capacity settings to default values.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Are you sure you want to reset all capacity settings to defaults? This cannot be undone.');">
                <?php wp_nonce_field('reset_capacity_action', 'capacity_nonce'); ?>
                <input type="hidden" name="action" value="reset_to_defaults">
                <input type="submit" name="submit" class="button button-secondary" value="Reset to Defaults">
            </form>
        </div>
    </div>
    
    <!-- Impact Preview Modal -->
    <div id="impact-preview-modal" class="impact-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Impact Preview</h3>
                <button class="modal-close" onclick="closeImpactModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="impact-analysis">
                    <!-- Impact analysis will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="button button-secondary" onclick="closeImpactModal()">Cancel</button>
                <button class="button button-primary" onclick="confirmChanges()">Apply Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Capacity Management Styles */
.capacity-environment-info {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.environment-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 10px;
}

.environment-badge.local {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.environment-badge.production {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.environment-details p {
    margin: 5px 0;
    font-size: 14px;
}

.capacity-stats-dashboard {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #333;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.capacity-progress-section {
    margin-bottom: 20px;
}

.capacity-progress-bar {
    position: relative;
    background: #f0f0f0;
    border-radius: 10px;
    height: 30px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    color: #333;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
}

.capacity-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 10px;
    color: #856404;
    font-weight: bold;
}

.attendee-breakdown {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
}

.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #e0e0e0;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-label {
    font-weight: 500;
}

.breakdown-value {
    font-weight: bold;
}

.capacity-settings-form {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-sections {
    margin-bottom: 20px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    margin-top: 0;
    color: #333;
}

.section-description {
    color: #666;
    font-style: italic;
    margin-bottom: 15px;
}

.form-actions {
    border-top: 1px solid #e0e0e0;
    padding-top: 20px;
}

.reset-defaults-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
}

.reset-defaults-section h3 {
    margin-top: 0;
    color: #dc3545;
}

/* Change History Styles */
.change-history-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
    margin-bottom: 20px;
}

.change-history-section h3 {
    margin-top: 0;
    color: #0073aa;
}

.history-table-container {
    margin-top: 15px;
    overflow-x: auto;
}

.history-table-container table {
    margin: 0;
}

.current-config {
    background-color: #e7f3ff !important;
}

.current-badge {
    background: #0073aa;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
    font-weight: bold;
}

.current-text {
    color: #0073aa;
    font-weight: bold;
    font-size: 12px;
}

.change-notes {
    cursor: help;
}

.rollback-btn {
    background: #f0ad4e;
    border-color: #eea236;
    color: #fff;
}

.rollback-btn:hover {
    background: #ec971f;
    border-color: #d58512;
    color: #fff;
}

.settings-notice {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.settings-notice p {
    margin: 0;
    color: #004085;
}



/* Modal Styles */
.impact-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .breakdown-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}
</style>

<script>
// Capacity Management JavaScript
jQuery(document).ready(function($) {
    // Form validation
    $('#capacity-settings-form').on('submit', function(e) {
        var valid = validateCapacityForm();
        if (!valid) {
            e.preventDefault();
        }
    });
    
    // Preview changes
    $('#preview-changes').on('click', function(e) {
        e.preventDefault();
        previewCapacityChanges();
    });
    
    // Real-time validation
    $('#target_capacity, #max_capacity, #alert_threshold').on('input', function() {
        validateCapacityInputs();
    });
    
    $('#early_bird_threshold, #late_bird_threshold, #very_late_bird_threshold').on('input', function() {
        validateThresholdInputs();
    });
});

function validateCapacityForm() {
    var errors = [];
    
    var targetCapacity = parseInt($('#target_capacity').val());
    var maxCapacity = parseInt($('#max_capacity').val());
    var alertThreshold = parseInt($('#alert_threshold').val());
    var earlyBird = parseInt($('#early_bird_threshold').val());
    var lateBird = parseInt($('#late_bird_threshold').val());
    var veryLateBird = parseInt($('#very_late_bird_threshold').val());
    
    // Validate capacity relationships
    if (maxCapacity <= targetCapacity) {
        errors.push('Maximum capacity must be greater than target capacity.');
    }
    
    if (alertThreshold >= targetCapacity) {
        errors.push('Alert threshold should be less than target capacity.');
    }
    
    // Validate threshold relationships
    if (earlyBird >= lateBird || lateBird >= veryLateBird) {
        errors.push('Ticket thresholds must be in ascending order.');
    }
    
    if (errors.length > 0) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

function validateCapacityInputs() {
    var targetCapacity = parseInt($('#target_capacity').val());
    var maxCapacity = parseInt($('#max_capacity').val());
    var alertThreshold = parseInt($('#alert_threshold').val());
    
    // Remove existing error styles
    $('#target_capacity, #max_capacity, #alert_threshold').removeClass('error');
    
    // Validate relationships
    if (maxCapacity && targetCapacity && maxCapacity <= targetCapacity) {
        $('#max_capacity').addClass('error');
    }
    
    if (alertThreshold && targetCapacity && alertThreshold >= targetCapacity) {
        $('#alert_threshold').addClass('error');
    }
}

function validateThresholdInputs() {
    var earlyBird = parseInt($('#early_bird_threshold').val());
    var lateBird = parseInt($('#late_bird_threshold').val());
    var veryLateBird = parseInt($('#very_late_bird_threshold').val());
    
    // Remove existing error styles
    $('#early_bird_threshold, #late_bird_threshold, #very_late_bird_threshold').removeClass('error');
    
    // Validate relationships
    if (earlyBird && lateBird && earlyBird >= lateBird) {
        $('#late_bird_threshold').addClass('error');
    }
    
    if (lateBird && veryLateBird && lateBird >= veryLateBird) {
        $('#very_late_bird_threshold').addClass('error');
    }
}

function previewCapacityChanges() {
    var formData = {
        action: 'reset_preview_capacity_impact',
        nonce: '<?php echo wp_create_nonce('reset_admin_nonce'); ?>',
        target_capacity: $('#target_capacity').val(),
        max_capacity: $('#max_capacity').val(),
        alert_threshold: $('#alert_threshold').val(),
        early_bird_threshold: $('#early_bird_threshold').val(),
        late_bird_threshold: $('#late_bird_threshold').val(),
        very_late_bird_threshold: $('#very_late_bird_threshold').val()
    };
    
    // Show modal with loading
    $('#impact-analysis').html('<div class="loading-spinner"><p>🔄 Analyzing impact...</p></div>');
    $('#impact-preview-modal').show();
    
    // Make AJAX call to get real impact analysis
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                var impactHtml = generateImpactAnalysisFromServer(response.data);
                $('#impact-analysis').html(impactHtml);
            } else {
                $('#impact-analysis').html('<div class="error-message">❌ Error: ' + response.data.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            $('#impact-analysis').html('<div class="error-message">❌ Failed to analyze impact: ' + error + '</div>');
        }
    });
}

function generateImpactAnalysisFromServer(data) {
    var html = '<div class="impact-analysis">';
    html += '<h4>📊 Impact Analysis</h4>';
    
    if (data.impact && data.impact.length > 0) {
        html += '<div class="impact-list">';
        
        data.impact.forEach(function(item) {
            if (item.type === 'warning') {
                html += '<div class="impact-warning">' + item.message + '</div>';
            } else {
                html += '<div class="impact-item">';
                html += '<strong>' + item.message + '</strong>';
                html += '</div>';
            }
        });
        
        html += '</div>';
    } else {
        html += '<div class="no-impact">';
        html += '<p>✅ No significant changes detected. The new settings are similar to current settings.</p>';
        html += '</div>';
    }
    
    // Show current vs new settings summary
    html += '<div class="settings-summary">';
    html += '<h5>Settings Summary</h5>';
    html += '<table class="settings-compare-table">';
    html += '<tr><th>Setting</th><th>Current</th><th>New</th></tr>';
    html += '<tr><td>Target Capacity</td><td>' + <?php echo $current_settings['main_capacity']['target_capacity']; ?> + '</td><td>' + data.new_settings.target_capacity + '</td></tr>';
    html += '<tr><td>Maximum Capacity</td><td>' + <?php echo $current_settings['main_capacity']['max_capacity']; ?> + '</td><td>' + data.new_settings.max_capacity + '</td></tr>';
    html += '<tr><td>Alert Threshold</td><td>' + <?php echo $current_settings['main_capacity']['alert_threshold']; ?> + '</td><td>' + data.new_settings.alert_threshold + '</td></tr>';
    html += '<tr><td>Early Bird Threshold</td><td>' + <?php echo $current_settings['ticket_thresholds']['early_bird']; ?> + '</td><td>' + data.new_settings.early_bird_threshold + '</td></tr>';
    html += '<tr><td>Late Bird Threshold</td><td>' + <?php echo $current_settings['ticket_thresholds']['late_bird']; ?> + '</td><td>' + data.new_settings.late_bird_threshold + '</td></tr>';
    html += '<tr><td>Very Late Bird Threshold</td><td>' + <?php echo $current_settings['ticket_thresholds']['very_late_bird']; ?> + '</td><td>' + data.new_settings.very_late_bird_threshold + '</td></tr>';
    html += '</table>';
    html += '</div>';
    
    html += '</div>';
    
    return html;
}

function closeImpactModal() {
    $('#impact-preview-modal').hide();
}

function confirmChanges() {
    $('#impact-preview-modal').hide();
    $('#capacity-settings-form').submit();
}

// CSS for error styling
var errorStyles = `
    <style>
        .error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 1px #dc3545;
        }
        .impact-analysis {
            margin: 20px 0;
        }
        .impact-item {
            padding: 10px;
            margin: 10px 0;
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
        }
        .impact-warning {
            padding: 10px;
            margin: 10px 0;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            color: #856404;
        }
        .loading-spinner {
            text-align: center;
            padding: 20px;
        }
        .error-message {
            padding: 10px;
            margin: 10px 0;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
        }
        .no-impact {
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            color: #155724;
            margin: 10px 0;
        }
        .settings-summary {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .settings-summary h5 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .settings-compare-table {
            width: 100%;
            border-collapse: collapse;
        }
        .settings-compare-table th,
        .settings-compare-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .settings-compare-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .impact-list {
            margin: 15px 0;
        }
    </style>
`;

$('head').append(errorStyles);
</script>

<!-- Development Tools Section (only shown in debug mode) -->
<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
<div class="capacity-card">
    <h3>🔧 Development Tools</h3>
    <div class="capacity-card-content">
        <div class="development-tools-section">
            <div class="migration-status">
                <h4>Database Migration Status</h4>
                <div class="migration-info">
                    <div class="migration-item">
                        <strong>Current Version:</strong> <?php echo esc_html($migration_status['current_version']); ?>
                    </div>
                    <div class="migration-item">
                        <strong>Target Version:</strong> <?php echo esc_html($migration_status['target_version']); ?>
                    </div>
                    <div class="migration-item">
                        <strong>Status:</strong> 
                        <?php if ($migration_status['needs_migration']): ?>
                            <span class="status-warning">⚠️ Migration Needed</span>
                        <?php else: ?>
                            <span class="status-success">✅ Up to Date</span>
                        <?php endif; ?>
                    </div>
                    <div class="migration-item">
                        <strong>Last Migration:</strong> <?php echo esc_html($migration_status['last_migration']); ?>
                    </div>
                </div>
                
                <div class="database-validation">
                    <h4>Database Validation</h4>
                    <div class="validation-info">
                        <div class="validation-item">
                            <strong>Status:</strong> 
                            <?php if ($db_validation['valid']): ?>
                                <span class="status-success">✅ Valid</span>
                            <?php else: ?>
                                <span class="status-error">❌ Invalid</span>
                            <?php endif; ?>
                        </div>
                        <div class="validation-item">
                            <strong>Tables:</strong> <?php echo esc_html($db_validation['existing_tables']); ?>/<?php echo esc_html($db_validation['total_tables']); ?>
                        </div>
                        <?php if (!empty($db_validation['missing_tables'])): ?>
                            <div class="validation-item">
                                <strong>Missing Tables:</strong> <?php echo esc_html(implode(', ', $db_validation['missing_tables'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="development-actions">
                <h4>Development Actions</h4>
                <div class="dev-actions-grid">
                    <div class="dev-action">
                        <h5>Run Migration</h5>
                        <p>Apply any pending database migrations without losing data.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                            <input type="hidden" name="action" value="run_database_migration">
                            <?php wp_nonce_field('reset_migration_action', 'migration_nonce'); ?>
                            <button type="submit" class="button button-secondary" onclick="return confirm('Run database migration? This will apply any pending schema changes.');">
                                🔄 Run Migration
                            </button>
                        </form>
                    </div>
                    
                    <div class="dev-action">
                        <h5>Force Recreate Tables</h5>
                        <p><strong>⚠️ WARNING:</strong> This will DROP all tables and recreate them. All data will be lost!</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                            <input type="hidden" name="action" value="force_recreate_tables">
                            <?php wp_nonce_field('reset_migration_action', 'migration_nonce'); ?>
                            <button type="submit" class="button button-secondary button-danger" onclick="return confirm('⚠️ WARNING: This will DELETE ALL DATA and recreate tables from scratch. This cannot be undone!\n\nAre you absolutely sure you want to continue?');">
                                🗑️ Force Recreate
                            </button>
                        </form>
                    </div>
                    

                    
                    <div class="dev-action">
                        <h5>Clear Caches</h5>
                        <p>Clear WordPress object cache and opcache (if available).</p>
                        <button type="button" class="button button-secondary" onclick="clearAllCaches()">
                            🧹 Clear Caches
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.development-tools-section {
    background: #f8f9fa;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.migration-status, .database-validation {
    margin-bottom: 20px;
}

.migration-info, .validation-info {
    background: white;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.migration-item, .validation-item {
    margin: 8px 0;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.migration-item:last-child, .validation-item:last-child {
    border-bottom: none;
}

.status-success {
    color: #28a745;
    font-weight: bold;
}

.status-warning {
    color: #ffc107;
    font-weight: bold;
}

.status-error {
    color: #dc3545;
    font-weight: bold;
}

.dev-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.dev-action {
    background: white;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.dev-action h5 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.dev-action p {
    margin-bottom: 15px;
    color: #666;
    font-size: 14px;
}

.button-danger {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
}

.button-danger:hover {
    background: #c82333 !important;
    border-color: #c82333 !important;
}
</style>

<script>
function clearAllCaches() {
    if (confirm('Clear all caches? This will flush WordPress object cache and opcache.')) {
        // You can implement an AJAX call here to clear caches
        // For now, we'll just show a message
        alert('Cache clearing would be implemented here. For now, you can:\n\n1. Deactivate and reactivate the plugin\n2. Use WP-CLI: wp cache flush\n3. Restart your web server');
    }
}

// Rollback functionality
$(document).ready(function() {
    $('.rollback-btn').on('click', function() {
        const configId = $(this).data('config-id');
        const target = $(this).data('target');
        const max = $(this).data('max');
        const date = $(this).data('date');
        
        const confirmMessage = `Are you sure you want to rollback to the configuration from ${date}?\n\n` +
                              `Target Capacity: ${target}\n` +
                              `Maximum Capacity: ${max}\n\n` +
                              `This action cannot be undone, but will be tracked in the change history.`;
        
        if (confirm(confirmMessage)) {
            performRollback(configId);
        }
    });
});

function performRollback(configId) {
    // Show loading state
    $('.rollback-btn').prop('disabled', true).text('Rolling back...');
    
    // Make AJAX call to perform rollback
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'reset_rollback_capacity',
            config_id: configId,
            nonce: '<?php echo wp_create_nonce('reset_rollback_nonce'); ?>'
        },
        success: function(response) {
            $('.rollback-btn').prop('disabled', false).text('Rollback');
            
            if (response.success) {
                // Show success message and reload page
                alert('✅ Successfully rolled back capacity settings!');
                window.location.reload();
            } else {
                alert('❌ Error: ' + (response.data.message || 'Failed to rollback settings'));
            }
        },
        error: function(xhr, status, error) {
            $('.rollback-btn').prop('disabled', false).text('Rollback');
            alert('❌ Network error: ' + error);
        }
    });
}


</script>
<?php endif; ?> 