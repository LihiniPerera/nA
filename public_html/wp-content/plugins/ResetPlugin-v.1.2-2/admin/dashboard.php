<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin = ResetAdmin::getInstance();
$core = ResetCore::getInstance();
$dashboard_data = $admin->get_dashboard_data();
$token_stats = $admin->get_token_statistics();

// Get capacity and environment info
$capacity_manager = ResetCapacity::getInstance();
$capacity_status = $capacity_manager->get_capacity_status();
$environment_info = $capacity_manager->get_environment_info();

// Get ticket types for name mapping
$ticket_types = $core->get_ticket_pricing();

// Helper function to get ticket name from ticket key
function get_ticket_name($ticket_key, $ticket_types) {
    return isset($ticket_types[$ticket_key]) ? $ticket_types[$ticket_key]['name'] : $ticket_key;
}
?>

<div class="wrap">
    <h1>RESET Ticketing Dashboard</h1>
    
    <!-- Capacity Status Widget -->
    <div class="reset-dashboard-widgets">
        <div class="reset-widget capacity-widget">
            <h3>Event Capacity</h3>
            <div class="capacity-meter">
                <div class="capacity-bar">
                    <div class="capacity-fill" style="width: <?php echo round($capacity_status['capacity_percentage'], 1); ?>%"></div>
                </div>
                <div class="capacity-text">
                    <?php echo $capacity_status['current_tickets']; ?> / <?php echo $capacity_status['max_capacity']; ?> tickets sold
                    (<?php echo round($capacity_status['capacity_percentage'], 1); ?>%)
                </div>
            </div>
            <?php if ($capacity_status['is_near_capacity']): ?>
                <div class="capacity-alert">
                    ⚠️ Nearing capacity! Consider cancelling unused tokens.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Revenue Widget -->
        <div class="reset-widget revenue-widget">
            <h3>Total Revenue</h3>
            <div class="revenue-amount">
                Rs. <?php echo number_format($dashboard_data['statistics']['total_revenue'], 2); ?>
            </div>
            <div class="revenue-subtitle">
                From <?php echo $dashboard_data['statistics']['completed_purchases']; ?> completed purchases
            </div>
        </div>
        
        <!-- Token Stats Widget -->
        <div class="reset-widget tokens-widget">
            <h3>Token Statistics</h3>
            <div class="token-stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $token_stats['active_tokens']; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $token_stats['used_tokens']; ?></div>
                    <div class="stat-label">Used</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $token_stats['cancelled_tokens']; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
        </div>
        
        <!-- Environment Info Widget -->
        <div class="reset-widget environment-widget">
            <h3>Environment Info</h3>
            <div class="environment-info">
                <div class="env-status">
                    <span class="env-label"><?php echo esc_html($environment_info['environment']); ?></span>
                    <?php if ($environment_info['is_local_development']): ?>
                        <span class="env-badge local">🧪 LOCAL</span>
                    <?php else: ?>
                        <span class="env-badge production">🚀 LIVE</span>
                    <?php endif; ?>
                </div>
                <div class="env-details">
                    <small><?php echo esc_html($environment_info['current_host']); ?></small>
                </div>
                <div class="env-note">
                    <small><?php echo esc_html($environment_info['testing_note']); ?></small>
                </div>
                <div class="env-thresholds">
                    <small><strong>Capacity:</strong> <?php echo esc_html($environment_info['capacity_limits']['current_attendees']); ?> / <?php echo esc_html($environment_info['capacity_limits']['max_capacity']); ?></small><br>
                    <small><strong>Thresholds:</strong> <?php echo esc_html($environment_info['threshold_description']); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Purchases -->
    <div class="reset-dashboard-section">
        <h2>Recent Purchases</h2>
        <div class="reset-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Ticket Type</th>
                        <th>Ticket Price</th>
                        <th>Addon Total</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dashboard_data['recent_purchases'])): ?>
                        <?php foreach ($dashboard_data['recent_purchases'] as $purchase): ?>
                            <tr>
                                <td><?php echo date('M j, Y H:i', strtotime($purchase['created_at'])); ?></td>
                                <td><?php echo esc_html($purchase['purchaser_name']); ?></td>
                                <td><?php echo esc_html($purchase['purchaser_email']); ?></td>
                                <td><?php echo esc_html(get_ticket_name($purchase['ticket_type'], $ticket_types)); ?></td>
                                <td>Rs. <?php echo number_format($purchase['ticket_price'], 2); ?></td>
                                <td>Rs. <?php echo number_format($purchase['addon_total'], 2); ?></td>
                                <td>Rs. <?php echo number_format($purchase['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $purchase['payment_status']; ?>">
                                        <?php echo ucfirst($purchase['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No purchases yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sales by Ticket Type -->
    <div class="reset-dashboard-section">
        <h2>Sales by Ticket Type</h2>
        <div class="sales-chart-container">
            <?php if (!empty($dashboard_data['sales_by_type'])): ?>
                <?php foreach ($dashboard_data['sales_by_type'] as $sale): ?>
                    <div class="sales-item">
                        <div class="sales-item-header">
                            <span class="ticket-type"><?php echo esc_html(get_ticket_name($sale['ticket_type'], $ticket_types)); ?></span>
                            <span class="sales-count"><?php echo $sale['count']; ?> sold</span>
                        </div>
                        <div class="sales-revenue">
                            Rs. <?php echo number_format($sale['revenue'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No sales data available yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="reset-dashboard-section">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <button class="button button-primary" onclick="openGenerateTokensModal()">
                Generate Tokens
            </button>
            <a href="<?php echo admin_url('admin.php?page=reset-token-management'); ?>" class="button">
                Manage Tokens
            </a>
            <a href="<?php echo admin_url('admin.php?page=reset-sales-report'); ?>" class="button">
                View Sales Report
            </a>
            <button class="button" onclick="exportData()">
                Export Data
            </button>
        </div>
    </div>
</div>

<!-- Generate Tokens Modal -->
<div id="generateTokensModal" class="reset-modal" style="display: none;">
    <div class="reset-modal-content">
        <div class="reset-modal-header">
            <h3>Generate Keys</h3>
            <button class="reset-modal-close" onclick="closeGenerateTokensModal()">&times;</button>
        </div>
        <div class="reset-modal-body">
            <form id="generateTokensForm">
                <label for="tokenType">Token Type:</label>
                <select id="tokenType" name="token_type" required style="width: 100%; margin-bottom: 15px; padding: 8px;">
                    <option value="normal">Normal Keys</option>
                    <option value="free_ticket">Free Ticket Keys</option>
                    <option value="polo_ordered">Polo Ordered Keys</option>
                    <option value="sponsor">Sponsor Keys</option>
                </select>
                
                <label for="tokenCount">Number of keys to generate:</label>
                <input type="number" id="tokenCount" min="1" value="10" required style="width: 100%; padding: 8px;">
                <p class="description">Generate keys that can be distributed to potential attendees.</p>
            </form>
        </div>
        <div class="reset-modal-footer">
            <button type="button" class="button" onclick="closeGenerateTokensModal()">Cancel</button>
            <button type="button" class="button button-primary" onclick="generateTokens()">Generate Keys</button>
        </div>
    </div>
</div>

<style>
.reset-dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.reset-widget {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.reset-widget h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.capacity-meter {
    margin: 15px 0;
}

.capacity-bar {
    background: #f0f0f0;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
}

.capacity-fill {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    transition: width 0.3s ease;
}

.capacity-text {
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
}

.capacity-alert {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 10px;
    margin-top: 15px;
    color: #856404;
}

.revenue-amount {
    font-size: 32px;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 5px;
}

.revenue-subtitle {
    color: #666;
    font-size: 14px;
}

.token-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    text-align: center;
}

.stat-item {
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.environment-info {
    text-align: center;
}

.env-status {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.env-label {
    font-size: 16px;
    font-weight: bold;
    color: #333;
}

.env-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.env-badge.local {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.env-badge.production {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.env-details {
    margin-bottom: 10px;
    color: #666;
}

.env-note {
    margin-bottom: 10px;
    color: #666;
    font-style: italic;
}

.env-thresholds {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    color: #495057;
}

.reset-dashboard-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.reset-dashboard-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.sales-chart-container {
    display: grid;
    gap: 15px;
}

.sales-item {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
}

.sales-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.ticket-type {
    font-weight: bold;
    color: #333;
}

.sales-count {
    color: #666;
    font-size: 14px;
}

.sales-revenue {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.reset-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.reset-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    margin: 50px auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.reset-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.reset-modal-header h3 {
    margin: 0;
}

.reset-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.reset-modal-body {
    padding: 20px;
}

.reset-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.reset-modal-footer .button {
    margin-left: 10px;
}

#generateTokensForm label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

#generateTokensForm input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
function openGenerateTokensModal() {
    document.getElementById('generateTokensModal').style.display = 'block';
}

function closeGenerateTokensModal() {
    document.getElementById('generateTokensModal').style.display = 'none';
}

function generateTokens() {
    const count = document.getElementById('tokenCount').value;
    const tokenType = document.getElementById('tokenType').value;
    
    if (!count || count < 1) {
        alert('Please enter a valid number greater than 0.');
        return;
    }
    
    fetch(resetAdminAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_generate_tokens',
            count: count,
            token_type: tokenType, // Pass token type
            nonce: resetAdminAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            closeGenerateTokensModal();
            location.reload();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        alert('Network error: ' + error.message);
    });
}

function exportData() {
    // In production, this would trigger a CSV/Excel export
    alert('Data export functionality would be implemented here.');
}

// Auto-refresh dashboard data every 30 seconds
setInterval(function() {
    fetch(resetAdminAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'reset_get_dashboard_data',
            nonce: resetAdminAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update dashboard elements with new data
            updateDashboardData(data.data);
        }
    })
    .catch(error => {
        console.error('Auto-refresh error:', error);
    });
}, 30000);

function updateDashboardData(data) {
    // Update capacity percentage
    const capacityFill = document.querySelector('.capacity-fill');
    const capacityText = document.querySelector('.capacity-text');
    
    if (capacityFill && data.capacity) {
        const percentage = Math.round(data.capacity.capacity_percentage * 10) / 10; // Round to 1 decimal
        capacityFill.style.width = percentage + '%';
        capacityText.textContent = `${data.capacity.current_tickets} / ${data.capacity.max_capacity} tickets sold (${percentage}%)`;
    }
}
</script> 