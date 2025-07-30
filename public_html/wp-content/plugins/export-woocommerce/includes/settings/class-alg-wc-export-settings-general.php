<?php
/**
 * Export WooCommerce - General Section Settings
 *
 * @version 2.0.14
 * @since   1.0.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Settings_General' ) ) :

class Alg_WC_Export_Settings_General extends Alg_WC_Export_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = '';
		$this->desc = __( 'General', 'export-woocommerce' );
		parent::__construct();
	}

	/**
	 * add_settings.
	 *
	 * @version 2.0.14
	 * @since   1.0.0
	 * @todo    [dev] add link to tools Dashboard
	 * @todo    [dev] add more info (e.g. Wrap)
	 * @todo    [dev] maybe add more secondary separators (i.e. for each column)
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'     => __( 'Export Options', 'export-woocommerce' ),
				'type'      => 'title',
				'id'        => 'alg_wc_export_options',
			),
			array(
				'title'     => __( 'CSV Separator', 'export-woocommerce' ),
				'id'        => 'alg_export_csv_separator',
				'default'   => ',',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'title'     => __( 'CSV Wrap', 'export-woocommerce' ),
				'id'        => 'alg_export_csv_wrap',
				'default'   => '',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'title'     => __( 'UTF-8 BOM', 'export-woocommerce' ),
				'desc'      => __( 'Add', 'export-woocommerce' ),
				'desc_tip'  => __( 'Add UTF-8 BOM sequence.', 'export-woocommerce' ),
				'id'        => 'alg_export_csv_add_utf_8_bom',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Content Length Header', 'export-woocommerce' ),
				'desc'      => __( 'Send', 'export-woocommerce' ),
				'desc_tip'  => __( 'Disable this if you are experiencing "endless" file download when using "Download CSV" or "Download XML" buttons.', 'export-woocommerce' ),
				'id'        => 'alg_export_csv_send_content_length_header',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'User Capability', 'export-woocommerce' ),
				'desc_tip'  => __( 'Set required user capability for CSV and XML export. Leave blank if you want all users (including not logged) to be able to export the data (not recommended).', 'export-woocommerce' ),
				'desc'      => sprintf( __( 'Default: %s (administrators).', 'export-woocommerce' ), '<code>manage_options</code>' ) . ' ' .
					sprintf( __( 'Try: %s (administrators & shop managers).', 'export-woocommerce' ), '<code>manage_woocommerce</code>' ),
				'id'        => 'alg_export_csv_xml_user_capability',
				'default'   => 'manage_options',
				'type'      => 'text',
			),
			array(
				'title'     => __( 'Timepicker', 'export-woocommerce' ),
				'desc_tip'  => __( 'Adds timepicker to all export tools (to "Custom" range).', 'export-woocommerce' ),
				'desc'      => __( 'Add', 'export-woocommerce' ),
				'id'        => 'alg_wc_export_add_timepicker',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'alg_wc_export_options',
			),
			array(
				'title'     => __( 'Secondary Separators Options', 'export-woocommerce' ),
				'type'      => 'title',
				'id'        => 'alg_wc_export_secondary_separators_options',
			),
			array(
				'title'     => __( 'Orders', 'export-woocommerce' ) . ': ' . __( 'Secondary Separator', 'export-woocommerce' ),
				'desc'      => sprintf( __( 'Used in: %s columns.', 'export-woocommerce' ), '"' . implode( '", "', array(
					__( 'Order Product Input Fields', 'export-woocommerce' ),
					__( 'Backend Order Notes', 'export-woocommerce' ),
					__( 'Order Items', 'export-woocommerce' ),
				) ) . '"' ) . ' ' . sprintf( __( 'Default: %s', 'export-woocommerce' ), '<code>&nbsp;/&nbsp;</code>' ),
				'id'        => 'alg_export_csv_separator_2_orders',
				'default'   => ' / ',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'title'     => __( 'Orders', 'export-woocommerce' ) . ': ' . __( 'Third Separator', 'export-woocommerce' ),
				'desc'      => sprintf( __( 'Used in: %s columns.', 'export-woocommerce' ), '"' . implode( '", "', array(
					__( 'Item Product Input Fields', 'export-woocommerce' ),
					__( 'Order Product Input Fields', 'export-woocommerce' ),
					__( 'Item Meta', 'export-woocommerce' ),
					__( 'Item Variation Meta', 'export-woocommerce' ),
					__( 'Order Items', 'export-woocommerce' ),
				) ) . '"' ) . ' ' . sprintf( __( 'Default: %s', 'export-woocommerce' ), '<code>,&nbsp;</code>' ),
				'id'        => 'alg_export_csv_separator_3_orders',
				'default'   => ', ',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'title'     => __( 'Customers', 'export-woocommerce' ) . ': ' . __( 'Secondary Separator', 'export-woocommerce' ),
				'desc'      => sprintf( __( 'Used in: %s column.', 'export-woocommerce' ), '"' . implode( '", "', array(
					__( 'User Roles', 'export-woocommerce' ),
				) ) . '"' ) . ' ' . sprintf( __( 'Default: %s', 'export-woocommerce' ), '<code>&nbsp;/&nbsp;</code>' ),
				'id'        => 'alg_export_csv_separator_2_customers',
				'default'   => ' / ',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'title'     => __( 'Products', 'export-woocommerce' ) . ': ' . __( 'Secondary Separator', 'export-woocommerce' ),
				'desc'      => sprintf( __( 'Used in: %s columns.', 'export-woocommerce' ), '"' . implode( '", "', array(
					__( 'Stock Quantity', 'export-woocommerce' ),
					__( 'Total Stock', 'export-woocommerce' ),
					__( 'Regular Price', 'export-woocommerce' ),
					__( 'Sale Price', 'export-woocommerce' ),
					__( 'Price', 'export-woocommerce' ),
					__( 'Variation Attributes', 'export-woocommerce' ),
				) ) . '"' ) . ' ' . sprintf( __( 'Default: %s', 'export-woocommerce' ), '<code>/</code>' ),
				'id'        => 'alg_export_csv_separator_2_products',
				'default'   => '/',
				'type'      => 'text',
				'alg_wc_export_raw' => true,
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'alg_wc_export_secondary_separators_options',
			),
			array(
				'title'     => __( 'Advanced Options', 'export-woocommerce' ),
				'type'      => 'title',
				'id'        => 'alg_wc_export_advanced_options',
			),
			array(
				'title'     => __( 'Block Size', 'export-woocommerce' ),
				'desc_tip'  => __( 'Number of items to process in one iteration.', 'export-woocommerce' ),
				'type'      => 'number',
				'id'        => 'alg_wc_export_wp_query_block_size',
				'default'   => 1024,
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'title'     => __( 'Time Limit', 'export-woocommerce' ),
				'desc_tip'  => __( 'Export tools maximum time limit in seconds.', 'export-woocommerce' ) . ' ' .
					__( 'If set to minus one, option is ignored and default time limit is used.', 'export-woocommerce' ) . ' ' .
					__( 'If set to zero, no time limit is imposed.', 'export-woocommerce' ),
				'type'      => 'number',
				'id'        => 'alg_wc_export_time_limit',
				'default'   => -1,
				'custom_attributes' => array( 'min' => -1 ),
			),
			array(
				'title'     => __( 'Ajax Download', 'export-woocommerce' ),
				'desc_tip'  => __( 'Please use for heavy volume data. ( For now only Product CSV )', 'export-woocommerce' ),
				'desc'  	=> __( 'Enable', 'export-woocommerce' ),
				'type'      => 'checkbox',
				'id'        => 'alg_wc_export_ajax_download',
				'default'   => 'no',
			),
			array(
				'title'     => __( 'Confirm HPOS enabled', 'export-woocommerce' ),
				'desc_tip'  => __( 'Order query differ based on HPOS enabled or not. (If you find no order appear on export but store has order. please check with enabeling this.)', 'export-woocommerce' ),
				'desc'  	=> __( 'Enable', 'export-woocommerce' ),
				'type'      => 'checkbox',
				'id'        => 'alg_wc_export_confirm_hpos',
				'default'   => 'no',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'alg_wc_export_advanced_options',
			),
		);
		return $settings;
	}

}

endif;

return new Alg_WC_Export_Settings_General();
