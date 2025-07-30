<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET Event - E-Ticket</title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .ticket-container {
            background: white;
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
            overflow: hidden;
            border: 2px dashed #667eea;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .ticket-logo img {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .event-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .event-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .ticket-body {
            padding: 30px;
        }
        
        .ticket-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        
        .ticket-details {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        
        .detail-value {
            font-weight: bold;
            color: #333;
        }
        

        
        .important-notes {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .important-notes h4 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .important-notes ul {
            color: #856404;
            margin: 0;
            padding-left: 20px;
        }
        
        .important-notes li {
            margin-bottom: 8px;
        }
        
        .ticket-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .download-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .ticket-serial {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 10px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .ticket-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .ticket-container {
                margin: 10px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .ticket-container {
                box-shadow: none;
                border: 1px solid #333;
            }
            
            .ticket-footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-serial">TKT-<?php echo time(); ?></div>
        
        <div class="ticket-header">
            <div class="ticket-logo">
                <?php
                $core = ResetCore::getInstance();
                echo $core->get_logo_html('white', '400', '', '120px');
                ?>
            </div>
            <h1 class="event-title">RESET 2025</h1>
            <p class="event-subtitle">Reunion of Sri Lankan Esports</p>
        </div>
        
        <div class="ticket-body">
            <div class="ticket-info">
                <div class="info-item">
                    <div class="info-label">Event Date</div>
                    <div class="info-value"><?php echo date('M j, Y', strtotime(RESET_EVENT_DATE)); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Time</div>
                    <div class="info-value">10:00 AM</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Venue</div>
                    <div class="info-value">Trace Expert City, Colombo - Bay 07</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Admission</div>
                    <div class="info-value">General</div>
                </div>
            </div>
            
            <div class="ticket-details" id="ticketDetails">
                <div class="detail-row">
                    <span class="detail-label">Ticket Holder</span>
                    <span class="detail-value" id="ticketHolder">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ticket Type</span>
                    <span class="detail-value" id="ticketType">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value" id="email">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value" id="phone">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Reference</span>
                    <span class="detail-value" id="bookingRef">Loading...</span>
                </div>
            </div>
            

            
            <div class="important-notes">
                <h4>Important Instructions</h4>
                <ul>
                    <li>Please arrive at the venue 30 minutes before the event starts</li>
                    <li>Bring a valid government-issued photo ID for verification</li>
                    <li>This ticket is non-transferable and non-refundable</li>
                    <li>Entry is subject to venue capacity and safety regulations</li>
                    <li>Outside food and beverages are not permitted</li>
                </ul>
            </div>
        </div>
        
        <div class="ticket-footer">
            <button class="print-btn" onclick="window.print()">Print Ticket</button>
            <button class="download-btn" onclick="downloadTicket()">Download PDF</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        loadTicketData();
    });
    
    function loadTicketData() {
        // Get ticket ID from URL
        const ticketId = getTicketIdFromUrl();
        
        if (!ticketId) {
            alert('Invalid ticket URL');
            window.location.href = '<?php echo site_url('/reset'); ?>';
            return;
        }
        
        // For demo purposes, show sample data
        // In production, this would fetch real data from the server
        showSampleTicketData(ticketId);
    }
    
    function getTicketIdFromUrl() {
        const pathParts = window.location.pathname.split('/');
        return pathParts[pathParts.length - 1] || pathParts[pathParts.length - 2];
    }
    
    function showSampleTicketData(ticketId) {
        // Sample data for demonstration
        document.getElementById('ticketHolder').textContent = 'John Doe';
        document.getElementById('ticketType').textContent = 'Early Bird';
        document.getElementById('email').textContent = 'john.doe@example.com';
        document.getElementById('phone').textContent = '+94 77 123 4567';
        document.getElementById('bookingRef').textContent = 'RESET' + Date.now();
        

    }
    
    function downloadTicket() {
        // In production, this would generate and download a PDF ticket
        alert('PDF download functionality would be implemented here with a proper PDF generation library.');
    }
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 