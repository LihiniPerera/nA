<?php
/*
 * 
Plugin Name: Order Export & Order Import for WooCommerce
Plugin URI: https://wordpress.org/plugins/order-import-export-for-woocommerce/
Description: Export and Import Order detail including line items, From and To your WooCommerce Store.
Author: WebToffee
Author URI: https://www.webtoffee.com/product/woocommerce-order-coupon-subscription-export-import/
Version: 2.6.2
Text Domain: order-import-export-for-woocommerce
Domain Path: /languages
Requires at least: 3.0
Requires PHP: 5.6
WC tested up to: 9.8.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/


if ( !defined( 'ABSPATH' ) || !is_admin() ) {
	return;
}


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define ( 'WT_O_IEW_PLUGIN_BASENAME', plugin_basename(__FILE__) );
define ( 'WT_O_IEW_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define ( 'WT_O_IEW_PLUGIN_URL', plugin_dir_url(__FILE__));
define ( 'WT_O_IEW_PLUGIN_FILENAME', __FILE__);
if ( ! defined( 'WT_IEW_PLUGIN_ID_BASIC' ) ) {
    define ( 'WT_IEW_PLUGIN_ID_BASIC', 'wt_import_export_for_woo_basic');
}
define ( 'WT_O_IEW_PLUGIN_NAME','Order/Coupon Import Export for WooCommerce');
define ( 'WT_O_IEW_PLUGIN_DESCRIPTION','Import and Export Order/Coupon From and To your WooCommerce Store.');

if ( ! defined( 'WT_IEW_DEBUG_BASIC' ) ) {
    define ( 'WT_IEW_DEBUG_BASIC', false );
}
if ( !defined( 'WT_IEW_DEBUG_BASIC_TROUBLESHOOT' ) ) {
	define( 'WT_IEW_DEBUG_BASIC_TROUBLESHOOT', 'https://www.webtoffee.com/finding-php-error-logs/' );
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WT_O_IEW_VERSION', '2.6.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wt-import-export-for-woo-activator.php
 */
function activate_wt_import_export_for_woo_basic_order() { 
    wt_order_activation_check();        
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wt-import-export-for-woo-activator.php';
	Wt_Import_Export_For_Woo_Basic_Activator_Order::activate();
    wt_order_imp_exp_basic_migrate_serialized_data_to_json();
}

require_once plugin_dir_path( __FILE__ ) . 'class-wt-order-welcome-script.php';


/* Checking WC is actived or not */
if ( !function_exists( 'is_plugin_active' ) ) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

add_action( 'plugins_loaded', 'wt_order_basic_check_for_woocommerce' );

if ( !function_exists( 'wt_order_basic_check_for_woocommerce' ) ) {

	function wt_order_basic_check_for_woocommerce() {


		if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) || !defined( 'WC_VERSION' ) ) {
			add_action( 'admin_notices', 'wt_wc_missing_warning_order_basic' );
		}
		if ( !function_exists( 'wt_wc_missing_warning_order_basic' ) ) {

			function wt_wc_missing_warning_order_basic() {

				$install_url = wp_nonce_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => 'woocommerce', ), admin_url( 'update.php' ) ), 'install-plugin_woocommerce' );
				$class		 = 'notice notice-error';
				$post_type	 = 'order';
				$message	 = sprintf( __( 'The <b>WooCommerce</b> plugin must be active for <b>%s / Coupon / Subscription Export Import Plugin for WooCommerce (BASIC)</b> plugin to work.  Please <a href="%s" target="_blank">install & activate WooCommerce</a>.' ), ucfirst( $post_type ), esc_url( $install_url ) );
				printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), ( $message ) );
			}

		}
	}
}

	/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wt-import-export-for-woo-deactivator.php
 */
function deactivate_wt_import_export_for_woo_basic_order() {
        
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wt-import-export-for-woo-deactivator.php';
	Wt_Import_Export_For_Woo_Basic_Deactivator_Order::deactivate();
}

