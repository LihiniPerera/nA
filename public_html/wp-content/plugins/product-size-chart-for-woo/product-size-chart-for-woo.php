<?php
/**
 * Plugin Name: Product Size Chart for WooCommerce
 * Plugin URI: https://villatheme.com/extensions/woo-product-size-chart/
 * Description: WooCommerce Size Chart lets customize and design size charts for specific products or categories, enhancing customer convenience and boosting sales.
 * Version: 2.1.0
 * Author URI: http://villatheme.com
 * Author: VillaTheme
 * Copyright 2021-2025 VillaTheme.com. All rights reserved.
 * Text Domain: product-size-chart-for-woo
 * Requires Plugins: woocommerce
 * Tested up to: 6.8
 * WC requires at least: 7.0
 * WC tested up to: 9.9
 * Requires PHP: 7.0
 **/

namespace PSCWF;

use PSCWF\Admin\Size_Chart_Product;
use PSCWF\Inc\Customizer\Customizer;
use PSCWF\Inc\Enqueue;
use PSCWF\Inc\Data;
use PSCWF\Admin\Settings;
use PSCWF\Admin\Size_Chart;
use PSCWF\Inc\Frontend\Front_End;
use PSCWF\Inc\Setup_Wizard;
use PSCWF\Inc\Short_Code;

defined( 'ABSPATH' ) || exit;
//Compatible with High-Performance order storage (COT)
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce-product-size-chart/woocommerce-product-size-chart.php' ) ) {
	return;
}
if (!defined('PSCW_CONST_F')){
	$plugin_url = plugins_url( 'assets/', __FILE__ );
	$plugin_dir = plugin_dir_path( __FILE__ );
	define( 'PSCW_CONST_F', [
		'version'     => '2.1.0',
		'plugin_name' => 'Product Size Chart for WooCommerce',
		'slug'        => 'pscw',
		'assets_slug' => 'pscw-',
		'file'        => __FILE__,
		'basename'    => plugin_basename( __FILE__ ),
		'plugin_dir'      => $plugin_dir,
		'languages'        =>$plugin_dir. "languages" . DIRECTORY_SEPARATOR ,
		'libs_url'        =>$plugin_url . "libs/" ,
		'css_url'         => $plugin_url . "css/" ,
		'js_url'          => $plugin_url . "js/"  ,
		'img_url'         => $plugin_url . "img/" ,
		'sample_data_url' => $plugin_url . "sample-data/" ,
	] );
}
require_once plugin_dir_path( __FILE__ ) . 'autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'support/support.php';

