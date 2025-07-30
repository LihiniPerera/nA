<?php
// Get the wizard instance to check session status
$wizard = ResetBookingWizard::getInstance();
$session_data = $wizard->get_session_data();

// Check if user has a valid session for booking
$has_valid_session = !empty($session_data) && !$wizard->is_session_expired();

// If session is expired, clear it
if (!empty($session_data) && $wizard->is_session_expired()) {
    $wizard->clear_session();
    $has_valid_session = false;
}

// Additional check: If session exists but token is invalid, clear session
// (This is now handled in the CRITICAL FIX section below)

// CRITICAL FIX: Force session validation for booking access
// Only allow booking if user has a valid session with a valid token
if ($has_valid_session) {
    // Double-check that the session has all required data
    if (empty($session_data['token_code']) || empty($session_data['token_id']) || empty($session_data['token_type'])) {
        $wizard->clear_session();
        $has_valid_session = false;
    }
    
    // Additional check: Ensure the token is still valid in the database
    if ($has_valid_session && !empty($session_data['token_code'])) {
        $tokens = ResetTokens::getInstance();
        $token_validation = $tokens->validate_token($session_data['token_code']);
        if (!$token_validation['valid']) {
            $wizard->clear_session();
            $has_valid_session = false;
        }
    }
}

// FINAL FIX: Check if user came from key entry page
$from_key_entry = isset($_GET['from']) && $_GET['from'] === 'key_entry';

// CRITICAL FIX: If user came from key entry page, they should NOT have booking access
// unless they have a valid session from proper key validation
if ($from_key_entry) {
    // User clicked "Event Details" from key entry page
    // This means they did NOT enter a key, so they should NOT have booking access
    $has_valid_session = false;
    
    // Clear any existing session since they didn't enter a key
    if (!empty($session_data)) {
        $wizard->clear_session();
        $session_data = array();
    }
}