register_activation_hook( __FILE__, 'activate_wt_import_export_for_woo_basic_order' );
register_deactivation_hook( __FILE__, 'deactivate_wt_import_export_for_woo_basic_order' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wt-import-export-for-woo.php';

$advanced_settings = get_option('wt_iew_advanced_settings', array());
$ier_get_max_execution_time = (isset($advanced_settings['wt_iew_maximum_execution_time']) && $advanced_settings['wt_iew_maximum_execution_time'] != '') ? $advanced_settings['wt_iew_maximum_execution_time'] : ini_get('max_execution_time');

if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
        @set_time_limit($ier_get_max_execution_time);
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wt_import_export_for_woo_basic_order() {

    if ( ! defined( 'WT_IEW_BASIC_STARTED' ) ) {
        define ( 'WT_IEW_BASIC_STARTED', 1);
	$plugin = new Wt_Import_Export_For_Woo_Basic();
	$plugin->run();
    }

}
/** this added for a temporary when a plugin update with the option upload zip file. need to remove this after some version release */
if ( !get_option( 'wt_o_iew_is_active' ) ) {
	activate_wt_import_export_for_woo_basic_order();
}

add_action( 'init', function() {
    if ( get_option( 'wt_o_iew_is_active' ) ) {
        run_wt_import_export_for_woo_basic_order();       
    }
} );

/* Plugin page links */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wt_oiew_plugin_action_links_basic_order' );

function wt_oiew_plugin_action_links_basic_order( $links ) {

	$plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wt_import_export_for_woo_basic_export') . '">' . __('Export') . '</a>',
		'<a href="' . admin_url('admin.php?page=wt_import_export_for_woo_basic_import') . '">' . __('Import') . '</a>',
		'<a href="https://www.webtoffee.com/order-coupon-subscription-export-import-plugin-woocommerce-user-guide/" target="_blank">' . __( 'Documentation' ) . '</a>',
		'<a href="https://wordpress.org/support/plugin/order-import-export-for-woocommerce/" target="_blank">' . __( 'Support' ) . '</a>',
		'<a href="https://www.webtoffee.com/product/woocommerce-order-coupon-subscription-export-import/?utm_source=free_plugin_listing&utm_medium=order_imp_exp_basic&utm_campaign=Order_Import_Export&utm_content=' . WT_O_IEW_VERSION . '" style="color:#3db634;">' . __('Premium Upgrade') . '</a>'
	);
	if ( array_key_exists( 'deactivate', $links ) ) {
		$links[ 'deactivate' ] = str_replace( '<a', '<a class="wforderimpexp-deactivate-link"', $links[ 'deactivate' ] );
	}
	return array_merge( $plugin_links, $links );
}

/*
 *  Displays update information for a plugin. 
 */
function wt_order_import_export_for_woocommerce_update_message( $data, $response )
{
    if(isset( $data['upgrade_notice']))
    {
		add_action( 'admin_print_footer_scripts','wt_order_imex_basic_plugin_screen_update_js');
        printf(
        '<div class="update-message wt-update-message">%s</div>',
           $data['upgrade_notice']
        );
    }
}
add_action( 'in_plugin_update_message-order-import-export-for-woocommerce/order-import-export-for-woocommerce.php', 'wt_order_import_export_for_woocommerce_update_message', 10, 2 );

if(!function_exists('wt_order_imex_basic_plugin_screen_update_js'))
{
    function wt_order_imex_basic_plugin_screen_update_js()
    {
        ?>
        <script>
            ( function( $ ){
                var update_dv=$( '#order-import-export-for-woocommerce-update');
                update_dv.find('.wt-update-message').next('p').remove();
                update_dv.find('a.update-link:eq(0)').click(function(){
                    $('.wt-update-message').remove();
                });
            })( jQuery );
        </script>
        <?php
    }
}


// uninstall feedback catch
include_once plugin_dir_path( __FILE__ ) . 'includes/class-wf-orderimpexp-plugin-uninstall-feedback.php';


