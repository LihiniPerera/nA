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
$core = ResetCore::getInstance();
$db = ResetDatabase::getInstance();
$capacity = ResetCapacity::getInstance();

// Get capacity status from database
$capacity_status = $capacity->get_capacity_status();

// Get date range filter
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Get statistics
$stats = $db->get_statistics();
$sales_by_type = $db->get_sales_by_ticket_type();
$recent_purchases = $db->get_recent_purchases(50);

// Calculate revenue by date
$revenue_by_date = $db->get_revenue_by_date_range($start_date, $end_date);

// Get conversion rates
$total_tokens = $stats['total_tokens'];
$used_tokens = $stats['used_tokens'];
$conversion_rate = $total_tokens > 0 ? round(($used_tokens / $total_tokens) * 100, 2) : 0;

// Calculate capacity utilization using new capacity system
$capacity_used = $capacity_status['current_tickets'];
$max_capacity = $capacity_status['max_capacity'];
$capacity_remaining = $capacity_status['remaining_capacity'];
$capacity_percentage = round($capacity_status['capacity_percentage'], 2);

// Get ticket types for name mapping
$ticket_types = $core->get_ticket_pricing();

// Helper function to get ticket name from ticket key
function get_ticket_name_sr($ticket_key, $ticket_types) {
    return isset($ticket_types[$ticket_key]) ? $ticket_types[$ticket_key]['name'] : $ticket_key;
}

?>

