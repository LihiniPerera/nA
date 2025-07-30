<?php
/**
 * WooCommerce Exporter Products
 *
 * The WooCommerce Exporter Products class.
 *
 * @version 2.3.1
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Exporter_Products' ) ) :

class Alg_Exporter_Products {

	/**
	 * @var is_wc_version_below_3
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	public $is_wc_version_below_3;

	/**
	 * Constructor.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		return true;
	}

	/**
	 * get_variable_or_grouped_product_info.
	 *
	 * @version 2.2.6
	 * @since   1.0.0
	 *
	 * @todo    (v2.3.0) remove `is_wc_version_below_3` support
	 */
	function get_variable_or_grouped_product_info( $_product, $which_info ) {
		$all_variations_data = array();

		// Append parent product data
		switch ( $which_info ) {
			case 'product_group_sku':
				$all_variations_data[] = ( '' === $_product->get_sku() ) ? '-' : $_product->get_sku();
				break;
			case 'variable_width':
				$all_variations_data[] = ( '' === $_product->get_width() ) ? '-' : $_product->get_width();
				break;
			case 'variable_length':
				$all_variations_data[] = ( '' === $_product->get_length() ) ? '-' : $_product->get_length();
				break;
			case 'variable_height':
				$all_variations_data[] = ( '' === $_product->get_height() ) ? '-' : $_product->get_height();
				break;
			case 'variable_weight':
				$all_variations_data[] = ( '' === $_product->get_weight() ) ? '-' : $_product->get_weight();
				break;
			case 'variable_downloadable':
				$all_variations_data[] = ( false === $_product->is_downloadable() ) ? '-' : $_product->is_downloadable();
				break;
			case 'variable_virtual':
				$all_variations_data[] = ( false === $_product->is_virtual( ) ) ? '-' : $_product->is_virtual();
				break;
			case 'variable_manage_stock':
				$all_variations_data[] = ( false === $_product->get_manage_stock() ) ? '-' : $_product->get_manage_stock();
				break;
		}

		foreach ( $_product->get_children() as $child_id ) {
			$variation = (
				$this->is_wc_version_below_3 ?
				$_product->get_child( $child_id ) :
				wc_get_product( $child_id )
			);
			if ( ! $variation ) {
				continue;
			}
			switch ( $which_info ) {
				case 'price':
					$all_variations_data[] = ( '' === $variation->get_price() ) ? '-' : $variation->get_price();
					break;
				case 'regular_price':
					$all_variations_data[] = ( '' === $variation->get_regular_price() ) ? '-' : $variation->get_regular_price();
					break;
				case 'sale_price':
					$all_variations_data[] = ( '' === $variation->get_sale_price() ) ? '-' : $variation->get_sale_price();
					break;
				case 'total_stock':
					$variation_total_stock = ( $this->is_wc_version_below_3 ? $variation->get_total_stock() : $variation->get_stock_quantity() );
					$all_variations_data[] = ( null === $variation_total_stock ) ? '-' : $variation_total_stock;
					break;
				case 'stock_quantity':
					$all_variations_data[] = ( null === $variation->get_stock_quantity() ) ? '-' : $variation->get_stock_quantity();
					break;
				case 'variation_attributes':
					$all_variations_data[] = ( ! $variation->is_type( 'variation' ) ) ? '-' : ( $this->is_wc_version_below_3 ? $variation->get_formatted_variation_attributes( true ) : wc_get_formatted_variation( $variation, true ) );
					break;
				case 'product_group_sku':
					$all_variations_data[] = ( '' === $variation->get_sku() ) ? '-' : $variation->get_sku();
					break;
				case 'variable_width':
					$all_variations_data[] = ( '' === $variation->get_width() ) ? '-' : $variation->get_width();
					break;
				case 'variable_length':
					$all_variations_data[] = ( '' === $variation->get_length() ) ? '-' : $variation->get_length();
					break;
				case 'variable_height':
					$all_variations_data[] = ( '' === $variation->get_height() ) ? '-' : $variation->get_height();
					break;
				case 'variable_weight':
					$all_variations_data[] = ( '' === $variation->get_weight() ) ? '-' : $variation->get_weight();
					break;
				case 'variable_downloadable':
					$all_variations_data[] = ( false === $variation->is_downloadable() ) ? '-' : $variation->is_downloadable();
					break;
				case 'variable_virtual':
					$all_variations_data[] = ( false === $variation->is_virtual( ) ) ? '-' : $variation->is_virtual();
					break;
				case 'variable_manage_stock':
					$all_variations_data[] = ( false === $variation->get_manage_stock() ) ? '-' : $variation->get_manage_stock();
					break;
			}
		}
		return implode( get_option( 'alg_export_csv_separator_2_products', '/' ), $all_variations_data );
	}

	/**
	 * export_products.
	 *
	 * @version 2.3.1
	 * @since   1.0.0
	 *
	 * @todo    (dev) export variations; `product-attributes` -> `( ! empty( $_product->get_attributes() ) ? serialize( $_product->get_attributes() ) : '' );`
	 */
	function export_products( $fields_helper, $attach_html = false, $page = 1, $start = 0, $is_ajax = false ) {

		// Time limit
		if ( -1 != ( $time_limit = get_option( 'alg_wc_export_time_limit', -1 ) ) ) {
			set_time_limit( $time_limit );
		}

		$is_prd_attr = false;

		// Standard Fields
		$all_fields        = $fields_helper->get_product_export_fields();
		$fields_ids        = get_option( 'alg_export_products_fields', $fields_helper->get_product_export_default_fields_ids() );
		$fields_ids_sorted = get_option( 'alg_export_products_fields_sorted', array() );

		if ( ! empty( $fields_ids_sorted ) ) {
			if ( false !== strpos( $fields_ids_sorted, ',' ) ) {
				$fields_ids = explode( ',', $fields_ids_sorted );
			} else {
				$fields_ids = array( $fields_ids_sorted );
			}
		}

		$titles = array();
		foreach ( $fields_ids as $field_key => $field_id ) {
			if ( 'product-attributes' == $field_id ){
				$is_prd_attr = true;
				unset( $fields_ids[ $field_key ] );
			} else {
				$titles[] = $all_fields[ $field_id ];
			}
		}

		// Variation product parent title
		$is_variation_newline = get_option( 'alg_export_products_variation_newline', 'no' );
		if ( 'yes' === $is_variation_newline ) {
			$titles[] = __( 'Parent SKU', 'export-woocommerce' );
		}

		// Product attributes
		if ( $is_prd_attr ) {
			$all_attributes = $fields_helper->get_product_export_attribute();
			$attributes_ids = get_option( 'alg_export_products_attribute', array() );
			foreach( $attributes_ids as $attribue_id ) {
				$titles[] = $all_attributes[ $attribue_id ];
			}
		}

		// Additional Fields
		$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_products' );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'alg_export_products_fields_additional_enabled_' . $i, 'no' ) ) {
				$titles[] = get_option( 'alg_export_products_fields_additional_title_' . $i, '' );
			}
		}

		$data = array();
		$data[] = $titles;
		if ( $page > 1 && $is_ajax ) {
			$data = array();
		}

		if ( $attach_html ) {
			$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );
			if ( $page <= 1 ) {
				$offset = 0;
			} else {
				$offset = ( $page - 1 ) * $block_size;
			}
		} else {
			$offset     = 0;
			$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );
		}

		if ( $is_ajax ) {
			$offset = $start;
		}

		while ( true ) {

			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$args = alg_maybe_add_date_query( $args, 'product', true );

			$loop = new WP_Query( $args );

			if ( $is_ajax ) {
				if ( ! $loop->have_posts() ) {
					break;
				}
			}

			if ( ! $attach_html ) {
				if ( ! $loop->have_posts() ) {
					break;
				}
			}

			foreach ( $loop->posts as $product_id ) {
				if ( ! ( $_product = wc_get_product( $product_id ) ) ) {
					continue;
				}
				$row = array();
				foreach ( $fields_ids as $field_id ) {
					$row[] = $this->get_field_value(
						$field_id,
						$_product,
						$product_id,
						$is_variation_newline
					);
				}

				if ( 'yes' === $is_variation_newline ) {
					$row[] = ''; // Parent SKU
				}

				// Product Attributes
				if ( $is_prd_attr ) {
					foreach ( $attributes_ids as $attribue_id ) {
						$txnm        = 'pa_' . $all_attributes[ $attribue_id ];
						$pa_tax_name = apply_filters( 'sanitize_taxonomy_name', urldecode( sanitize_title( urldecode( $txnm ) ) ), $txnm );
						$attr_val    = $_product->get_attribute( $pa_tax_name );
						$row[]       = alg_get_string_comma_replace( ( ! empty( $attr_val ) ? $attr_val : '-' ) );
					}
				}

				// Additional Fields
				$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_products' );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === get_option( 'alg_export_products_fields_additional_enabled_' . $i, 'no' ) ) {
						if ( '' != ( $additional_field_value = get_option( 'alg_export_products_fields_additional_value_' . $i, '' ) ) ) {
							$row[] = get_post_meta( $product_id, $additional_field_value, true );
						} else {
							$row[] = '';
						}
					}
				}

				$data[] = $row;

				// Variation product data newline
				$variations = $_product->get_children();
				if (
					$variations &&
					'yes' === $is_variation_newline &&
					$_product->is_type( 'variable' )
				) {
					foreach ( $variations as $variation_id ) {
						if ( ! ( $variation = wc_get_product( $variation_id ) ) ) {
							continue;
						}
						$row = array();
						foreach ( $fields_ids as $field_id ) {
							$row[] = $this->get_field_value(
								$field_id,
								$variation,
								$variation_id,
								$is_variation_newline
							);
						}
						$row[] = $_product->get_sku(); // Parent SKU
						$data[] = $row;
					}
				}
			}
			$offset += $block_size;
			if ( $attach_html || $is_ajax ) {
				break;
			}
		}

		if ( $attach_html ) {
			$output_html = '';
			$output_html .= '<div class="paginate-ajax-alg-preview">';
			$output_html .= paginate_links( array(
				'total'   => $loop->max_num_pages,
				'current' => $page,
				'base'    => "#%#%", //will make hrefs like "#3"
			) );
			$output_html .= '</div>';
			$output_html .= (
				is_array( $data ) ?
				alg_get_table_html( $data, array( 'table_class' => 'widefat striped' ) ) :
				$data
			);
			return $output_html;
		}

		return $data;
	}

	/**
	 * is_variable_or_grouped_product_info.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function is_variable_or_grouped_product_info( $product, $is_variation_newline ) {
		return (
			'no' === $is_variation_newline &&
			(
				$product->is_type( 'variable' ) ||
				$product->is_type( 'grouped' )
			)
		);
	}

	/**
	 * is_variable_product_info.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function is_variable_product_info( $product, $is_variation_newline ) {
		return (
			'no' === $is_variation_newline &&
			$product->is_type( 'variable' )
		);
	}

	/**
	 * get_field_value.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function get_field_value( $field_id, $product, $product_id, $is_variation_newline ) {
		switch ( $field_id ) {

			case 'product-id':
				return $product_id;

			case 'product-name':
				return $product->get_title();

			case 'product-sku':
				return $product->get_sku();

			case 'product-stock-quantity':
				return (
					$this->is_variable_or_grouped_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'stock_quantity' ) :
					$product->get_stock_quantity()
				);

			case 'product-stock':
				return (
					$this->is_variable_or_grouped_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'total_stock' ) :
					$product->get_stock_quantity()
				);

			case 'product-regular-price':
				return (
					$this->is_variable_or_grouped_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'regular_price' ) :
					$product->get_regular_price()
				);

			case 'product-sale-price':
				return (
					$this->is_variable_or_grouped_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'sale_price' ) :
					$product->get_sale_price()
				);

			case 'product-price':
				return (
					$this->is_variable_or_grouped_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'price' ) :
					$product->get_price()
				);

			case 'product-type':
				return alg_get_string_comma_replace( $product->get_type(), '' );

			case 'product-variation-attributes':
				return (
					$product->is_type( 'variable' ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variation_attributes' ) :
					''
				);

			case 'product-image-url':
				return alg_get_product_image_url( $product_id, 'full' );

			case 'product-gallery-image-url':
				return (
					! $product->is_type( 'variation' ) ?
					alg_get_product_gallery_image_url( $product_id ) :
					''
				);

			case 'product-short-description':
				return (
					! $product->is_type( 'variation' ) ?
					alg_get_string_comma_replace( $product->get_short_description(), '' ) :
					''
				);

			case 'product-description':
				return alg_get_string_comma_replace( $product->get_description(), '' );

			case 'product-status':
				return $product->get_status();

			case 'product-url':
				return (
					! $product->is_type( 'variation' ) ?
					$product->get_permalink() :
					''
				);

			case 'product-group-sku':
				return (
					$product->is_type( 'grouped' ) ?
					$this->get_variable_or_grouped_product_info( $product, 'product_group_sku' ) :
					''
				);

			case 'product-shipping-class':
				return $product->get_shipping_class();

			case 'product-shipping-class-id':
				return $product->get_shipping_class_id();

			case 'product-width':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_width' ) :
					$product->get_width()
				);

			case 'product-length':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_length' ) :
					$product->get_length()
				);

			case 'product-height':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_height' ) :
					$product->get_height()
				);

			case 'product-weight':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_weight' ) :
					$product->get_weight()
				);

			case 'product-downloadable':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_downloadable' ) :
					$product->get_downloadable()
				);

			case 'product-virtual':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_virtual' ) :
					$product->get_virtual()
				);

			case 'product-sold-individually':
				return $product->get_sold_individually();

			case 'product-tax-status':
				return $product->get_tax_status();

			case 'product-tax-class':
				return $product->get_tax_class();

			case 'product-manage-stock':
				return (
					$this->is_variable_product_info( $product, $is_variation_newline ) ?
					$this->get_variable_or_grouped_product_info( $product, 'variable_manage_stock' ) :
					$product->get_manage_stock()
				);

			case 'product-stock-status':
				return $product->get_stock_status();

			case 'product-backorders':
				return $product->get_backorders();

			case 'product-featured':
				return (
					! $product->is_type( 'variation' ) ?
					$product->get_featured() :
					''
				);

			case 'product-visibility':
				return $product->get_catalog_visibility();

			case 'product-price-including-tax':
				return wc_get_price_including_tax( $product );

			case 'product-price-excluding-tax':
				return wc_get_price_excluding_tax( $product );

			case 'product-display-price':
				return wc_get_price_to_display( $product );

			case 'product-average-rating':
				return $product->get_average_rating();

			case 'product-rating-count':
				return $product->get_rating_count();

			case 'product-review-count':
				return $product->get_review_count();

			case 'product-categories':
				return (
					! $product->is_type( 'variation' ) ?
					strip_tags( wc_get_product_category_list( $product->get_id() ) ) :
					''
				);

			case 'product-tags':
				return (
					! $product->is_type( 'variation' ) ?
					strip_tags( wc_get_product_tag_list( $product->get_id() ) ) :
					''
				);

			case 'product-dimensions':
				return wc_format_dimensions( $product->get_dimensions( false ) );

			case 'product-formatted-name':
				return $product->get_formatted_name();

			case 'product-availability':
				$availability = $product->get_availability();
				return $availability['availability'];

			case 'product-availability-class':
				$availability = $product->get_availability();
				return $availability['class'];

		}

	}

}

endif;

return new Alg_Exporter_Products();