include_once 'class-wt-order-review-request.php';

// Load Common Helper Class (needed by non-apache-info)
if ( ! class_exists( 'Wt_Import_Export_For_Woo_Basic_Common_Helper' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'helpers/class-wt-common-helper.php';
}

// Add dismissible server info for file restrictions
include_once plugin_dir_path( __FILE__ ) . 'includes/class-wt-non-apache-info.php';
$inform_server_secure = new wt_inform_server_secure('order');
$inform_server_secure->plugin_title = "Order Import Export";
$inform_server_secure->banner_message = sprintf(__("The <b>%s</b> plugin uploads the imported file into <b>wp-content/webtoffee_import</b> folder. Please ensure that public access restrictions are set in your server for this folder."), $inform_server_secure->plugin_title);


add_action( 'wt_order_addon_basic_help_content', 'wt_order_import_export_basic_help_content' );

function wt_order_import_export_basic_help_content() {
	if ( defined( 'WT_IEW_PLUGIN_ID_BASIC' ) ) {
    ?>
        <li>
            <img src="<?php echo WT_O_IEW_PLUGIN_URL; ?>assets/images/sample-csv.png">
            <h3><?php _e( 'Sample Order CSV' ); ?></h3>
            <p><?php _e( 'Familiarize yourself with the sample CSV.' ); ?></p>
            <a target="_blank" href="https://www.webtoffee.com/wp-content/uploads/2021/03/Order_SampleCSV.csv" class="button button-primary">
            <?php _e( 'Get Order CSV' ); ?>        
            </a>
        </li>
    <?php
	}
}

add_action( 'wt_coupon_addon_basic_help_content', 'wt_coupon_import_export_basic_help_content' );

function wt_coupon_import_export_basic_help_content() {
	if ( defined( 'WT_IEW_PLUGIN_ID_BASIC' ) ) {
    ?>
        <li>
            <img src="<?php echo WT_O_IEW_PLUGIN_URL; ?>assets/images/sample-csv.png">
            <h3><?php _e( 'Sample Coupon CSV' ); ?></h3>
            <p><?php _e( 'Familiarize yourself with the sample CSV.' ); ?></p>
            <a target="_blank" href="https://www.webtoffee.com/wp-content/uploads/2016/09/Coupon_Sample_CSV.csv" class="button button-primary">
            <?php _e( 'Get Coupon CSV' ); ?>        
            </a>
        </li>
    <?php
	}
}

add_action( 'wt_order_addon_basic_gopro_content', 'wt_order_addon_basic_gopro_content' );

function wt_order_addon_basic_gopro_content() {
	if ( defined( 'WT_IEW_PLUGIN_ID_BASIC' ) ) {
    ?>
                <div class="wt-ier-coupon wt-ier-order wt-ier-gopro-cta wt-ierpro-features"  style="display: none;">
                    <ul class="ticked-list wt-ierpro-allfeat">
						<li><?php _e('Import and export in XLS and XLSX formats'); ?><span class="wt-iew-upgrade-to-pro-new-feature"><?php esc_html_e( 'New' ); ?></span></li>
						<li><?php _e('All free version features'); ?></li>
						<li><?php _e('XML file type support'); ?></li>						
                        <li><?php _e('Import and export subscription orders'); ?></li>												
                        <li><?php _e('Export and import custom fields and third-party plugin fields'); ?></li>                         
                        <li><?php _e('Run scheduled automatic import and export'); ?></li>
                        <li><?php _e('Import from URL, FTP/SFTP'); ?></li>
						<li><?php _e('Export to FTP/SFTP'); ?></li>
						<li><?php _e('Option to email customers on order status change'); ?></li>
						<li><?php _e('Option to create users on order import'); ?></li>
                        <li><?php _e('Tested compatibility with major third-party plugins.'); ?></li>
                    </ul>    
                    <div class="wt-ierpro-btn-wrapper"> 
                        <a href="<?php echo "https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_revamp&utm_medium=basic_revamp&utm_campaign=Order_Import_Export&utm_content=".WT_O_IEW_VERSION; ?>" target="_blank"  class="wt-ierpro-outline-btn"><?php _e('UPGRADE TO PREMIUM'); ?></a>
                    </div>
                    <p style="padding-left:25px;"><b><a href="<?php echo admin_url('admin.php?page=wt_import_export_for_woo_basic#wt-pro-upgrade'); ?>" target="_blank"><?php _e('Get more import export addons >>'); ?></a></b></p>
                </div>
    <?php
	}
}

/**
 * Add Export to CSV link in order listing page near the filter button.
 * 
 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
 */
function export_csv_linkin_order_listing_page($which) {

	$currentScreen = get_current_screen();

	if ( ( 'edit-shop_order' === $currentScreen->id || 'edit-shop_coupon' === $currentScreen->id ) && !is_plugin_active( 'wt-import-export-for-woo/wt-import-export-for-woo.php' ) ) {
		$post_type = ( 'edit-shop_order' === $currentScreen->id ) ? 'order' : 'coupon';
		$style = ( 'order' === $post_type ) ? 'style="height:32px;"' : '';
		echo '<a target="_blank" href="' . admin_url('admin.php?page=wt_import_export_for_woo_basic_export&wt_to_export='.$post_type.'') . '" class="button"'.$style.' >' . __('Export to CSV') . ' </a>';
	}
}

add_filter('manage_posts_extra_tablenav', 'export_csv_linkin_order_listing_page');

/*
 * Add CSS for Pro Upgrade link in export/import menu
 */
add_action('admin_head', 'wt_pro_upgrad_link');

if (!function_exists('wt_pro_upgrad_link')) {

	function wt_pro_upgrad_link() {
		echo '<style>.wp-submenu li span.wt-go-premium {font-weight: 700;color: #28e499;} </style>';
	}

}


// HPOS compatibility decleration
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Convert serialized data to JSON in database tables
 * 
 * @since    2.6.1 Added to migrate serialized data to JSON format for better compatibility and security
 * @access   public
 * @return   boolean    Success status of the conversion
 */
function wt_order_imp_exp_basic_migrate_serialized_data_to_json() {
    global $wpdb;
        
    $tables = array(
        'mapping' => $wpdb->prefix . 'wt_iew_mapping_template',
        'history' => $wpdb->prefix . 'wt_iew_action_history'
    );
        
    $success = true;
        
    foreach ($tables as $table_type => $table_name) {
        $rows = $wpdb->get_results("SELECT id, data FROM {$table_name}", ARRAY_A);
            
        if ($rows) {
            foreach ($rows as $row) {
                // Check if data is already in JSON format
                $json_check = json_decode($row['data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Skip if already valid JSON
                    continue;
                }
                    
                // Check if data is serialized
                if (is_serialized($row['data'])) {
                    $unserialized_data = Wt_Import_Export_For_Woo_Basic_Common_Helper::wt_unserialize_safe($row['data']);
                    if ($unserialized_data !== false) {
                        $json_data = wp_json_encode($unserialized_data);
                        $update_result = $wpdb->update(
                            $table_name,
                            array('data' => $json_data),
                            array('id' => $row['id']),
                            array('%s'),
                            array('%d')
                        );
                        if ($update_result === false) {
                            $success = false;
                            break 2; // Break both loops if update fails
                        }
                    }
                }
            }
        }
    }
        
    // If migration was successful, store the option
    if ($success) {
        update_option('wt_o_iew_basic_json_migration_complete', 'yes');
    }
        
    return $success;
}

/**
 * Check and convert serialized data to JSON if not already done
 */
function wt_check_and_convert_to_json() {
    $migration_complete = get_option('wt_o_iew_basic_json_migration_complete');
    if (empty($migration_complete) || $migration_complete !== 'yes') {
        wt_order_imp_exp_basic_migrate_serialized_data_to_json();
    }
}
add_action('admin_init', 'wt_check_and_convert_to_json');