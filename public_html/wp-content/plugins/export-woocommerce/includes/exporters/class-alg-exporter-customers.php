<?php
/**
 * WooCommerce Exporter Customers
 *
 * The WooCommerce Exporter Customers class.
 *
 * @version 2.2.0
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Exporter_Customers' ) ) :

class Alg_Exporter_Customers {

	/**
	 * @var   alg_wc_export_confirm_hpos
	 *
	 * @version 2.0.15
	 * @since   2.0.15
	 */
	public $alg_wc_export_confirm_hpos = 'no';

	/**
	 * Constructor.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function __construct() {

		$this->alg_wc_export_confirm_hpos = get_option( 'alg_wc_export_confirm_hpos', 'no' );

		return true;
	}

	/**
	 * export_customers.
	 *
	 * @version 1.5.1
	 * @since   1.0.0
	 * @todo    [dev] check https://docs.woocommerce.com/wc-apidocs/class-WC_Customer.html for more fields (e.g. `get_is_paying_customer()` etc.)
	 * @todo    [dev] (maybe) `$is_wc_version_below_3` -> `WC_Customer`
	 */
	function export_customers( $fields_helper ) {

		$is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );

		// Standard Fields
		$all_fields = $fields_helper->get_customer_export_fields();
		$fields_ids = get_option( 'alg_export_customers_fields', $fields_helper->get_customer_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Get the Data
		$sep2                = get_option( 'alg_export_csv_separator_2_customers', ' / ' );
		$total_customers     = 0;
		$data                = array();
		$data[]              = $titles;
		$user_roles          = apply_filters( 'alg_wc_export', array( 'customer' ), 'value_export_customers_user_roles' );
		$args                = ( ! empty( $user_roles ) ? array( 'role__in' => $user_roles ) : array() );
		$args                = alg_maybe_add_date_query( $args );
		$customers           = get_users( $args );
		$do_init_wc_customer = array_intersect( array( 'total-spent', 'order-count', 'last-order-date' ), $fields_ids );
		$do_init_wc_customer = ( ! empty( $do_init_wc_customer ) );
		foreach ( $customers as $customer ) {
			$total_customers++;
			$row = array();
			if ( $do_init_wc_customer ) {
				$wc_customer = ( ! $is_wc_version_below_3 ? new WC_Customer( $customer->ID ) : false );
			}
			foreach( $fields_ids as $field_id ) {
				switch ( $field_id ) {
					case 'customer-nr':
						$row[] = $total_customers;
						break;
					case 'customer-id':
						$row[] = $customer->ID;
						break;
					case 'customer-email':
						$row[] = $customer->user_email;
						break;
					case 'customer-login':
						$row[] = $customer->user_login;
						break;
					case 'customer-nicename':
						$row[] = $customer->user_nicename;
						break;
					case 'customer-url':
						$row[] = $customer->user_url;
						break;
					case 'customer-registered':
						$row[] = $customer->user_registered;
						break;
					case 'customer-display-name':
						$row[] = $customer->display_name;
						break;
					case 'customer-first-name':
						$row[] = $customer->first_name;
						break;
					case 'customer-last-name':
						$row[] = $customer->last_name;
						break;
					case 'user-roles':
						$row[] = implode( $sep2, $customer->roles );
						break;
					case 'customer-debug':
						$row[] = '<pre>' . print_r( $customer, true ) . '</pre>';
						break;
					case 'last-update':
						$last_update = (int) get_user_meta( $customer->ID, 'last_update', true );
						if($last_update <= 0 ){
							$last_update = time();
						}
						$row[] = date( get_option( 'date_format' ), $last_update );
						break;
					case 'total-spent':
						$row[] = apply_filters( 'alg_wc_export', '', 'customer_export_total_spent', array( 'wc_customer' => $wc_customer ) );
						break;
					case 'order-count':
						$row[] = apply_filters( 'alg_wc_export', '', 'customer_export_order_count', array( 'wc_customer' => $wc_customer ) );
						break;
					case 'last-order-date':
						$row[] = apply_filters( 'alg_wc_export', '', 'customer_export_last_order_date', array( 'wc_customer' => $wc_customer ) );
						break;
					case 'nickname':
					case 'first-name':
					case 'last-name':
					case 'description':
					case 'billing-first-name':
					case 'billing-last-name':
					case 'billing-company':
					case 'billing-address-1':
					case 'billing-address-2':
					case 'billing-city':
					case 'billing-postcode':
					case 'billing-country':
					case 'billing-state':
					case 'billing-phone':
					case 'billing-email':
					case 'shipping-first-name':
					case 'shipping-last-name':
					case 'shipping-company':
					case 'shipping-address-1':
					case 'shipping-address-2':
					case 'shipping-city':
					case 'shipping-postcode':
					case 'shipping-country':
					case 'shipping-state':
						$row[] = get_user_meta( $customer->ID, str_replace( '-', '_', $field_id ), true );
						break;
				}
			}
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * calculate_tmp_data.
	 *
	 * @version 1.5.0
	 * @since   1.5.0
	 * @todo    [dev] recheck `$order->paid_date` (i.e. for `$is_wc_version_below_3`)
	 */
	function calculate_tmp_data( $tmp_data, $customer_id, $order_id, $order, $order_statuses, $is_wc_version_below_3 ) {
		if ( ! isset( $tmp_data[ $customer_id ]['total-spent'] ) ) {
			$tmp_data[ $customer_id ]['total-spent'] = 0;
		}
		if ( ! isset( $tmp_data[ $customer_id ]['order-count'] ) ) {
			$tmp_data[ $customer_id ]['order-count'] = 0;
		}
		if ( ! isset( $tmp_data[ $customer_id ]['item-count'] ) ) {
			$tmp_data[ $customer_id ]['item-count']  = 0;
		}
		if ( ! isset( $tmp_data[ $customer_id ]['customer-first-order-date'] ) ) {
			$tmp_data[ $customer_id ]['customer-first-order-date'] = '';
		}
		if ( ( $is_wc_version_below_3 ? $order->paid_date : $order->is_paid() ) ) {
			$tmp_data[ $customer_id ]['total-spent'] += $order->get_total();
		}
		if ( in_array( 'wc-' . $order->get_status(), $order_statuses ) ) {
			$tmp_data[ $customer_id ]['order-count'] += 1;
			$tmp_data[ $customer_id ]['item-count']  += $order->get_item_count();
			$tmp_data[ $customer_id ]['customer-first-order-date'] = get_the_date( get_option( 'date_format' ), $order_id );
		}
		return $tmp_data;
	}

	/**
	 * export_customers_from_orders.
	 *
	 * @version 2.0.15
	 * @since   1.0.0
	 * @todo    [dev] check https://docs.woocommerce.com/wc-apidocs/class-WC_Customer.html for more fields (e.g. `get_is_paying_customer()` etc.)
	 * @todo    [dev] (maybe) `$order_statuses  = array( 'wc-processing', 'wc-completed' );`
	 * @todo    [dev] (maybe) calculate `total-spent-period` etc. for guests
	 * @todo    [dev] (maybe) `customer-last-order-date` leave as it is now or use `$wc_customer->get_last_order();`?
	 * @todo    [dev] (maybe) add more order fields (shipping)
	 * @todo    [dev] (maybe) `$is_wc_version_below_3`: 'total-spent', 'order-count'
	 */
	function export_customers_from_orders( $fields_helper, $attach_html = false, $page = 1, $start = 0, $is_ajax = false ) {

		// Time limit
		if ( -1 != ( $time_limit = get_option( 'alg_wc_export_time_limit', -1 ) ) ) {
			set_time_limit( $time_limit );
		}

		$is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );

		// Standard Fields
		$all_fields = $fields_helper->get_customer_from_order_export_fields();
		$fields_ids = get_option( 'alg_export_customers_from_orders_fields', $fields_helper->get_customer_from_order_export_default_fields_ids() );
		$titles = array();
		foreach( $fields_ids as $field_id ) {
			$titles[] = $all_fields[ $field_id ];
		}

		// Get the Data
		$sep2                 = get_option( 'alg_export_csv_separator_2_customers', ' / ' );
		$data                  = array();
		$data[]                = $titles;
		$total_customers       = 0;
		$orders                = array();
		$do_calculate_tmp_data = array_intersect( array( 'total-spent-period', 'order-count-period', 'item-count-period', 'customer-first-order-date' ), $fields_ids );
		$do_calculate_tmp_data = ( ! empty( $do_calculate_tmp_data ) );
		if ( $do_calculate_tmp_data ) {
			$tmp_data          = array();
			$order_statuses    = array_keys( wc_get_order_statuses() );
		}
		$do_init_wc_customer   = array_intersect( array( 'total-spent', 'order-count', 'customer-first-name', 'customer-last-name' ), $fields_ids );
		$do_init_wc_customer   = ( ! empty( $do_init_wc_customer ) );
		$offset                = 0;
		$block_size            = get_option( 'alg_wc_export_wp_query_block_size', 1024 );

		if($page > 1 && $is_ajax){
			$data       = array();
		}

		if($attach_html){
			$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );
			if($page <= 1){
				$offset  = 0;
			}else{
				$offset  = ($page-1) * $block_size;
			}
		}else{
			$offset     = 0;
			$block_size = get_option( 'alg_wc_export_wp_query_block_size', 1024 );
		}

		if($is_ajax) {
			$offset = $start;
		}

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
					'paginate' 		 => true,
				);
			}

			$args_orders = alg_maybe_add_date_query( $args_orders );

			$result_order_ids = array();

			if ( $this->alg_wc_export_confirm_hpos == 'yes' ) {

				$loop_orders = wc_get_orders( $args_orders );

				$result_order_ids = $loop_orders->orders;

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

			if( $is_ajax ) {
				if( empty( $result_order_ids ) ) {
					break;
				}
			}

			if( ! $attach_html ){
				if( empty( $result_order_ids ) ) {
					break;
				}
			}


			foreach ( $result_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				$customer_id = ( $is_wc_version_below_3 ? $order->customer_user : $order->get_customer_id() );
				if ( $do_calculate_tmp_data && 0 != $customer_id ) {
					$tmp_data = $this->calculate_tmp_data( $tmp_data, $customer_id, $order_id, $order, $order_statuses, $is_wc_version_below_3 );
				}
				$_billing_email = $is_wc_version_below_3 ? $order->billing_email : $order->get_billing_email();
				if ( isset( $_billing_email ) && '' != $_billing_email && ! in_array( $_billing_email, $orders ) ) {
					$total_customers++;
					$row = array();
					if ( $do_init_wc_customer ) {
						if ( $is_wc_version_below_3 ) {
							$wc_customer = ( 0 != $customer_id ? get_user_by( 'ID', $customer_id ) : false );
						} else {
							$wc_customer = ( 0 != $customer_id ? new WC_Customer( $customer_id )   : false );
						}
					}
					foreach ( $fields_ids as $field_id ) {
						switch ( $field_id ) {
							case 'customer-nr':
								$row[] = $total_customers;
								break;
							case 'customer-first-name':
								$row[] = ( $wc_customer ? ( $is_wc_version_below_3 ? $wc_customer->first_name : $wc_customer->get_first_name() ) : '' );
								break;
							case 'customer-last-name':
								$row[] = ( $wc_customer ? ( $is_wc_version_below_3 ? $wc_customer->last_name  : $wc_customer->get_last_name() )  : '' );
								break;
							case 'customer-last-order-date':
								$row[] = get_the_date( get_option( 'date_format' ), $order_id );
								break;
							case 'customer-id':
								$row[] = ( $is_wc_version_below_3 ? $order->customer_user : $order->get_customer_id() );
								break;
							case 'user-roles':
								if ( 0 != $customer_id ) {
									$userdata = get_userdata( $customer_id );
									$row[] = implode( $sep2, $userdata->roles );
								} else {
									$row[] = 'guest';
								}
								break;
							case 'total-spent':
								$row[] = ( $is_wc_version_below_3 ? '' : apply_filters( 'alg_wc_export', '', 'customer_export_total_spent', array( 'wc_customer' => $wc_customer ) ) );
								break;
							case 'order-count':
								$row[] = ( $is_wc_version_below_3 ? '' : apply_filters( 'alg_wc_export', '', 'customer_export_order_count', array( 'wc_customer' => $wc_customer ) ) );
								break;
							case 'total-spent-period':
								$row[] = apply_filters( 'alg_wc_export', '', 'customer_from_orders_export_total_spent_period', array( 'customer_id' => $customer_id ) );
								break;
							case 'order-count-period':
								$row[] = apply_filters( 'alg_wc_export', '', 'customer_from_orders_export_order_count_period', array( 'customer_id' => $customer_id ) );
								break;
							case 'item-count-period':
								$row[] = apply_filters( 'alg_wc_export', '', 'customer_from_orders_export_item_count_period',  array( 'customer_id' => $customer_id ) );
								break;
							case 'customer-first-order-date':
								$row[] = apply_filters( 'alg_wc_export', '', 'customer_from_orders_export_first_order', array( 'customer_id' => $customer_id ) );
								break;
							default:
								$_field_id = str_replace( array( 'customer-', '-' ), array( '', '_' ), $field_id );
								$row[] = alg_get_order_field( $order, $_field_id, $is_wc_version_below_3 );
								break;
						}
					}
					$data[] = $row;
					$orders[] = $_billing_email;
				}
			}
			$offset += $block_size;

			if( $attach_html || $is_ajax ){
				break;
			}
		}
		if ( $do_calculate_tmp_data ) {
			foreach ( $data as &$row ) {
				foreach ( $tmp_data as $customer_id => $customer_tmp_data ) {
					$replace = array(
						'%alg-wc-export-replace-total-spent=' . $customer_id . '%' => $customer_tmp_data['total-spent'],
						'%alg-wc-export-replace-order-count=' . $customer_id . '%' => $customer_tmp_data['order-count'],
						'%alg-wc-export-replace-item-count='  . $customer_id . '%' => $customer_tmp_data['item-count'],
						'%alg-wc-export-replace-first-order=' . $customer_id . '%' => $customer_tmp_data['customer-first-order-date'],
					);
					$row = str_replace( array_keys( $replace ), $replace, $row );
				}
			}
		}

		if($attach_html){
			$output_html = '';
			$output_html .= '<div class="paginate-ajax-alg-preview">';
			$output_html .= paginate_links( array(
				'total'        => $loop_orders->max_num_pages,
				'current'      => $page,
				'base' => "#%#%" //will make hrefs like "#3"
			) );
			$output_html .= '</div>';
			$output_html .= ( is_array( $data ) ) ? alg_get_table_html( $data, array( 'table_class' => 'widefat striped' ) ) : $data;
			return $output_html;
		}

		return $data;
	}

}

endif;

return new Alg_Exporter_Customers();
