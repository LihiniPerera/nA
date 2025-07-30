<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the addons instance
$addons = ResetAddons::getInstance();
$database = ResetDatabase::getInstance();

// Handle form submissions
$message = '';
$message_type = '';
$validation_errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'reset_admin_addon_action')) {
        $message = 'Security check failed.';
        $message_type = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create_addon':
                $result = $addons->create_addon($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                    // Clear the form by redirecting to avoid form resubmission
                    wp_redirect(admin_url('admin.php?page=reset-addon-management&created=1'));
                    exit;
                } else {
                    $message = $result['message'] ?? 'Failed to create addon.';
                    $message_type = 'error';
                    $validation_errors = $result['errors'] ?? array();
                }
                break;
                
            case 'update_addon':
                $addon_id = intval($_POST['addon_id']);
                if ($addon_id > 0) {
                    $result = $addons->update_addon($addon_id, $_POST);
                    if ($result['success']) {
                        $message = $result['message'];
                        $message_type = 'success';
                        // Redirect to avoid form resubmission
                        wp_redirect(admin_url('admin.php?page=reset-addon-management&updated=1'));
                        exit;
                    } else {
                        $message = $result['message'] ?? 'Failed to update addon.';
                        $message_type = 'error';
                        $validation_errors = $result['errors'] ?? array();
                    }
                } else {
                    $message = 'Invalid addon ID.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete_addon':
                $addon_id = intval($_POST['addon_id']);
                if ($addon_id > 0) {
                    // Check if addon is used in any purchases
                    $addon_usage = $database->get_addons_for_purchase_count($addon_id);
                    if ($addon_usage > 0) {
                        $message = sprintf('Cannot delete addon. It is used in %d purchase(s).', $addon_usage);
                        $message_type = 'error';
                    } else {
                        $result = $addons->delete_addon($addon_id);
                        if ($result['success']) {
                            $message = $result['message'];
                            $message_type = 'success';
                            // Redirect to avoid form resubmission
                            wp_redirect(admin_url('admin.php?page=reset-addon-management&deleted=1'));
                            exit;
                        } else {
                            $message = $result['message'] ?? 'Failed to delete addon.';
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Invalid addon ID.';
                    $message_type = 'error';
                }
                break;
                
            case 'toggle_addon_status':
                $addon_id = intval($_POST['addon_id']);
                if ($addon_id > 0) {
                    $result = $addons->toggle_addon_status($addon_id);
                    if ($result['success']) {
                        $message = $result['message'];
                        $message_type = 'success';
                        // Redirect to avoid form resubmission
                        wp_redirect(admin_url('admin.php?page=reset-addon-management&toggled=1'));
                        exit;
                    } else {
                        $message = $result['message'] ?? 'Failed to toggle addon status.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Invalid addon ID.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Handle URL parameters for success messages
if (isset($_GET['created']) && $_GET['created'] == '1') {
    $message = 'Add-on created successfully!';
    $message_type = 'success';
}
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = 'Add-on updated successfully!';
    $message_type = 'success';
}
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = 'Add-on deleted successfully!';
    $message_type = 'success';
}
if (isset($_GET['toggled']) && $_GET['toggled'] == '1') {
    $message = 'Add-on status toggled successfully!';
    $message_type = 'success';
}

// Get all addons
$all_addons = $database->get_all_addons();
$addon_stats = $addons->get_addon_statistics();

// Handle edit mode
$editing_addon = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editing_addon = $database->get_addon_by_id(intval($_GET['edit']));
    if (!$editing_addon) {
        wp_redirect(admin_url('admin.php?page=reset-addon-management'));
        exit;
    }
}

// Prepare form data for display
$form_data = array(
    'addon_key' => $editing_addon['addon_key'] ?? '',
    'name' => $editing_addon['name'] ?? '',
    'description' => $editing_addon['description'] ?? '',
    'price' => $editing_addon['price'] ?? '0',
    'drink_count' => $editing_addon['drink_count'] ?? '0',
    'sort_order' => $editing_addon['sort_order'] ?? '0',
    'is_enabled' => $editing_addon['is_enabled'] ?? 1
);

// If there were validation errors, preserve the submitted data
if (!empty($validation_errors) && !empty($_POST)) {
    $form_data = array(
        'addon_key' => sanitize_text_field($_POST['addon_key'] ?? ''),
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'price' => sanitize_text_field($_POST['price'] ?? '0'),
        'drink_count' => sanitize_text_field($_POST['drink_count'] ?? '0'),
        'sort_order' => sanitize_text_field($_POST['sort_order'] ?? '0'),
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0
    );
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Add-on Management</h1>
    <a href="<?php echo admin_url('admin.php?page=reset-addon-management'); ?>" class="page-title-action">Add New</a>
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
    
    <!-- Statistics -->
    <div class="addon-stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3><?php echo $addon_stats['total_addons']; ?></h3>
                <p>Total Add-ons</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $addon_stats['enabled_addons']; ?></h3>
                <p>Enabled</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $addon_stats['disabled_addons']; ?></h3>
                <p>Disabled</p>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Form -->
    <div class="addon-form-section">
        <h2><?php echo $editing_addon ? 'Edit Add-on' : 'Add New Add-on'; ?></h2>
        
        <form method="post" action="" id="addon-form">
            <?php wp_nonce_field('reset_admin_addon_action'); ?>
            <input type="hidden" name="action" value="<?php echo $editing_addon ? 'update_addon' : 'create_addon'; ?>">
            <?php if ($editing_addon): ?>
                <input type="hidden" name="addon_id" value="<?php echo esc_attr($editing_addon['id']); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="addon_key">Add-on Key *</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="addon_key" 
                               name="addon_key" 
                               value="<?php echo esc_attr($form_data['addon_key']); ?>" 
                               class="regular-text" 
                               required
                               pattern="[a-z0-9_]+"
                               title="Only lowercase letters, numbers, and underscores allowed"
                               <?php echo $editing_addon ? 'readonly' : ''; ?>>
                        <p class="description">
                            Unique identifier (lowercase letters, numbers, underscores only)
                            <?php if ($editing_addon): ?>
                                <br><strong>Note:</strong> Add-on key cannot be changed after creation.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="name">Add-on Name *</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo esc_attr($form_data['name']); ?>" 
                               class="regular-text" 
                               required
                               maxlength="255">
                        <p class="description">Display name for the add-on (max 255 characters)</p>
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
                                  class="large-text"
                                  maxlength="1000"><?php echo esc_textarea($form_data['description']); ?></textarea>
                        <p class="description">Brief description of the add-on (max 1000 characters)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="price">Price (Rs.) *</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               value="<?php echo esc_attr($form_data['price']); ?>" 
                               min="0" 
                               max="999999"
                               step="0.01" 
                               class="regular-text" 
                               required>
                        <p class="description">Price in Sri Lankan Rupees (0.00 to 999,999.00)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="drink_count">Drink Count *</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="drink_count" 
                               name="drink_count" 
                               value="<?php echo esc_attr($form_data['drink_count']); ?>" 
                               min="0" 
                               max="99"
                               class="small-text" 
                               required>
                        <p class="description">Number of drinks included with this add-on (0-99)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sort_order">Sort Order</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="sort_order" 
                               name="sort_order" 
                               value="<?php echo esc_attr($form_data['sort_order']); ?>" 
                               min="0"
                               max="9999"
                               class="small-text">
                        <p class="description">Display order (0-9999, lower numbers appear first)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="is_enabled" 
                                   value="1" 
                                   <?php checked($form_data['is_enabled'], 1); ?>>
                            <strong>Enabled</strong>
                        </label>
                        <p class="description">Only enabled add-ons are available for selection during booking</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button($editing_addon ? 'Update Add-on' : 'Create Add-on', 'primary', 'submit', false, array('id' => 'submit-addon')); ?>
            
            <?php if ($editing_addon): ?>
                <a href="<?php echo admin_url('admin.php?page=reset-addon-management'); ?>" class="button">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Addons List -->
    <div class="addon-list-section">
        <h2>Existing Add-ons</h2>
        
        <?php if (empty($all_addons)): ?>
            <div class="no-addons-message">
                <p>No add-ons found. Create your first add-on using the form above.</p>
            </div>
        <?php else: ?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <p class="description">
                        <strong>Total:</strong> <?php echo count($all_addons); ?> add-ons
                        | <strong>Enabled:</strong> <?php echo $addon_stats['enabled_addons']; ?>
                        | <strong>Disabled:</strong> <?php echo $addon_stats['disabled_addons']; ?>
                    </p>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 120px;">Add-on Key</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="width: 100px;">Price</th>
                        <th style="width: 80px;">Drinks</th>
                        <th style="width: 80px;">Status</th>
                        <th style="width: 80px;">Sort Order</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_addons as $addon): ?>
                        <tr>
                            <td><code><?php echo esc_html($addon['addon_key']); ?></code></td>
                            <td><strong><?php echo esc_html($addon['name']); ?></strong></td>
                            <td>
                                <?php if (!empty($addon['description'])): ?>
                                    <?php echo esc_html($addon['description']); ?>
                                <?php else: ?>
                                    <em>No description</em>
                                <?php endif; ?>
                            </td>
                            <td>Rs. <?php echo number_format($addon['price'], 2); ?></td>
                            <td class="text-center"><?php echo intval($addon['drink_count'] ?? 0); ?></td>
                            <td>
                                <span class="status-badge <?php echo $addon['is_enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $addon['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo esc_html($addon['sort_order']); ?></td>
                            <td>
                                <div class="addon-actions">
                                    <a href="<?php echo admin_url('admin.php?page=reset-addon-management&edit=' . $addon['id']); ?>" 
                                       class="button button-small" 
                                       title="Edit add-on">Edit</a>
                                    
                                    <form method="post" style="display: inline-block;" 
                                          class="toggle-status-form">
                                        <?php wp_nonce_field('reset_admin_addon_action'); ?>
                                        <input type="hidden" name="action" value="toggle_addon_status">
                                        <input type="hidden" name="addon_id" value="<?php echo esc_attr($addon['id']); ?>">
                                        <button type="submit" 
                                                class="button button-small <?php echo $addon['is_enabled'] ? 'button-secondary' : 'button-primary'; ?>"
                                                title="<?php echo $addon['is_enabled'] ? 'Disable' : 'Enable'; ?> add-on">
                                            <?php echo $addon['is_enabled'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="post" style="display: inline-block;" 
                                          class="delete-addon-form"
                                          onsubmit="return confirm('Are you sure you want to delete this add-on? This action cannot be undone.');">
                                        <?php wp_nonce_field('reset_admin_addon_action'); ?>
                                        <input type="hidden" name="action" value="delete_addon">
                                        <input type="hidden" name="addon_id" value="<?php echo esc_attr($addon['id']); ?>">
                                        <button type="submit" 
                                                class="button button-small button-link-delete"
                                                title="Delete add-on">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.addon-stats {
    margin: 20px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    max-width: 600px;
}

.stat-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-item h3 {
    font-size: 2em;
    margin: 0 0 5px 0;
    color: #f9c613;
}

.stat-item p {
    margin: 0;
    color: #666;
    font-weight: 500;
}

.addon-form-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.addon-form-section h2 {
    margin-top: 0;
    color: #333;
}

.addon-list-section {
    margin: 20px 0;
}

.no-addons-message {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    color: #666;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.status-badge.enabled {
    background: #d4edda;
    color: #155724;
}

.status-badge.disabled {
    background: #f8d7da;
    color: #721c24;
}

.addon-actions {
    display: flex;
    gap: 5px;
    align-items: center;
}

.button-link-delete {
    color: #a00 !important;
    border-color: #a00 !important;
}

.button-link-delete:hover {
    color: #dc3232 !important;
    border-color: #dc3232 !important;
    background: #f8d7da !important;
}

.text-center {
    text-align: center;
}

.form-table th {
    width: 150px;
    padding: 20px 10px 20px 0;
    vertical-align: top;
}

.form-table td {
    padding: 15px 0;
    vertical-align: top;
}

.form-table input[type="text"],
.form-table input[type="number"],
.form-table textarea {
    width: 100%;
    max-width: 500px;
}

.form-table .description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

#addon-form {
    max-width: 800px;
}

.tablenav {
    padding: 8px 0;
}

.tablenav .description {
    margin: 0;
    font-size: 13px;
}

/* Responsive design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .addon-actions {
        flex-direction: column;
        gap: 3px;
    }
    
    .addon-actions .button {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-generate addon key from name (for new addons only)
    <?php if (!$editing_addon): ?>
    $('#name').on('input', function() {
        var name = $(this).val();
        var key = name.toLowerCase()
                     .replace(/[^a-z0-9\s]/g, '')
                     .replace(/\s+/g, '_')
                     .substring(0, 50);
        $('#addon_key').val(key);
    });
    <?php endif; ?>
    
    // Form validation
    $('#addon-form').on('submit', function(e) {
        var addonKey = $('#addon_key').val();
        var name = $('#name').val();
        var price = $('#price').val();
        var drinkCount = $('#drink_count').val();
        
        if (!addonKey || !name || price === '' || drinkCount === '') {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (!/^[a-z0-9_]+$/.test(addonKey)) {
            e.preventDefault();
            alert('Add-on key must contain only lowercase letters, numbers, and underscores.');
            return false;
        }
        
        if (parseFloat(price) < 0) {
            e.preventDefault();
            alert('Price must be 0 or greater.');
            return false;
        }
        
        if (parseInt(drinkCount) < 0) {
            e.preventDefault();
            alert('Drink count must be 0 or greater.');
            return false;
        }
        
        if (parseInt(drinkCount) > 99) {
            e.preventDefault();
            alert('Drink count cannot exceed 99.');
            return false;
        }
        
        return true;
    });
    
    // Character count for description
    $('#description').on('input', function() {
        var current = $(this).val().length;
        var max = 1000;
        var remaining = max - current;
        
        if (remaining < 0) {
            $(this).val($(this).val().substring(0, max));
            remaining = 0;
        }
        
        // Update or create character counter
        var counter = $(this).siblings('.char-counter');
        if (counter.length === 0) {
            counter = $('<div class="char-counter" style="font-size: 12px; color: #666; margin-top: 5px;"></div>');
            $(this).after(counter);
        }
        
        counter.text(remaining + ' characters remaining');
        
        if (remaining < 50) {
            counter.css('color', '#d63638');
        } else {
            counter.css('color', '#666');
        }
    });
    
    // Confirm deletion
    $('.delete-addon-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to delete this add-on? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Auto-dismiss notices
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut();
    }, 5000);
});
</script> 