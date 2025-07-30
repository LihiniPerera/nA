<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reusable Recent Purchases Carousel Component
 * Displays recent purchases with dynamic scaling and real-time updates
 */
class ResetCarousel {
    private static $instance = null;
    private $db;
    private $capacity;
    
    private function __construct() {
        $this->db = ResetDatabase::getInstance();
        $this->capacity = ResetCapacity::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Render the recent purchases carousel
     * 
     * @param array $options Configuration options for the carousel
     *   - context: 'booking' | 'event_details' (default: 'event_details')
     *   - min_purchases: Minimum purchases needed to show carousel (default: 3)
     *   - max_rows: Maximum number of rows to display (default: 2)
     *   - dynamic_scaling: Whether to use capacity-based scaling (default: false)
     *   - css_class: Additional CSS class for styling (default: '')
     *   - title: Custom title for the carousel (default: 'Recent purchases')
     */
    public function render($options = array()) {
        // Default configuration
        $defaults = array(
            'context' => 'event_details',
            'min_purchases' => 3,
            'max_rows' => 2,
            'dynamic_scaling' => false,
            'css_class' => '',
            'title' => 'Recent purchases'
        );
        
        $config = array_merge($defaults, $options);
        
        // Get capacity status for calculations
        $capacity_status = $this->capacity->get_capacity_status();
        $total_purchases = $capacity_status['current_tickets'];
        $max_capacity = $capacity_status['max_capacity'];
        
        // Check if we should show the carousel
        if ($total_purchases < $config['min_purchases']) {
            return; // Don't show carousel if not enough purchases
        }
        
        // Calculate display parameters
        $display_params = $this->calculateDisplayParameters($config, $capacity_status);
        
        // Get recent purchases data
        $recent_purchases = $this->db->get_recent_purchases_for_display($display_params['max_items']);
        
        if (empty($recent_purchases)) {
            return; // No purchases to show
        }
        
        // Chunk purchases into rows
        $chunks = array_chunk($recent_purchases, $display_params['items_per_row']);
        $chunks = array_slice($chunks, 0, $display_params['max_rows']);
        
        // Render the carousel
        $this->renderCarousel($config, $display_params, $chunks, $capacity_status);
    }
    
    /**
     * Calculate display parameters based on context and capacity
     */
    private function calculateDisplayParameters($config, $capacity_status) {
        $max_capacity = $capacity_status['max_capacity'];
        $total_purchases = $capacity_status['current_tickets'];
        $capacity_percentage = ($total_purchases / $max_capacity) * 100;
        
        // if ($config['dynamic_scaling'] && $config['context'] === 'booking') {
        if ($config['dynamic_scaling']) {
            // Full dynamic scaling for booking context
            if ($capacity_percentage <= 20) {
                $items_per_row = 5;
                $max_rows = 2;
                $stage = 'early';
            } elseif ($capacity_percentage <= 50) {
                $items_per_row = 5;
                $max_rows = 3;
                $stage = 'growing';
            } elseif ($capacity_percentage <= 80) {
                $items_per_row = 6;
                $max_rows = 3;
                $stage = 'popular';
            } else {
                $items_per_row = 7;
                $max_rows = 3;
                $stage = 'near_capacity';
            }
            
            // Cap for very large events
            if ($max_capacity > 1000) {
                $max_items = min($items_per_row * $max_rows, 30);
            } else {
                $max_items = $items_per_row * $max_rows;
            }
        }
        // } else {
        //     // Simplified scaling for event details or non-dynamic contexts
        //     if ($config['context'] === 'event_details') {
        //         $items_per_row = min(4, max(3, floor($total_purchases / 10))); // 3-4 items per row
        //         $max_rows = min($config['max_rows'], 2); // Max 2 rows for event details
        //         $stage = 'simplified';
        //     } else {
        //         $items_per_row = 5;
        //         $max_rows = $config['max_rows'];
        //         $stage = 'static';
        //     }
            
        //     $max_items = $items_per_row * $max_rows;
        // }
        
        return array(
            'items_per_row' => $items_per_row,
            'max_rows' => $max_rows,
            'max_items' => $max_items,
            'stage' => $stage,
            'capacity_percentage' => $capacity_percentage
        );
    }
    
    /**
     * Render the carousel HTML
     */
    private function renderCarousel($config, $display_params, $chunks, $capacity_status) {
        $css_classes = 'reset-recent-purchases-section';
        if (!empty($config['css_class'])) {
            $css_classes .= ' ' . $config['css_class'];
        }
        
        ?>
        <div class="<?php echo esc_attr($css_classes); ?>" 
             data-context="<?php echo esc_attr($config['context']); ?>"
             data-capacity-percentage="<?php echo round($display_params['capacity_percentage'], 1); ?>" 
             data-stage="<?php echo esc_attr($display_params['stage']); ?>">
            
            <div class="reset-recent-purchases-header">
                <span class="reset-recent-purchases-title"><?php echo esc_html($config['title']); ?></span>
            </div>
            
            <div class="reset-recent-purchases-grid">
                <?php foreach ($chunks as $row_index => $row_purchases): ?>
                    <div class="reset-recent-purchases-row" data-row="<?php echo $row_index; ?>">
                        <div class="reset-recent-purchases-track">
                            <?php foreach ($row_purchases as $purchase): ?>
                                <div class="reset-recent-purchase-badge">
                                    <div class="reset-purchase-name"><?php echo esc_html($purchase['display_name']); ?></div>
                                    <div class="reset-purchase-time <?php echo $purchase['has_addons'] ? 'has-addons' : ''; ?>" data-created-at="<?php echo esc_attr($purchase['created_at']); ?>"><?php echo esc_html($purchase['time_ago']); ?></div>
                                </div>
                            <?php endforeach; ?>
                            <!-- Duplicate badges for seamless loop -->
                            <?php foreach ($row_purchases as $purchase): ?>
                                <div class="reset-recent-purchase-badge">
                                    <div class="reset-purchase-name"><?php echo esc_html($purchase['display_name']); ?></div>
                                    <div class="reset-purchase-time <?php echo $purchase['has_addons'] ? 'has-addons' : ''; ?>" data-created-at="<?php echo esc_attr($purchase['created_at']); ?>"><?php echo esc_html($purchase['time_ago']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        // Include CSS and JavaScript
        $this->renderStyles($config);
        $this->renderScripts($config);
    }
    
    /**
     * Render carousel styles
     */
    private function renderStyles($config) {
        static $styles_rendered = false;
        
        if ($styles_rendered) {
            return; // Avoid duplicate styles
        }
        
        $styles_rendered = true;
        ?>
        <style>
        /* Reset Carousel Styles */
        .reset-recent-purchases-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        /* Context-specific styling */
        .reset-recent-purchases-section[data-context="event_details"] {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 16px;
            margin-bottom: 40px;
            margin-right: 40px;
            margin-left: 40px;
        }
        
        .reset-recent-purchases-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .reset-recent-purchases-title {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchases-title {
            font-size: 13px;
            color: #777;
        }
        
        .reset-recent-purchases-grid {
            display: flex;
            flex-direction: column;
            gap: 0px;
        }
        
        .reset-recent-purchases-row {
            overflow: hidden;
            white-space: nowrap;
            position: relative;
            mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
        }
        
        .reset-recent-purchases-track {
            display: inline-flex;
            gap: 12px;
            padding: 10px 0;
            animation-play-state: paused;
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchases-track {
            gap: 10px;
            padding: 8px 0;
        }
        
        .reset-recent-purchase-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            padding: 8px 16px;
            flex-shrink: 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchase-badge {
            padding: 6px 12px;
            border-radius: 16px;
        }
        
        .reset-recent-purchase-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .reset-purchase-name {
            font-size: 13px;
            color: #000000;
            font-weight: 500;
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-name {
            font-size: 11px;
        }
        
        .reset-purchase-time {
            background: #f9c613;
            color: #000000;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .reset-purchase-time.has-addons {
            background: #8B5FBF;
            color: #ffffff;
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-time {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 8px;
        }
        
        /* Animation keyframes */
        @keyframes resetMarqueeScroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        /* Pause animation on hover */
        .reset-recent-purchases-row:hover .reset-recent-purchases-track {
            animation-play-state: paused;
        }
        
        /* Enable animation */
        .reset-recent-purchases-track.animate {
            animation: resetMarqueeScroll 30s linear infinite;
        }
        
        .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchases-track.animate {
            animation-duration: 35s; /* Slightly slower for event details */
        }
        
        /* Staggered animation for rows */
        .reset-recent-purchases-row:nth-child(1) .reset-recent-purchases-track.animate {
            animation-delay: 0s !important;
        }
        
        .reset-recent-purchases-row:nth-child(2) .reset-recent-purchases-track.animate {
            animation-delay: 0s !important;
        }
        
        .reset-recent-purchases-row:nth-child(3) .reset-recent-purchases-track.animate {
            animation-delay: 0s !important;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .reset-recent-purchases-section {
                padding: 16px;
                margin-bottom: 24px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] {
                padding: 12px;
                margin-bottom: 20px;
                margin-right: 20px;
                margin-left: 20px;
            }
            
            .reset-recent-purchases-title {
                font-size: 12px;
            }
            
            .reset-recent-purchases-track.animate {
                gap: 8px;
                animation-duration: 25s;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchases-track.animate {
                animation-duration: 30s;
            }
            
            .reset-recent-purchase-badge {
                min-width: 120px;
                padding: 10px 12px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchase-badge {
                min-width: 100px;
                padding: 8px 10px;
            }
            
            .reset-purchase-name {
                font-size: 11px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-name {
                font-size: 10px;
            }
            
            .reset-purchase-time {
                font-size: 10px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-time {
                font-size: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .reset-recent-purchases-section {
                padding: 14px;
                margin-bottom: 20px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] {
                padding: 10px;
                margin-bottom: 20px;
                margin-right: 20px;
                margin-left: 20px;
            }
            
            .reset-recent-purchases-title {
                font-size: 11px;
            }
            
            .reset-recent-purchases-track.animate {
                gap: 6px;
                animation-duration: 20s;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchases-track.animate {
                animation-duration: 25s;
            }
            
            .reset-recent-purchase-badge {
                min-width: 100px;
                padding: 8px 10px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-recent-purchase-badge {
                min-width: 90px;
                padding: 6px 8px;
            }
            
            .reset-purchase-name {
                font-size: 10px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-name {
                font-size: 9px;
            }
            
            .reset-purchase-time {
                font-size: 9px;
            }
            
            .reset-recent-purchases-section[data-context="event_details"] .reset-purchase-time {
                font-size: 8px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Render carousel JavaScript
     */
    private function renderScripts($config) {
        static $scripts_rendered = false;
        
        if ($scripts_rendered) {
            return; // Avoid duplicate scripts
        }
        
        $scripts_rendered = true;
        ?>
        <script>
        // Reset Carousel JavaScript
        (function() {
            // Time calculation functions
            function calculateTimeAgo(datetime) {
                const now = new Date();
                let created;
                
                if (datetime.includes('T')) {
                    created = new Date(datetime);
                } else if (datetime.includes('-') && datetime.includes(':')) {
                    created = new Date(datetime.replace(' ', 'T'));
                } else {
                    created = new Date(datetime);
                }
                
                if (isNaN(created.getTime())) {
                    console.error('Invalid date format:', datetime);
                    return 'Just Now';
                }
                
                const timeDiff = Math.floor((now - created) / 1000);
                
                if (timeDiff < -60) {
                    return 'Just Now';
                }
                
                if (timeDiff < 0) {
                    return '1 sec ago';
                }
                
                if (timeDiff < 60) {
                    return Math.min(timeDiff, 99) + ' sec ago';
                } else if (timeDiff < 3600) {
                    const minutes = Math.floor(timeDiff / 60);
                    return minutes + ' min ago';
                } else if (timeDiff < 86400) {
                    const hours = Math.floor(timeDiff / 3600);
                    return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
                } else if (timeDiff < 2592000) {
                    const days = Math.floor(timeDiff / 86400);
                    return days + ' day' + (days > 1 ? 's' : '') + ' ago';
                } else {
                    const date = new Date(datetime);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
            
            // Update times function
            function updateRecentPurchasesTimes() {
                const timeElements = document.querySelectorAll('.reset-purchase-time');
                
                timeElements.forEach(element => {
                    const createdAt = element.getAttribute('data-created-at');
                    if (createdAt) {
                        const timeAgo = calculateTimeAgo(createdAt);
                        element.textContent = timeAgo;
                    }
                });
            }
            
            // Initialize animation
            function initRecentPurchasesAnimation() {
                const tracks = document.querySelectorAll('.reset-recent-purchases-track');
                
                if (tracks.length > 0) {
                    setTimeout(() => {
                        tracks.forEach((track, index) => {
                            track.classList.add('animate');
                            
                            const section = track.closest('.reset-recent-purchases-section');
                            const stage = section?.getAttribute('data-stage');
                            
                            let animationDelay = index * 10;
                            if (stage === 'near_capacity') {
                                animationDelay = index * 8;
                            } else if (stage === 'early' || stage === 'simplified') {
                                animationDelay = index * 12;
                            }
                            
                            track.style.animationDelay = animationDelay + 's';
                            track.style.animationPlayState = 'running';
                        });
                    }, 500);
                }
            }
            
            // Handle page visibility changes
            function handleVisibilityChange() {
                const tracks = document.querySelectorAll('.reset-recent-purchases-track');
                tracks.forEach(track => {
                    if (track.classList.contains('animate')) {
                        if (document.hidden) {
                            track.style.animationPlayState = 'paused';
                        } else {
                            track.style.animationPlayState = 'running';
                        }
                    }
                });
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    if (document.querySelector('.reset-recent-purchases-section')) {
                        initRecentPurchasesAnimation();
                        setInterval(updateRecentPurchasesTimes, 10000);
                        document.addEventListener('visibilitychange', handleVisibilityChange);
                    }
                });
            } else {
                if (document.querySelector('.reset-recent-purchases-section')) {
                    initRecentPurchasesAnimation();
                    setInterval(updateRecentPurchasesTimes, 10000);
                    document.addEventListener('visibilitychange', handleVisibilityChange);
                }
            }
        })();
        </script>
        <?php
    }
} 