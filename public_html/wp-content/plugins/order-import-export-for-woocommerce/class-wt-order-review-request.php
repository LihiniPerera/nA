<?php

/**
 * Review request
 *  
 *
 * @package  Cookie_Law_Info  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Order_Import_Export_Review_Request
{
    /**
     * config options 
     */
    private $new_review_banner_title = "";
    private $plugin_title                 =   "Order Export & Order Import for WooCommerce";
    private $review_url                   =   '';
    private $plugin_prefix                =   "wt_o_iew_basic"; /* must be unique name */
    private $activation_hook              =   "wt_o_iew_basic_activate"; /* hook for activation, to store activated date */
    private $deactivation_hook            =   "wt_o_iew_basic_deactivate"; /* hook for deactivation, to delete activated date */
    private $days_to_show_banner          =   7; /* when did the banner to show */
    private $remind_days                  =   5; /* remind interval in days */
    private $webtoffee_logo_url           =   WT_O_IEW_PLUGIN_URL . 'assets/images/webtoffee-logo_small.png';
    private $review_request_bg            =   WT_O_IEW_PLUGIN_URL . 'assets/images/wbtf_review_banner_bg.png';




    private $start_date                   =   0; /* banner to show count start date. plugin installed date, remind me later added date */
    private $current_banner_state         =   2; /* 1: active, 2: waiting to show(first after installation), 3: closed by user/not interested to review, 4: user done the review, 5:remind me later */
    private $banner_state_option_name     =   ''; /* WP option name to save banner state */
    private $start_date_option_name       =   ''; /* WP option name to save start date */
    private $banner_css_class             =   ''; /* CSS class name for Banner HTML element. */
    private $banner_message               =   ''; /* Banner message. */
    private $new_review_banner_message    =   ''; /* New banner message. */
    private $later_btn_text               =   ''; /* Remind me later button text */
    private $later_btn_new_text           =   ''; /* New remind me later button text */
    private $already_did_btn_new_text     =   ''; /* New never review button text */
    private $never_btn_text               =   ''; /* Never review button text. */
    private $review_btn_new_text          =   ''; /* New review now button text */
    private $review_btn_text              =   ''; /* Review now button text. */
    private $ajax_action_name             =   ''; /* Name of ajax action to save banner state. */
    private $current_post_type            =   ''; /* Current post type being processed */
    private $dismissal_count_option       =   ''; /* Option name for dismissal count */
    private $last_dismissal_option        =   ''; /* Option name for last dismissal date */
    private $allowed_action_type_arr      = array(
        'later', /* remind me later */
        'never', /* never */
        'review', /* review now */
        'closed', /* not interested */
    );
    private $plugins_array = array( );

    public function __construct()
    {
        global $wt_iew_review_banner_shown;
        
        //Set config vars
        $this->set_vars();

        add_action($this->activation_hook, array($this, 'on_activate'));
        add_action($this->deactivation_hook, array($this, 'on_deactivate'));
        add_action('admin_notices', array($this, 'show_banner_cta'));

        
        if ($this->check_condition()) /* checks the banner is active now */ {  
            
            $post_type = $this->current_post_type; 

            // Determine which plugin URL to use based on post type
            foreach ($this->plugins_array as $plugin) {
                if (in_array($post_type, $plugin['post_types'])) {
                    $this->review_url = $plugin['url'];
                    break;
                }
            }
            
            $wt_iew_review_banner_shown = true;

            $this->banner_message = sprintf(__("Hey, we at %sWebToffee%s would like to thank you for using our plugin. We would really appreciate if you could take a moment to drop a quick review that will inspire us to keep going."), '<b>', '</b>');

            $this->new_review_banner_title = sprintf(__('%1$s  %2$s  Loving %3$s  WebToffee Import Export plugin? %4$s  Share Your Feedback! %5$s', 'product-import-export-for-woo'), '🌟', '<span style="font-weight:300;">', '</span>', '<span style="font-weight:300;">', '</span>');

            /* button texts */
            $this->later_btn_text   = __("Remind me later", 'order-import-export-for-woocommerce');
            $this->never_btn_text   = __("Not interested", 'order-import-export-for-woocommerce');
            $this->review_btn_text  = __("Review now", 'order-import-export-for-woocommerce');
            $this->review_btn_new_text = __("You deserve it", 'order-import-export-for-woocommerce  ');
            $this->later_btn_new_text = __("Nope, maybe later", 'order-import-export-for-woocommerce');
            $this->already_did_btn_new_text = __("I already did", 'order-import-export-for-woocommerce');

            add_action('admin_notices', array($this, 'show_banner')); /* show banner */
            add_action('admin_print_footer_scripts', array($this, 'add_banner_scripts')); /* add banner scripts */
            add_action('wp_ajax_' . $this->ajax_action_name, array($this, 'process_user_action')); /* process banner user action */
        }
    }

    /**
     *	Set config vars
     */
    public function set_vars()
    {
        $this->ajax_action_name             =   $this->plugin_prefix . '_process_user_review_action';
        $this->banner_state_option_name     =   $this->plugin_prefix . "_review_request";
        $this->start_date_option_name       =   $this->plugin_prefix . "_start_date";
        $this->banner_css_class             =   $this->plugin_prefix . "_review_request";

        $this->start_date                   =   absint(get_option($this->start_date_option_name));
        $banner_state                       =   absint(get_option($this->banner_state_option_name));
        $this->current_banner_state         =   ($banner_state == 0 ? $this->current_banner_state : $banner_state);

        $this->dismissal_count_option       =   'wt_iew_basic_dismiss_count';
        $this->last_dismissal_option        =   'wt_iew_basic_last_dismiss_date';

        $this->plugins_array                = array(
            'order' => array(
                'base_name' => 'order-import-export-for-woocommerce/order-import-export-for-woocommerce.php',
                'prefix' => 'wt_o_iew_basic',
                'post_types' => array('order', 'coupon', 'subscription'),
                'url' => 'https://wordpress.org/support/plugin/order-import-export-for-woocommerce/reviews/#new-post'
            ),
            'products' => array(
                'base_name' => 'product-import-export-for-woo/product-import-export-for-woo.php',
                'prefix' => 'wt_p_iew_basic',
                'post_types' => array('product', 'product_review', 'product_categories', 'product_tags'),
                'url' => 'https://wordpress.org/support/plugin/product-import-export-for-woo/reviews/#new-post'
            ),
            'users' => array(
                'base_name' => 'users-customers-import-export-for-wp-woocommerce/users-customers-import-export-for-wp-woocommerce.php',
                'prefix' => 'wt_u_iew_basic',
                'post_types' => array('user'),
                'url' => 'https://wordpress.org/support/plugin/users-customers-import-export-for-wp-woocommerce/reviews/#new-post'
            ),
        );
    } 

    /**
     *	Actions on plugin activation
     *	Saves activation date
     */
    public function on_activate()
    {
        $this->reset_start_date();
    }

    /**
     *	Actions on plugin deactivation
     *	Removes activation date
     */
    public function on_deactivate()
    {
        delete_option($this->start_date_option_name);
    }

    /**
     *	Reset the start date. 
     */
    private function reset_start_date()
    {
        update_option($this->start_date_option_name, time());
    }

    /**
     *	Update the banner state 
     */
    private function update_banner_state($val)
    {
        // Get post type and find matching plugin prefix from cache
        $post_type = $this->current_post_type;
        foreach ($this->plugins_array as $plugin) {
            if (in_array($post_type, $plugin['post_types'])) {
                $prefix = isset($plugin['prefix']) ?  $plugin['prefix'] :  $this->plugin_prefix;
                break;
            }
        }
        
        // Update option with prefix
        update_option($prefix . "_review_request", $val);
    }

    /**
     *	Prints the banner 
     */
    public function show_banner()
    {
        $border_radius = $border_color = $banner_color = '';
        $post_type = $this->current_post_type; 
        $currentScreen = get_current_screen(); 

        $plugin_pages = array('toplevel_page_wt_import_export_for_woo_basic_export', 
            'webtoffee-import-export-basic_page_wt_import_export_for_woo_basic_import', 
            'webtoffee-import-export-basic_page_wt_iew_scheduled_job',
            'webtoffee-import-export-basic_page_wt_import_export_for_woo_basic', 
            'toplevel_page_wt_import_export_for_woo_export', 
            'webtoffee-import-export-pro_page_wt_import_export_for_woo_import', 
            'webtoffee-import-export-pro_page_wt_import_export_for_woo_history', 
            'webtoffee-import-export-pro_page_wt_import_export_for_woo_history_log', 
            'webtoffee-import-export-pro_page_wt_import_export_for_woo_cron', 
            'webtoffee-import-export-pro_page_wt_import_export_for_woo'
        );

        // Common pages for all types
        $allowed_pages = array_merge($plugin_pages, array('dashboard', 'plugins', 'woocommerce_page_wc-admin'));

        // Post type specific pages
        $type_specific_pages = array(
            'order' => array('edit-shop_order', 'shop_order', 'edit-shop_coupon', 'edit-shop_subscription', 'shop_subscription', 'woocommerce_page_wc-reports', 'woocommerce_page_wc-admin'),
            'coupon' => array('edit-shop_order', 'shop_order', 'edit-shop_coupon', 'edit-shop_subscription', 'shop_subscription', 'woocommerce_page_wc-reports', 'woocommerce_page_wc-admin'),
            'subscription' => array('edit-shop_order', 'shop_order', 'edit-shop_coupon', 'edit-shop_subscription', 'shop_subscription', 'woocommerce_page_wc-reports'),
            'product' => array('edit-product', 'product'),
            'product_review' => array('edit-product', 'product'),
            'product_categories' => array('edit-product', 'product'),
            'product_tags' => array('edit-product', 'product'),
            'user' => array('users', 'woocommerce_page_wc-reports')
        );

        // Add type specific pages if they exist for current post type
        if (isset($type_specific_pages[$post_type])) {
            $allowed_pages = array_merge($allowed_pages, $type_specific_pages[$post_type]);
        }

        // Check if current screen is allowed
        if (!in_array($currentScreen->id, $allowed_pages)) {
            return;
        } 
        
        // Check WC Reports tab if applicable
        if ($currentScreen->id === 'woocommerce_page_wc-reports') {
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'orders';
            $required_tab = in_array($post_type, array('order', 'coupon', 'subscription')) ? 'orders' : 
            ($post_type === 'user' ? 'customers' : '');
            if (!$required_tab || $current_tab !== $required_tab) {
                return;
            }
        }

        // $this->update_banner_state(1); /* update banner active state */
        $current_user = wp_get_current_user();
        $user_first_name = !empty($current_user->first_name) ? $current_user->first_name : __('there', 'users-customers-import-export-for-wp-woocommerce');

        if(in_array($currentScreen->id, $plugin_pages)){
            $banner_color = 'rgba(233, 242, 252, 1)';
            $border_radius = '8px'; 
            $border_color = '#A0B2D6';
            $this->new_review_banner_message = sprintf(__('Hi  %1$s, %2$s We’re thrilled to see you making great use of our plugin! It’s our mission to make %3$s data management %4$s as %5$s efficient %6$s as possible for you. If you found the plugin helpful, please leave us a quick %7$s 5-star review. %8$s', 'product-import-export-for-woos'),  '<b>' . $user_first_name . '</b>', '<br>', '<b>', '</b>', '<b>', '</b>', '<b>', '</b>');
        }else{
            $banner_color = '#ffffff';
            $border_color = '#ffffff';
            $this->new_review_banner_message = sprintf(__('Hi  %1$s, %2$s We’re thrilled to see you making great use of our WooCommerce import export plugin! It’s our mission to make %3$s data management %4$s as %5$s efficient %6$s as possible for you. %7$s If you found the plugin helpful, please leave us a quick %8$s 5-star review. %9$s It would mean the world to us. %10$s Warm regards, %11$s Team WebToffee %12$s', 'product-import-export-for-woo'), '<b>' . $user_first_name . '</b>', '<br>', '<b>', '</b>', '<b>', '</b>', '<br><br>', '<b>', '</b>', '<br><br>', '<br><b>', '</b>');
        }

    ?>
        <div class="<?php echo esc_attr($this->banner_css_class); ?> notice-info notice is-dismissible " style="padding: 20px; border: 1px solid <?php echo esc_attr($border_color); ?>; border-radius: <?php echo esc_attr($border_radius); ?>; background-color: <?php echo esc_attr($banner_color); ?>; );">
        <?php
        if ("" !== $this->webtoffee_logo_url) {
        ?>
            <h3 style="margin: 10px 0;"><?php echo wp_kses_post($this->new_review_banner_title); ?></h3>
        <?php } ?>
            <div class="wbtf-review-content-wrap">
                <p style="width: 65%;">
        <?php echo wp_kses_post($this->new_review_banner_message); ?>
                </p>
                <p class="wbtf-btns-wrap">
                    <a class="button  wbtf-button-primary"   data-type="review"><?php echo wp_kses_post($this->review_btn_new_text); ?></a>
                    <a class="button  wbtf-button-secondary" style="color:#333; border-color:#ccc; background:#efefef;" data-type="later"><?php echo wp_kses_post($this->later_btn_new_text); ?></a>
                    <a class="button  wbtf-button-secondary" style="color:#333; border-color:#ccc; background:#efefef;" data-type="never"><?php echo wp_kses_post($this->already_did_btn_new_text); ?></a>
                </p>
            </div>
            <figure class="wbtf_review_background_img_wrap">
                <img src="<?php echo esc_url($this->review_request_bg); ?>" alt="wbtf-background">
            </figure>
        </div>
        <?php
    }

    /**
     *	Ajax hook to process user action on the banner
     */
    public function process_user_action()
    {
        check_ajax_referer($this->plugin_prefix);
        if (isset($_POST['wt_review_action_type'])) {
            $action_type = sanitize_text_field($_POST['wt_review_action_type']);

            /* current action is in allowed action list */
            if (in_array($action_type, $this->allowed_action_type_arr)) {
                if ($action_type == 'never' || $action_type == 'closed') {
                    $this->update_banner_state(3);
                } elseif ($action_type == 'review') {
                    $this->update_banner_state(4);
                } elseif ($action_type == 'later') {
                    // Get current dismissal count
                    $dismissal_count = get_option($this->dismissal_count_option, 0);
                    $dismissal_count++;
                    
                    // Update dismissal tracking
                    update_option($this->dismissal_count_option, $dismissal_count);
                    update_option($this->last_dismissal_option, time());
                    
                    if ($dismissal_count >= 3) {
                        $this->update_banner_state(3); // Never show again
                    } else {
                        $this->update_banner_state(5); ; // Remind later
                    }
                }
            }
        }
        exit();
    }

    /**
     *	Add banner JS to admin footer
     */
    public function add_banner_scripts()
    {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce($this->plugin_prefix);
    ?>
        <style type="text/css">
            .wbtf_review_background_img_wrap { position: absolute; bottom: 0; right: 0; width: 75%; height: 50%; overflow: hidden; margin: 0; z-index: 1; padding: 10px 0; }
            .wbtf_review_background_img_wrap img { width: 100%; height: 110%; object-fit: cover; }
            .wbtf-review-content-wrap { padding-left: 22px; z-index: 9; position: relative; padding-top: 9px; }
            .notice-info .wbtf-button-primary  { background: #2860F4 !important; color: #FFF !important; padding: 2px 10px !important; border: 1px solid #2860F4; }
            .notice-info .wbtf-button-secondary { background: #fff !important; color: #000 !important; padding: 2px 10px !important; border: 1px solid #C3C4C7; }
            .notice-info .wbtf-btns-wrap { margin-top: 20px; }
            .notice-info .wbtf-btns-wrap a { margin-right: 6px; }
            @media (max-width: 800px) { .wbtf_review_background_img_wrap {  display: none; } } 

        </style>
        <script type="text/javascript">
            (function($) {
                "use strict";

                /* prepare data object */
                var data_obj = {
                    _wpnonce: '<?php echo $nonce; ?>',
                    action: '<?php echo $this->ajax_action_name; ?>',
                    wt_review_action_type: ''
                };

                $(document).on('click', '.<?php echo $this->banner_css_class; ?> a.button', function(e) {
                    e.preventDefault();
                    var elm = $(this);
                    var btn_type = elm.attr('data-type');
                    if (btn_type == 'review') {
                        window.open('<?php echo esc_url($this->review_url); ?>');
                    }
                    elm.parents('.<?php echo $this->banner_css_class; ?>').hide();

                    data_obj['wt_review_action_type'] = btn_type;
                    $.ajax({
                        url: '<?php echo esc_url($ajax_url); ?>',
                        data: data_obj,
                        type: 'POST'
                    });

                }).on('click', '.<?php echo $this->banner_css_class; ?> .notice-dismiss', function(e) {
                    e.preventDefault();
                    data_obj['wt_review_action_type'] = 'closed';
                    $.ajax({
                        url: '<?php echo esc_url($ajax_url); ?>',
                        data: data_obj,
                        type: 'POST',
                    });

                });

            })(jQuery)
        </script>
        <?php
    }

    /**
     *	Checks the condition to show the banner
     */
    public function check_condition()
    { 
        global $wt_iew_review_banner_shown; 
        if (true === $wt_iew_review_banner_shown) {
            return false;
        } 

        // Collect all banner states
        $new_start_date = get_option($this->last_dismissal_option, 0);
        $dismissal_count = get_option($this->dismissal_count_option, 0);
        $latest_start_date = 0;
        foreach ($this->plugins_array as $plugin) {
            $plugin_prefix = $plugin['prefix'];
            $banner_state = absint(get_option($plugin_prefix . "_review_request", 0));
            
            // Exit early if banner state indicates we shouldn't show
            if (!in_array($banner_state, array(0, 1, 2, 5))) {
                return false;
            }
            
            if(5===$banner_state && $new_start_date === 0){
                
                $plugin_start_date = absint(get_option($plugin['prefix'] . '_start_date', 0));
                // Update latest_start_date if current plugin's start date is more recent
                if ($plugin_start_date > $latest_start_date) {
                    $latest_start_date = $plugin_start_date;
                }
                $dismissal_count = 1;
                update_option($this->dismissal_count_option, $dismissal_count);
            }
        }

        if($latest_start_date === 0){ // New user.
            $latest_start_date = $new_start_date;
        }

        // Handle "remind later" state if any plugin has it
        if ( $dismissal_count > 0 && $dismissal_count < 3 ) { 
           // dismissed condition
           return $this->handle_dissmissed($dismissal_count, $latest_start_date);
        }            

        // Check never dismissed condition
        return $this->handle_never_dissmissed();
    }

    private function handle_never_dissmissed() {
        global $wpdb, $wt_iew_review_banner_shown;

        // Get first successful job date
        $start_date = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT created_at FROM {$wpdb->prefix}wt_iew_action_history 
                WHERE status = %d ORDER BY created_at ASC LIMIT 1",
                1
            )
        ); 
        
        if (!$start_date) {
            return false;
        } 


        $days_since_start = floor((time() - $start_date) / 86400);
        // If less than 30 days from start
        if ($days_since_start > 5 && $days_since_start <= 30) {
            // Get successful jobs on distinct dates after 5 days
            $query = $wpdb->prepare(
                "SELECT h.item_type, 
                    COUNT(DISTINCT DATE(FROM_UNIXTIME(h.created_at))) as date_count,
                    MAX(h.created_at) as last_success
                FROM {$wpdb->prefix}wt_iew_action_history h
                WHERE h.status = %d 
                AND h.created_at >= %d
                GROUP BY h.item_type
                HAVING COUNT(DISTINCT DATE(FROM_UNIXTIME(h.created_at))) >= 2
                ORDER BY date_count DESC, last_success DESC 
                LIMIT 1",
                1, $start_date
            );

            $success_jobs = $wpdb->get_row($query);  

            if ($success_jobs && $success_jobs->date_count >= 2) { 
                $this->current_post_type = $success_jobs->item_type; 
                $wt_iew_review_banner_shown = true;
                return true;
            }
        } 
        
        if ($days_since_start > 30) {
            // After 30 days, check last job (regardless of success)

            // First get the last job regardless of post type
            $last_job = $wpdb->get_row(
                "SELECT item_type, status, created_at 
                FROM {$wpdb->prefix}wt_iew_action_history 
                ORDER BY created_at DESC 
                LIMIT 1"
            );
            
            if ($last_job && $last_job->status == 1) {
                $this->current_post_type = $last_job->item_type;
                $wt_iew_review_banner_shown = true;
                return true;
            }
        }
        return false;
    }

    private function handle_dissmissed($dismissal_count, $last_dismissal) {
        
        global $wt_iew_review_banner_shown;

        $days_since_dismissal = floor((time() - $last_dismissal) / (60 * 60 * 24));
        $jobs_since_dismissal = $this->get_jobs_since_dismissal($last_dismissal);

        
        if ($dismissal_count > 0) {

            if ($dismissal_count == 1) {
                // First dismissal: 15 jobs OR 50 days
                if ($jobs_since_dismissal >= 15 || $days_since_dismissal >= 50) { 
                    $wt_iew_review_banner_shown = true;
                    return true;
                }
            } elseif ($dismissal_count == 2) {
                // Second dismissal: 30 jobs OR 90 days
                if ($jobs_since_dismissal >= 30 || $days_since_dismissal >= 90) {
                    $wt_iew_review_banner_shown = true;
                    return true;
                }
            } 
        }
        return false;
    }

    private function get_jobs_since_dismissal($last_dismissal) {
        
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT h.item_type, 
                    COUNT(*) as success_count,
                    MAX(h.created_at) as last_success
             FROM {$wpdb->prefix}wt_iew_action_history h
             WHERE h.status = %d 
             AND h.created_at >= %s
             GROUP BY h.item_type
             ORDER BY success_count DESC, last_success DESC
             LIMIT 1",
            1, $last_dismissal
        );
        
        $results = $wpdb->get_row($query);
        
        // If we have results, get the highest count (with latest success date if tied)
        if ($results) {
            $this->current_post_type = $results->item_type;
            return $results->success_count;
        }
        return 0;
    }

    public function show_banner_cta()
    {
        // Check if the WooCommerce Order Import Export plugin is active
        if (is_plugin_active('order-import-export-for-woocommerce/order-import-export-for-woocommerce.php')) {
    
            // Get the current screen object
            $screen = get_current_screen();
    
            // Check if we're on the WooCommerce Reports page
            if ($screen->id == 'woocommerce_page_wc-reports') {
                // Set 'orders' as default tab if no 'tab' is set
                $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'orders';
    
                // Define content and plugin URL based on the current tab
                $content = '';
                $plugin_url = '';
                $title = esc_html__('Did You Know?', 'order-import-export-for-woocommerce');
                $cookie_name = ''; // We'll set this based on the current tab
    
                switch ($current_tab) {
                    case 'orders':
                        // Check if the 'orders' banner has been hidden
                        $cookie_name = 'hide_cta_orders';
                        if (isset($_COOKIE[$cookie_name]) && sanitize_text_field($_COOKIE[$cookie_name]) == 'true') {
                            return; // Don't show the banner if the cookie is set
                        }
    
                        $content = '<span style="color: #212121;">' . esc_html__('You can now export WooCommerce order', 'order-import-export-for-woocommerce') . '</span> <span style="color: #5454A5; font-weight: bold;">' . esc_html__('data with custom filters, custom metadata, FTP export, and scheduling options.', 'order-import-export-for-woocommerce') . '</span> <span style="color: #212121;">' . esc_html__('Bulk edit or update orders using CSV, XML, Excel, or TSV files in one go.', 'order-import-export-for-woocommerce') . '</span>';
                        $plugin_url = 'https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_report&utm_medium=basic_revamp&utm_campaign=Order_Import_Export';
                        break;
    
                    case 'customers':
                        // Check if the 'customers' banner has been hidden
                        $cookie_name = 'hide_cta_customers';
                        if (isset($_COOKIE[$cookie_name]) && sanitize_text_field($_COOKIE[$cookie_name]) == 'true') {
                            return; // Don't show the banner if the cookie is set
                        }
    
                        $content = '<span style="color: #212121;">' . esc_html__('You can easily bulk export your customers', 'order-import-export-for-woocommerce') . '</span> <span style="color: #5454A5; font-weight: bold;">' . esc_html__('data to CSV, XML, Excel, or TSV files in just a few clicks.', 'order-import-export-for-woocommerce') . '</span> <span style="color: #212121;">' . esc_html__('Export custom user metadata of third-party plugins seamlessly.', 'order-import-export-for-woocommerce') . '</span>';
                        $plugin_url = 'https://www.webtoffee.com/product/wordpress-users-woocommerce-customers-import-export/?utm_source=free_plugin_report&utm_medium=basic_revamp&utm_campaign=User_Import_Export';
                        break;
    
                    case 'stock':
                        // Check if the 'stock' banner has been hidden
                        $cookie_name = 'hide_cta_stock';
                        if (isset($_COOKIE[$cookie_name]) && sanitize_text_field($_COOKIE[$cookie_name]) == 'true') {
                            return; // Don't show the banner if the cookie is set
                        }
    
                        $content = '<span style="color: #212121;">' . esc_html__('Get your store products', 'order-import-export-for-woocommerce') . '</span> <span style="color: #5454A5; font-weight: bold;">' . esc_html__('bulk exported for hassle-free migration, inventory management, and bookkeeping.', 'order-import-export-for-woocommerce') . '</span> <span style="color: #212121;">' . esc_html__('Import/export WooCommerce products with reviews, images, and custom metadata.', 'order-import-export-for-woocommerce') . '</span>';
                        $plugin_url = 'https://www.webtoffee.com/product/product-import-export-woocommerce/?utm_source=free_plugin_report&utm_medium=basic_revamp&utm_campaign=Product_Import_Export';
                        break;
    
                    case 'subscriptions':
                        // Check if the 'subscriptions' banner has been hidden
                        $cookie_name = 'hide_cta_subscriptions';
                        if (isset($_COOKIE[$cookie_name]) && sanitize_text_field($_COOKIE[$cookie_name]) == 'true') {
                            return; // Don't show the banner if the cookie is set
                        }
    
                        $content = '<span style="color: #212121;">' . esc_html__('Get your subscription orders exported to a', 'order-import-export-for-woocommerce') . '</span> <span style="color: #5454A5; font-weight: bold;">' . esc_html__('CSV, XML, Excel, or TSV file.', 'order-import-export-for-woocommerce') . '</span> <span style="color: #212121;">' . esc_html__('Featuring scheduled exports, advanced filters, custom metadata, and more.', 'order-import-export-for-woocommerce') . '</span>';
                        $plugin_url = 'https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_report&utm_medium=basic_revamp&utm_campaign=Order_Import_Export';
                        break;
    
                    default:
                        return; // Exit if not on a recognized tab
                }
    
                // HTML for the banner remains unchanged
                ?>
                <div id="cta-banner" class="notice notice-info" style="position: relative; padding: 15px; background-color: #f3f0ff; border-left: 4px solid #5454A5; display: flex; justify-content: space-between; align-items: center; border-radius: 1px;">
                    <div style="flex: 1; margin-right: 10px;">
                        <div style="display: flex; align-items: center; margin-bottom: 5px;">
                            <img src="<?php echo esc_url(WT_O_IEW_PLUGIN_URL . 'assets/images/idea_bulb_purple.svg'); ?>" style="width: 25px; margin-right: 10px;">
                            <h2 style="margin: 0; font-size: 16px; color: #2d2d77; font-weight: 600;"><?php echo esc_html($title); ?></h2>
                        </div>
                        <p style="margin: 0; font-size: 14px; color: #6f6f6f; line-height: 1.4;"><?php echo wp_kses_post($content); ?></p>
                    </div>
    
                    <div style="display: flex; gap: 10px;">
                        <a href="<?php echo esc_url($plugin_url); ?>" target="_blank" class="button-primary" style="background: #5454A5; color: white; border: none; padding: 8px 15px; border-radius: 4px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-size: 14px;"><?php esc_html_e('Check out plugin ➔', 'order-import-export-for-woocommerce'); ?></a>
                        <button id="maybe-later" class="button-secondary" style="background-color: #f3f0ff; color: #4a42a3; padding: 8px 15px; border: 1px solid #5454A5; border-radius: 4px; font-size: 14px;"><?php esc_html_e('Maybe later', 'order-import-export-for-woocommerce'); ?></button>
                    </div>
                </div>
    
                <script type="text/javascript">
                    (function($) {
                        $('#maybe-later').on('click', function(e) {
                            e.preventDefault();
                            // Set a cookie to hide the banner for 30 days for this specific tab
                            document.cookie = "<?php echo esc_js($cookie_name); ?>=true; path=/; max-age=" + (30*24*60*60) + ";";
                            $('#cta-banner').remove();
                        });
                    })(jQuery);
                </script>
                <?php
            }
        }
    }
    
}
new Order_Import_Export_Review_Request();
