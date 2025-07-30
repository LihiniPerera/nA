<?php
/**
 * Export WooCommerce - Functions
 *
 * @version 2.2.4
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'alg_maybe_add_date_query' ) ) {
	/**
	 * alg_maybe_add_date_query.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function alg_maybe_add_date_query( $args, $section = '', $get_from_setting = false ) {
		if ( ( isset( $_GET['start_date'] ) && '' != $_GET['start_date'] ) || ( isset( $_GET['end_date'] ) && '' != $_GET['end_date'] ) )  {
			$date_query = array();
			$date_query['inclusive'] = true;
			if ( isset( $_GET['start_date'] ) && '' != $_GET['start_date'] ) {
				$date_query['after'] = $_GET['start_date'];
			}
			if ( isset( $_GET['end_date'] ) && '' != $_GET['end_date'] ) {
				$date_query['before'] = $_GET['end_date'];
			}
			$args['date_query'] = array( $date_query );
		}
		if($get_from_setting){
			if($section == 'product'){
				$date_filter = get_option('alg_export_products_fields_date_filter', '');
				if(!empty($date_filter)){

					if($date_filter == 'custom_date_range'){
						$from_date = get_option('alg_export_products_fields_from_date', '');
						$end_date = get_option('alg_export_products_fields_end_date', '');
						if(!empty($from_date) && !empty($end_date)){
							$date_query['after'] = $from_date;
							$date_query['before'] = $end_date;
							$args['date_query'] = array( $date_query );
						}
					}else{
						foreach ( array_merge( alg_wc_export_get_reports_standard_ranges(), alg_wc_export_get_reports_custom_ranges() ) as $range_id => $range_data ) {
							if($date_filter == $range_id){
								$date_query['after'] = $range_data['start_date'];
								$date_query['before'] = $range_data['end_date'];
								$args['date_query'] = array( $date_query );
							}
						}
					}
				}
			}
		}
		return $args;
	}
}

if ( ! function_exists( 'alg_get_order_field' ) ) {
	/**
	 * alg_get_order_field.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function alg_get_order_field( $order, $field_id, $is_wc_version_below_3 ) {
		if ( $is_wc_version_below_3 ) {
			return $order->$field_id;
		} else {
			$field_id = 'get_' . $field_id;
			return $order->$field_id();
		}
	}
}

if ( ! function_exists( 'alg_get_export_tools_data' ) ) {
	/**
	 * alg_get_export_tools_data.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @todo    [dev] add/expand tools descriptions
	 */
	function alg_get_export_tools_data() {
		return array(
			'customers' => array(
				'title'     => __( 'Export Customers', 'export-woocommerce' ),
				'desc'      => __( 'Export customers tool.', 'export-woocommerce' ) . ' ' .
					__( 'Customers are exported from WordPress users table, filtering users with WooCommerce customer role.', 'export-woocommerce' ),
			),
			'customers_from_orders' => array(
				'title'     => __( 'Export Customers from Orders', 'export-woocommerce' ),
				'desc'      => __( 'Export customers from orders tool.', 'export-woocommerce' ) . ' ' .
					__( 'Customers are extracted from orders.', 'export-woocommerce' ) . ' ' .
					__( 'Customers are identified by billing email.', 'export-woocommerce' ),
			),
			'orders' => array(
				'title'     => __( 'Export Orders', 'export-woocommerce' ),
				'desc'      => __( 'Export orders tool.', 'export-woocommerce' ),
			),
			'orders_items' => array(
				'title'     => __( 'Export Orders Items', 'export-woocommerce' ),
				'desc'      => __( 'Export orders items tool.', 'export-woocommerce' ),
			),
			'products' => array(
				'title'     => __( 'Export Products', 'export-woocommerce' ),
				'desc'      => __( 'Export products tool.', 'export-woocommerce' ),
			),
		);
	}
}

