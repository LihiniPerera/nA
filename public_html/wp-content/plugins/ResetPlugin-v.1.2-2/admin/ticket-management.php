<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
$database = ResetDatabase::getInstance();

// Handle form submissions
$action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$message = '';
$error = '';

// Process actions
if ($action && wp_verify_nonce($_POST['_wpnonce'], 'reset_ticket_action')) {
    
    switch ($action) {
        case 'create_ticket':
            $ticket_data = array(
                'ticket_key' => sanitize_text_field($_POST['ticket_key']),
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'features' => sanitize_textarea_field($_POST['features']),
                'ticket_price' => floatval($_POST['ticket_price']),
                'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
                'sort_order' => intval($_POST['sort_order'])
            );
            
            if ($database->create_ticket_type($ticket_data)) {
                $message = 'Ticket type created successfully!';
            } else {
                $error = 'Failed to create ticket type. Please try again.';
            }
            break;
            
        case 'update_ticket':
            $ticket_data = array(
                'ticket_key' => sanitize_text_field($_POST['ticket_key']),
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'features' => sanitize_textarea_field($_POST['features']),
                'ticket_price' => floatval($_POST['ticket_price']),
                'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
                'sort_order' => intval($_POST['sort_order'])
            );
            
            if ($database->update_ticket_type($ticket_id, $ticket_data)) {
                $message = 'Ticket type updated successfully!';
            } else {
                $error = 'Failed to update ticket type. Please try again.';
            }
            break;
            
        case 'delete_ticket':
            if ($database->delete_ticket_type($ticket_id)) {
                $message = 'Ticket type deleted successfully!';
            } else {
                $error = 'Failed to delete ticket type. Please try again.';
            }
            break;
            
        case 'toggle_status':
            if ($database->toggle_ticket_type_status($ticket_id)) {
                $message = 'Ticket type status updated successfully!';
            } else {
                $error = 'Failed to update ticket type status. Please try again.';
            }
            break;
    }
}

// Get current ticket types
$tickets = $database->get_all_ticket_types();

// Handle edit mode
$edit_ticket = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_ticket = $database->get_ticket_type_by_id(intval($_GET['edit']));
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="reset-ticket-management">
        
        <!-- Add/Edit Ticket Form -->
        <div class="postbox">
            <h2 class="hndle">
                <?php echo $edit_ticket ? 'Edit Ticket Type' : 'Add New Ticket Type'; ?>
            </h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('reset_ticket_action'); ?>
                    <input type="hidden" name="action" value="<?php echo $edit_ticket ? 'update_ticket' : 'create_ticket'; ?>">
                    <?php if ($edit_ticket): ?>
                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($edit_ticket['id']); ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ticket_key">Ticket Key</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="ticket_key" 
                                       name="ticket_key" 
                                       value="<?php echo esc_attr($edit_ticket ? $edit_ticket['ticket_key'] : ''); ?>"
                                       class="regular-text" 
                                       required>
                                <p class="description">Unique identifier for the ticket (e.g., general_early, afterparty_package1)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="name">Ticket Name</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo esc_attr($edit_ticket ? $edit_ticket['name'] : ''); ?>"
                                       class="regular-text" 
                                       required>
                                <p class="description">Display name for the ticket</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea id="description" 
                                          name="description" 
                                          rows="3" 
                                          cols="50"><?php echo esc_textarea($edit_ticket ? $edit_ticket['description'] : ''); ?></textarea>
                                <p class="description">Brief description of the ticket</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="features">Features/Benefits</label>
                            </th>
                            <td>
                                <textarea id="features" 
                                          name="features" 
                                          rows="3" 
                                          cols="50"><?php echo esc_textarea($edit_ticket ? $edit_ticket['features'] : ''); ?></textarea>
                                <p class="description">What benefits does this ticket include?</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ticket_price">Ticket Price</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="ticket_price" 
                                       name="ticket_price" 
                                       value="<?php echo esc_attr($edit_ticket ? $edit_ticket['ticket_price'] : '0'); ?>"
                                       min="0" 
                                       step="0.01"
                                       class="regular-text" 
                                       required>
                                <p class="description">Ticket price in Sri Lankan Rupees (Rs)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Settings</th>
                            <td>
                                <fieldset>
                                    <label for="is_enabled">
                                        <input type="checkbox" 
                                               id="is_enabled" 
                                               name="is_enabled" 
                                               value="1"
                                               <?php checked($edit_ticket ? $edit_ticket['is_enabled'] : 1); ?>>
                                        Enable this ticket type
                                    </label>
                                    <br><br>
                                    
                                    <label for="sort_order">
                                        Sort Order: 
                                        <input type="number" 
                                               id="sort_order" 
                                               name="sort_order" 
                                               value="<?php echo esc_attr($edit_ticket ? $edit_ticket['sort_order'] : '0'); ?>"
                                               class="small-text">
                                    </label>
                                    <p class="description">Lower numbers appear first</p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="submit" 
                               class="button-primary" 
                               value="<?php echo $edit_ticket ? 'Update Ticket' : 'Create Ticket'; ?>">
                        
                        <?php if ($edit_ticket): ?>
                            <a href="<?php echo admin_url('admin.php?page=reset-ticket-management'); ?>" 
                               class="button">Cancel</a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Ticket Types List -->
        <div class="postbox">
            <h2 class="hndle">Current Ticket Types</h2>
            <div class="inside">
                <?php if (empty($tickets)): ?>
                    <p>No ticket types found. Create your first ticket type above.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column">Ticket</th>
                                <th scope="col" class="manage-column">Price</th>
                                <th scope="col" class="manage-column">Status</th>
                                <th scope="col" class="manage-column">Sort Order</th>
                                <th scope="col" class="manage-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($ticket['name']); ?></strong>
                                        <br>
                                        <code><?php echo esc_html($ticket['ticket_key']); ?></code>
                                        <?php if ($ticket['description']): ?>
                                            <br>
                                            <em><?php echo esc_html($ticket['description']); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>Rs <?php echo number_format($ticket['ticket_price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="<?php echo $ticket['is_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                            <?php echo $ticket['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html($ticket['sort_order']); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=reset-ticket-management&edit=' . $ticket['id']); ?>" 
                                           class="button button-small">Edit</a>
                                        
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('reset_ticket_action'); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['id']); ?>">
                                            <input type="submit" 
                                                   name="submit" 
                                                   class="button button-small" 
                                                   value="<?php echo $ticket['is_enabled'] ? 'Disable' : 'Enable'; ?>">
                                        </form>
                                        
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this ticket type?');">
                                            <?php wp_nonce_field('reset_ticket_action'); ?>
                                            <input type="hidden" name="action" value="delete_ticket">
                                            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['id']); ?>">
                                            <input type="submit" 
                                                   name="submit" 
                                                   class="button button-small button-link-delete" 
                                                   value="Delete">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
.reset-ticket-management .postbox {
    margin-bottom: 20px;
}

.reset-ticket-management .form-table th {
    vertical-align: top;
    padding-top: 10px;
}

.reset-ticket-management .form-table td {
    padding-top: 10px;
}

.status-enabled {
    color: #46b450;
    font-weight: bold;
}

.status-disabled {
    color: #dc3232;
    font-weight: bold;
}

.reset-ticket-management .wp-list-table th,
.reset-ticket-management .wp-list-table td {
    padding: 8px 10px;
}

.reset-ticket-management .button-small {
    margin-right: 5px;
}
</style> 