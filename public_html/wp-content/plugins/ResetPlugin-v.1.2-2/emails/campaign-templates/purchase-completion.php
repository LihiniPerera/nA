<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESET 2025 - Thank You & What's Next</title>
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
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
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
        
        .thank-you-section {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .thank-you-section h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 800;
        }
        
        .celebration-icons {
            font-size: 3rem;
            margin: 20px 0;
        }
        
        .next-steps {
            background: #f0f8ff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #4ecdc4;
        }
        
        .next-steps h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .step-item:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            background: #4ecdc4;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .step-content h4 {
            color: #2d3748;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .step-content p {
            color: #4a5568;
            font-size: 0.95rem;
        }
        
        .preparation-checklist {
            background: #e6fffa;
            border: 2px solid #81e6d9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .preparation-checklist h3 {
            color: #0d9488;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .checklist-column {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #b2f5ea;
        }
        
        .checklist-column h4 {
            color: #0d9488;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .checklist-column ul {
            list-style: none;
            padding: 0;
        }
        
        .checklist-column li {
            padding: 5px 0;
            color: #2d3748;
            font-size: 0.9rem;
        }
        
        .checklist-column li::before {
            content: "✓";
            color: #0d9488;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .important-info {
            background: #fff5f5;
            border: 2px solid #feb2b2;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .important-info h4 {
            color: #c53030;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .important-info p {
            color: #744210;
            margin-bottom: 10px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 10px 5px;
            transition: transform 0.2s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        .cta-button.secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            
            .celebration-icons {
                font-size: 2.5rem;
            }
            
            .checklist-grid {
                grid-template-columns: 1fr;
            }
            
            .step-item {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin: 0 auto 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>RESET 2025</h1>
            <p>Thank You & What's Next</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Thank You Section -->
            <div class="thank-you-section">
                <h2>🎉 Thank You, {{RECIPIENT_NAME}}!</h2>
                <div class="celebration-icons">🎮 🎊 🏆</div>
                <p style="font-size: 1.1rem; font-weight: 600;">Your ticket purchase is confirmed! Get ready for an epic gaming experience.</p>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>📋 What Happens Next?</h3>
                
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Check Your Email</h4>
                        <p>You should have received a ticket confirmation email with your e-ticket and invitation tokens. If not, check your spam folder.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Share Invitation Tokens</h4>
                        <p>Use your 5 invitation tokens to invite friends! Each token allows one person to purchase their ticket.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Prepare for the Event</h4>
                        <p>Get your gaming gear ready and mark your calendar. We'll send more preparation details closer to the event date.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Join the Community</h4>
                        <p>Follow our social media pages for updates, announcements, and behind-the-scenes content leading up to the event.</p>
                    </div>
                </div>
            </div>

            <!-- Preparation Checklist -->
            <div class="preparation-checklist">
                <h3>🎯 Preparation Checklist</h3>
                <p style="margin-bottom: 20px; color: #0d9488;">Here's what you should start preparing for the ultimate gaming experience:</p>
                
                <div class="checklist-grid">
                    <div class="checklist-column">
                        <h4>🎮 Gaming Gear</h4>
                        <ul>
                            <li>Gaming mouse & keyboard</li>
                            <li>Mouse pad</li>
                            <li>Headset/earphones</li>
                            <li>USB cables & adapters</li>
                            <li>Controller (if needed)</li>
                        </ul>
                    </div>
                    
                    <div class="checklist-column">
                        <h4>📋 Event Essentials</h4>
                        <ul>
                            <li>E-ticket (print or mobile)</li>
                            <li>Valid photo ID</li>
                            <li>Comfortable clothing</li>
                            <li>Water bottle</li>
                            <li>Snacks (if allowed)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Important Information -->
            <div class="important-info">
                <h4>⚠️ Important Event Information</h4>
                <p><strong>Event Date:</strong> {{EVENT_DATE}}</p>
                <p><strong>Event Time:</strong> {{EVENT_TIME}}</p>
                <p><strong>Venue:</strong> {{VENUE_ADDRESS}}</p>
                <p><strong>Doors Open:</strong> 30 minutes before event start time</p>
                <p><strong>Contact:</strong> support@nooballiance.lk for any questions</p>
            </div>

            <!-- Action Buttons -->
            <div style="text-align: center; margin-top: 30px;">
                <h4>Stay Connected</h4>
                <p style="margin-bottom: 20px;">Don't miss any updates about RESET 2025!</p>
                
                <div>
                    <a href="https://nooballiance.lk/reset" class="cta-button">Event Information</a>
                    <a href="#" class="cta-button secondary">Join Discord</a>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="mailto:support@nooballiance.lk" class="cta-button">Contact Support</a>
                </div>
            </div>

            <!-- Final Message -->
            <div style="text-align: center; margin-top: 40px; padding: 20px; background: #f7fafc; border-radius: 8px;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">We Can't Wait to See You!</h4>
                <p style="color: #4a5568;">Get ready for an unforgettable experience at Sri Lanka's premier esports reunion. Practice your skills, invite your friends, and prepare for epic battles!</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>Welcome to RESET 2025!</h4>
            <p>Reunion of Sri Lankan Esports</p>
            <p>Organized by <a href="https://nooballiance.lk">Noob Alliance</a></p>
            <p style="margin-top: 15px; font-size: 0.8rem; opacity: 0.7;">
                This is an automated follow-up email. Please do not reply to this email address.
            </p>
        </div>
    </div>
</body>
</html> 