if ( ! function_exists( 'alg_get_tool_description' ) ) {
	/**
	 * alg_get_tool_description.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_get_tool_description( $tool_id ) {
		$tools = alg_get_export_tools_data();
		return $tools[ $tool_id ]['desc'];
	}
}

if ( ! function_exists( 'alg_get_settings_button_html' ) ) {
	/**
	 * alg_get_settings_button_html.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_get_settings_button_html( $tool_id ) {
		return '<a class="button-primary alg-button-export-tool-settings" href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_export&section=' ) . $tool_id . '">' . __( 'Tool Settings', 'export-woocommerce' ) . '</a>';
	}
}

if ( ! function_exists( 'alg_get_tool_button_html' ) ) {
	/**
	 * alg_get_tool_button_html.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_get_tool_button_html( $tool_id ) {
		return '<a class="button-primary alg-button-export-tool-open" href="' . admin_url( 'admin.php?page=alg-wc-export-tools&alg_wc_export_tool=' ) . $tool_id . '">' . __( 'Open Tool', 'export-woocommerce' ) . '</a>';
	}
}

if ( ! function_exists( 'alg_get_table_html' ) ) {
	/**
	 * alg_get_table_html.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function alg_get_table_html( $data, $args = array() ) {
		$defaults = array(
			'table_class'        => '',
			'table_style'        => '',
			'row_styles'         => '',
			'table_heading_type' => 'horizontal',
			'columns_classes'    => array(),
			'columns_styles'     => array(),
		);
		$args = array_merge( $defaults, $args );
		extract( $args );
		$table_class = ( '' == $table_class ) ? '' : ' class="' . $table_class . '"';
		$table_style = ( '' == $table_style ) ? '' : ' style="' . $table_style . '"';
		$row_styles  = ( '' == $row_styles )  ? '' : ' style="' . $row_styles  . '"';
		$html = '';
		$html .= '<table' . $table_class . $table_style . '>';
		$html .= '<tbody>';
		foreach( $data as $row_number => $row ) {
			$html .= '<tr' . $row_styles . '>';
			foreach( $row as $column_number => $value ) {
				$th_or_td = ( ( 0 === $row_number && 'horizontal' === $table_heading_type ) || ( 0 === $column_number && 'vertical' === $table_heading_type ) ) ? 'th' : 'td';
				$column_class = ( ! empty( $columns_classes ) && isset( $columns_classes[ $column_number ] ) ) ? ' class="' . $columns_classes[ $column_number ] . '"' : '';
				$column_style = ( ! empty( $columns_styles ) && isset( $columns_styles[ $column_number ] ) ) ? ' style="' . $columns_styles[ $column_number ] . '"' : '';

				$html .= '<' . $th_or_td . $column_class . $column_style . '>';
				$html .= $value;
				$html .= '</' . $th_or_td . '>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .= '</table>';
		return $html;
	}
}

if ( ! function_exists( 'alg_get_order_item_product_input_fields' ) ) {
	/**
	 * alg_get_order_item_product_input_fields.
	 *
	 * @version 1.5.1
	 * @since   1.2.1
	 */
	function alg_get_order_item_product_input_fields( $item_id, $item, $_order ) {
		$meta_info             = '';
		$is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		$metadata              = ( $is_wc_version_below_3 ? $_order->has_meta( $item_id ) : $item->get_meta_data() );
		if ( $metadata ) {
			$sep3      = get_option( 'alg_export_csv_separator_3_orders', ', ' );
			$meta_info = array();
			foreach ( $metadata as $meta ) {
				$_meta_key   = ( $is_wc_version_below_3 ? $meta['meta_key']   : $meta->key );
				$_meta_value = ( $is_wc_version_below_3 ? $meta['meta_value'] : $meta->value );
				if ( ! in_array( $_meta_key, array( '_alg_wc_pif_global', '_alg_wc_pif_local' ) ) ) {
					continue;
				}
				if ( empty( $_meta_value ) ) {
					continue;
				}
				$output = array();
				foreach ( $_meta_value as $values ) {
					$output[] = ( '' !== $values['title'] ? $values['title'] . ': ' : '' ) . $values['_value'];
				}
				$meta_info[] = implode( $sep3, $output );
			}
			$meta_info = implode( $sep3, $meta_info );
		}
		return $meta_info;
	}
}

