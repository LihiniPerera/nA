<?php
/**
 * Export WooCommerce - Section Settings
 *
 * @version 2.2.1
 * @since   1.0.0
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Settings_Section' ) ) :

class Alg_WC_Export_Settings_Section {

	/**
	 * ID.
	 *
	 * @since 2.2.1
	 */
	public $id;

	/**
	 * Description.
	 *
	 * @since 2.2.1
	 */
	public $desc;

	/**
	 * Constructor.
	 *
	 * @version 1.3.0
	 * @since   1.0.0
	 */
	function __construct() {
		add_filter( 'woocommerce_get_sections_alg_wc_export',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_alg_wc_export_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
	}
	

	/**
	 * settings_section.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}

	/**
	 * get_save_button.
	 *
	 * @version 1.3.0
	 * @since   1.3.0
	 */
	function get_save_button() {
		return '<button name="save" class="button-primary woocommerce-save-button" type="submit" value="' . esc_attr( __( 'Save changes', 'woocommerce' ) ) . '">' .
			esc_html( __( 'Save changes', 'woocommerce' ) ) . '</button>' .
		apply_filters( 'alg_wc_export', '<p>' .
			sprintf( 'Please upgrade to <a href="%s" target="_blank">Export WooCommerce Pro plugin</a> to add more than one additional export field.',
				'https://wpfactory.com/item/export-woocommerce/' ) .
			'</p>', 'settings' );
	}

}

endif;
