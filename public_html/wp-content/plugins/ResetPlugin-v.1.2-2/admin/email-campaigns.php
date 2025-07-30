<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is admin
if (!is_user_logged_in()) {
    $login_url = wp_login_url(admin_url('admin.php?page=reset-email-campaigns'));
    wp_redirect($login_url);
    exit;
}

// Check if logged-in user has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('Access denied. This page is for administrators only.');
}

// Initialize required classes
$campaigns = ResetEmailCampaigns::getInstance();
$db = ResetDatabase::getInstance();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'reset_email_campaign_action')) {
        $message = 'Security check failed.';
        $message_type = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create_campaign':
                $campaign_data = array(
                    'subject' => sanitize_text_field($_POST['subject']),
                    'template' => sanitize_text_field($_POST['template']),
                    'content' => wp_kses_post($_POST['content']),
                    'filters' => $_POST['filters'] ?? array(),
                    'custom_emails' => sanitize_textarea_field($_POST['custom_emails']),
                    'scheduled_date' => sanitize_text_field($_POST['scheduled_date']),
                    'scheduled_time' => sanitize_text_field($_POST['scheduled_time'])
                );
                
                $campaign_id = $campaigns->create_campaign($campaign_data);
                if ($campaign_id) {
                    $message = 'Campaign created successfully! Campaign ID: ' . $campaign_id;
                    $message_type = 'success';
                } else {
                    $message = 'Failed to create campaign. Please check all required fields.';
                    $message_type = 'error';
                }
                break;
                
            case 'schedule_campaign':
                $campaign_id = (int)$_POST['campaign_id'];
                $send_time = '';
                
                if (!empty($_POST['scheduled_date']) && !empty($_POST['scheduled_time'])) {
                    $send_time = $_POST['scheduled_date'] . ' ' . $_POST['scheduled_time'];
                }
                
                if ($campaigns->schedule_campaign($campaign_id, $send_time)) {
                    $message = 'Campaign scheduled successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to schedule campaign.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get existing campaigns
$existing_campaigns = get_posts(array(
    'post_type' => 'reset_email_campaign',
    'post_status' => array('draft', 'publish'),
    'numberposts' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get available templates
$templates = $campaigns->get_templates();
?>

<div class="wrap reset-email-campaigns">
    <h1>📧 Email Campaign Management</h1>
    <p class="description">Create and manage email campaigns for RESET 2025 attendees. Filter recipients by token types, payment status, and more.</p>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="reset-admin-container">
        <!-- Campaign Creation Form -->
        <div class="reset-card">
            <h2>🚀 Create New Campaign</h2>
            
            <form method="post" id="campaignForm">
                <?php wp_nonce_field('reset_email_campaign_action'); ?>
                <input type="hidden" name="action" value="create_campaign">
                
                <!-- Basic Campaign Info -->
                <div class="form-section">
                    <h3>📋 Campaign Details</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="subject">Email Subject *</label>
                            <input type="text" id="subject" name="subject" required 
                                   placeholder="e.g., 🎮 RESET 2025 - Gaming Equipment Reminder!" 
                                   class="widefat">
                            <p class="description">Use {{RECIPIENT_NAME}} and {{EVENT_NAME}} for personalization</p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="template">Email Template *</label>
                            <select id="template" name="template" required class="widefat">
                                <option value="">Select a template...</option>
                                <?php foreach ($templates as $key => $template): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($template['name']); ?> - <?php echo esc_html($template['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" id="customContentRow" style="display: none;">
                        <div class="form-field">
                            <label for="content">Custom Email Content</label>
                            <?php 
                            wp_editor('', 'content', array(
                                'textarea_name' => 'content',
                                'media_buttons' => false,
                                'textarea_rows' => 10,
                                'teeny' => true
                            )); 
                            ?>
                            <p class="description">Available variables: {{RECIPIENT_NAME}}, {{RECIPIENT_EMAIL}}, {{EVENT_NAME}}, {{EVENT_DATE}}, {{EVENT_TIME}}, {{VENUE_ADDRESS}}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recipient Filtering -->
                <div class="form-section">
                    <h3>🎯 Recipient Filtering</h3>
                    
                    <div class="filter-grid">
                        <!-- Token Types -->
                        <div class="filter-group">
                            <h4>🔑 Token Types</h4>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="filters[token_types][]" value="free_ticket"> Free Tickets</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="polo_ordered"> Polo Ordered</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="normal"> Normal Tokens</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="master"> Invitation Tokens</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="general_early"> General Early</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="general_late"> Late Bird</label>
                                <label><input type="checkbox" name="filters[token_types][]" value="general_very_late"> Very Late Bird</label>
                            </div>
                        </div>
                        
                        <!-- Payment Status -->
                        <div class="filter-group">
                            <h4>💳 Payment Status</h4>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="filters[payment_status][]" value="completed" checked> Completed</label>
                                <label><input type="checkbox" name="filters[payment_status][]" value="pending"> Pending</label>
                            </div>
                        </div>
                        
                        <!-- Add-ons Filter -->
                        <div class="filter-group">
                            <h4>🎮 Add-ons</h4>
                            <div class="radio-group">
                                <label><input type="radio" name="filters[has_addons]" value=""> All Users</label>
                                <label><input type="radio" name="filters[has_addons]" value="yes"> Has Add-ons</label>
                                <label><input type="radio" name="filters[has_addons]" value="no"> No Add-ons</label>
                            </div>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="filter-group">
                            <h4>📅 Purchase Date</h4>
                            <div class="date-range">
                                <label>From: <input type="date" name="filters[date_from]" class="date-input"></label>
                                <label>To: <input type="date" name="filters[date_to]" class="date-input"></label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Recipients Button -->
                    <div class="preview-section">
                        <button type="button" id="previewRecipients" class="button button-secondary">
                            👥 Preview Recipients
                        </button>
                        <div id="recipientPreview" class="recipient-preview"></div>
                    </div>
                </div>
                
                <!-- Manual Email Addresses -->
                <div class="form-section">
                    <h3>📧 Additional Email Addresses</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="custom_emails">Manual Email List</label>
                            <textarea id="custom_emails" name="custom_emails" rows="6" class="widefat" 
                                      placeholder="Enter email addresses, one per line:&#10;john@example.com&#10;Jane Doe &lt;jane@example.com&gt;&#10;admin@company.com"></textarea>
                            <p class="description">Enter one email per line. Format: email@domain.com or "Name &lt;email@domain.com&gt;"</p>
                        </div>
                    </div>
                </div>
                
                <!-- Scheduling -->
                <div class="form-section">
                    <h3>⏰ Scheduling</h3>
                    <div class="scheduling-options">
                        <label class="radio-option">
                            <input type="radio" name="send_option" value="now" checked>
                            <span>📤 Send Immediately</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="send_option" value="schedule">
                            <span>📅 Schedule for Later</span>
                        </label>
                    </div>
                    
                    <div id="scheduleFields" class="schedule-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-field half">
                                <label for="scheduled_date">Date</label>
                                <input type="date" id="scheduled_date" name="scheduled_date" class="widefat">
                            </div>
                            <div class="form-field half">
                                <label for="scheduled_time">Time</label>
                                <input type="time" id="scheduled_time" name="scheduled_time" class="widefat">
                            </div>
                        </div>
                        <p class="description">⏰ Time zone: Asia/Colombo (Sri Lanka Time)</p>
                    </div>
                </div>
                
                <!-- Submit Section -->
                <div class="form-section submit-section">
                    <div class="submit-buttons">
                        <button type="button" id="sendTestEmail" class="button button-secondary">
                            ✉️ Send Test Email
                        </button>
                        <button type="submit" class="button button-primary">
                            🚀 Create & Send Campaign
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Existing Campaigns -->
        <div class="reset-card">
            <h2>📊 Campaign History</h2>
            
            <?php if (empty($existing_campaigns)): ?>
                <div class="no-campaigns">
                    <p>No campaigns created yet. Create your first campaign above!</p>
                </div>
            <?php else: ?>
                <div class="campaigns-table-container">
                    <table class="campaigns-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Recipients</th>
                                <th>Success Rate</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_campaigns as $campaign): ?>
                                <?php
                                $campaign_status = get_post_meta($campaign->ID, '_campaign_status', true) ?: 'draft';
                                $stats = $campaigns->get_campaign_statistics($campaign->ID);
                                ?>
                                <tr class="campaign-row" data-campaign-id="<?php echo esc_attr($campaign->ID); ?>">
                                    <td>
                                        <strong><?php echo esc_html($campaign->post_title); ?></strong>
                                        <div class="campaign-meta">
                                            Template: <?php echo esc_html(get_post_meta($campaign->ID, '_campaign_template', true)); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($campaign_status); ?>">
                                            <?php echo esc_html(ucfirst($campaign_status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($stats)): ?>
                                            <div class="recipient-stats">
                                                <div>Total: <?php echo (int)$stats['total_recipients']; ?></div>
                                                <div>Sent: <?php echo (int)$stats['sent']; ?></div>
                                                <div>Failed: <?php echo (int)$stats['failed']; ?></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not sent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($stats) && $stats['total_recipients'] > 0): ?>
                                            <div class="success-rate">
                                                <?php echo esc_html($stats['success_rate']); ?>%
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo esc_attr($stats['success_rate']); ?>%"></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date('M j, Y g:i A', strtotime($campaign->post_date))); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($campaign_status === 'draft'): ?>
                                                <button type="button" class="button button-small schedule-campaign" 
                                                        data-campaign-id="<?php echo esc_attr($campaign->ID); ?>">
                                                    📤 Send Now
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="button button-small view-stats" 
                                                    data-campaign-id="<?php echo esc_attr($campaign->ID); ?>">
                                                📊 Stats
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Campaign Stats Modal -->
<div id="statsModal" class="reset-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📊 Campaign Statistics</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="statsModalBody">
            <p>Loading statistics...</p>
        </div>
    </div>
</div>

<style>
/* Email Campaigns Specific Styles */
.reset-email-campaigns {
    background: #f1f1f1;
    margin: 20px 0;
}

.reset-admin-container {
    display: grid;
    gap: 20px;
    max-width: 1200px;
}

.reset-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.reset-card h2 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #f9c613;
    padding-bottom: 10px;
}

.form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 4px solid #f9c613;
}

.form-section h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.2em;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-field {
    flex: 1;
}

.form-field.half {
    flex: 0 0 calc(50% - 10px);
}

.form-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.form-field input,
.form-field select,
.form-field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-field .description {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    font-style: italic;
}

/* Filter Grid */
.filter-grid {
    display: flex;
    /* grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); */
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group {
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.filter-group h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 1em;
}

.checkbox-group label,
.radio-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: normal;
    cursor: pointer;
}

.checkbox-group input,
.radio-group input {
    margin-right: 8px;
    width: auto;
}

.date-range {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.date-input {
    width: 100%;
}

/* Preview Section */
.preview-section {
    margin-top: 20px;
    text-align: center;
}

.recipient-preview {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: none;
}

.recipient-count {
    font-size: 18px;
    font-weight: bold;
    color: #f9c613;
    margin-bottom: 10px;
}

.recipient-list {
    text-align: left;
    max-height: 200px;
    overflow-y: auto;
}

.recipient-item {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

/* Scheduling */
.scheduling-options {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 10px 15px;
    background: #f9f9f9;
    border: 2px solid #ddd;
    border-radius: 6px;
    transition: all 0.2s;
}

.radio-option:hover {
    background: #f0f0f0;
}

.radio-option input[type="radio"]:checked + span {
    color: #f9c613;
    font-weight: bold;
}

.schedule-fields {
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

/* Submit Section */
.submit-section {
    background: #fff !important;
    border: 2px solid #f9c613 !important;
    text-align: center;
}

.submit-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.submit-buttons .button {
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 6px;
}

/* Campaigns Table */
.campaigns-table-container {
    overflow-x: auto;
}

.campaigns-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.campaigns-table th,
.campaigns-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.campaigns-table th {
    background: #f9f9f9;
    font-weight: 600;
    color: #333;
}

.campaigns-table tr:hover {
    background: #f5f5f5;
}

.campaign-meta {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-draft { background: #ffeaa7; color: #2d3748; }
.status-scheduled { background: #74b9ff; color: white; }
.status-sending { background: #fdcb6e; color: #2d3748; }
.status-completed { background: #00b894; color: white; }
.status-failed { background: #e17055; color: white; }

.recipient-stats {
    font-size: 12px;
    line-height: 1.4;
}

.success-rate {
    font-size: 13px;
    font-weight: bold;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: #eee;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 3px;
}

.progress-fill {
    height: 100%;
    background: #00b894;
    transition: width 0.3s ease;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.action-buttons .button {
    font-size: 11px;
    padding: 4px 8px;
}

.text-muted {
    color: #999;
    font-style: italic;
}

.no-campaigns {
    text-align: center;
    padding: 40px;
    color: #666;
}

/* Modal Styles */
.reset-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .scheduling-options {
        flex-direction: column;
    }
    
    .submit-buttons {
        flex-direction: column;
    }
    
    .action-buttons {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Template selection handler
    $('#template').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customContentRow').show();
        } else {
            $('#customContentRow').hide();
        }
    });
    
    // Schedule option handler
    $('input[name="send_option"]').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#scheduleFields').show();
        } else {
            $('#scheduleFields').hide();
        }
    });
    
    // Preview recipients
    $('#previewRecipients').on('click', function() {
        const filters = {};
        const customEmails = $('#custom_emails').val();
        
        // Collect filter data
        $('input[name="filters[token_types][]"]:checked').each(function() {
            if (!filters.token_types) filters.token_types = [];
            filters.token_types.push($(this).val());
        });
        
        $('input[name="filters[payment_status][]"]:checked').each(function() {
            if (!filters.payment_status) filters.payment_status = [];
            filters.payment_status.push($(this).val());
        });
        
        const hasAddons = $('input[name="filters[has_addons]"]:checked').val();
        if (hasAddons) filters.has_addons = hasAddons;
        
        const dateFrom = $('input[name="filters[date_from]"]').val();
        const dateTo = $('input[name="filters[date_to]"]').val();
        if (dateFrom) filters.date_from = dateFrom;
        if (dateTo) filters.date_to = dateTo;
        
        // AJAX call
        $(this).prop('disabled', true).text('Loading...');
        
        $.post(ajaxurl, {
            action: 'reset_preview_recipients',
            nonce: '<?php echo wp_create_nonce('reset_email_nonce'); ?>',
            filters: filters,
            custom_emails: customEmails
        }, function(response) {
            $('#previewRecipients').prop('disabled', false).text('👥 Preview Recipients');
            
            if (response.success) {
                let html = '<div class="recipient-count">📊 Total Recipients: ' + response.data.count + '</div>';
                
                if (response.data.recipients.length > 0) {
                    html += '<div class="recipient-list"><strong>Preview (first 10):</strong>';
                    response.data.recipients.forEach(function(recipient) {
                        html += '<div class="recipient-item">' + 
                               (recipient.name || 'Unknown') + ' &lt;' + recipient.email + '&gt; ' +
                               '<span style="font-size: 11px; color: #666;">(' + (recipient.source || 'filtered') + ')</span></div>';
                    });
                    html += '</div>';
                } else {
                    html += '<p>No recipients found with current filters.</p>';
                }
                
                $('#recipientPreview').html(html).show();
            } else {
                $('#recipientPreview').html('<p style="color: red;">Error: ' + (response.data.message || 'Unknown error') + '</p>').show();
            }
        }).fail(function() {
            $('#previewRecipients').prop('disabled', false).text('👥 Preview Recipients');
            $('#recipientPreview').html('<p style="color: red;">Error loading preview.</p>').show();
        });
    });
    
    // Send test email
    $('#sendTestEmail').on('click', function() {
        // First create a draft campaign
        const formData = new FormData(document.getElementById('campaignForm'));
        formData.set('action', 'create_campaign');
        
        $(this).prop('disabled', true).text('Sending...');
        
        // We need to create campaign first, then send test
        alert('Please save the campaign first, then use the test email feature from the campaign list.');
        $(this).prop('disabled', false).text('✉️ Send Test Email');
    });
    
    // View campaign stats
    $('.view-stats').on('click', function() {
        const campaignId = $(this).data('campaign-id');
        
        $('#statsModalBody').html('<p>Loading statistics...</p>');
        $('#statsModal').show();
        
        $.post(ajaxurl, {
            action: 'reset_get_campaign_stats',
            nonce: '<?php echo wp_create_nonce('reset_email_nonce'); ?>',
            campaign_id: campaignId
        }, function(response) {
            if (response.success) {
                const stats = response.data;
                let html = '<div class="stats-grid">';
                html += '<div class="stat-item"><strong>Total Recipients:</strong> ' + stats.total_recipients + '</div>';
                html += '<div class="stat-item"><strong>Successfully Sent:</strong> ' + stats.sent + '</div>';
                html += '<div class="stat-item"><strong>Failed:</strong> ' + stats.failed + '</div>';
                html += '<div class="stat-item"><strong>Pending:</strong> ' + stats.pending + '</div>';
                html += '<div class="stat-item"><strong>Success Rate:</strong> ' + stats.success_rate + '%</div>';
                html += '<div class="stat-item"><strong>Status:</strong> ' + stats.status + '</div>';
                if (stats.created_at) html += '<div class="stat-item"><strong>Created:</strong> ' + stats.created_at + '</div>';
                if (stats.completed_at) html += '<div class="stat-item"><strong>Completed:</strong> ' + stats.completed_at + '</div>';
                html += '</div>';
                
                $('#statsModalBody').html(html);
            } else {
                $('#statsModalBody').html('<p style="color: red;">Error loading stats: ' + response.data.message + '</p>');
            }
        }).fail(function() {
            $('#statsModalBody').html('<p style="color: red;">Error loading statistics.</p>');
        });
    });
    
    // Close modal
    $('.modal-close, .reset-modal').on('click', function(e) {
        if (e.target === this) {
            $('#statsModal').hide();
        }
    });
    
    // Schedule campaign
    $('.schedule-campaign').on('click', function() {
        const campaignId = $(this).data('campaign-id');
        
        if (confirm('Send this campaign immediately?')) {
            $(this).prop('disabled', true).text('Sending...');
            
            // Create a hidden form to submit
            const form = $('<form method="post" style="display: none;">' +
                          '<input name="_wpnonce" value="<?php echo wp_create_nonce('reset_email_campaign_action'); ?>">' +
                          '<input name="action" value="schedule_campaign">' +
                          '<input name="campaign_id" value="' + campaignId + '">' +
                          '</form>');
            
            $('body').append(form);
            form.submit();
        }
    });
});
</script> 