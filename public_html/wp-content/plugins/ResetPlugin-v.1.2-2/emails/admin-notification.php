<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESET 2025 - Admin Notification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert-section {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .alert-section.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .alert-section.warning {
            background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);
        }
        
        .alert-section.info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        
        .alert-section h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .notification-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .notification-details h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .purchase-details {
            background: #e8f5e8;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .purchase-details h3 {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .token-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .token-info h3 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .token-code {
            background: #495057;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 1px;
            display: inline-block;
        }
        
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            margin: 8px;
            transition: transform 0.2s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        .cta-button.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .cta-button.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .quick-stats {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .quick-stats h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            height: 100%;
            transition: width 0.5s ease;
        }
        
        .progress-fill.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .progress-fill.danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        
        .footer {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 25px;
            text-align: center;
        }
        
        .footer h4 {
            color: #ffffff;
            margin-bottom: 10px;
        }
        
        .footer p {
            margin-bottom: 5px;
            opacity: 0.9;
        }
        
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .cta-button {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔔 RESET 2025</h1>
            <p>Admin Notification System</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Alert Section -->
            <div class="alert-section {{ALERT_TYPE}}">
                <h2>{{ALERT_ICON}} {{ALERT_TITLE}}</h2>
                <p>{{ALERT_MESSAGE}}</p>
            </div>

            <!-- Notification Details -->
            <div class="notification-details">
                <h3>📋 Notification Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Notification Type:</span>
                    <span class="detail-value">{{NOTIFICATION_TYPE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Timestamp:</span>
                    <span class="detail-value">{{TIMESTAMP}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority:</span>
                    <span class="detail-value">{{PRIORITY}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reference ID:</span>
                    <span class="detail-value">{{REFERENCE_ID}}</span>
                </div>
            </div>

            <!-- Purchase Details (for new purchase notifications) -->
            {{#IS_PURCHASE_NOTIFICATION}}
            <div class="purchase-details">
                <h3>💳 Purchase Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value">{{CUSTOMER_NAME}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{CUSTOMER_EMAIL}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{CUSTOMER_PHONE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ticket Type:</span>
                    <span class="detail-value">{{TICKET_TYPE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Price:</span>
                    <span class="detail-value">Rs {{TICKET_PRICE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Reference:</span>
                    <span class="detail-value">{{PAYMENT_REFERENCE}}</span>
                </div>
            </div>
            {{/IS_PURCHASE_NOTIFICATION}}

            <!-- Token Information (for token notifications) -->
            {{#IS_TOKEN_NOTIFICATION}}
            <div class="token-info">
                <h3>🎟️ Token Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Token Code:</span>
                    <span class="detail-value"><span style="background: white; color: #000000; border: 2px solid #f9c613; padding: 8px 10px; border-radius: 8px; text-align: center; font-family: 'Courier New', monospace; font-weight: 700; font-size: 0.9rem; letter-spacing: 1px; display: inline-block;">{{TOKEN_CODE}}</span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Token Type:</span>
                    <span class="detail-value">{{TOKEN_TYPE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Used By:</span>
                    <span class="detail-value">{{USED_BY}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Usage Time:</span>
                    <span class="detail-value">{{USAGE_TIME}}</span>
                </div>
            </div>
            {{/IS_TOKEN_NOTIFICATION}}

            <!-- Quick Stats -->
            <div class="quick-stats">
                <h3>📊 Current System Status</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number">{{TOTAL_SALES}}</span>
                        <div class="stat-label">Total Sales</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{TOKENS_USED}}</span>
                        <div class="stat-label">Tokens Used</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{REVENUE}}</span>
                        <div class="stat-label">Revenue (Rs)</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">{{CAPACITY_USED}}%</span>
                        <div class="stat-label">Capacity Used</div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <strong>Capacity Status:</strong>
                    <div class="progress-bar">
                        <div class="progress-fill {{CAPACITY_STATUS}}" style="width: {{CAPACITY_PERCENTAGE}}%"></div>
                    </div>
                    <p style="font-size: 0.9rem; color: #6c757d; margin-top: 5px;">
                        {{CAPACITY_USED}} / {{TOTAL_CAPACITY}} attendees
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="{{ADMIN_DASHBOARD_URL}}" class="cta-button">View Dashboard</a>
                <a href="{{SALES_REPORT_URL}}" class="cta-button secondary">Sales Report</a>
                {{#IS_URGENT}}
                <a href="{{TOKEN_MANAGEMENT_URL}}" class="cta-button danger">Manage Tokens</a>
                {{/IS_URGENT}}
            </div>

            <!-- System Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #495057; margin-bottom: 10px;">🔧 System Information</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.9rem; color: #6c757d;">
                    <div>
                        <strong>Server Time:</strong><br>
                        {{SERVER_TIME}}
                    </div>
                    <div>
                        <strong>Database Status:</strong><br>
                        {{DATABASE_STATUS}}
                    </div>
                    <div>
                        <strong>Last Backup:</strong><br>
                        {{LAST_BACKUP}}
                    </div>
                    <div>
                        <strong>Active Sessions:</strong><br>
                        {{ACTIVE_SESSIONS}}
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            {{#HAS_RECENT_ACTIVITY}}
            <div style="background: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">📈 Recent Activity (Last 24 Hours)</h4>
                <ul style="list-style: none; padding: 0;">
                    {{#RECENT_ACTIVITIES}}
                    <li style="padding: 8px 0; border-bottom: 1px solid #f8f9fa; display: flex; justify-content: space-between;">
                        <span>{{ACTIVITY_DESCRIPTION}}</span>
                        <span style="color: #6c757d; font-size: 0.9rem;">{{ACTIVITY_TIME}}</span>
                    </li>
                    {{/RECENT_ACTIVITIES}}
                </ul>
            </div>
            {{/HAS_RECENT_ACTIVITY}}
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>RESET 2025 Admin System</h4>
            <p>Automated notification from the event management system</p>
            <p><a href="https://nooballiance.lk/wp-admin">WordPress Admin</a> | <a href="https://nooballiance.lk">Website</a></p>
            <p style="margin-top: 15px; font-size: 0.8rem; opacity: 0.7;">
                This is an automated admin notification. Configure notification settings in the admin panel.
            </p>
        </div>
    </div>
</body>
</html> 