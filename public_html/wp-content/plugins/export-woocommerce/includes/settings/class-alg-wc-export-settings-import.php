<?php
/**
 * Export WooCommerce - Import Section Settings
 *
 * @version 2.2.5
 * @since   2.2.5
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Settings_Import' ) ) :

class Alg_WC_Export_Settings_Import extends Alg_WC_Export_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.2.5
	 * @since   2.2.5
	 */
	function __construct() {
		$this->id   = 'import';
		$this->desc = __( 'Import', 'export-woocommerce' );
		parent::__construct();
	}

}

endif;

return new Alg_WC_Export_Settings_Import();