<div class="wrap">
    <h1><?php echo esc_html__('Sales Report', 'reset-ticketing'); ?></h1>
    
    <!-- Key Metrics Overview -->
    <div class="reset-metrics-overview">
        <div class="reset-metric-boxes">
            <div class="reset-metric-box highlight">
                <h3>Rs <?php echo esc_html(number_format($stats['total_revenue'], 2)); ?></h3>
                <p><?php echo esc_html__('Total Revenue', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-metric-box">
                <h3><?php echo esc_html($stats['completed_purchases']); ?></h3>
                <p><?php echo esc_html__('Tickets Sold', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-metric-box">
                <h3><?php echo esc_html($conversion_rate); ?>%</h3>
                <p><?php echo esc_html__('Conversion Rate', 'reset-ticketing'); ?></p>
            </div>
            <div class="reset-metric-box">
                <h3><?php echo esc_html($capacity_percentage); ?>%</h3>
                <p><?php echo esc_html__('Capacity Used', 'reset-ticketing'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Capacity Progress -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Event Capacity', 'reset-ticketing'); ?></h2>
        <div class="reset-capacity-progress">
            <div class="reset-progress-bar">
                <div class="reset-progress-fill" style="width: <?php echo esc_attr($capacity_percentage); ?>%;"></div>
            </div>
            <div class="reset-progress-text">
                <span><?php echo esc_html($capacity_used); ?> / <?php echo esc_html($max_capacity); ?> tickets sold</span>
                <span class="reset-progress-percentage"><?php echo esc_html($capacity_percentage); ?>%</span>
            </div>
        </div>
        
        <?php if ($capacity_percentage >= 90): ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('Warning: Event is approaching capacity. Consider implementing waiting list or token cancellation.', 'reset-ticketing'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sales by Ticket Type -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Sales by Ticket Type', 'reset-ticketing'); ?></h2>
        
        <?php if ($sales_by_type): ?>
            <div class="reset-sales-chart">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Ticket Type', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Tickets Sold', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Revenue', 'reset-ticketing'); ?></th>
                            <th><?php echo esc_html__('Percentage', 'reset-ticketing'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_by_type as $sale): ?>
                            <?php
                            $percentage = $stats['completed_purchases'] > 0 ? round(($sale['count'] / $stats['completed_purchases']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html(get_ticket_name_sr($sale['ticket_type'], $ticket_types)); ?></strong></td>
                                <td><?php echo esc_html($sale['count']); ?></td>
                                <td>Rs <?php echo esc_html(number_format($sale['revenue'], 2)); ?></td>
                                <td>
                                    <div class="reset-percentage-bar">
                                        <div class="reset-percentage-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                        <span class="reset-percentage-text"><?php echo esc_html($percentage); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="reset-no-results">
                <p><?php echo esc_html__('No sales data available yet.', 'reset-ticketing'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Date Range Filter -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Revenue Trends', 'reset-ticketing'); ?></h2>
        
        <form method="get" action="" class="reset-date-filter">
            <input type="hidden" name="page" value="reset-sales-report">
            
            <div class="reset-filter-row">
                <label for="start_date"><?php echo esc_html__('From:', 'reset-ticketing'); ?></label>
                <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
                
                <label for="end_date"><?php echo esc_html__('To:', 'reset-ticketing'); ?></label>
                <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
                
                <?php submit_button(__('Filter', 'reset-ticketing'), 'secondary', 'submit', false); ?>
            </div>
        </form>
        
        <?php if ($revenue_by_date): ?>
            <div class="reset-revenue-chart">
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Purchases -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Recent Purchases', 'reset-ticketing'); ?></h2>
        
        <!-- Export Filters -->
        <div class="reset-export-filters" style="margin: 20px 0; padding: 0 20px 20px; background: #f8f9fa; border-radius: 4px; border: 1px solid #ddd;">
            <h3><?php echo esc_html__('Export with Filters', 'reset-ticketing'); ?></h3>
            <form method="get" action="" class="reset-export-filter-form">
                <input type="hidden" name="page" value="reset-sales-report">
                <input type="hidden" name="export" value="purchases">
                
                <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                    <div>
                        <label for="export_status"><?php echo esc_html__('Status:', 'reset-ticketing'); ?></label>
                        <select name="export_status" id="export_status">
                            <option value=""><?php echo esc_html__('All Statuses', 'reset-ticketing'); ?></option>
                            <option value="completed"><?php echo esc_html__('Completed', 'reset-ticketing'); ?></option>
                            <option value="pending"><?php echo esc_html__('Pending', 'reset-ticketing'); ?></option>
                            <option value="failed"><?php echo esc_html__('Failed', 'reset-ticketing'); ?></option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="export_start_date"><?php echo esc_html__('From Date:', 'reset-ticketing'); ?></label>
                        <input type="date" name="export_start_date" id="export_start_date">
                    </div>
                    
                    <div>
                        <label for="export_end_date"><?php echo esc_html__('To Date:', 'reset-ticketing'); ?></label>
                        <input type="date" name="export_end_date" id="export_end_date">
                    </div>
                </div>
                
                <div>
                    <?php submit_button(__('Export Filtered Data', 'reset-ticketing'), 'primary', 'submit', false); ?>
                    <span style="margin-left: 10px; color: #666; font-size: 12px;">
                        <?php echo esc_html__('Leave filters empty to export all data', 'reset-ticketing'); ?>
                    </span>
                </div>
            </form>
            
            <!-- Check-In Export -->
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <h4><?php echo esc_html__('Check-In Export', 'reset-ticketing'); ?></h4>
                <p style="color: #666; font-size: 13px; margin-bottom: 10px;">
                    <?php echo esc_html__('Export complete check-in data including attendance status and timing.', 'reset-ticketing'); ?>
                </p>
                
                <form method="GET" action="<?php echo admin_url('admin.php'); ?>" style="margin-bottom: 0;">
                    <input type="hidden" name="page" value="reset-sales-report">
                    <input type="hidden" name="export" value="checkin">
                    
                    <div style="margin-bottom: 15px; display: flex; gap: 15px; align-items: center;">
                        <div>
                            <label for="checkin_filter"><?php echo esc_html__('Check-In Status:', 'reset-ticketing'); ?></label>
                            <select name="checkin_filter" id="checkin_filter">
                                <option value="all"><?php echo esc_html__('All Users', 'reset-ticketing'); ?></option>
                                <option value="checked_in"><?php echo esc_html__('Checked In Only', 'reset-ticketing'); ?></option>
                                <option value="not_checked_in"><?php echo esc_html__('Not Checked In Only', 'reset-ticketing'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="checkin_start_date"><?php echo esc_html__('From Date:', 'reset-ticketing'); ?></label>
                            <input type="date" name="checkin_start_date" id="checkin_start_date">
                        </div>
                        
                        <div>
                            <label for="checkin_end_date"><?php echo esc_html__('To Date:', 'reset-ticketing'); ?></label>
                            <input type="date" name="checkin_end_date" id="checkin_end_date">
                        </div>
                    </div>
                    
                    <div>
                        <?php submit_button(__('📋 Export Check-In Data', 'reset-ticketing'), 'secondary', 'submit', false); ?>
                        <span style="margin-left: 10px; color: #666; font-size: 12px;">
                            <?php echo esc_html__('Leave filters empty to export all data', 'reset-ticketing'); ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($recent_purchases): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Date', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Customer', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Ticket Type', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Ticket Price', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Addon Total', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Drinks', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Total Amount', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Status', 'reset-ticketing'); ?></th>
                        <th><?php echo esc_html__('Payment Reference', 'reset-ticketing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_purchases as $purchase): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($purchase['created_at']))); ?></td>
                            <td>
                                <strong><?php echo esc_html($purchase['purchaser_name']); ?></strong><br>
                                <small><?php echo esc_html($purchase['purchaser_email']); ?></small><br>
                                <small><?php echo esc_html($purchase['purchaser_phone']); ?></small>
                            </td>
                            <td><?php echo esc_html(get_ticket_name_sr($purchase['ticket_type'], $ticket_types)); ?></td>
                            <td>Rs <?php echo esc_html(number_format($purchase['ticket_price'], 2)); ?></td>
                            <td>Rs <?php echo esc_html(number_format($purchase['addon_total'], 2)); ?></td>
                            <td style="text-align: center;"><?php echo intval($purchase['total_drink_count'] ?? 0); ?></td>
                            <td>Rs <?php echo esc_html(number_format($purchase['total_amount'], 2)); ?></td>
                            <td>
                                <span class="reset-status-<?php echo esc_attr($purchase['payment_status']); ?>">
                                    <?php echo esc_html(ucfirst($purchase['payment_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($purchase['payment_reference']): ?>
                                    <code><?php echo esc_html($purchase['payment_reference']); ?></code>
                                <?php else: ?>
                                    <span class="reset-no-ref">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="reset-no-results">
                <p><?php echo esc_html__('No purchases found.', 'reset-ticketing'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Stats -->
    <div class="reset-card">
        <h2><?php echo esc_html__('Quick Statistics', 'reset-ticketing'); ?></h2>
        
        <div class="reset-quick-stats">
            <div class="reset-stat-item">
                <span class="reset-stat-label"><?php echo esc_html__('Average Transaction Amount:', 'reset-ticketing'); ?></span>
                <span class="reset-stat-value">
                    Rs <?php echo esc_html(number_format($stats['completed_purchases'] > 0 ? $stats['total_revenue'] / $stats['completed_purchases'] : 0, 2)); ?>
                </span>
            </div>
            
            <div class="reset-stat-item">
                <span class="reset-stat-label"><?php echo esc_html__('Tokens Conversion Rate:', 'reset-ticketing'); ?></span>
                <span class="reset-stat-value"><?php echo esc_html($conversion_rate); ?>%</span>
            </div>
            
            <div class="reset-stat-item">
                <span class="reset-stat-label"><?php echo esc_html__('Remaining Capacity:', 'reset-ticketing'); ?></span>
                <span class="reset-stat-value"><?php echo esc_html($capacity_remaining); ?> tickets</span>
            </div>
            
            <div class="reset-stat-item">
                <span class="reset-stat-label"><?php echo esc_html__('Projected Revenue (Full Capacity):', 'reset-ticketing'); ?></span>
                <span class="reset-stat-value">
                    Rs <?php echo esc_html(number_format($stats['completed_purchases'] > 0 ? ($stats['total_revenue'] / $stats['completed_purchases']) * $max_capacity : 0, 2)); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.reset-metrics-overview {
    margin: 20px 0;
}

.reset-metric-boxes {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.reset-metric-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
}

.reset-metric-box.highlight {
    border-color: #007cba;
    background: #f0f8ff;
}

.reset-metric-box h3 {
    font-size: 32px;
    margin: 0 0 10px 0;
    color: #1d2327;
}

.reset-metric-box.highlight h3 {
    color: #007cba;
}

.reset-metric-box p {
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

.reset-capacity-progress {
    margin: 20px 0;
}

.reset-progress-bar {
    width: 100%;
    height: 30px;
    background: #f0f0f1;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 10px;
}

.reset-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #00a32a, #72aee6);
    transition: width 0.3s ease;
}

.reset-progress-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #646970;
}

.reset-progress-percentage {
    font-weight: bold;
    color: #1d2327;
}

.reset-percentage-bar {
    position: relative;
    width: 100%;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.reset-percentage-fill {
    height: 100%;
    background: #007cba;
    transition: width 0.3s ease;
}

.reset-percentage-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    color: #1d2327;
    font-weight: bold;
}

.reset-filter-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
}

.reset-filter-row label {
    font-weight: bold;
}

.reset-filter-row input,
.reset-filter-row select {
    margin-right: 10px;
}

.reset-table-actions {
    margin-bottom: 20px;
}

.reset-status-completed {
    color: #00a32a;
    font-weight: bold;
}

.reset-status-pending {
    color: #dba617;
    font-weight: bold;
}

.reset-status-failed {
    color: #d63638;
    font-weight: bold;
}

.reset-no-ref {
    color: #646970;
    font-style: italic;
}

.reset-no-results {
    text-align: center;
    padding: 40px;
    color: #646970;
}

.reset-quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.reset-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.reset-stat-label {
    font-weight: bold;
    color: #646970;
}

.reset-stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #1d2327;
}

.reset-revenue-chart {
    margin: 20px 0;
    text-align: center;
}

#revenueChart {
    max-width: 100%;
    height: auto;
}
</style>

<?php if ($revenue_by_date): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($revenue_by_date, 'date')); ?>,
            datasets: [{
                label: 'Daily Revenue (Rs)',
                data: <?php echo json_encode(array_column($revenue_by_date, 'revenue')); ?>,
                borderColor: '#007cba',
                backgroundColor: 'rgba(0, 124, 186, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Revenue Trend'
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rs ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?> 