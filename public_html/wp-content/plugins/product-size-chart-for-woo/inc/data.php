<?php

namespace PSCWF\Inc;

defined( 'ABSPATH' ) || exit;

class Data {
	private $params;
	private $default;
	protected static $instance = null;
	public static $cache = [];

	private function __construct() {
		global $woopscw_settings;

		if ( ! $woopscw_settings ) {
			$woopscw_settings = get_option( 'woo_sc_setting', array() );
		}

		$this->default = array(
			'enable'         => '1',
			'position'       => 'product_tabs',
			'woo_sc_name'    => '',
			'btn_horizontal' => 'right',
			'btn_vertical'   => '50',
			'multi_sc'       => '1',
			'button_type'    => 'text',
			'btn_color'      => '#2185d0',
			'text_color'     => '#ffffff',
			'pscw_icon'      => 'ruler-icon-2',
			'custom_css'     => '',
			/*Dummy option*/
			'cus_design'     => '',
			'cus_table'      => '',
			'cus_tab'        => '',
			'cus_text'       => '',
			'cus_image'      => '',
			'cus_divider'    => '',
			'cus_accordion'  => '',

		);

		$this->params = apply_filters( 'woo_sc_setting', wp_parse_args( $woopscw_settings, $this->default ) );
	}

	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		} elseif ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 'woo_sc_setting_' . $name, $this->params[ $name ] );
		} else {
			return false;
		}
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	public static function get_assign(){
		return  [
			'products'           => [
				'inc_title'   => __( 'Products', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to the selected products.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart will not be applied to the selected products.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude products', 'product-size-chart-for-woo' ),
				'placeholder' => __( 'Please enter the product name to search', 'product-size-chart-for-woo' ),
				'all_data'    => '',
			],
			'product_cat'        => [
				'inc_title'   => __( 'Product categories', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to products with the selected categories.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart will not be applied to products with the selected categories.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude categories', 'product-size-chart-for-woo' ),
				'placeholder' => __( 'Please enter the category name to search', 'product-size-chart-for-woo' ),
				'all_data'    => self::get_all_data( 'product_cat' ),
			],
			'product_tag'        => [
				'inc_title'   => __( 'Product tags', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to products with the selected tags.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart will not be applied to products with the selected tags.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude tags', 'product-size-chart-for-woo' ),
				'all_data'    => '',
				'is_pro'    => 1,
			],
			'product_type'       => [
				'inc_title'   => __( 'Product type', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to products of the selected type.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart does not apply to products of the selected type.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude type', 'product-size-chart-for-woo' ),
				'all_data'    => '',
				'is_pro'    => 1,
			],
			'catalog_visibility' => [
				'inc_title'   => __( 'Catalog visibility', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to products of the selected catalog visibility.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart does not apply to products of the selected catalog visibility.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude catalog visibility', 'product-size-chart-for-woo' ),
				'all_data'    => '',
				'is_pro'    => 1,
			],
			'shipping_class'     => [
				'inc_title'   => __( 'Shipping class', 'product-size-chart-for-woo' ),
				'inc_des'     => __( 'This size chart will be applied to products of the selected shipping class.', 'product-size-chart-for-woo' ),
				'exc_des'     => __( 'This size chart does not apply to products of the selected shipping class.', 'product-size-chart-for-woo' ),
				'exc_title'   => __( 'Exclude class', 'product-size-chart-for-woo' ),
				'all_data'    => '',
				'is_pro'    => 1,
			],
		];
	}
	public static function get_item_name( $id, $type ) {
		if ( isset( self::$cache[ 'item_name_' . $type ][ $id ] ) ) {
			return self::$cache[ 'item_name_' . $type ][ $id ];
		}
		if ( ! isset( self::$cache[ 'item_name_' . $type ] ) ) {
			self::$cache[ 'item_name_' . $type ] = [];
		}
		$result = false;
		switch ( $type ) {
			case 'products':
				$product = wc_get_product( $id );
				if ( $product ) {
					$result = $product->get_name();
				}
				break;
		}
		self::$cache[ 'item_name_' . $type ][ $id ] = $result;

		return self::$cache[ 'item_name_' . $type ][ $id ];
	}

	public static function get_all_data( $type ) {
		if ( isset( self::$cache[ 'all_data_' . $type ] ) ) {
			return self::$cache[ 'all_data_' . $type ];
		}
		$result = 'none';
		switch ( $type ) {
			case 'product_cat':
				$categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
				$result     = self::build_dropdown_categories_tree( $categories );
				break;
			case 'product_type':
				$product_types = wc_get_product_types();
				if ( isset( $product_types['variation'] ) ) {
					unset( $product_types['variation'] );
				}
				$result = $product_types;
				break;
		}
		self::$cache[ 'all_data_' . $type ] = $result;

		return self::$cache[ 'all_data_' . $type ];
	}

	private static function build_dropdown_categories_tree( $all_cats, $parent_cat = 0, $level = 1 ) {
		foreach ( $all_cats as $cat ) {
			if ( $cat->parent == $parent_cat ) {
				$prefix               = str_repeat( '&nbsp;-&nbsp;', $level - 1 );
				$res[ $cat->term_id ] = $prefix . $cat->name . " ({$cat->count})";
				$child_cats           = self::build_dropdown_categories_tree( $all_cats, $cat->term_id, $level + 1 );
				if ( $child_cats ) {
					$res += $child_cats;
				}
			}
		}

		return $res ?? [];
	}
	public static function upgrade_button(){
		?>
		<a class="vi-ui button small pscw-upgrade-button" target="_blank"
		     href="https://1.envato.market/zN1kJe"><?php esc_html_e( 'Unlock This Feature', 'product-size-chart-for-woo' ) ?></a>
		<?php
	}
}