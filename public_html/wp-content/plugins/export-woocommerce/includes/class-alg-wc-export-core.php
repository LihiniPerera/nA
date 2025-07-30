<?php
/**
 * Export WooCommerce - Core Class
 *
 * @version 2.2.3
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Core' ) ) :

class Alg_WC_Export_Core {

	/**
	 * Constructor.
	 *
	 * @version 2.2.0
	 * @since   1.0.0
	 *
	 * @todo    (dev) Export Orders Items: Item's (product's) description
	 * @todo    (dev) Export single (or limited number) order only (Export Orders Items and Export Orders and maybe single product/customer also)
	 * @todo    (dev) check if maybe `strip_tags` needs to be applied in some exported fields
	 */
	function __construct() {
		add_action( 'admin_head', array( $this, 'add_admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_export_tools_page' ), PHP_INT_MAX );
		add_action( 'init', array( $this, 'export_csv' ) );
		add_action( 'init', array( $this, 'export_xml' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ) );
		add_action( 'admin_footer', array( $this, 'add_admin_js' ) );
	}

	/**
	 * add_admin_js.
	 *
	 * @version 2.2.0
	 */
	function add_admin_js() {
		?>
		<script>
			jQuery( "select#alg_export_products_fields" ).change( function () {
				if ( jQuery( this ).val().indexOf( "product-attributes" ) > 0 ) {
					show_hide_attr( true );
				} else {
					show_hide_attr();
				}
			} );

			function show_hide_attr( flag = false ) {
				if ( flag ) {
					if ( jQuery( "select#alg_export_products_attribute" ).length > 0 ) {
						jQuery( "label[for='alg_export_products_attribute']" ).show();
						jQuery( "select#alg_export_products_attribute" ).show();
					}
				} else {
					if ( jQuery( "select#alg_export_products_attribute" ).length > 0 ) {
						jQuery( "label[for='alg_export_products_attribute']" ).hide();
						jQuery( "select#alg_export_products_attribute" ).hide();
					}
				}
			}

			jQuery( document ).ready( function () {
				if ( jQuery( "select#alg_export_products_fields" ).length > 0 ) {
					jQuery( "select#alg_export_products_fields" ).change();
				}
			} );
		</script>
		<?php
	}

	/**
	 * enqueue_backend_scripts_and_styles.
	 *
	 * @version 2.1.0
	 * @since   1.1.0
	 */
	function enqueue_backend_scripts_and_styles() {

		if (
			isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) &&
			'wc-settings' === $_GET['page'] &&
			'alg_wc_export' === $_GET['tab'] &&
			(
				'products' === $_GET['section'] ||
				'customers_from_orders' === $_GET['section']
			)
		) {
			$do_add_timepicker = ( 'yes' === get_option( 'alg_wc_export_add_timepicker', 'no' ) );
			wp_enqueue_script( 'alg-wc-export-admin-own-js',
				alg_wc_export()->plugin_url() . '/includes/js/alg-wc-export-admin-own.js',
				array( 'jquery' ),
				alg_wc_export()->version,
				true
			);
			$nonce   = wp_create_nonce( 'alg-wc-export-ajax-nonce' );
			$section = $_GET['section'];
			wp_localize_script(
				'alg-wc-export-admin-own-js',
				'alg_wc_export_admin_own_js',
				array(
					'nonce'   => $nonce,
					'section' => $section,
				)
			);

			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'alg-wc-export-datepicker',
				alg_wc_export()->plugin_url() . '/includes/js/alg-wc-export-datepicker.js',
				array( 'jquery' ),
				alg_wc_export()->version,
				true
			);
			wp_localize_script( 'alg-wc-export-datepicker', 'alg_wc_export_datepicker_options', array( 'do_add_timepicker' => $do_add_timepicker ) );

			wp_enqueue_style( 'jquery-ui-style',
				'//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
				array(),
				alg_wc_export()->version
			);
		}

		if (
			isset( $_GET['page'], $_GET['alg_wc_export_tool'] ) &&
			'alg-wc-export-tools' === $_GET['page']
		) {

			$do_add_timepicker = ( 'yes' === get_option( 'alg_wc_export_add_timepicker', 'no' ) );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'alg-wc-export-datepicker',
				alg_wc_export()->plugin_url() . '/includes/js/alg-wc-export-datepicker.js',
				array( 'jquery' ),
				alg_wc_export()->version,
				true
			);
			wp_localize_script( 'alg-wc-export-datepicker', 'alg_wc_export_datepicker_options', array( 'do_add_timepicker' => $do_add_timepicker ) );
			wp_enqueue_style( 'jquery-ui-style',
				'//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
				array(),
				alg_wc_export()->version
			);
			if ( $do_add_timepicker ) {
				wp_enqueue_script( 'alg-wc-export-jquery-timepicker-addon',
					'//cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js',
					array( 'jquery' ),
					alg_wc_export()->version,
					true
				);
				wp_enqueue_style( 'alg-wc-export-jquery-timepicker-addon-style',
					'//cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css',
					array(),
					alg_wc_export()->version
				);
			}
		}
	}

	/**
	 * add_admin_styles.
	 *
	 * @version 2.1.0
	 * @since   1.0.0
	 */
	function add_admin_styles() {
		echo '<style>';
		echo '.alg-button-export-tool-settings, .alg-button-export-tool-open {
			background: #00c28e !important;
			border-color: #009967 !important;
			color: #fff !important;
			box-shadow: 0 1px 0 #009967 !important;
			text-shadow: 0 -1px 1px #009967,1px 0 1px #009967,0 1px 1px #009967,-1px 0 1px #009967 !important;
		}';
		echo '</style>';

		if (
			isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) &&
			'wc-settings' === $_GET['page'] &&
			'alg_wc_export' === $_GET['tab'] &&
			(
				'products' === $_GET['section'] ||
				'customers_from_orders' === $_GET['section']
			)
		) {
			echo '<style>';
			echo 'p.submit {
					display: none !important;
				}';
			echo 'input#alg_export_products_fields_from_date, input#alg_export_customers_from_orders_fields_from_date{
					float: left;
					margin-right: 20px;
					width: 12em;
				}';
			echo 'input#alg_export_products_fields_from_date + p.description, input#alg_export_customers_from_orders_fields_from_date + p.description{
					float: left;
					width: 12em;
					margin-top: 0px!important;
				}';
			echo 'input#alg_export_products_fields_end_date_alternative, input#alg_export_customers_from_orders_fields_end_date_alternative{
					width: 12em;
				}';
			echo 'body div#alg_wc_export_products_filter_options-description + table.form-table tbody tr:nth-child(3), body div#alg_wc_export_customers_from_orders_filter_options-description + table.form-table tbody tr:nth-child(3) {
					display: none;
				}';
			echo '</style>';
		}
	}

	/**
	 * Add menu item.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function add_export_tools_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Export', 'export-woocommerce' ),
			__( 'Export', 'export-woocommerce' ),
			'manage_woocommerce',
			'alg-wc-export-tools',
			array( $this, 'create_export_tools_page' )
		);
	}

	/**
	 * create_export_tools_page.
	 *
	 * @version 2.0.11
	 * @since   1.0.0
	 *
	 * @todo    (dev) add link to General settings
	 */
	function create_export_tools_page() {
		$tools      = alg_get_export_tools_data();
		$table_data = array();

		$menu = '<div class="wrap woocommerce">';
		$menu .= '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
		$menu .= '<a href="' . admin_url( 'admin.php?page=alg-wc-export-tools' ) . '" class="nav-tab' . $this->is_tab_active( 'dashboard' ) . '">' . __( 'Dashboard', 'export-woocommerce' ) . '</a>';

		foreach ( $tools as $tool_id => $tool_desc ) {
			$menu         .= '<a href="' . admin_url( 'admin.php?page=alg-wc-export-tools&alg_wc_export_tool=' ) . esc_attr( $tool_id ) . '" class="nav-tab' . $this->is_tab_active( $tool_id ) . '">' . $tool_desc['title'] . '</a>';
			$table_data[] = array(
				$tool_desc['title'],
				'<em>' . $tool_desc['desc'] . '</em>',
				alg_get_tool_button_html( $tool_id ),
				alg_get_settings_button_html( $tool_id ),
			);
		}
		$menu      .= '</nav>';
		$menu      .= '</div>';

		if ( isset( $_GET['alg_wc_export_tool'] ) && '' != $_GET['alg_wc_export_tool'] ) {
			$tool_id   = esc_attr( $_GET['alg_wc_export_tool'] );
			$tool_html = $this->create_export_tool( $tool_id, $tools[ $tool_id ]['title'], $tools[ $tool_id ]['desc'] );
		} else {
			$tool_html = '<h1>' . __( 'Dashboard', 'export-woocommerce' ) . '</h1>' .
				alg_get_table_html( $table_data, array(
					'table_class'        => 'widefat striped',
					'table_heading_type' => 'none',
				) );
		}

		echo $menu . $tool_html;
	}

	/**
	 * is_tab_active.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function is_tab_active( $tool_id ) {
		$current_tool_id = ( isset( $_GET['alg_wc_export_tool'] ) ) ? esc_attr( $_GET['alg_wc_export_tool'] ) : 'dashboard';

		return ( $current_tool_id === $tool_id ) ? ' nav-tab-active' : '';
	}

	/**
	 * export_date_fields.
	 *
	 * @version 2.1.0
	 * @since   1.1.0
	 */
	function export_date_fields( $tool_id ) {
		$current_start_date = ( isset( $_GET['start_date'] ) ? esc_attr( $_GET['start_date'] ) : '' );
		$current_end_date   = ( isset( $_GET['end_date'] ) ? esc_attr( $_GET['end_date'] ) : '' );
		$predefined_ranges  = array();

		$predefined_ranges[] = '<a' .
			' href="' . esc_url( add_query_arg( 'range', 'all_time', remove_query_arg( array( 'start_date', 'end_date' ) ) ) ) . '"' .
			(
				(
					empty( $_GET['start_date'] ) &&
					empty( $_GET['end_date'] ) &&
					isset( $_GET['range'] ) &&
					'all_time' === $_GET['range']
				) ?
				' style="font-weight:bold;color:black;"' :
				''
			) .
		'>' .
			__( 'All time', 'export-woocommerce' ) .
		'</a>';

		foreach ( array_merge( alg_wc_export_get_reports_standard_ranges(), alg_wc_export_get_reports_custom_ranges() ) as $range_id => $range_data ) {
			$link                = add_query_arg( array(
				'start_date' => $range_data['start_date'],
				'end_date'   => $range_data['end_date'],
				'range'      => $range_id,
			) );
			$is_active_style     = (
			isset( $_GET['start_date'] ) && strtotime( $range_data['start_date'] ) === strtotime( $_GET['start_date'] ) &&
			isset( $_GET['end_date'] ) && strtotime( $range_data['end_date'] ) === strtotime( $_GET['end_date'] ) ?
				' style="font-weight:bold;color:black;"' : '' );
			$predefined_ranges[] = '<a href="' . esc_url( $link ) . '"' . $is_active_style . '>' . $range_data['title'] . '</a>';
		}

		$predefined_ranges = implode( ' | ', $predefined_ranges );
		$date_input_fields =
			'<strong>' . __( 'Custom:', 'export-woocommerce' ) . '</strong>' . ' ' .
			'<input name="start_date" id="start_date" type="text" display="date" value="' . $current_start_date . '">' .
			'<strong>' . ' - ' . '</strong>' .
			'<input name="end_date" id="end_date" type="text" display="date" value="' . $current_end_date . '">' .
			' ' .
			'<button class="button-primary" name="range" id="range" type="submit" value="custom">' . __( 'Go', 'export-woocommerce' ) . '</button>';

		return '<p>' . $predefined_ranges . '</p>' . '<p>' . $date_input_fields . '</p>';
	}

	/**
	 * create_export_tool.
	 *
	 * @version 2.0.12
	 * @since   1.0.0
	 *
	 * @todo    (dev) `alg_export` and `alg_export_xml` - `$date_args` and `$filter_args` should be updated if changed?
	 */
	function create_export_tool( $tool_id, $tool_title, $tool_desc ) {
		$html = '<h1>' . $tool_title . '</h1>';
		$html .= '<p><em>' . $tool_desc . '</em></p>';
		$html .= '<form action="" method="get">';
		$html .= '<input type="hidden" value="alg-wc-export-tools" name="page">';
		$html .= '<input type="hidden" value="' . $tool_id . '" name="alg_wc_export_tool">';
		$html .= '<input type="hidden" value="' . ( isset( $_GET['range'] ) ? esc_attr( $_GET['range'] ) : '' ) . '" name="range">';
		$html .= '<p>';
		$html .= $this->export_date_fields( $tool_id );

		if ( ! isset( $_GET['range'] ) ) {
			return $html;
		}
		$html .= alg_get_settings_button_html( $tool_id ) . ' ';


		$date_args = ( isset( $_GET['start_date'] ) ? '&start_date=' . esc_attr( $_GET['start_date'] ) : '' );
		$date_args .= ( isset( $_GET['end_date'] ) ? '&end_date=' . esc_attr( $_GET['end_date'] ) : '' );

		$filter_args = ( isset( $_GET['alg_export_filter_all_columns'] ) ? '&alg_export_filter_all_columns=' . esc_attr( $_GET['alg_export_filter_all_columns'] ) : '' );

		$html .= '<a class="button-primary" href="' . home_url( '/?alg_export=' . $tool_id . $date_args . $filter_args ) . '">' . __( 'Download CSV', 'export-woocommerce' ) . '</a>' . ' ';
		$html .= '<a class="button-primary" href="' . home_url( '/?alg_export_xml=' . $tool_id . $date_args . $filter_args ) . '">' . __( 'Download XML', 'export-woocommerce' ) . '</a>';
		$html .= '<button style="float:right;margin-right:10px;" class="button-primary" type="submit" name="alg_export_filter" value="' . $tool_id . '">' . __( 'Filter by All Fields', 'export-woocommerce' ) . '</button>';
		$html .= '<input style="float:right;margin-right:10px;" type="text" name="alg_export_filter_all_columns" value="' . ( isset( $_GET['alg_export_filter_all_columns'] ) ? esc_attr( $_GET['alg_export_filter_all_columns'] ) : '' ) . '">';
		$html .= '</p>';
		$html .= '</form>';
		$data = $this->export( $tool_id );
		$html .= ( is_array( $data ) ) ? alg_get_table_html( $data, array( 'table_class' => 'widefat striped' ) ) : $data;

		return $html;
	}

	/**
	 * export.
	 *
	 * @version 2.0.11
	 * @since   1.0.0
	 *
	 * @todo    (dev) when filtering now using strpos, but other options would be stripos (case-insensitive) or strict equality
	 * @todo    (dev) `if ( 1 == count( $data ) ) { return '<em>' . __( 'No results found.', 'export-woocommerce' ) . '</em>'; }`
	 */
	function export( $tool_id, $attach_html = false, $page = 1, $start = 0, $is_ajax = false ) {
		$data = array();
		switch ( $tool_id ) {
			case 'customers':
				$exporter = require_once( 'exporters/class-alg-exporter-customers.php' );
				$data     = $exporter->export_customers( alg_wc_export()->fields_helper );
				break;
			case 'customers_from_orders':
				$exporter = require_once( 'exporters/class-alg-exporter-customers.php' );
				$data     = $exporter->export_customers_from_orders( alg_wc_export()->fields_helper, $attach_html, $page, $start, $is_ajax );
				break;
			case 'orders':
				$exporter = require_once( 'exporters/class-alg-exporter-orders.php' );
				$data     = $exporter->export_orders( alg_wc_export()->fields_helper );
				break;
			case 'orders_items':
				$exporter = require_once( 'exporters/class-alg-exporter-orders.php' );
				$data     = $exporter->export_orders_items( alg_wc_export()->fields_helper );
				break;
			case 'products':
				$exporter = require_once( 'exporters/class-alg-exporter-products.php' );
				$data     = $exporter->export_products( alg_wc_export()->fields_helper, $attach_html, $page, $start, $is_ajax );
				break;
		}

		if ( ! $attach_html ) {
			if ( isset( $_GET['alg_export_filter_all_columns'] ) && '' != $_GET['alg_export_filter_all_columns'] ) {
				$filter_str = esc_attr( $_GET['alg_export_filter_all_columns'] );

				foreach ( $data as $row_id => $row ) {
					if ( 0 == $row_id ) {
						continue;
					}
					$is_filtered = false;

					foreach ( $row as $cell ) {
						if ( false !== strpos( $cell, $filter_str ) ) {
							$is_filtered = true;
							break;
						}
					}

					if ( ! $is_filtered ) {
						unset( $data[ $row_id ] );
					}
				}
			}
		}

		return apply_filters( 'alg_export_data', $data, $tool_id );
	}

	/**
	 * export_xml.
	 *
	 * @version 2.0.11
	 * @since   1.0.0
	 *
	 * @todo    (dev) templates for xml_start, xml_end, xml_item
	 * @todo    (dev) str_replace( '&', '&amp;', $cell_value )
	 */
	function export_xml() {
		if ( isset( $_GET['alg_export_xml'] ) ) {
			if (
				'' != get_option( 'alg_export_csv_xml_user_capability', 'manage_options' ) &&
				( ! function_exists( 'current_user_can' ) || ! current_user_can( get_option( 'alg_export_csv_xml_user_capability', 'manage_options' ) ) )
			) {
				return;
			}
			$tool_id = esc_attr( $_GET['alg_export_xml'] );
			$data    = $this->export( $tool_id );

			$data = apply_filters( 'alg_export_data_xml', $data, $tool_id );

			if ( is_array( $data ) ) {
				$xml = '';
				$xml .= '<?xml version = "1.0" encoding = "utf-8" ?>' . PHP_EOL . '<root>' . PHP_EOL;

				foreach ( $data as $row_num => $row ) {
					if ( 0 == $row_num ) {
						foreach ( $row as $cell_id => $cell_value ) {
							$cell_ids[ $cell_id ] = sanitize_title_with_dashes( $cell_value );
						}
						continue;
					}
					$xml .= '<item>' . PHP_EOL;

					foreach ( $row as $cell_id => $cell_value ) {
						$cell_value = str_replace( '&', '&amp;', $cell_value );
						$xml        .= "\t" . '<' . $cell_ids[ $cell_id ] . '>' . $cell_value . '</' . $cell_ids[ $cell_id ] . '>' . PHP_EOL;
					}
					$xml .= '</item>' . PHP_EOL;
				}
				$xml .= '</root>';

				header( "Content-Encoding: UTF-8" );
				header( "Content-Disposition: attachment; filename=" . $tool_id . ".xml" );
				header( "Content-Type: text/xml; charset=utf-8" );
				header( "Content-Description: File Transfer" );
				if ( 'yes' === get_option( 'alg_export_csv_send_content_length_header', 'yes' ) ) {
					header( "Content-Length: " . strlen( $xml ) );
				}
				echo $xml;
				die();
			}
		}
	}

	/**
	 * export_csv.
	 *
	 * @version 2.2.2
	 * @since   1.0.0
	 */
	function export_csv() {
		if ( isset( $_GET['alg_export'] ) ) {
			if (
				'' != get_option( 'alg_export_csv_xml_user_capability', 'manage_options' ) &&
				(
					! function_exists( 'current_user_can' ) ||
					! current_user_can( get_option( 'alg_export_csv_xml_user_capability', 'manage_options' ) )
				)
			) {
				return;
			}

			$tool_id = esc_attr( $_GET['alg_export'] );
			$data    = $this->export( $tool_id );
			$data    = apply_filters( 'alg_export_data_csv', $data, $tool_id );

			if ( is_array( $data ) ) {
				$csv  = '';
				$wrap = get_option( 'alg_export_csv_wrap', '' );
				$sep  = $wrap . get_option( 'alg_export_csv_separator', ',' ) . $wrap;
				foreach ( $data as $row ) {
					$row = array_map( array( $this, 'format_string' ), $row );
					$csv .= $wrap . implode( $sep, $row ) . $wrap . PHP_EOL;
				}
				if ( 'yes' === get_option( 'alg_export_csv_add_utf_8_bom', 'yes' ) ) {
					$csv = "\xEF\xBB\xBF" . $csv; // UTF-8 BOM
				}
				header( "Content-Encoding: UTF-8" );
				header( "Content-Disposition: attachment; filename=" . $tool_id . ".csv" );
				header( "Content-Type: text/csv; charset=utf-8" );
				header( "Content-Description: File Transfer" );
				if ( 'yes' === get_option( 'alg_export_csv_send_content_length_header', 'yes' ) ) {
					header( "Content-Length: " . strlen( $csv ) );
				}
				echo $csv;
				die();
			}
		}
	}

	/**
	 * Formats a string for safe output.
	 *
	 * @version 2.2.3
	 * @since   2.2.2
	 */
	function format_string( $value ) {
		if ( ! empty( $value ) && preg_match( '/[",\r\n]/', $value ) ) {
			// Normalize line breaks to a single newline character; remove comma
			$value = str_replace( [ "\r\n", "\r", "," ], [ "\n", "", " " ], $value );

			// Escape double quotes by doubling them
			$value = str_replace( '"', '""', $value );

			if ( '"' !== get_option( 'alg_export_csv_wrap', '' ) ) {
				// Enclose the string in double quotes
				$value = '"' . $value . '"';
			}
		}

		return $value;
	}

}

endif;

return new Alg_WC_Export_Core();