// ADDITIONAL SECURITY: If user accessed page directly without any parameters and has no valid session,
// they should not have booking access
if (!$from_key_entry && !$has_valid_session) {
    // User accessed page directly without proper key validation
    $has_valid_session = false;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RESET 2025 - Event Details</title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .event-container {
            background: white;
            border-radius: 20px;
            margin: 20px auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            max-width: 1000px;
            width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .event-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
        }
        
        .event-header {
            text-align: center;
            padding: 40px 40px 20px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        
        .event-logo {
            /* margin-bottom: 30px; */
        }
        
        .event-logo img {
            max-width: 300px !important;
            height: auto !important;
            transition: transform 0.3s ease !important;
            display: unset !important;
        }
        
        .event-logo img:hover {
            transform: scale(1.05);
        }
        
        .event-title {
            font-size: 32px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        
        .event-tagline {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
            font-style: italic;
        }
        
        .event-basic-info {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            padding: 20px;
            border-radius: 15px;
            margin: 0px 40px;
            font-weight: 600;
        }
        
        .basic-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            text-align: center;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .info-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 700;
        }
        
        .activities-section {
            padding: 40px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #000000;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
            border-radius: 2px;
        }
        
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .activity-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #f9c613;
        }
        
        .activity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .activity-card:hover::before {
            transform: scaleX(1);
        }
        
        .activity-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .activity-icon {
            font-size: 24px;
            margin-right: 12px;
            width: 32px;
            text-align: center;
        }
        
        .activity-title {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
            flex: 1;
        }
        
        .activity-description {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }
        
        .highlights-section {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: white;
            padding: 40px;
            margin: 0 -1px;
        }
        
        .highlights-title {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            color: #f9c613;
        }
        
        .highlights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .highlight-item {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            border: 1px solid rgba(249, 198, 19, 0.3);
        }
        
        .highlight-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #f9c613;
        }
        
        .highlight-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #f9c613;
        }
        
        .highlight-description {
            font-size: 14px;
            color: rgba(255,255,255,0.9);
            line-height: 1.4;
        }
        
        .continue-section {
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        
        .continue-message {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .continue-btn {
            background: linear-gradient(135deg, #f9c613 0%, #ffdd44 100%);
            color: #000000;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(249, 198, 19, 0.3);
        }
        
        .continue-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(249, 198, 19, 0.4);
            text-decoration: none;
            color: #000000;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #f9c613;
            text-decoration: none;
        }
        
        .event-description-top-wrapper {
            margin: 0px 30px 40px;
            text-align: center;
            font-weight: 600;
            font-size: 20px;
        }

        p.event-description-top{
            margin-top: 0;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .event-description-top-wrapper {
                font-size: 18px;
            }

            .event-container {
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .event-header {
                padding: 30px 20px 15px 20px;
            }
            
            .event-title {
                font-size: 26px;
            }
            
            .event-tagline {
                font-size: 16px;
            }
            
            .event-basic-info {
                margin: 0px 20px;
                padding: 15px;
            }
            
            .basic-info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .activities-section {
                padding: 30px 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .activities-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .activity-card {
                padding: 15px;
            }
            
            .highlights-section {
                padding: 30px 20px;
            }
            
            .highlights-title {
                font-size: 20px;
            }
            
            .highlights-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .continue-section {
                padding: 30px 20px;
            }
            
            .continue-message {
                font-size: 16px;
            }
            
            .continue-btn {
                padding: 12px 30px;
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .event-title {
                font-size: 22px;
            }
            
            .event-tagline {
                font-size: 14px;
            }
            
            .info-value {
                font-size: 14px;
            }
            
            .activity-title {
                font-size: 14px;
            }
            
            .activity-description {
                font-size: 13px;
            }
            
            .highlight-icon {
                font-size: 32px;
            }
            
            .highlight-title {
                font-size: 16px;
            }
            
            .highlight-description {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="event-container">
        <!-- Event Header -->
        <div class="event-header">
            <div class="event-logo">
                <?php
                $core = ResetCore::getInstance();
                echo $core->get_logo_html('black', '400', '', '300px');
                ?>
            </div>
        </div>
        
        <div class="event-description-top-wrapper">
            <p class="event-description-top">Read the Event Details below, then press continue at the bottom.</p>
        </div>
        
        <!-- Recent Purchases Carousel -->
        <?php 
        // Initialize and render carousel for event details context
        $carousel = ResetCarousel::getInstance();
        $carousel->render(array(
            'context' => 'event_details',
            'min_purchases' => 5,
            'max_rows' => 3,
            'dynamic_scaling' => true,
            'title' => 'Recent purchases',
            'css_class' => 'event-details-carousel'
        )); 
        ?>
        
        <!-- Basic Event Information -->
        <div class="event-basic-info">
            <div class="basic-info-grid">
                <div class="info-item">
                    <div class="info-icon">📅</div>
                    <div class="info-label">Date</div>
                    <div class="info-value">July 27th 2025</div>
                    <div class="info-value">(Sunday - 10:00 AM - 11:00 PM)</div>
                </div>
                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <div class="info-label">Venue</div>
                    <div class="info-value">Trace Expert City</div>
                </div>
                <div class="info-item">
                    <div class="info-icon">🎮</div>
                    <div class="info-label">Experience</div>
                    <div class="info-value">Ultimate Gaming</div>
                    <div class="info-value">Reunion</div>
                </div>
            </div>
        </div>
        
        <!-- Activities Section -->
        <div class="activities-section">
            <h2 class="section-title">Event Activities</h2>
            
            <div class="activities-grid">
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🖥️</div>
                        <div class="activity-title">PC Gaming Stations</div>
                    </div>
                    <div class="activity-description">20-60 gaming stations for the ultimate gaming experience</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🎬</div>
                        <div class="activity-title">Documentary Premiere</div>
                    </div>
                    <div class="activity-description">Exclusive premiere and release of Sri Lankan Esports documentary</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🏆</div>
                        <div class="activity-title">Official Tournaments</div>
                    </div>
                    <div class="activity-description">Valorant & PUBG Mobile Finals, specatate the best teams in Sri Lanka battling it out</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🎉</div>
                        <div class="activity-title">Afterparty Experience</div>
                    </div>
                    <div class="activity-description">DJ entertainment and drinks (5:00 PM - 11:00 PM)</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">⚡</div>
                        <div class="activity-title">Mini Tournaments</div>
                    </div>
                    <div class="activity-description">1v1 challenges, strafing competitions, and skill contests</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🏎️</div>
                        <div class="activity-title">Challenge Arena</div>
                    </div>
                    <div class="activity-description">RC racing, VR experiences, and 2v2 IRL challenges</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🍜</div>
                        <div class="activity-title">Street Food</div>
                    </div>
                    <div class="activity-description">Authentic Sri Lankan street food</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">📸</div>
                        <div class="activity-title">Photobooth</div>
                    </div>
                    <div class="activity-description">Professional photobooth for memorable moments</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🗂️</div>
                        <div class="activity-title">Community Wall</div>
                    </div>
                    <div class="activity-description">Memory sharing and community connection space</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🎖️</div>
                        <div class="activity-title">Alumni Recognition</div>
                    </div>
                    <div class="activity-description">Special ceremony honoring esports alumni achievements</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🎪</div>
                        <div class="activity-title">Opening Ceremony</div>
                    </div>
                    <div class="activity-description">Fun experience</div>
                </div>
                
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-icon">🛋️</div>
                        <div class="activity-title">Chill Zones</div>
                    </div>
                    <div class="activity-description">Comfortable rest areas for relaxation and networking</div>
                </div>
            </div>
        </div>
        
        <!-- Highlights Section -->
        <div class="highlights-section">
            <h2 class="highlights-title">Why RESET 2025?</h2>
            
            <div class="highlights-grid">
                <div class="highlight-item">
                    <div class="highlight-icon">🎯</div>
                    <div class="highlight-title">Reunion Experience</div>
                    <div class="highlight-description">Reconnect with Sri Lankan Esports legends from 2007-2018 era</div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">🏅</div>
                    <div class="highlight-title">Competitive Gaming</div>
                    <div class="highlight-description">Official tournaments and mini-competitions for all skill levels</div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">🎊</div>
                    <div class="highlight-title">Complete Experience</div>
                    <div class="highlight-description">Gaming, entertainment, food, and networking all in one event</div>
                </div>
            </div>
        </div>
        
        <!-- Continue Section -->
        <div class="continue-section">
            <?php if ($has_valid_session): ?>
                <!-- User has valid session - show booking option -->
                <div class="continue-message">
                    Ready to be part of Sri Lanka's most epic Esports reunion? <br>
                    <strong>Let's get your ticket sorted!</strong>
                </div>
                
                <a href="<?php echo site_url('/reset/booking/step/1'); ?>" class="continue-btn">
                    Continue to Booking
                </a>
                
                <div>
                    <a href="<?php echo site_url('/reset'); ?>" class="back-link">← Back to Key Entry</a>
                </div>
            <?php else: ?>
                <!-- User has no valid session - show key entry option only -->
                <div class="continue-message">
                    Ready to be part of Sri Lanka's most epic Esports reunion? <br>
                    <strong>Enter your key to start booking!</strong>
                </div>
                
                <a href="<?php echo site_url('/reset'); ?>" class="continue-btn">
                    Back to Key Entry
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html> 