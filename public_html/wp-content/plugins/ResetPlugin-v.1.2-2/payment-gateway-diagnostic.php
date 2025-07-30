<?php
/**
 * RESET Payment Gateway Diagnostic Tool
 * 
 * This tool helps diagnose payment gateway issues and configuration problems.
 * Use this to troubleshoot "CARD HAS EXPIRED" and other payment gateway errors.
 */

// Security check
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator access required.');
}

// Load required classes
$required_classes = array(
    'ResetSampathGateway' => 'includes/class-reset-sampath-gateway.php',
    'ResetCore' => 'includes/class-reset-core.php',
    'ResetDatabase' => 'includes/class-reset-database.php'
);

foreach ($required_classes as $class_name => $file_path) {
    if (!class_exists($class_name)) {
        require_once($file_path);
    }
}

// Initialize instances
$gateway = ResetSampathGateway::getInstance();
$core = ResetCore::getInstance();
$db = ResetDatabase::getInstance();

// Run diagnostics
$diagnostics = array();

// 1. Check gateway configuration
$gateway_config = $gateway->get_gateway_config();
$diagnostics['gateway_config'] = $gateway_config;

// 2. Check WooCommerce Sampath plugin
$diagnostics['wc_sampath_plugin'] = array(
    'class_exists' => class_exists('WC_Sampath'),
    'plugin_active' => is_plugin_active('paycorp_sampath_ipg/paycorp_sampath_ipg.php'),
    'files_exist' => file_exists(WP_PLUGIN_DIR . '/paycorp_sampath_ipg/paycorp_sampath_ipg.php')
);

// 3. Check gateway settings
$gateway_settings = array();
if (class_exists('WC_Sampath')) {
    $wc_gateway = new WC_Sampath();
    $gateway_settings = $wc_gateway->settings;
}
$diagnostics['gateway_settings'] = $gateway_settings;

// 4. Check recent payment attempts
global $wpdb;
$table_purchases = $wpdb->prefix . 'reset_purchases';
$recent_payments = $wpdb->get_results(
    "SELECT * FROM {$table_purchases} 
    WHERE payment_status = 'pending' 
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY created_at DESC 
    LIMIT 10",
    ARRAY_A
);
$diagnostics['recent_payments'] = $recent_payments;

// 5. Check debug log entries
$debug_log_path = WP_CONTENT_DIR . '/debug.log';
$debug_entries = array();
if (file_exists($debug_log_path)) {
    $log_content = file_get_contents($debug_log_path);
    $lines = explode("\n", $log_content);
    $reset_lines = array_filter($lines, function($line) {
        return stripos($line, 'reset') !== false || stripos($line, 'payment') !== false;
    });
    $debug_entries = array_slice(array_reverse($reset_lines), 0, 20);
}
$diagnostics['debug_entries'] = $debug_entries;

// 6. Test payment reference generation
$test_reference = $core->generate_payment_reference('TEST123');
$diagnostics['test_reference'] = $test_reference;

