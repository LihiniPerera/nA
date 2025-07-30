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
$admin = ResetAdmin::getInstance();
$db = ResetDatabase::getInstance();
$tokens = ResetTokens::getInstance();

// Handle form submissions
$message = '';
$error = '';

if ($_POST && wp_verify_nonce($_POST['reset_admin_nonce'], 'reset_admin_action')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'generate_tokens':
            $count = intval($_POST['token_count']);
            $token_type = sanitize_text_field($_POST['token_type']);
            
            if ($count > 0) {
                $valid_types = array('normal', 'free_ticket', 'polo_ordered', 'sponsor');
                if (in_array($token_type, $valid_types)) {
                    $result = $admin->generate_tokens($token_type, $count);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = __('Invalid token type selected.', 'reset-ticketing');
                }
            } else {
                $error = __('Invalid token count. Please enter a number greater than 0.', 'reset-ticketing');
            }
            break;
            
        case 'cancel_token':
            $token_id = intval($_POST['token_id']);
            $reason = sanitize_textarea_field($_POST['cancellation_reason']);
            
            $result = $admin->cancel_token($token_id, $reason);
            if ($result['success']) {
                $message = __('Token cancelled successfully.', 'reset-ticketing');
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'bulk_cancel':
            $token_ids = isset($_POST['token_ids']) && is_array($_POST['token_ids']) ? array_map('intval', $_POST['token_ids']) : array();
            $reason = isset($_POST['bulk_cancellation_reason']) ? sanitize_textarea_field($_POST['bulk_cancellation_reason']) : '';
            
            if (empty($token_ids)) {
                $error = __('No tokens selected for bulk cancellation.', 'reset-ticketing');
            } else {
                $result = $admin->bulk_cancel_tokens($token_ids, $reason);
                if ($result['success']) {
                    $message = sprintf(__('Successfully cancelled %d tokens.', 'reset-ticketing'), $result['count']);
                } else {
                    $error = $result['message'];
                }
            }
            break;
    }
}

// Get tokens for display
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$used = isset($_GET['used']) ? sanitize_text_field($_GET['used']) : '';
$sent = isset($_GET['sent']) ? sanitize_text_field($_GET['sent']) : '';
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Check if user wants to show all tokens (bypass defaults)
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';

// Apply default filters if no filters are set and not showing all
$current_filters = array(
    'search' => $search,
    'status' => $status,
    'type' => $type,
    'used' => $used,
    'sent' => $sent
);

if ($show_all) {
    $applied_filters = $current_filters;
    $is_default_applied = false;
} else {
    $applied_filters = $admin->apply_default_filters($current_filters);
    // Check if defaults were applied (when current filters are empty but applied filters are not)
    $is_default_applied = empty($current_filters['search']) && 
                         empty($current_filters['status']) && 
                         empty($current_filters['type']) && 
                         empty($current_filters['used']) && 
                         empty($current_filters['sent']) &&
                         (!empty($applied_filters['status']) || 
                          !empty($applied_filters['type']) || 
                          !empty($applied_filters['used']) || 
                          !empty($applied_filters['sent']));
}

// Use applied filters for search
$search = $applied_filters['search'];
$status = $applied_filters['status'];
$type = $applied_filters['type'];
$used = $applied_filters['used'];
$sent = $applied_filters['sent'];

$tokens_data = $admin->search_tokens($search, $status, $type, $used, $sent, $per_page, $offset);
$tokens_list = $tokens_data['tokens'];
$total_tokens = $tokens_data['total'];
$total_pages = ceil($total_tokens / $per_page);

// Get default filter settings for the settings form
$default_settings = $admin->get_default_filter_settings();

// Debug: Add this temporarily to see what's happening
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
    echo '<strong>Debug Info:</strong><br>';
    echo 'Current Filters: ' . print_r($current_filters, true) . '<br>';
    echo 'Applied Filters: ' . print_r($applied_filters, true) . '<br>';
    echo 'Default Settings: ' . print_r($default_settings, true) . '<br>';
    echo 'Is Default Applied: ' . ($is_default_applied ? 'Yes' : 'No') . '<br>';
    echo 'Show All: ' . ($show_all ? 'Yes' : 'No') . '<br>';
    echo '<br><strong>Test Defaults:</strong><br>';
    echo 'Raw option value: ' . print_r(get_option('reset_token_filter_defaults'), true) . '<br>';
    echo '</div>';
}