if ( ! class_exists( 'Product_Size_Chart_F' ) ) {
	class Product_Size_Chart_F {
		public function __construct() {
			add_action( 'activated_plugin', [ $this, 'after_activated' ] );
			add_action( 'plugins_loaded', array( $this, 'check_environment' ) );
		}

		function check_environment($recent_activate = false) {
			$environment = new \VillaTheme_Require_Environment( [
					'plugin_name'     => 'Product Size Chart for WooCommerce',
					'php_version'     => '7.0',
					'wp_version'      => '5.0',
					'wc_version'      => '7.0',
					'require_plugins' => [
						[
							'slug'    => 'woocommerce',
							'name'    => 'WooCommerce',
							'file'    => 'woocommerce/woocommerce.php',
							'version' => '7.0',
						],

					]
				]
			);

			if ( $environment->has_error() ) {
				return;
			}
			require_once PSCW_CONST_F['plugin_dir'] . 'inc/functions.php';
			if ( get_option( 'pscw_setup_wizard' )&&
			     ( $recent_activate || ( ! empty( $_GET['post_type'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ), "pscw" ) === 0 ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url = add_query_arg( [ 'page' => 'pscw-setup' ], admin_url() );
				wp_safe_redirect( $url );
				exit();
			}
			add_action( 'admin_init', array( $this, 'update_database' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
			$this->load_classes();
		}
		public function update_database(){
			if (get_option('pscw_maybe_migrate') || !get_option('pscw_maybe_re_migrate')){
				$this->migrate_data_from_free_to_pro();
				delete_option( 'pscw_maybe_migrate' );
			}
		}

		public function init() {
			$this->load_plugin_textdomain();
			if ( is_admin() && ! wp_doing_ajax() ) {
				$this->support();
			}
		}

		public function load_plugin_textdomain() {
			/**
			 * load Language translate
			 */
			$locale = apply_filters( 'plugin_locale', get_locale(), 'product-size-chart-for-woo' );
			load_textdomain( 'product-size-chart-for-woo', PSCW_CONST_F['languages'] . "product-size-chart-for-woo-$locale.mo" );
		}

		function load_classes() {
			Enqueue::instance();
			Setup_Wizard::instance();
			Settings::instance();
			Size_Chart::instance();
			Size_Chart_Product::instance();
			Short_Code::instance();
			Front_End::instance();
			Customizer::instance();

		}

		public function support() {
			new \VillaTheme_Support(
				array(
					'support'    => 'https://wordpress.org/support/plugin/product-size-chart-for-woo/',
					'docs'       => 'https://docs.villatheme.com/?item=woocommerce-product-size-chart',
					'review'     => 'https://wordpress.org/support/plugin/product-size-chart-for-woo/reviews/?rate=5#rate-response',
					'pro_url'    => 'https://1.envato.market/zN1kJe',
					'css'        => PSCW_CONST_F['css_url'],
					'image'      => PSCW_CONST_F['img_url'],
					'slug'       => 'product-size-chart-for-woo',
					'menu_slug'  => 'edit.php?post_type=pscw-size-chart',
					'version'    => PSCW_CONST_F['version'],
					'survey_url' => 'https://script.google.com/macros/s/AKfycbyu3hbv83J-U0p0RxhdqaTBKXlE2A7Vja6BC2XmaYq8bXymI4VDeDA2sFYgjTH-c3yXfw/exec'
				)
			);
		}

		public function settings_link( $links ) {
			return array_merge(
				[
					sprintf( "<a href='%1s' >%2s</a>", esc_url( admin_url( 'edit.php?post_type=pscw-size-chart&page=pscw-size-chart-setting' ) ),
						esc_html__( 'Settings', 'product-size-chart-for-woo' ) )
				],
				$links );
		}

		public function after_activated( $plugin ) {
			if ( $plugin !== PSCW_CONST_F['basename'] ) {
				return;
			}
			if (!get_option( 'woo_sc_setting' )){
				update_option( 'pscw_setup_wizard', 1, 'no' );
				$this->check_environment( true );
			}else{
				update_option( 'pscw_maybe_migrate', 1, 'no' );
			}
		}

		public function migrate_data_from_free_to_pro() {
			$posts = get_posts( array(
					'post_type'   => 'pscw-size-chart',
					'numberposts' => - 1,
					//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'  => array(
						'relation' => 'AND',
						array(
							'key'     => 'woo_sc_size_chart_data',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => 'pscw_data',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'pscw_interface',
							'compare' => 'NOT EXISTS',
						),
					)
				)
			);
			if ($posts) {
				foreach ( $posts as $postt ) {
					$woo_sc_size_chart_data = get_post_meta( $postt->ID, 'woo_sc_size_chart_data', true );
					pscw_migrate_size_chart_data( $postt, $woo_sc_size_chart_data );
					$this->migrate_products( $postt, $woo_sc_size_chart_data );
				}
				update_option('pscw_maybe_re_migrate',1,'no');
			}
		}

		public function migrate_products( $postt, $woo_sc_size_chart_data ) {
			$pscw_data = [
				'all_products'          => '',
//				'assign'          => 'none',
//				'allow_countries' => array(),
//				'condition'       => array(),
			];
			if (!empty($woo_sc_size_chart_data['categories']) && is_array($woo_sc_size_chart_data['categories'])){
				$pscw_data['include_product_cat'] = $woo_sc_size_chart_data['categories'];
			}
			if (!empty($woo_sc_size_chart_data['search_product']) && is_array($woo_sc_size_chart_data['search_product'])){
				$pscw_data['include_products'] = $woo_sc_size_chart_data['search_product'];
			}
			update_post_meta( $postt->ID, 'pscw_data', $pscw_data );
		}
	}

	new Product_Size_Chart_F();
}