if ( ! function_exists( 'alg_get_order_item_meta_info' ) ) {
	/**
	 * alg_get_order_item_meta_info.
	 *
	 * from woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
	 *
	 * @version 1.5.1
	 * @since   1.0.0
	 */
	function alg_get_order_item_meta_info( $item_id, $item, $_order, $_product = null ) {
		$meta_info             = '';
		$is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		$metadata              = ( $is_wc_version_below_3 ? $_order->has_meta( $item_id ) : $item->get_meta_data() );
		if ( $metadata ) {
			$sep3      = get_option( 'alg_export_csv_separator_3_orders', ', ' );
			$meta_info = array();
			foreach ( $metadata as $meta ) {

				$_meta_key   = ( $is_wc_version_below_3 ? $meta['meta_key']   : $meta->key );
				$_meta_value = ( $is_wc_version_below_3 ? $meta['meta_value'] : $meta->value );

				// Skip hidden core fields
				if ( in_array( $_meta_key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'method_id',
					'cost'
				) ) ) ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $_meta_value ) || is_array( $_meta_value ) ) {
					continue;
				}

				// Get attribute data
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $_meta_key ) ) ) {
					$term        = get_term_by( 'slug', $_meta_value, wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $_meta_key ) );
					$_meta_value = isset( $term->name ) ? $term->name : $_meta_value;
				} else {
					$the_product = null;
					if ( is_object( $_product ) ) {
						$the_product = $_product;
					} elseif ( is_object( $item ) ) {
						$the_product = $_order->get_product_from_item( $item );
					}
					$_meta_key   = ( is_object( $the_product ) ) ? wc_attribute_label( $_meta_key, $the_product ) : $_meta_key;
				}
				$meta_info[] = wp_kses_post( rawurldecode( $_meta_key ) ) . ': ' . wp_kses_post( rawurldecode( $_meta_value ) );
			}
			$meta_info = implode( $sep3, $meta_info );
		}
		return $meta_info;
	}
}

if ( ! function_exists( 'alg_get_product_image_url' ) ) {
 	/**
	 * alg_get_product_image_url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @todo    [dev] placeholder
	 */
	function alg_get_product_image_url( $product_id, $image_size = 'shop_thumbnail' ) {
		if ( has_post_thumbnail( $product_id ) ) {
			$image_url = get_the_post_thumbnail_url( $product_id, $image_size );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_url = get_the_post_thumbnail_url( $parent_id, $image_size );
		} else {
			$image_url = '';
		}
		return $image_url;
	}
}

if ( ! function_exists( 'alg_get_product_gallery_image_url' ) ) {
	/**
	 * alg_get_product_gallery_image_url.
	 *
	 * @version 2.2.4
	 * @since   2.2.4
	 */
	function alg_get_product_gallery_image_url( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return '';
		}

		$gallery_image_ids = $product->get_gallery_image_ids();
		$gallery_urls = [];
		if ( ! empty( $gallery_image_ids ) ) {
			foreach ( $gallery_image_ids as $image_id ) {
				$image_url = wp_get_attachment_url( $image_id );
				if ( $image_url ) {
					$gallery_urls[] = $image_url;
				}
			}
		}

		return implode( ';', $gallery_urls );
	}
}

if ( ! function_exists( 'alg_get_string_comma_replace' ) ) {
 	/**
	 * alg_get_string_comma_replace.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @todo    [dev] placeholder
	 */
	function alg_get_string_comma_replace( $string = '', $del=' | ' ) {

		$string = str_replace(",", $del, $string);
		return $string;
	}
}
if ( ! function_exists( 'sort_array_by_array' ) ) {
	function sort_array_by_array(array $array, array $order_array) {
		$ordered = array();
		foreach ($order_array as $key) {
			if( in_array( $key, $array ) ){
				$ordered[] = $key;
				unset($array[$key]);
			}
		}
		return $ordered + $array;
	}
}
