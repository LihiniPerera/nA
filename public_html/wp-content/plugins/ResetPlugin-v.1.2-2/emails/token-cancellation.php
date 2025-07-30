<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESET 2025 - Token Cancellation Notice</title>
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
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
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
        
        .notice-section {
            background: linear-gradient(135deg, #ffab91 0%, #ff8a65 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .notice-section h2 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .notice-section p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cancellation-details {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #ffa726;
        }
        
        .cancellation-details h3 {
            color: #ef6c00;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #ffe0b2;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #bf360c;
        }
        
        .detail-value {
            color: #d84315;
            font-weight: 500;
        }
        
        .reason-section {
            background: #f3e5f5;
            border: 1px solid #ce93d8;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .reason-section h3 {
            color: #7b1fa2;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .reason-section p {
            color: #4a148c;
            line-height: 1.7;
        }
        
        .apology-section {
            background: #e8f5e8;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .apology-section h3 {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .apology-section p {
            color: #1b5e20;
            font-size: 1.1rem;
            line-height: 1.7;
        }
        
        .alternatives-section {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .alternatives-section h3 {
            color: #0d47a1;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .alternative-option {
            background: #ffffff;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alternative-option:last-child {
            margin-bottom: 0;
        }
        
        .option-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            background: #2196f3;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .option-content h4 {
            color: #1565c0;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .option-content p {
            color: #0d47a1;
            font-size: 0.95rem;
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
            margin: 10px;
            transition: transform 0.2s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        .cta-button.secondary {
            background: linear-gradient(135deg, #26c6da 0%, #00acc1 100%);
        }
        
        .contact-section {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .contact-section h3 {
            color: #424242;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .contact-section p {
            color: #616161;
            margin-bottom: 10px;
        }
        
        .contact-section a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 600;
        }
        
        .contact-section a:hover {
            text-decoration: underline;
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
            
            .alternative-option {
                flex-direction: column;
                text-align: center;
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
            <h1>RESET 2025</h1>
            <p>Token Cancellation Notice</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Notice Section -->
            <div class="notice-section">
                <h2>⚠️ Token Cancellation Notice</h2>
                <p>We regret to inform you that your invitation token has been cancelled</p>
            </div>

            <!-- Cancellation Details -->
            <div class="cancellation-details">
                <h3>📋 Cancellation Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Token Code:</span>
                    <span class="detail-value">{{TOKEN_CODE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Token Type:</span>
                    <span class="detail-value">{{TOKEN_TYPE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cancelled Date:</span>
                    <span class="detail-value">{{CANCELLED_DATE}}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cancelled By:</span>
                    <span class="detail-value">{{CANCELLED_BY}}</span>
                </div>
            </div>

            <!-- Reason Section -->
            <div class="reason-section">
                <h3>💭 Reason for Cancellation</h3>
                <p>{{CANCELLATION_REASON}}</p>
            </div>

            <!-- Apology Section -->
            <div class="apology-section">
                <h3>🙏 Our Sincere Apologies</h3>
                <p>We deeply apologize for any inconvenience this may cause. This decision was not made lightly, and we understand your disappointment. We appreciate your interest in RESET 2025 and hope to provide you with alternative opportunities.</p>
            </div>

            <!-- Alternatives Section -->
            <div class="alternatives-section">
                <h3>🔄 Alternative Options</h3>
                
                <div class="alternative-option">
                    <div class="option-icon">📝</div>
                    <div class="option-content">
                        <h4>Join Our Waitlist</h4>
                        <p>Be the first to know if more tokens become available or if there are last-minute cancellations.</p>
                    </div>
                </div>
                
                <div class="alternative-option">
                    <div class="option-icon">🎯</div>
                    <div class="option-content">
                        <h4>Priority for Future Events</h4>
                        <p>Get priority access to tokens for our next major event. We'll notify you first when tickets become available.</p>
                    </div>
                </div>
                
                <div class="alternative-option">
                    <div class="option-icon">📱</div>
                    <div class="option-content">
                        <h4>Follow Updates</h4>
                        <p>Stay connected with our social media and newsletter for announcements about additional opportunities.</p>
                    </div>
                </div>
                
                <div class="alternative-option">
                    <div class="option-icon">🤝</div>
                    <div class="option-content">
                        <h4>Community Involvement</h4>
                        <p>Join our community events and activities. Active members get early access to future tickets.</p>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <h3>📞 Questions or Concerns?</h3>
                <p>If you have any questions about this cancellation or would like to discuss alternative options, please don't hesitate to contact us:</p>
                <p><strong>Email:</strong> <a href="mailto:support@nooballiance.lk">support@nooballiance.lk</a></p>
                <p><strong>Phone:</strong> <a href="tel:+94771234567">+94 77 123 4567</a></p>
                <p><strong>Website:</strong> <a href="https://nooballiance.lk">nooballiance.lk</a></p>
                
                <div style="margin-top: 20px;">
                    <a href="https://nooballiance.lk/contact" class="cta-button">Contact Us</a>
                    <a href="https://nooballiance.lk/waitlist" class="cta-button secondary">Join Waitlist</a>
                </div>
            </div>

            <!-- Additional Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h4 style="color: #495057; margin-bottom: 10px;">📢 What This Means</h4>
                <ul style="color: #6c757d; margin-left: 20px;">
                    <li>Your token <strong>{{TOKEN_CODE}}</strong> is no longer valid for ticket purchases</li>
                    <li>If you haven't purchased a ticket yet, you won't be able to use this token</li>
                    <li>If you've already purchased a ticket, this notice doesn't affect your existing booking</li>
                    <li>We'll keep your contact information for future event announcements</li>
                </ul>
            </div>

            <!-- Thank You Message -->
            <div style="text-align: center; margin-top: 30px;">
                <h4 style="color: #2d3748; margin-bottom: 15px;">Thank You for Your Understanding</h4>
                <p style="color: #4a5568; font-size: 1.1rem; line-height: 1.6;">
                    We truly appreciate your interest in RESET 2025. While this particular token couldn't be fulfilled, 
                    we're committed to making future events accessible to passionate community members like you.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>RESET 2025 Team</h4>
            <p>Reunion of Sri Lankan Esports</p>
            <p>Organized by <a href="https://nooballiance.lk">Noob Alliance</a></p>
            <p style="margin-top: 15px; font-size: 0.8rem; opacity: 0.7;">
                This is an automated cancellation notice. Please reply if you have any questions.
            </p>
        </div>
    </div>
</body>
</html> 