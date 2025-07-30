<?php

namespace PSCWF\Inc\Frontend;

use PSCWF\Inc\Data;

defined( 'ABSPATH' ) || exit;

class Front_End {

	protected static $instance = null;
	protected $settings;

	private function __construct() {
		$this->settings = Data::get_instance();
		if ( ! $this->settings->get_params( 'enable' ) ) {
			return;
		}
		$this->handle_sc_button_position();
		$this->handle_sc_show_popup();
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self() : self::$instance;
	}

	public function handle_sc_button_position() {
		switch ( $this->get_params( 'position' ) ) {
			case 'before_add_to_cart':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'show_sc_button' ) );
				break;
			case 'after_add_to_cart':
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'show_sc_button' ) );
				break;
			case 'pop-up':
				add_action( 'wp_footer', array( $this, 'show_sc_button' ) );
				break;
			case 'product_tabs':
				add_filter( 'woocommerce_product_tabs', array( $this, 'custom_product_tabs' ) );
				break;
		}
	}

	public function pscw_helper_show_size_chart( $product_id, $update_option_editing = false ) {
		$product = wc_get_product($product_id);
		if (!$product){
			return array( 'disable', [] );
		}
		$size_charts_allow=[];
		$product_sc_mode     = $product->get_meta('pscw_mode')?: 'global';
		if ($product_sc_mode === 'disable' && !is_customize_preview()){
			return array( $product_sc_mode, $size_charts_allow );
		}
		if ($product_sc_mode === 'override'){
			$product_sc_override = $product->get_meta('pscw_override')?:[];
			$size_chart_id       = array_values( $product_sc_override ) ;
		}else {
			$size_charts = get_posts( array(
				'post_type'   => 'pscw-size-chart',
				'post_status' => 'publish',
				'numberposts' => - 1,
				'fields'      => 'ids'
			) );

			$product_cate = wc_get_product_cat_ids( $product_id );
			if ( ! is_array( $product_cate ) ) {
				$product_cate = [];
			}
			$size_chart_id = [];
			foreach ( $size_charts as $sc_id ) {
				$pscw_data = get_post_meta( $sc_id, 'pscw_data', true );
				if (isset($pscw_data['assign'])){
					$apply     = self::size_chart_is_available_old($product_id, $product_cate,$pscw_data['assign']??'',$pscw_data['condition'] ?? []);
				}else{
					$apply     = self::size_chart_is_available($product_id, $product_cate,$pscw_data);
				}
				if ( $apply ) {
					$size_chart_id[] = $sc_id;
				}
			}
		}
        if (is_array($size_chart_id) && !empty($size_chart_id)) {
	        foreach ( $size_chart_id as $pscw_id ) {
		        if ( ! $pscw_id ) {
			        continue;
		        }
		        $pscw_data_ct = get_post_meta( $pscw_id, 'pscw_data', true );
		        if ( empty( $pscw_data_ct ) ) {
			        continue;
		        }
		        $size_charts_allow[] = $pscw_id;
	        }
        }

		if ( $update_option_editing && is_customize_preview() ) {
			$user_id = get_current_user_id();
			if ( is_array( $size_charts_allow ) && !empty( $size_charts_allow ) ) {
				update_option( 'pscw_size_charts_editing', $size_charts_allow );
				$old_sc_editing = get_user_meta( get_current_user_id(), 'pscw_current_editing_sc', true );
				if (!$old_sc_editing || !in_array($old_sc_editing, $size_charts_allow)){
					update_user_meta($user_id , 'pscw_current_editing_sc',  $size_charts_allow[0] );
				}
			} else {
				update_option( 'pscw_size_charts_editing', '' );
				update_user_meta( $user_id, 'pscw_current_editing_sc', '' );
			}
			update_user_meta( $user_id, 'pscw_sizechart_mode', '' );
		}
		return array( $product_sc_mode, $size_charts_allow );
	}
	public static function size_chart_is_available($product_id, $product_cate,$pscw_data){
		$product = wc_get_product($product_id);
		if (!$product){
			return false;
		}
		if (!empty($pscw_data['all_products'] ?? '1')){
			return true;
		}
		if (empty($pscw_data['include_products']) && empty($pscw_data['include_product_cat']) ){
			return false;
		}
		if (!empty($pscw_data['include_products']) && is_array($pscw_data['include_products'])
		    && !in_array( $product_id, $pscw_data['include_products'] ) ){
			return false;
		}
		if (!empty($pscw_data['include_product_cat']) && is_array($pscw_data['include_product_cat'])
		    && empty( array_intersect( $product_cate, $pscw_data['include_product_cat'] ) )){
			return false;
		}
		return true;
	}
	public static function size_chart_is_available_old($product_id,$product_cate,$assign,$condition){
		$apply = false;
		$product = wc_get_product($product_id);
		if (!$product){
			return $apply;
		}
		if (!is_array($condition)){
			$condition =[];
		}
		switch ( $assign ) {
			case 'all':
				$apply = true;
				break;
			case 'product_cat':
				$apply = ! empty( array_intersect( $product_cate, $condition ) );
				break;
			case 'products':
				$apply = in_array( $product_id, $condition );
				break;
		}
		return $apply;
	}

	public function handle_sc_show_popup() {
		$position = $this->get_params( 'position' );
		/* Exception for none position */
		$exception = $position === 'none' ?? false;
		if ( $exception || $position === 'before_add_to_cart' || $position === 'before_atc_after_variations' || $position === 'after_add_to_cart' || $position === 'pop-up' || $position === 'after_title' || $position === 'after_meta' ) {
			add_action( 'wp_footer', array( $this, 'pscw_size_chart_popup' ) );
		}
	}

	public function show_sc_button() {
		if ( ! is_customize_preview() ) {
			list( $pscw_mode, $size_chart_id ) = $this->pscw_helper_show_size_chart( get_the_ID() );
			if ( $pscw_mode === 'disable' || empty( $size_chart_id ) ) {
				return;
			}
		}
        if (!is_product()){
            return;
        }
		?>
        <div class="woo_sc_frontend_btn">
            <div id="woo_sc_show_popup"
                 class="woo_sc_price_btn_popup woo_sc_btn_popup woo_sc_btn_span woo_sc_call_popup">
                <div class="woo_sc_text_icon">
					<?php
					$size_chart_name = ! empty( $this->get_params( 'woo_sc_name' ) ) ? $this->get_params( 'woo_sc_name' ) : esc_html__( 'Size Chart', 'product-size-chart-for-woo' );
					switch ( $this->get_params( 'button_type' ) ) {
						case 'icon':
							?>
                            <span class="woo_sc_size_icon">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 viewBox="<?php echo esc_html( pscw_get_view_box( $this->get_params( 'pscw_icon' ) ) ); ?>"
                                 width="30" height="30">

                            <?php
                            $response = wp_remote_get( PSCW_CONST_F['img_url'] . 'svg/' . $this->get_params( 'pscw_icon' ) . '.svg' );
                            if ( is_wp_error( $response ) ) {
	                            $file_content = '';
                            } else {
	                            $file_content = wp_remote_retrieve_body( $response );
                            }
                            echo wp_kses( $file_content, pscw_get_allow_svg() )
                            ?>
                            </svg>
                            </span>
							<?php
							break;
						case 'text':
							?>
                            <span class="woo_sc_text"><?php echo esc_html( $size_chart_name ); ?></span>
							<?php
							break;
					}

					?>
                </div>
            </div>
        </div>
		<?php
	}

	public function custom_product_tabs( $tabs ) {
		global $product;
		if ( ! empty( $this->get_params( 'woo_sc_name' ) ) ) {
			$title = $this->get_params( 'woo_sc_name' );
		} else {
			$title = esc_html__( 'Size Chart', 'product-size-chart-for-woo' );
		}
        list( $product_sc_mode, $size_charts_allow_countries ) = $this->pscw_helper_show_size_chart( $product->get_id(), true );
		if ( is_customize_preview() ) {
			if ( 'product_tabs' === $this->get_params( 'position' ) ) {
				$tabs['size_chart_tab'] = array(
					'title'    => esc_html( $title ),
					'priority' => apply_filters('woo_sc_size_chart_tab_priority', 50),
				);
			}
		} else {
			if ( ( ! empty( $size_charts_allow_countries ) && $product_sc_mode !== 'disable' ) ) {
				if ( 'product_tabs' === $this->get_params( 'position' ) ) {
					$tabs['size_chart_tab'] = array(
						'title'    => esc_html( $title ),
						'priority' => apply_filters('woo_sc_size_chart_tab_priority', 50),
						'callback' => function () use ( $size_charts_allow_countries ) {
							$this->custom_product_tabs_content( $size_charts_allow_countries );
						}
					);
				}
			}
		}

		return $tabs;
	}

	public function custom_product_tabs_content( $size_chart_id ) {
		if ( is_customize_preview() ) {
			return;
		}
		$is_multiple = $this->get_params( 'multi_sc' );
		if ( $is_multiple ) {
			foreach ( $size_chart_id as $sc_id ) {
				echo do_shortcode( '[PSCW_SIZE_CHART id=' . $sc_id . ']' );
			}
		} else {
			echo do_shortcode( '[PSCW_SIZE_CHART id=' . $size_chart_id[0] . ']' );
		}
	}

	public function pscw_size_chart_popup() {
		$product_id     = get_the_ID();
		list( $product_sc_mode, $size_chart_id ) = $this->pscw_helper_show_size_chart( $product_id, true );
		if ( ! is_customize_preview() ) {
			/* Only display for customize*/
			$position = $this->get_params( 'position' );
			if ($position === 'none') return;

			if ( ! ( ( ! empty( $size_chart_id ) && $product_sc_mode !== 'disable' ) ) ) {
				return;
			}
		}
		if ( ! is_product() ) {
			return;
		}

		?>
        <div id="woo_sc_modal" class="woo_sc_modal">
            <div class="woo_sc_modal_content">
                <span class="woo_sc_modal_close">&times;</span>
                <div class="woo_sc_scroll_content">
					<?php
					if ( ! is_customize_preview() ) {
						if ( isset( $size_chart_id[0] ) ) {
							echo do_shortcode( '[PSCW_SIZE_CHART id=' . $size_chart_id[0] . ']' );
						}
					}
					?>
                </div>
            </div>
        </div>
		<?php
	}


	public function get_params( $param ) {
		return $this->settings->get_params( $param );
	}

}