// Get statistics
$stats = $db->get_statistics();
?>

<div class="wrap">
    <h1><?php echo esc_html__('Token Management', 'reset-ticketing'); ?></h1>
    
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
    
    <!-- Statistics Overview -->
    <div class="reset-stats-overview">
        <div class="reset-stat-boxes">
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['total_tokens']); ?></h3>
                <p><?php echo esc_html__('Total Tokens', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['active_tokens']); ?></h3>
                <p><?php echo esc_html__('Active Tokens', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['used_tokens']); ?></h3>
                <p><?php echo esc_html__('Used Tokens', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['sent_tokens']); ?></h3>
                <p><?php echo esc_html__('Sent Tokens', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['unsent_tokens']); ?></h3>
                <p><?php echo esc_html__('Not Sent', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-stat-box">
                <h3><?php echo esc_html($stats['cancelled_tokens']); ?></h3>
                <p><?php echo esc_html__('Cancelled Tokens', 'reset-ticketing'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Token Generation Form -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Generate New Tokens', 'reset-ticketing'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('reset_admin_action', 'reset_admin_nonce'); ?>
            <input type="hidden" name="action" value="generate_tokens">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Token Type', 'reset-ticketing'); ?></th>
                    <td>
                        <select name="token_type" required>
                            <option value="normal"><?php echo esc_html__('Normal Tokens', 'reset-ticketing'); ?></option>
                            <option value="free_ticket"><?php echo esc_html__('Free Tickets', 'reset-ticketing'); ?></option>
                            <option value="polo_ordered"><?php echo esc_html__('Polo Ordered', 'reset-ticketing'); ?></option>
                            <option value="sponsor"><?php echo esc_html__('Sponsors', 'reset-ticketing'); ?></option>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the type of tokens to generate.', 'reset-ticketing'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Number of Tokens', 'reset-ticketing'); ?></th>
                    <td>
                        <input type="number" name="token_count" min="1" required>
                        <p class="description"><?php echo esc_html__('Enter number of tokens to generate (greater than 0).', 'reset-ticketing'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Generate Tokens', 'reset-ticketing'), 'primary', 'submit', false); ?>
        </form>
    </div>
    
    <!-- Token Search & Filter -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Search & Filter Tokens', 'reset-ticketing'); ?></h2>
        <?php if ($is_default_applied): ?>
            <div class="reset-default-indicator">
                <span class="dashicons dashicons-info"></span>
                <?php echo esc_html__('Default filters applied', 'reset-ticketing'); ?>
            </div>
        <?php endif; ?>
        <form method="get" action="">
            <input type="hidden" name="page" value="reset-token-management">
            
            <div class="reset-filter-row">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Search by token code or email...', 'reset-ticketing'); ?>">
                
                <select name="status">
                    <option value=""><?php echo esc_html__('All Statuses', 'reset-ticketing'); ?></option>
                    <option value="active" <?php selected($status, 'active'); ?>><?php echo esc_html__('Active', 'reset-ticketing'); ?></option>
                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php echo esc_html__('Cancelled', 'reset-ticketing'); ?></option>
                    <option value="expired" <?php selected($status, 'expired'); ?>><?php echo esc_html__('Expired', 'reset-ticketing'); ?></option>
                </select>
                
                <select name="type">
                    <option value=""><?php echo esc_html__('All Types', 'reset-ticketing'); ?></option>
                    <option value="normal" <?php selected($type, 'normal'); ?>><?php echo esc_html__('Normal Token', 'reset-ticketing'); ?></option>
                    <option value="free_ticket" <?php selected($type, 'free_ticket'); ?>><?php echo esc_html__('Free Ticket Tokens', 'reset-ticketing'); ?></option>
                    <option value="polo_ordered" <?php selected($type, 'polo_ordered'); ?>><?php echo esc_html__('Polo Ordered Tokens', 'reset-ticketing'); ?></option>
                    <option value="sponsor" <?php selected($type, 'sponsor'); ?>><?php echo esc_html__('Sponsor Tokens', 'reset-ticketing'); ?></option>
                    <option value="invitation" <?php selected($type, 'invitation'); ?>><?php echo esc_html__('Invitation Tokens', 'reset-ticketing'); ?></option>
                </select>
                
                <select name="used">
                    <option value=""><?php echo esc_html__('All (Used/Unused)', 'reset-ticketing'); ?></option>
                    <option value="used" <?php selected($used, 'used'); ?>><?php echo esc_html__('Used Only', 'reset-ticketing'); ?></option>
                    <option value="unused" <?php selected($used, 'unused'); ?>><?php echo esc_html__('Unused Only', 'reset-ticketing'); ?></option>
                </select>
                
                <select name="sent">
                    <option value=""><?php echo esc_html__('All (Sent/Not Sent)', 'reset-ticketing'); ?></option>
                    <option value="sent" <?php selected($sent, 'sent'); ?>><?php echo esc_html__('Sent Only', 'reset-ticketing'); ?></option>
                    <option value="not_sent" <?php selected($sent, 'not_sent'); ?>><?php echo esc_html__('Not Sent Only', 'reset-ticketing'); ?></option>
                </select>
                
                <?php submit_button(__('Filter', 'reset-ticketing'), 'secondary', 'submit', false); ?>
                <button type="button" id="set-current-as-default" class="button"><?php echo esc_html__('Set Current as Default', 'reset-ticketing'); ?></button>
                <?php if ($search || $status || $type || $used || $sent): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=reset-token-management')); ?>" class="button"><?php echo esc_html__('Clear to Defaults', 'reset-ticketing'); ?></a>
                <?php endif; ?>
                <?php if ($is_default_applied): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('show_all' => '1'), admin_url('admin.php?page=reset-token-management'))); ?>" class="button"><?php echo esc_html__('Show All', 'reset-ticketing'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Default Filter Settings -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Default Filter Settings', 'reset-ticketing'); ?></h2>
        <p class="description"><?php echo esc_html__('Configure the default filters that will be applied when no filters are set.', 'reset-ticketing'); ?></p>
        
        <form method="post" id="default-filter-settings-form">
            <?php wp_nonce_field('reset_admin_nonce', 'reset_admin_nonce'); ?>
            <input type="hidden" name="action" value="save_default_filters">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Default Status', 'reset-ticketing'); ?></th>
                    <td>
                        <select name="default_status" id="default-status">
                            <option value=""><?php echo esc_html__('All Statuses', 'reset-ticketing'); ?></option>
                            <option value="active" <?php selected($default_settings['status'], 'active'); ?>><?php echo esc_html__('Active', 'reset-ticketing'); ?></option>
                            <option value="cancelled" <?php selected($default_settings['status'], 'cancelled'); ?>><?php echo esc_html__('Cancelled', 'reset-ticketing'); ?></option>
                            <option value="expired" <?php selected($default_settings['status'], 'expired'); ?>><?php echo esc_html__('Expired', 'reset-ticketing'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Default Type', 'reset-ticketing'); ?></th>
                    <td>
                        <select name="default_type" id="default-type">
                            <option value=""><?php echo esc_html__('All Types', 'reset-ticketing'); ?></option>
                            <option value="normal" <?php selected($default_settings['type'], 'normal'); ?>><?php echo esc_html__('Normal Token', 'reset-ticketing'); ?></option>
                            <option value="free_ticket" <?php selected($default_settings['type'], 'free_ticket'); ?>><?php echo esc_html__('Free Ticket Tokens', 'reset-ticketing'); ?></option>
                            <option value="polo_ordered" <?php selected($default_settings['type'], 'polo_ordered'); ?>><?php echo esc_html__('Polo Ordered Tokens', 'reset-ticketing'); ?></option>
                            <option value="sponsor" <?php selected($default_settings['type'], 'sponsor'); ?>><?php echo esc_html__('Sponsor Tokens', 'reset-ticketing'); ?></option>
                            <option value="invitation" <?php selected($default_settings['type'], 'invitation'); ?>><?php echo esc_html__('Invitation Tokens', 'reset-ticketing'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Default Usage', 'reset-ticketing'); ?></th>
                    <td>
                        <select name="default_used" id="default-used">
                            <option value=""><?php echo esc_html__('All (Used/Unused)', 'reset-ticketing'); ?></option>
                            <option value="used" <?php selected($default_settings['used'], 'used'); ?>><?php echo esc_html__('Used Only', 'reset-ticketing'); ?></option>
                            <option value="unused" <?php selected($default_settings['used'], 'unused'); ?>><?php echo esc_html__('Unused Only', 'reset-ticketing'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Default Sent Status', 'reset-ticketing'); ?></th>
                    <td>
                        <select name="default_sent" id="default-sent">
                            <option value=""><?php echo esc_html__('All (Sent/Not Sent)', 'reset-ticketing'); ?></option>
                            <option value="sent" <?php selected($default_settings['sent'], 'sent'); ?>><?php echo esc_html__('Sent Only', 'reset-ticketing'); ?></option>
                            <option value="not_sent" <?php selected($default_settings['sent'], 'not_sent'); ?>><?php echo esc_html__('Not Sent Only', 'reset-ticketing'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="save-default-filters" class="button button-primary"><?php echo esc_html__('Save Default Filters', 'reset-ticketing'); ?></button>
                <button type="button" id="reset-default-filters" class="button"><?php echo esc_html__('Reset to System Defaults', 'reset-ticketing'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Tokens Table -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Tokens List', 'reset-ticketing'); ?></h2>
        
        <?php if ($tokens_list): ?>
            <form method="post" action="" id="bulk-actions-form">
                <?php wp_nonce_field('reset_admin_action', 'reset_admin_nonce'); ?>
                <input type="hidden" name="action" value="bulk_cancel">
                
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="bulk_action" id="bulk-action">
                            <option value=""><?php echo esc_html__('Bulk Actions', 'reset-ticketing'); ?></option>
                            <option value="mark_sent"><?php echo esc_html__('Mark Selected as Sent', 'reset-ticketing'); ?></option>
                            <option value="cancel"><?php echo esc_html__('Cancel Selected', 'reset-ticketing'); ?></option>
                        </select>
                        <button type="button" id="bulk-cancel-btn" class="button"><?php echo esc_html__('Apply', 'reset-ticketing'); ?></button>
                    </div>
                    
                    <div class="alignright">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=reset-token-management&export=csv')); ?>" class="button"><?php echo esc_html__('Export CSV', 'reset-ticketing'); ?></a>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th><?php echo esc_html__('Token Code', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Sent To', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Type', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Status', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Used By', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Created', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Used Date', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Actions', 'reset-ticketing'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens_list as $token): ?>
                            <tr>
                                <td class="check-column">
                                    <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                                        <input type="checkbox" name="token_ids[]" value="<?php echo esc_attr($token['id']); ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="token-code-copy" 
                                          data-token="<?php echo esc_attr($token['token_code']); ?>" 
                                          title="<?php echo esc_attr__('Click to copy token code', 'reset-ticketing'); ?>">
                                        <?php echo esc_html($token['token_code']); ?>
                                    </code>
                                </td>
                                <td class="sent-to-column">
                                    <?php if (!empty($token['sent_to_name'])): ?>
                                        <div class="sent-display" data-token-id="<?php echo esc_attr($token['id']); ?>" title="<?php echo esc_attr__('Click to edit', 'reset-ticketing'); ?>">
                                            <div class="sent-info">
                                                <strong><?php echo esc_html($token['sent_to_name']); ?></strong>
                                                <?php if (!empty($token['sent_to_email']) && $token['sent_to_email'] !== 'devnooballiance@gmail.com'): ?>
                                                    <br><small><?php echo esc_html($token['sent_to_email']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($token['sent_to_phone'])): ?>
                                                    <br><small><?php echo esc_html($token['sent_to_phone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                                            <input type="text" 
                                                   class="sent-to-input" 
                                                   data-token-id="<?php echo esc_attr($token['id']); ?>"
                                                   placeholder="<?php echo esc_attr__('Type recipient name...', 'reset-ticketing'); ?>"
                                                   data-original-value="">
                                        <?php else: ?>
                                            <span class="reset-not-sent"><?php echo esc_html__('Not sent', 'reset-ticketing'); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="reset-token-type-<?php echo esc_attr($token['token_type']); ?>">
                                        <?php echo esc_html(ucfirst($token['token_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="reset-status-<?php echo esc_attr($token['status']); ?>">
                                        <?php echo esc_html(ucfirst($token['status'])); ?>
                                        <?php if ($token['is_used']): ?>
                                            <br><small><?php echo esc_html__('Used', 'reset-ticketing'); ?></small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($token['used_by_email']): ?>
                                        <strong><?php echo esc_html($token['used_by_name']); ?></strong><br>
                                        <small><?php echo esc_html($token['used_by_email']); ?></small><br>
                                        <small><?php echo esc_html($token['used_by_phone']); ?></small>
                                    <?php else: ?>
                                        <span class="reset-not-used"><?php echo esc_html__('Not used', 'reset-ticketing'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($token['created_at']))); ?></td>
                                <td>
                                    <?php if ($token['used_at']): ?>
                                        <?php echo esc_html(date('Y-m-d H:i', strtotime($token['used_at']))); ?>
                                    <?php else: ?>
                                        <span class="reset-not-used">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-column">
                                    <div class="action-buttons">
                                        <?php if ($token['status'] === 'active' && !$token['is_used']): ?>
                                            <?php if (empty($token['sent_to_email'])): ?>
                                                <button type="button" class="reset-button action-btn icon-only primary mark-sent mark-sent-btn" 
                                                        data-token-id="<?php echo esc_attr($token['id']); ?>" 
                                                        data-token-code="<?php echo esc_attr($token['token_code']); ?>"
                                                        data-tooltip="<?php echo esc_attr__('Mark as Sent', 'reset-ticketing'); ?>">✓
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="reset-button action-btn icon-only danger cancel cancel-token-btn" 
                                                    data-token-id="<?php echo esc_attr($token['id']); ?>" 
                                                    data-token-code="<?php echo esc_attr($token['token_code']); ?>"
                                                    data-tooltip="<?php echo esc_attr__('Cancel Token', 'reset-ticketing'); ?>">X
                                            </button>
                                        <?php elseif ($token['status'] === 'cancelled'): ?>
                                            <small class="status-text"><?php echo esc_html__('Cancelled', 'reset-ticketing'); ?></small>
                                            <?php if ($token['cancellation_reason']): ?>
                                                <small class="reason-text" title="<?php echo esc_attr($token['cancellation_reason']); ?>">
                                                    <?php echo esc_html(wp_trim_words($token['cancellation_reason'], 5)); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif (!empty($token['sent_to_email'])): ?>
                                            <small class="status-text"><?php echo esc_html__('Sent', 'reset-ticketing'); ?></small>
                                            <?php if ($token['sent_at']): ?>
                                                <small class="date-text"><?php echo esc_html(date('M j, Y', strtotime($token['sent_at']))); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo; Previous'),
                                'next_text' => __('Next &raquo;'),
                                'total' => $total_pages,
                                'current' => $page,
                                'add_args' => array(
                                    'search' => $search,
                                    'status' => $status,
                                    'type' => $type,
                                    'used' => $used,
                                    'sent' => $sent
                                )
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="reset-no-results">
                <p><?php echo esc_html__('No tokens found matching your criteria.', 'reset-ticketing'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Token Modal -->
<div id="cancel-token-modal" class="reset-modal" style="display:none;">
    <div class="reset-modal-content">
        <h3><?php echo esc_html__('Cancel Token', 'reset-ticketing'); ?></h3>
        <p><?php echo esc_html__('Are you sure you want to cancel this token?', 'reset-ticketing'); ?></p>
        <p><strong><?php echo esc_html__('Token:', 'reset-ticketing'); ?></strong> <span id="cancel-token-code"></span></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('reset_admin_action', 'reset_admin_nonce'); ?>
            <input type="hidden" name="action" value="cancel_token">
            <input type="hidden" name="token_id" id="cancel-token-id">
            
            <p>
                <label for="cancellation_reason"><?php echo esc_html__('Reason for cancellation:', 'reset-ticketing'); ?></label>
                <textarea name="cancellation_reason" id="cancellation_reason" rows="3" cols="50" placeholder="<?php echo esc_attr__('Optional reason for cancellation...', 'reset-ticketing'); ?>"></textarea>
            </p>
            
            <p>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Cancel Token', 'reset-ticketing'); ?></button>
                <button type="button" class="button cancel-modal-btn"><?php echo esc_html__('Close', 'reset-ticketing'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Bulk Cancel Modal -->
<div id="bulk-cancel-modal" class="reset-modal" style="display:none;">
    <div class="reset-modal-content">
        <h3><?php echo esc_html__('Bulk Cancel Tokens', 'reset-ticketing'); ?></h3>
        <p><?php echo esc_html__('Are you sure you want to cancel the selected tokens?', 'reset-ticketing'); ?></p>
        <p><strong><?php echo esc_html__('Number of tokens:', 'reset-ticketing'); ?></strong> <span id="bulk-cancel-count"></span></p>
        
        <p>
            <label for="bulk_cancellation_reason"><?php echo esc_html__('Reason for cancellation:', 'reset-ticketing'); ?></label>
            <textarea name="bulk_cancellation_reason" id="bulk_cancellation_reason" rows="3" cols="50" placeholder="<?php echo esc_attr__('Optional reason for cancellation...', 'reset-ticketing'); ?>"></textarea>
        </p>
        
        <p>
            <button type="button" id="confirm-bulk-cancel" class="button button-primary"><?php echo esc_html__('Cancel Tokens', 'reset-ticketing'); ?></button>
            <button type="button" class="button cancel-modal-btn"><?php echo esc_html__('Close', 'reset-ticketing'); ?></button>
        </p>
    </div>
</div>

<!-- Mark as Sent Modal -->
<div id="mark-sent-modal" class="reset-modal" style="display:none;">
    <div class="reset-modal-content">
        <h3><?php echo esc_html__('Mark Token as Sent', 'reset-ticketing'); ?></h3>
        <p><?php echo esc_html__('Please enter the recipient details for this token.', 'reset-ticketing'); ?></p>
        <p><strong><?php echo esc_html__('Token:', 'reset-ticketing'); ?></strong> <span id="mark-sent-token-code"></span></p>
        
        <form id="mark-sent-form">
            <input type="hidden" id="mark-sent-token-id">
            
            <p>
                <label for="recipient_name"><?php echo esc_html__('Recipient Name:', 'reset-ticketing'); ?> <span style="color:red;">*</span></label>
                <input type="text" id="recipient_name" name="recipient_name" style="width: 100%;" required>
            </p>
            
            <p>
                                            <label for="recipient_email"><?php echo esc_html__('Recipient Email:', 'reset-ticketing'); ?></label>
                                            <input type="email" id="recipient_email" name="recipient_email" style="width: 100%;" placeholder="optional">
            </p>
            
            <p>
                <label for="sent_notes"><?php echo esc_html__('Notes (optional):', 'reset-ticketing'); ?></label>
                <textarea id="sent_notes" name="notes" rows="3" style="width: 100%;" placeholder="<?php echo esc_attr__('Optional notes about sending this token...', 'reset-ticketing'); ?>"></textarea>
            </p>
            
            <p>
                <button type="button" id="confirm-mark-sent" class="button button-primary"><?php echo esc_html__('Mark as Sent', 'reset-ticketing'); ?></button>
                <button type="button" class="button cancel-modal-btn"><?php echo esc_html__('Close', 'reset-ticketing'); ?></button>
            </p>
        </form>
    </div>
</div>

<style>
.reset-stats-overview {
    margin: 20px 0;
}

.reset-stat-boxes {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.reset-stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
}

.reset-stat-box h3 {
    font-size: 32px;
    margin: 0 0 10px 0;
    color: #1d2327;
}

.reset-stat-box p {
    margin: 0;
    color: #646970;
}

.reset-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.reset-card h2 {
    margin-top: 0;
}

.reset-filter-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.reset-filter-row input,
.reset-filter-row select {
    margin-right: 10px;
}

.reset-token-type-master {
    color: #007cba;
    font-weight: bold;
}

.reset-token-type-invitation {
    color: #72aee6;
    font-weight: bold;
}

.reset-status-active {
    color: #00a32a;
    font-weight: bold;
}

.reset-status-cancelled {
    color: #d63638;
    font-weight: bold;
}

.reset-status-expired {
    color: #dba617;
    font-weight: bold;
}

.reset-not-used {
    color: #646970;
    font-style: italic;
}

.reset-not-sent {
    color: #d63638;
    font-style: italic;
}

.reset-token-sent {
    color: #00a32a;
    font-weight: bold;
}

.reset-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
}

.reset-modal-content {
    background: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 4px;
    width: 500px;
    max-width: 90%;
    position: relative;
}

.reset-no-results {
    text-align: center;
    padding: 40px;
    color: #646970;
}

/* Inline editing styles for Sent To column */
.sent-to-column {
    position: relative;
    min-width: 150px;
}

.sent-display {
    display: flex;
    align-items: flex-start;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.sent-display:hover {
    background-color: #f8f9fa;
    border-color: #ddd;
}

.sent-info {
    flex: 1;
    min-width: 0;
}

.sent-to-input {
    border: 1px solid #ddd;
    padding: 6px 8px;
    width: 100%;
    font-size: 13px;
    line-height: 1.4;
    border-radius: 3px;
    transition: all 0.3s ease;
    background: #fff;
}

.sent-to-input:focus {
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
    outline: none;
}

.sent-to-input.saving {
    background: #f8f9fa;
    border-color: #999;
    color: #666;
}

.saving-indicator {
    font-size: 11px;
    color: #666;
    font-style: italic;
    margin-left: 4px;
}

.inline-message {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    font-size: 11px;
    padding: 4px 6px;
    border-radius: 3px;
    margin-top: 2px;
    z-index: 1000;
    text-align: center;
}

.inline-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.inline-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Ensure the input field is visible and properly sized */
.sent-to-input.editing {
    min-height: 28px;
    box-sizing: border-box;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sent-display {
        padding: 6px;
    }
}

/* Default Filter Indicator */
.reset-default-indicator {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    color: #1976d2;
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    font-size: 13px;
}

.reset-default-indicator .dashicons {
    margin-right: 6px;
    color: #2196f3;
}

/* Default Filter Settings */
#default-filter-settings-form {
    max-width: 600px;
}

#default-filter-settings-form .form-table th {
    width: 200px;
}

#default-filter-settings-form .submit {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

#save-default-filters {
    margin-right: 10px;
}

/* Token code copy functionality */
.token-code-copy {
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    padding: 4px 8px;
    border-radius: 4px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.token-code-copy:hover {
    background: #e3f2fd;
    border-color: #2196f3;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
}

.token-code-copy:active {
    transform: translateY(0);
    background: #bbdefb;
}

.token-code-copy.copied {
    background: #e8f5e8;
    border-color: #4caf50;
    color: #2e7d32;
}

.copy-feedback {
    position: absolute;
    top: -35px;
    left: 50%;
    transform: translateX(-50%);
    background: #2e7d32;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.copy-feedback.show {
    opacity: 1;
}

.copy-feedback::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -4px;
    border-width: 4px;
    border-style: solid;
    border-color: #2e7d32 transparent transparent transparent;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle cancel token button
    $('.cancel-token-btn').click(function() {
        var tokenId = $(this).data('token-id');
        var tokenCode = $(this).data('token-code');
        
        $('#cancel-token-id').val(tokenId);
        $('#cancel-token-code').text(tokenCode);
        $('#cancel-token-modal').show();
    });
    
    // Handle bulk cancel button
    $('#bulk-cancel-btn').click(function() {
        var selected = $('input[name="token_ids[]"]:checked');
        if (selected.length === 0) {
            alert('Please select tokens to cancel.');
            return;
        }
        
        $('#bulk-cancel-count').text(selected.length);
        $('#bulk-cancel-modal').show();
    });
    
    // Handle confirm bulk cancel
    $('#confirm-bulk-cancel').click(function() {
        var reason = $('#bulk_cancellation_reason').val();
        $('<input>').attr({
            type: 'hidden',
            name: 'bulk_cancellation_reason',
            value: reason
        }).appendTo('#bulk-actions-form');
        
        $('#bulk-actions-form').submit();
    });
    
    // Handle modal close
    $('.cancel-modal-btn').click(function() {
        $('.reset-modal').hide();
    });
    
    // Handle select all checkbox
    $('#cb-select-all').change(function() {
        $('input[name="token_ids[]"]').prop('checked', this.checked);
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).hasClass('reset-modal')) {
            $('.reset-modal').hide();
        }
    });
    
    // Default Filter Settings Functionality
    $('#save-default-filters').click(function() {
        var settings = {
            status: $('#default-status').val(),
            type: $('#default-type').val(),
            used: $('#default-used').val(),
            sent: $('#default-sent').val(),
            search: ''
        };
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_save_default_filters',
                nonce: resetAdminAjax.nonce,
                ...settings
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error saving default filters.');
            }
        });
    });
    
    $('#reset-default-filters').click(function() {
        if (!confirm('Are you sure you want to reset to system defaults?')) {
            return;
        }
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_reset_default_filters',
                nonce: resetAdminAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error resetting default filters.');
            }
        });
    });
    
    $('#set-current-as-default').click(function() {
        var settings = {
            status: $('select[name="status"]').val(),
            type: $('select[name="type"]').val(),
            used: $('select[name="used"]').val(),
            sent: $('select[name="sent"]').val(),
            search: $('input[name="search"]').val()
        };
        
        $.ajax({
            url: resetAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_set_current_as_default',
                nonce: resetAdminAjax.nonce,
                ...settings
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error setting current filters as default.');
            }
        });
    });
});
</script> 