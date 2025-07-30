<?php

namespace wtierorder\Banners;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Wtier_Bfcm_Twenty_Twenty_Four
 *
 * This class is responsible for displaying and handling the Black Friday and Cyber Monday CTA banners for 2024.
 */
if (!class_exists('\\Wtierorder\\Banners\\Wtier_Bfcm_Twenty_Twenty_Four')) {

    class Wtier_Bfcm_Twenty_Twenty_Four
    {

        private $banner_id = 'wtier-bfcm-twenty-twenty-four';
        private static $banner_state_option_name = "wtier_bfcm_twenty_twenty_four_banner_state"; // Banner state, 1: Show, 2: Closed by user, 3: Clicked the grab button, 4: Expired
        private $banner_state = 1;
        private static $show_banner = null;
        private static $ajax_action_name = "wtier_bcfm_twenty_twenty_four_banner_state";
        private static $promotion_link = "https://www.webtoffee.com/plugins/?utm_source=BFCM_administration&utm_medium=import_export&utm_campaign=BFCM-Administration";
        private static $banner_version = '';

        public function __construct()
        {
            self::$banner_version = WT_O_IEW_VERSION; // Plugin version

            $this->banner_state = get_option(self::$banner_state_option_name); // Current state of the banner
            $this->banner_state = absint(false === $this->banner_state ? 1 : $this->banner_state);

            // Enqueue styles
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_and_scripts'));

            // Add banner
            add_action('admin_notices', array($this, 'show_banner'), 999);

            // Ajax hook to save banner state
            add_action('wp_ajax_' . self::$ajax_action_name, array($this, 'update_banner_state'));
        }

        /**
         * To add the banner styles
         *
         * @return void
         */
        public function enqueue_styles_and_scripts()
        {
            wp_enqueue_style($this->banner_id . '-css', plugin_dir_url(__FILE__) . 'assets/css/wtier-bfcm-twenty-twenty-four.css', array(), self::$banner_version, 'all');
            $params = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wtier_bfcm_twenty_twenty_four_banner_nonce'),
                'action' => self::$ajax_action_name,
                'cta_link' => self::$promotion_link,
            );
            wp_enqueue_script($this->banner_id . '-js', plugin_dir_url(__FILE__) . 'assets/js/wtier-bfcm-twenty-twenty-four.js', array('jquery'), self::$banner_version, false);
            wp_localize_script($this->banner_id . '-js', 'wtier_bfcm_twenty_twenty_four_banner_js_params', $params);
        }

        public function show_banner()
        {
            if ($this->is_show_banner()) {
                // Check for the specific screen ID
                $screen = get_current_screen();
                $screen_id = $screen ? $screen->id : '';
                $extra_class = '';

                // If the screen is 'webtoffee-import-export-basic_page_wt_iew_scheduled_job', append an additional class
                if ('webtoffee-import-export-basic_page_wt_iew_scheduled_job' === $screen_id) {
                    $extra_class = 'wtier-bfcm-banner-2024-scheduled-job'; // Add this class for additional styling
                }
?>
                <div class="wtier-bfcm-banner-2024 notice is-dismissible <?php echo esc_attr($extra_class); ?>">
                    <div class="wtier-bfcm-banner-body">
                        <div class="wtier-bfcm-banner-body-img-section">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/black-friday-2024.svg'); ?>" alt="<?php esc_attr_e('Black Friday Cyber Monday 2024', 'order-import-export-for-woocommerce'); ?>">
                        </div>
                        <div class="wtier-bfcm-banner-body-info">
                            <div class="never-miss-this-deal">
                                <p><?php echo esc_html__('Never Miss This Deal', 'order-import-export-for-woocommerce'); ?></p>
                            </div>
                            <div class="info">
                                <p>
                                    <?php
                                    
                                    // Check if the screen ID matches 'webtoffee-import-export-basic_page_wt_iew_scheduled_job'
                                    if ('webtoffee-import-export-basic_page_wt_iew_scheduled_job' === $screen_id) {
                                        // Add a line break for this specific screen
                                        echo sprintf(
                                            __('Your Last Chance to Avail %1$s on<br>WebToffee Plugins. Grab the deal before it`s gone!', 'order-import-export-for-woocommerce'),
                                            '<span>30% ' . __("OFF", "order-import-export-for-woocommerce") . '</span>'
                                        );
                                    } else {
                                        // Regular display for other screens
                                        echo sprintf(
                                            __('Your Last Chance to Avail %1$s on WebToffee Plugins. Grab the deal before it`s gone!', 'order-import-export-for-woocommerce'),
                                            '<span>30% ' . __("OFF", "order-import-export-for-woocommerce") . '</span>'
                                        );
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="info-button">
                                <a href="<?php echo esc_url(self::$promotion_link); ?>" class="bfcm_cta_button" target="_blank"><?php echo esc_html__('View plugins', 'order-import-export-for-woocommerce'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
                            </div>
                        </div>
                    </div>
                </div>
<?php
            }
        }


        public function is_show_banner()
        {

            $start_date = new \DateTime('25-NOV-2024, 12:00 AM', new \DateTimeZone('Asia/Kolkata')); // Start date.
            $current_date = new \DateTime('now', new \DateTimeZone('Asia/Kolkata')); // Current date.
            $end_date = new \DateTime('02-DEC-2024, 11:59 PM', new \DateTimeZone('Asia/Kolkata')); // End date.

            /**
             * check if the current date is less than the start date then wait for the start date.
             */
            if ($current_date < $start_date) {
                self::$show_banner = false;
                return self::$show_banner;
            }

            /**
             * 	check if the current date is greater than the end date, then set the banner state as expired.
             */
            if ($current_date >= $end_date) {
                update_option(self::$banner_state_option_name, 4); // Set as expired.
                self::$show_banner = false;
                return self::$show_banner;
            }

            /**
             *  Already checked.
             */
            if (! is_null(self::$show_banner)) {
                return self::$show_banner;
            }

            /**
             * 	Check current banner state
             */
            if (1 !== $this->banner_state) {
                self::$show_banner = false;
                return self::$show_banner;
            }

            /**
             * 	Check screens
             */
            $screen    = get_current_screen();
            $screen_id = $screen ? $screen->id : '';

            /**
             *  Pages to show this black friday and cyber monday banner for 2024.
             * 	
             * 	@param 	string[] 	Default screen ids
             */
            $screens_to_show = (array) apply_filters('wtier_bfcm_banner_screens', array('toplevel_page_wt_import_export_for_woo_basic_export', 'webtoffee-import-export-basic_page_wt_import_export_for_woo_basic_import', 'webtoffee-import-export-basic_page_wt_iew_scheduled_job','webtoffee-import-export-basic_page_wt_import_export_for_woo_basic'));
            self::$show_banner = in_array($screen_id, $screens_to_show);

            return apply_filters("wtier_bfcm_show_banner", self::$show_banner);
        }

        /**
         * 	Update banner state ajax hook
         * 
         */
        public function update_banner_state()
        {
            check_ajax_referer('wtier_bfcm_twenty_twenty_four_banner_nonce');
            if (isset($_POST['wtier_bfcm_twenty_twenty_four_banner_action_type'])) {

                $action_type = absint(sanitize_text_field($_POST['wtier_bfcm_twenty_twenty_four_banner_action_type']));
                // Current action is allowed?
                if (in_array($action_type, array(2, 3))) {
                    update_option(self::$banner_state_option_name, $action_type);
                }
            }
            exit();
        }
    }

    new \wtierorder\Banners\Wtier_Bfcm_Twenty_Twenty_Four();
}