// 7. Check environment details
$diagnostics['environment'] = array(
    'site_url' => site_url(),
    'home_url' => home_url(),
    'wp_version' => get_bloginfo('version'),
    'php_version' => phpversion(),
    'ssl_enabled' => is_ssl(),
    'currency' => get_woocommerce_currency(),
    'timezone' => wp_timezone_string(),
    'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
    'current_time' => current_time('mysql'),
    'server_time' => date('Y-m-d H:i:s')
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>RESET Payment Gateway Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .log-entry { background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 4px; font-size: 12px; }
        .section { margin: 20px 0; }
        .section h3 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background: #f8f9fa; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f1aeb5; }
    </style>
</head>
<body>
    <h1>🔧 RESET Payment Gateway Diagnostic</h1>
    
    <div class="alert alert-info">
        <strong>💡 Purpose:</strong> This tool helps diagnose payment gateway issues, especially "CARD HAS EXPIRED" errors. 
        Run this after experiencing payment problems to identify configuration issues.
    </div>

    <!-- Gateway Configuration Status -->
    <div class="card">
        <h2>1. Gateway Configuration Status</h2>
        <div class="grid">
            <div>
                <h3>Configuration Check</h3>
                <p><strong>Gateway Configured:</strong> 
                    <?php echo $gateway_config['configured'] ? '<span class="status-ok">✅ YES</span>' : '<span class="status-error">❌ NO</span>'; ?>
                </p>
                <p><strong>Gateway Config Object:</strong> 
                    <?php echo $gateway_config['gateway_config_exists'] ? '<span class="status-ok">✅ EXISTS</span>' : '<span class="status-error">❌ MISSING</span>'; ?>
                </p>
                <p><strong>Callback URL:</strong> <code><?php echo esc_html($gateway_config['callback_url']); ?></code></p>
            </div>
            <div>
                <h3>WooCommerce Sampath Plugin</h3>
                <p><strong>WC_Sampath Class:</strong> 
                    <?php echo $diagnostics['wc_sampath_plugin']['class_exists'] ? '<span class="status-ok">✅ EXISTS</span>' : '<span class="status-error">❌ MISSING</span>'; ?>
                </p>
                <p><strong>Plugin Active:</strong> 
                    <?php echo $diagnostics['wc_sampath_plugin']['plugin_active'] ? '<span class="status-ok">✅ ACTIVE</span>' : '<span class="status-error">❌ INACTIVE</span>'; ?>
                </p>
                <p><strong>Plugin Files:</strong> 
                    <?php echo $diagnostics['wc_sampath_plugin']['files_exist'] ? '<span class="status-ok">✅ EXIST</span>' : '<span class="status-error">❌ MISSING</span>'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Gateway Settings -->
    <div class="card">
        <h2>2. Gateway Settings</h2>
        <?php if (!empty($gateway_settings)): ?>
            <table>
                <tr><th>Setting</th><th>Status</th><th>Value</th></tr>
                <?php foreach ($gateway_settings as $key => $value): ?>
                    <tr>
                        <td><?php echo esc_html($key); ?></td>
                        <td>
                            <?php 
                            $is_set = !empty($value);
                            echo $is_set ? '<span class="status-ok">✅ SET</span>' : '<span class="status-error">❌ NOT SET</span>';
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (in_array($key, ['hmac_secret', 'auth_token', 'client_id'])) {
                                echo $is_set ? '<code>[HIDDEN]</code>' : '<code>NOT SET</code>';
                            } else {
                                echo '<code>' . esc_html($value) . '</code>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ Warning:</strong> No gateway settings found. The WooCommerce Sampath plugin may not be configured properly.
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Payment Attempts -->
    <div class="card">
        <h2>3. Recent Payment Attempts</h2>
        <?php if (!empty($recent_payments)): ?>
            <table>
                <tr><th>ID</th><th>Reference</th><th>Amount</th><th>Status</th><th>Customer</th><th>Created</th></tr>
                <?php foreach ($recent_payments as $payment): ?>
                    <tr>
                        <td><?php echo esc_html($payment['id']); ?></td>
                        <td><code><?php echo esc_html($payment['payment_reference'] ?: 'N/A'); ?></code></td>
                        <td>Rs. <?php echo esc_html($payment['total_amount'] ?: $payment['ticket_price']); ?></td>
                        <td>
                            <?php 
                            $status = $payment['payment_status'];
                            $class = $status === 'completed' ? 'status-ok' : ($status === 'pending' ? 'status-warning' : 'status-error');
                            echo '<span class="' . $class . '">' . strtoupper($status) . '</span>';
                            ?>
                        </td>
                        <td><?php echo esc_html($payment['purchaser_email']); ?></td>
                        <td><?php echo esc_html($payment['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No recent payment attempts found.</p>
        <?php endif; ?>
    </div>

    <!-- Debug Log Entries -->
    <div class="card">
        <h2>4. Recent Debug Log Entries</h2>
        <?php if (!empty($debug_entries)): ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($debug_entries as $entry): ?>
                    <div class="log-entry"><?php echo esc_html($entry); ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No relevant debug log entries found.</p>
        <?php endif; ?>
    </div>

    <!-- Test Results -->
    <div class="card">
        <h2>5. Test Results</h2>
        <div class="grid">
            <div>
                <h3>Payment Reference Generation</h3>
                <p><strong>Test Reference:</strong> <code><?php echo esc_html($test_reference); ?></code></p>
                <p><strong>Format Check:</strong> 
                    <?php echo strpos($test_reference, 'RESET') === 0 ? '<span class="status-ok">✅ CORRECT</span>' : '<span class="status-error">❌ INCORRECT</span>'; ?>
                </p>
            </div>
            <div>
                <h3>Environment</h3>
                <p><strong>SSL:</strong> <?php echo $diagnostics['environment']['ssl_enabled'] ? '<span class="status-ok">✅ ENABLED</span>' : '<span class="status-error">❌ DISABLED</span>'; ?></p>
                <p><strong>Currency:</strong> <code><?php echo esc_html($diagnostics['environment']['currency']); ?></code></p>
                <p><strong>Timezone:</strong> <code><?php echo esc_html($diagnostics['environment']['timezone']); ?></code></p>
                <p><strong>Debug Mode:</strong> <?php echo $diagnostics['environment']['debug_mode'] ? '<span class="status-warning">⚠️ ENABLED</span>' : '<span class="status-ok">✅ DISABLED</span>'; ?></p>
            </div>
        </div>
    </div>

    <!-- Common Issues & Solutions -->
    <div class="card">
        <h2>6. Common Issues & Solutions</h2>
        
        <div class="section">
            <h3>🔴 "CARD HAS EXPIRED" Error</h3>
            <div class="alert alert-warning">
                <strong>Possible Causes:</strong>
                <ul>
                    <li><strong>Card Date Format:</strong> Gateway may expect MM/YY format but receiving YY/MM</li>
                    <li><strong>Time Zone Issues:</strong> Server time vs gateway time mismatch</li>
                    <li><strong>Gateway Configuration:</strong> Invalid merchant settings</li>
                    <li><strong>Test Mode:</strong> Using test card numbers in production</li>
                    <li><strong>Currency Issues:</strong> Incorrect currency code or amount format</li>
                </ul>
                
                <strong>Solutions:</strong>
                <ul>
                    <li>Check WooCommerce Sampath plugin configuration</li>
                    <li>Verify merchant credentials with bank</li>
                    <li>Test with different card (if available)</li>
                    <li>Check gateway logs for detailed error messages</li>
                    <li>Contact Sampath Bank support with transaction details</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h3>🔴 Configuration Issues</h3>
            <?php if (!$gateway_config['configured']): ?>
                <div class="alert alert-danger">
                    <strong>❌ Gateway Not Configured:</strong> The payment gateway is not properly configured. Please check:
                    <ul>
                        <li>WooCommerce Sampath plugin is installed and activated</li>
                        <li>All required settings are filled in WooCommerce > Settings > Payments > Sampath Bank</li>
                        <li>Merchant credentials are correct</li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$diagnostics['wc_sampath_plugin']['class_exists']): ?>
                <div class="alert alert-danger">
                    <strong>❌ WC_Sampath Class Missing:</strong> The WooCommerce Sampath plugin is not properly loaded.
                    <ul>
                        <li>Install the paycorp_sampath_ipg plugin</li>
                        <li>Activate the plugin</li>
                        <li>Check for plugin conflicts</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Items -->
    <div class="card">
        <h2>7. Recommended Actions</h2>
        <div class="alert alert-info">
            <strong>For "CARD HAS EXPIRED" Issue:</strong>
            <ol>
                <li><strong>Contact Sampath Bank:</strong> Provide them with payment reference <code>RESET24713328</code></li>
                <li><strong>Check Merchant Settings:</strong> Verify your merchant account is active and properly configured</li>
                <li><strong>Test Environment:</strong> Try payment in test mode first</li>
                <li><strong>Alternative Payment:</strong> Use a different card or payment method temporarily</li>
                <li><strong>Check Server Time:</strong> Ensure server time matches your local time zone</li>
            </ol>
        </div>
    </div>
</body>
</html> 