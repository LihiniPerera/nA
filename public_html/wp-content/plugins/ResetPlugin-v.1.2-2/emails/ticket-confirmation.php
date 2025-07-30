<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>RESET 2025 - Ticket Confirmation</title>
    <style>
        /* Basic reset for maximum email client compatibility */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Root color scheme support */
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
        
        /* Logo switching for dark mode support */
        .logo-dark {
            display: none !important;
        }
        
        .invitaion-title {
            color: #000000;
        }

        .invitaion-keys {
            color: #000000;
        }

        @media (prefers-color-scheme: dark) {
            .logo-light {
                display: none !important;
            }
            .logo-dark {
                display: block !important;
            }

            .invitaion-title {
                color: #ffffff;
            }

            .invitaion-keys {
                color: #ffffff;
            }
        }
        
        /* Mobile responsive styles for supporting email clients */
        @media (max-width: 600px) {
            body {
                padding: 10px 0 !important;
            }
            
            .email-container {
                margin: 0 10px !important;
                border-radius: 15px !important;
                box-shadow: none !important;
            }
            
            .email-header, .email-content, .email-footer {
                padding: 25px 20px !important;
            }
            
            .email-header img {
                max-width: 250px !important;
            }
            
            .email-success h2 {
                font-size: 1.5rem !important;
            }
            
            .email-token {
                padding: 12px 8px !important;
                font-size: 0.8rem !important;
                min-height: 40px !important;
                width: 120px !important;
                margin: 5px !important;
            }
            
            .email-detail-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
            }
            
            .email-section {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body class="body" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #333; padding: 20px 0; margin: 0;">
    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px rgba(255, 255, 255, 0.1); border: 4px solid #f9c613">
        <!-- Header with Logo -->
        <div style="background: linear-gradient(#000000, #000000); color: white; padding: 40px 30px; text-align: center; position: relative;">
            <div style="margin-bottom: 20px;">
                <!-- Default logo for Gmail iOS and other clients that don't support prefers-color-scheme -->
                <img src="https://nooballiance.lk/wp-content/uploads/2025/07/logo-with-text-white-400.png" alt="RESET 2025 Logo" style="max-width: 300px; height: auto; display: block; margin: 0 auto;">
                
                <!-- Light mode logo (hidden by default, shown for prefers-color-scheme: light) -->
                <div class="logo-light" style="mso-hide: all; display: none;">
                    <img src="https://nooballiance.lk/wp-content/uploads/2025/07/logo-with-text-white-400.png" alt="RESET 2025 Logo" style="max-width: 300px; height: auto; display: block; margin: 0 auto;">
                </div>
                
                <!-- Dark mode logo (hidden by default, shown for prefers-color-scheme: dark) -->
                <div class="logo-dark" style="mso-hide: all; display: none;">
                    <img src="https://nooballiance.lk/wp-content/uploads/2025/07/logo-with-text-white-400.png" alt="RESET 2025 Logo" style="max-width: 300px; height: auto; display: block; margin: 0 auto;">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
                        <!-- Success Message -->
            <div style="background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%); color: #000000; padding: 25px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(249, 198, 19, 0.3);">
                <h2 style="font-size: 1.8rem; margin-bottom: 10px; font-weight: 800;">🎉 Congratulations!</h2>
                <p style="font-weight: 600; font-size: 1.1rem; margin: 0;">Your ticket has been confirmed for RESET 2025</p>
            </div>

            <!-- invitation keys -->
            <div style="background: #ffffff; border-radius: 15px; padding: 30px; margin-bottom: 30px; border: 1px solid #e9ecef; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); -webkit-text-fill-color: #000000 !important; color: #000000;" >
                <h3 style="color: #000000; margin-bottom: 20px; font-size: 1.4rem; font-weight: 700; -webkit-text-fill-color: #000000 !important;" class="invitaion-title">Your 5 Invitation keys</h3>
                <p style="margin-bottom: 25px; color: #333; line-height: 1.6; -webkit-text-fill-color: #333333 !important;">Share these keys with your friends so they can join the event too! Each token allows one person to purchase a ticket.</p>
                
                <!-- Tokens Container using table for email client compatibility -->
                <div style="text-align: center; margin: 25px 0; -webkit-text-fill-color: #000000 !important; color: #000000;" class="invitaion-keys">
                    {{INVITATION_TOKENS}}
                </div>
                
                <p style="margin-top: 20px; font-size: 0.9rem; color: #666; -webkit-text-fill-color: #666666 !important;">
                    <strong style="-webkit-text-fill-color: #000000 !important; color: #000000;">Important:</strong> They can use these keys at <a href="https://nooballiance.lk/reset" style="color: #f9c613; text-decoration: none; font-weight: 600; -webkit-text-fill-color: #f9c613 !important;">nooballiance.lk/reset</a>
                </p>
            </div>

            <!-- Important Notes -->
            <div style="background: #f3e5f5; border: 2px solid #2196f3; border-radius: 15px; padding: 25px; margin-bottom: 30px; position: relative;">
                <h4 style="color: #1565c0; margin-bottom: 15px; font-size: 1.2rem; font-weight: 700; margin-top: 10px;">ℹ️ Important Information</h4>
                <ul style="margin-left: 20px; color: #1565c0;">
                    <li style="margin-bottom: 8px; font-size: 0.95rem; line-height: 1.5;">You will receive a confirmation email with your ticket details</li>
                    <li style="margin-bottom: 8px; font-size: 0.95rem; line-height: 1.5;">Download your e-ticket from the success page for event entry</li>
                    <li style="margin-bottom: 8px; font-size: 0.95rem; line-height: 1.5;">Please arrive at the venue 30 minutes before the event</li>
                    <li style="margin-bottom: 8px; font-size: 0.95rem; line-height: 1.5;">Each invitation key can only be used once</li>
                </ul>
            </div>

            <!-- Event Details -->
            <div style="background:  #ffffff; border-radius: 15px; padding: 30px; margin-bottom: 30px; border: 1px solid #e9ecef; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); -webkit-text-fill-color: #000000; color: #000000 !important;">
                <h3 style="color: #000000; margin-bottom: 20px; font-size: 1.4rem; font-weight: 700; -webkit-text-fill-color: #000000 !important;">📅 Event Details</h3>
                <!-- Using table layout for better email client compatibility -->
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Event:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">RESET 2025</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Date:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">July 27, 2025</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Time:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">10:00 AM - 11:00 PM</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Venue:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">Trace Expert City, Colombo - Bay 07</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Purchaser:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333;">{{PURCHASER_NAME}}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Ticket Type:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 500; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">
                            <span style="background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%); color: #000000; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; -webkit-text-fill-color: #000000 !important;">{{TICKET_TYPE_NAME}}</span>
                        </td>
                    </tr>
                    {{ADDONS_TABLE_ROWS}}
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600; color: #000000; font-size: 1rem; text-align: left; -webkit-text-fill-color: #000000 !important;">Total Amount:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: 700; font-size: 1rem; text-align: right; -webkit-text-fill-color: #333333 !important;">{{TOTAL_AMOUNT}}</td>
                    </tr>
                </table>
 
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #000000; color: #ffffff; padding: 30px; text-align: center;">
            <h4 style="color: #f9c613; margin-bottom: 10px; font-size: 1.3rem; font-weight: 700;">RESET 2025</h4>
            <p style="margin-bottom: 8px; color: #cccccc;">Reunion of Sri Lankan Esports</p>
            <p style="margin-bottom: 8px; color: #cccccc;"><strong>Email:</strong> <a href="mailto:support@nooballiance.lk" style="color: #f9c613; text-decoration: none; font-weight: 600;">support@nooballiance.lk</a></p>
            <p style="margin-bottom: 8px; color: #cccccc;"><strong>Website:</strong> <a href="https://nooballiance.lk" style="color: #f9c613; text-decoration: none; font-weight: 600;">nooballiance.lk</a></p>
            <p style="margin-bottom: 8px; color: #cccccc;">Organized by <a href="https://nooballiance.lk" style="color: #f9c613; text-decoration: none; font-weight: 600;">Noob Alliance</a></p>
            <p style="margin-top: 20px; font-size: 0.8rem; opacity: 0.7; color: #999999;">
                This is an automated email. Please do not reply to this email address.
            </p>
        </div>
    </div>
</body>
</html> 