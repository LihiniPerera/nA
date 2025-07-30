<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESET 2025 - Event Reminder</title>
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .countdown-section {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .countdown-section h2 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .countdown {
            font-size: 3rem;
            font-weight: 700;
            margin: 20px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .event-details {
            background: #f7fafc;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #ff6b6b;
        }
        
        .event-details h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: 500;
        }
        

        
        .invitation-status {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .invitation-status h3 {
            color: #0d9488;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .usage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: #ffffff;
            border: 1px solid #81e6d9;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0d9488;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #4a5568;
            margin-top: 5px;
        }
        
        .unused-tokens {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .unused-tokens h4 {
            color: #c53030;
            margin-bottom: 10px;
        }
        
        .tokens-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        
        .token-code {
            background: #e53e3e;
            color: white;
            padding: 8px 6px;
            border-radius: 4px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        
        .important-reminders {
            background: #fffbeb;
            border: 1px solid #f6d55c;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .important-reminders h4 {
            color: #d69e2e;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .important-reminders ul {
            margin-left: 20px;
            color: #744210;
        }
        
        .important-reminders li {
            margin-bottom: 5px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 30px;
            text-align: center;
        }
        
        .footer h4 {
            color: #e2e8f0;
            margin-bottom: 10px;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        .footer a {
            color: #63b3ed;
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
            
            .header h1 {
                font-size: 2rem;
            }
            
            .countdown {
                font-size: 2rem;
            }
            
            .usage-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>RESET 2025</h1>
            <p>Event Reminder - Only 2 Days to Go!</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Countdown Section -->
            <div class="countdown-section">
                <h2>⏰ Get Ready!</h2>
                <div class="countdown">2 DAYS</div>
                <p>Until RESET 2025 begins!</p>
            </div>

            <!-- Event Details -->
            <div class="event-details">
                <h3>📅 Event Details - Final Reminder</h3>
                <div class="detail-row">
                    <span class="detail-label">Event:</span>
                    <span class="detail-value">RESET 2025</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">July 27, 2025 (This Sunday!)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">10:00 AM - 11:00 PM</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Venue:</span>
                    <span class="detail-value">{{VENUE_ADDRESS}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Your Ticket:</span>
                    <span class="detail-value">{{TICKET_TYPE}} - Rs {{TICKET_PRICE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Purchaser:</span>
                    <span class="detail-value">{{PURCHASER_NAME}}</span>
                </div>
            </div>



            <!-- Invitation Token Status -->
            <div class="invitation-status">
                <h3>🎁 Invitation keys Status</h3>
                <p>Here's how your invitation keys are being used:</p>
                
                <div class="usage-stats">
                    <div class="stat-box">
                        <span class="stat-number">{{TOKENS_USED}}</span>
                        <div class="stat-label">Used</div>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number">{{TOKENS_UNUSED}}</span>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number">{{FRIENDS_ATTENDING}}</span>
                        <div class="stat-label">Friends Coming</div>
                    </div>
                </div>

                {{#HAS_UNUSED_TOKENS}}
                <div class="unused-tokens">
                    <h4>💡 Still have unused tokens?</h4>
                    <p>Last chance to invite friends! Here are your remaining tokens:</p>
                    
                    <div style="text-align: center; margin-top: 10px;">
                        {{UNUSED_TOKENS}}
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="https://nooballiance.lk/reset" class="cta-button">Share Tokens with Friends</a>
                    </div>
                </div>
                {{/HAS_UNUSED_TOKENS}}
            </div>

            <!-- Important Reminders -->
            <div class="important-reminders">
                <h4>⚠️ Important Reminders</h4>
                <ul>
                    <li><strong>Bring your ticket:</strong> Digital (this email) or printed copy</li>
                    <li><strong>Arrive early:</strong> Doors open at 10:00 AM</li>
                    <li><strong>What to bring:</strong> Valid ID, comfortable clothes, water bottle</li>
                    <li><strong>Parking:</strong> Limited parking available, consider carpooling</li>
                    <li><strong>Food & Drinks:</strong> Available for purchase at the venue</li>
                    <li><strong>No outside food/drinks:</strong> Venue policy</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div style="text-align: center; margin-top: 30px;">
                <h4>Need Help?</h4>
                <p>For any questions or assistance:</p>
                <p><strong>Email:</strong> <a href="mailto:support@nooballiance.lk">support@nooballiance.lk</a></p>
                <p><strong>Phone:</strong> <a href="tel:+94771234567">+94 77 123 4567</a></p>
                <p><strong>Website:</strong> <a href="https://nooballiance.lk">nooballiance.lk</a></p>
                
                <div style="margin-top: 20px;">
                    <a href="https://nooballiance.lk/reset" class="cta-button">Visit Event Page</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>See You at RESET 2025!</h4>
            <p>Reunion of Sri Lankan Esports</p>
            <p>Organized by <a href="https://nooballiance.lk">Noob Alliance</a></p>
            <p style="margin-top: 15px; font-size: 0.8rem; opacity: 0.7;">
                This is an automated reminder email. Please do not reply to this email address.
            </p>
        </div>
    </div>
</body>
</html> 