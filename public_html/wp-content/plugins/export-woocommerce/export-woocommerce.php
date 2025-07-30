<?php
/*
Plugin Name: Export Products, Orders & Customers for WooCommerce
Plugin URI: https://wpfactory.com/item/export-woocommerce/
Description: Advanced export tools for all your WooCommerce store data: Orders, Products Customers & More, export to XML or CSV in one click.
Version: 2.3.1
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: export-woocommerce
Domain Path: /langs
WC tested up to: 9.8
Requires Plugins: woocommerce
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined( 'ABSPATH' ) || exit;

/**
 * Check if WooCommerce is active.
 */
$plugin = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! (
		is_multisite() &&
		array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) )
	)
) {
	return;
}

/**
 * before_woocommerce_init.
 *
 * @version 2.2.0
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			dirname( __FILE__ ),
			true
		);
	}
} );

/**
 * Check if Pro is active.
 */
if ( 'export-woocommerce.php' === basename( __FILE__ ) ) {
	// Check if Pro is active, if so then return
	$plugin = 'export-woocommerce-pro/export-woocommerce-pro.php';
	if (
		in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
		(
			is_multisite() &&
			array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) )
		)
	) {
		return;
	}
}

defined( 'ALG_WC_EXPORT_VERSION' ) || define( 'ALG_WC_EXPORT_VERSION', '2.3.1' );

defined( 'ALG_WC_EXPORT_FILE' ) || define( 'ALG_WC_EXPORT_FILE', __FILE__ );

if ( ! class_exists( 'Alg_WC_Export' ) ) :

/**
 * Main Alg_WC_Export Class
 *
 * @version 2.3.1
 * @since   1.0.0
 *
 * @class   Alg_WC_Export
 */
final class Alg_WC_Export {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = ALG_WC_EXPORT_VERSION;

	/**
	 * core.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	public $core;

	/**
	 * fields_helper.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	public $fields_helper;

	/**
	 * @since 1.0.0
	 *
	 * @var   Alg_WC_Export The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_WC_Export Instance.
	 *
	 * Ensures only one instance of Alg_WC_Export is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @static
	 * @return  Alg_WC_Export - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_WC_Export Constructor.
	 *
	 * @version 2.2.1
	 * @since   1.0.0
	 *
	 * @access  public
	 */
	function __construct() {

		// Load libs
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		}

		// Set up localisation
		add_action( 'init', array( $this, 'localize' ) );

		// Pro
		if ( 'export-woocommerce-pro.php' === basename( __FILE__ ) ) {
			require_once( 'includes/pro/class-alg-wc-export-pro.php' );
		}

		// Include required files
		$this->includes();

		// Admin
		if ( is_admin() ) {
			$this->admin();
		}

	}

	/**
	 * localize.
	 *
	 * @version 2.2.1
	 * @since   2.2.1
	 *
	 */
	function localize() {
		load_plugin_textdomain(
			'export-woocommerce',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/langs/'
		);
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 2.2.0
	 * @since   1.0.0
	 *
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();

		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_export' ) . '">' .
			__( 'Settings', 'export-woocommerce' ) .
		'</a>';

		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=alg-wc-export-tools' ) . '">' .
			__( 'Tools', 'export-woocommerce' ) .
		'</a>';

		if ( 'export-woocommerce.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a style="color: green; font-weight: bold;" target="_blank" href="' . esc_url( 'https://wpfactory.com/item/export-woocommerce/"' ) . '">' .
				__( 'Go Pro', 'export-woocommerce' ) .
			'</a>';
		}

		$custom_links[] = '<a style=" font-weight: bold;" target="_blank" href="' . esc_url( 'https://wordpress.org/support/plugin/export-woocommerce/reviews/#new-post"' ) . '">' .
			__( 'Review Us', 'export-woocommerce' ) .
		'</a>';

		return array_merge( $custom_links, $links );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 1.3.0
	 * @since   1.0.0
	 */
	function includes() {
		// Fields helper
		$this->fields_helper = require_once( 'includes/class-alg-export-fields-helper.php' );
		// Functions
		require_once( 'includes/alg-wc-export-functions.php' );
		require_once( 'includes/alg-wc-export-functions-ranges.php' );
		require_once( 'includes/alg-wc-export-functions-ajax.php' );
		// Core
		$this->core = require_once( 'includes/class-alg-wc-export-core.php' );
	}

	/**
	 * admin.
	 *
	 * @version 2.3.1
	 * @since   1.3.0
	 */
	function admin() {

		// Action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

		// "Recommendations" page
		add_action( 'init', array( $this, 'add_cross_selling_library' ) );

		// WC Settings tab as WPFactory submenu item
		add_action( 'init', array( $this, 'move_wc_settings_tab_to_wpfactory_menu' ) );

		// Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );

		// Version updated
		if ( get_option( 'alg_wc_export_version', '' ) !== $this->version ) {
			add_action( 'admin_init', array( $this, 'version_updated' ) );
		}

	}

	/**
	 * add_cross_selling_library.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function add_cross_selling_library() {

		if ( ! class_exists( '\WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling' ) ) {
			return;
		}

		$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
		$cross_selling->setup( array( 'plugin_file_path' => __FILE__ ) );
		$cross_selling->init();

	}

	/**
	 * move_wc_settings_tab_to_wpfactory_menu.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function move_wc_settings_tab_to_wpfactory_menu() {

		if ( ! class_exists( '\WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu' ) ) {
			return;
		}

		$wpfactory_admin_menu = \WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();

		if ( ! method_exists( $wpfactory_admin_menu, 'move_wc_settings_tab_to_wpfactory_menu' ) ) {
			return;
		}

		$wpfactory_admin_menu->move_wc_settings_tab_to_wpfactory_menu( array(
			'wc_settings_tab_id' => 'alg_wc_export',
			'menu_title'         => __( 'Export', 'export-woocommerce' ),
			'page_title'         => __( 'Export', 'export-woocommerce' ),
		) );

	}

	/**
	 * version_updated.
	 *
	 * @version 1.4.0
	 * @since   1.3.0
	 */
	function version_updated() {
		update_option( 'alg_wc_export_version', $this->version );
	}

	/**
	 * Add Export settings tab to WooCommerce settings.
	 *
	 * @version 1.3.0
	 * @since   1.0.0
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = require_once( 'includes/settings/class-alg-wc-settings-export.php' );
		return $settings;
	}

	/**
	 * Get the plugin url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

endif;

if ( ! function_exists( 'alg_wc_export' ) ) {
	/**
	 * Returns the main instance of Alg_WC_Export to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  Alg_WC_Export
	 */
	function alg_wc_export() {
		return Alg_WC_Export::instance();
	}
}

/**
 * init.
 *
 * @version 2.2.0
 * @since   1.0.0
 */
add_action( 'plugins_loaded', 'alg_wc_export' );
