<?php
/**
 * WooCommerce Exporter Orders
 *
 * @version 2.2.3
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Exporter_Orders' ) ) :

class Alg_Exporter_Orders {

	/**
	 * @var is_wc_version_below_3
	 *
	 * @version 2.2.3
	 * @since   2.2.3
	 */
	public $is_wc_version_below_3;

	/**
	 * @var alg_wc_export_confirm_hpos
	 *
	 * @version 2.0.14
	 * @since   2.0.14
	 */
	public $alg_wc_export_confirm_hpos = 'no';

	/**
	 * Constructor.
	 *
	 * @version 2.0.14
	 * @since   1.0.0
	 */
	function __construct() {
		$this->is_wc_version_below_3      = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		$this->alg_wc_export_confirm_hpos = get_option( 'alg_wc_export_confirm_hpos', 'no' );
		return true;
	}

	/**
	 * get_export_orders_row.
	 *
	 * @version 2.0.8
	 * @since   1.0.0
	 */
	function get_export_orders_row( $fields_ids, $order_id, $order, $items, $item, $item_id ) {
		$sep2 = get_option( 'alg_export_csv_separator_2_orders', ' / ' );
		$row  = array();

		foreach( $fields_ids as $field_id ) {
			switch ( $field_id ) {
				case 'item-id':
					$row[] = $item_id;
					break;
				case 'item-debug':
					$row[] = '<pre>' . print_r( $item, true ) . '</pre>';
					break;
				case 'item-name':
					$row[] = $item['name'];
					break;
				case 'item-sku':
					$sku = '';
					if(isset($item['variation_id']) && $item['variation_id'] > 0){
						$sku = get_post_meta( $item['variation_id'], '_sku', true );
					} else if(isset($item['product_id']) && $item['product_id'] > 0){
						$sku = get_post_meta( $item['product_id'], '_sku', true );
					}
					$row[] = $sku;
					break;
				case 'item-product-input-fields':
					$row[] = alg_get_order_item_product_input_fields( $item_id, $item, $order );
					break;
				case 'item-meta':
					$row[] = alg_get_order_item_meta_info( $item_id, $item, $order );
					break;
				case 'item-variation-meta':
					$row[] = ( 0 != $item['variation_id'] ) ? alg_get_order_item_meta_info( $item_id, $item, $order ) : '';
					break;
				case 'item-qty':
					$row[] = $item['qty'];
					break;
				case 'item-tax-class':
					$row[] = $item['tax_class'];
					break;
				case 'item-product-id':
					$row[] = $item['product_id'];
					break;
				case 'item-variation-id':
					$row[] = $item['variation_id'];
					break;
				case 'item-line-subtotal':
					$row[] = $item['line_subtotal'];
					break;
				case 'item-line-total':
					$row[] = $item['line_total'];
					break;
				case 'item-line-subtotal-tax':
					$row[] = $item['line_subtotal_tax'];
					break;
				case 'item-line-tax':
					$row[] = $item['line_tax'];
					break;
				case 'item-line-total-plus-tax':
					$row[] = $item['line_total'] + $item['line_tax'];
					break;
				case 'item-line-subtotal-plus-tax':
					$row[] = $item['line_subtotal'] + $item['line_subtotal_tax'];
					break;
				case 'order-id':
					$row[] = $order_id;
					break;
				case 'order-number':
					$row[] = $order->get_order_number();
					break;
				case 'order-status':
					$row[] = $order->get_status();
					break;
				case 'order-date':
					$row[] = str_replace(',', ' ', get_the_date( get_option( 'date_format' ), $order_id ));
					break;
				case 'order-time':
					$row[] = get_the_time( get_option( 'time_format' ), $order_id );
					break;
				case 'order-item-count':
					$row[] = $order->get_item_count();
					break;
				case 'order-items':
					$row[] = $items;
					break;
				case 'order-product-input-fields':
					$result = array();
					foreach ( $order->get_items() as $_item_id => $_item ) {
						$result[] = alg_get_order_item_product_input_fields( $_item_id, $_item, $order );
					}
					$row[] = implode( $sep2, $result );
					break;
				case 'order-currency':
					$row[] = ( $this->is_wc_version_below_3 ? $order->get_order_currency() : $order->get_currency() );
					break;
				case 'order-total':
					$row[] = $order->get_total();
					break;
				case 'order-total-tax':
					$row[] = $order->get_total_tax();
					break;
				case 'order-shipping-total':
					$row[] = $order->get_shipping_total();
					break;
				case 'order-payment-method':
					$row[] = ( $this->is_wc_version_below_3 ? $order->payment_method_title : $order->get_payment_method_title() );
					break;
				case 'order-notes':
					$row[] = ( $this->is_wc_version_below_3 ? $order->customer_note : $order->get_customer_note() );
					break;
				case 'customer-id':
					$row[] = (int) $order->get_user_id();
					break;
				case 'shipping-method':
					$sresult = array();
					foreach( $order->get_items( 'shipping' ) as $sitem_id => $sitem ){
						// Get the data in an unprotected array
						$sitem_data = $sitem->get_data();
						$sresult[]  = $sitem_data['method_title'];
					}
					$row[] = implode( $sep2, $sresult );
					break;
				case 'backend-order-notes':
					$row[] = apply_filters( 'alg_wc_export', '', 'order_export_backend_order_notes', array( 'order_id' => $order_id, 'sep2' => $sep2 ) );
					break;
				case 'billing-first-name':
				case 'billing-last-name':
				case 'billing-company':
				case 'billing-address-1':
				case 'billing-address-2':
				case 'billing-city':
				case 'billing-state':
				case 'billing-postcode':
				case 'billing-country':
				case 'billing-phone':
				case 'billing-email':
				case 'shipping-first-name':
				case 'shipping-last-name':
				case 'shipping-company':
				case 'shipping-address-1':
				case 'shipping-address-2':
				case 'shipping-city':
				case 'shipping-state':
				case 'shipping-postcode':
				case 'shipping-country':
					$_field_id = str_replace( '-', '_', $field_id );
					$row[] = alg_get_order_field( $order, $_field_id, $this->is_wc_version_below_3 );
					break;
			}
		}

		return $row;
	}

	/**
	 * export_orders.
	 *
	 * @version 2.0.14
	 * @since   1.0.0
	 * @todo    [dev] (maybe) metainfo as separate column
	 */
	function export_orders( $fields_helper ) {

		// Time limit
		if ( -1 != ( $time_limit = get_option( 'alg_wc_export_time_limit', -1 ) ) ) {
			set_time_limit( $time_limit );
		}

		// Standard Fields
		$all_fields = $fields_helper->get_order_export_fields();
		$fields_ids = get_option( 'alg_export_orders_fields', $fields_helper->get_order_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Additional Fields
		$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_orders' );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'alg_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
				$titles[] = get_option( 'alg_export_orders_fields_additional_title_' . $i, '' );
			}
		}

		$sep2       = get_option( 'alg_export_csv_separator_2_orders', ' / ' );
		$data       = array();
		$data[]     = $titles;
		$offset     = 0;
		$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );

		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);

			if( $this->alg_wc_export_confirm_hpos == 'yes' ){
				$args_orders = array(
					'type'      	 => 'shop_order',
					'status'         => array_keys(wc_get_order_statuses()),
					'limit' 		 => $block_size,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'offset'         => $offset,
					'return'         => 'ids',
				);
			}

			$args_orders = alg_maybe_add_date_query( $args_orders );

			$result_order_ids = array();

			if ( $this->alg_wc_export_confirm_hpos == 'yes' ) {

				$loop_orders = new WC_Order_Query( $args_orders );
				$result_order_ids = $loop_orders->get_orders();

				if( empty( $result_order_ids ) ) {
					break;
				}

			} else {

				$loop_orders = new WP_Query( $args_orders );

				if ( ! $loop_orders->have_posts() ) {
					break;
				}


				$result_order_ids = $loop_orders->posts;

			}


			foreach ( $result_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				// Standard Fields
				$items = array();
				if ( in_array( 'order-items', $fields_ids ) ) {
					foreach ( $order->get_items() as $item_id => $item ) {
						$meta_info = ( 0 != $item['variation_id'] ) ? alg_get_order_item_meta_info( $item_id, $item, $order ) : '';
						if ( '' != $meta_info ) {
							$meta_info = ' [' . $meta_info . ']';
						}
						$items[] = $item['name'] . $meta_info;
					}
					$items = implode( $sep2, $items );
				}
				$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, $items, null, null );
				// Additional Fields

				$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_orders' );
				for ( $i = 1; $i <= $total_number; $i++ ) {
					if ( 'yes' === get_option( 'alg_export_orders_fields_additional_enabled_' . $i, 'no' ) ) {
						if ( '' != ( $additional_field_value = get_option( 'alg_export_orders_fields_additional_value_' . $i, '' ) ) ) {
							/* $row[] = get_post_meta( $order_id, $additional_field_value, true ); */
							$row[] = $order->get_meta( $additional_field_value );
						} else {
							$row[] = '';
						}
					}
				}

				$data[] = $row;

			}
			$offset += $block_size;
		}

		return $data;
	}

	/**
	 * export_orders_items.
	 *
	 * @version 2.0.7
	 * @since   1.0.0
	 */
	function export_orders_items( $fields_helper ) {

		// Time limit
		if ( -1 != ( $time_limit = get_option( 'alg_wc_export_time_limit', -1 ) ) ) {
			set_time_limit( $time_limit );
		}

		// Standard Fields
		$all_fields = $fields_helper->get_order_items_export_fields();
		$fields_ids = get_option( 'alg_export_orders_items_fields', $fields_helper->get_order_items_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Additional Fields
		$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_orders_items' );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'alg_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
				$titles[] = get_option( 'alg_export_orders_items_fields_additional_title_' . $i, '' );
			}
		}

		$data       = array();
		$data[]     = $titles;
		$offset     = 0;
		$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );
		while( true ) {
			$args_orders = array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'posts_per_page' => $block_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'offset'         => $offset,
				'fields'         => 'ids',
			);
			$args_orders = alg_maybe_add_date_query( $args_orders );
			$loop_orders = new WP_Query( $args_orders );
			if ( ! $loop_orders->have_posts() ) {
				break;
			}
			foreach ( $loop_orders->posts as $order_id ) {
				$order = wc_get_order( $order_id );

				foreach ( $order->get_items() as $item_id => $item ) {

					// Standard Fields
					$row = $this->get_export_orders_row( $fields_ids, $order_id, $order, null, $item, $item_id );

					// Additional Fields
					$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_orders_items' );
					for ( $i = 1; $i <= $total_number; $i++ ) {
						if ( 'yes' === get_option( 'alg_export_orders_items_fields_additional_enabled_' . $i, 'no' ) ) {
							if ( '' != ( $additional_field_value = get_option( 'alg_export_orders_items_fields_additional_value_' . $i, '' ) ) ) {
								if ( 'meta_order' === get_option( 'alg_export_orders_items_fields_additional_type_' . $i, 'meta' ) ) {
									/* $row[] = get_post_meta( $order_id, $additional_field_value, true ); */
									$row[] = $order->get_meta( $additional_field_value );
								} elseif ( 'meta_product' === get_option( 'alg_export_orders_items_fields_additional_type_' . $i, 'meta' ) ) {
									$product_id = ( 0 != $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
									$row[] = get_post_meta( $product_id, $additional_field_value, true );
								}
							} else {
								$row[] = '';
							}
						}
					}

					$data[] = $row;
				}
			}
			$offset += $block_size;
		}
		return $data;
	}

}

endif;

return new Alg_Exporter_Orders();
