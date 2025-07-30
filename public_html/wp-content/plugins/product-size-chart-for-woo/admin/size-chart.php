<?php

namespace PSCWF\Admin;
use PSCWF\Inc\Data;

defined( 'ABSPATH' ) || exit;

class Size_Chart {
	protected static $instance = null;

	private function __construct() {
		add_action( 'init', array( $this, 'pscw_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'pscw_meta_box' ) );
		add_action( 'save_post', array( $this, 'pscw_save_data_meta_box' ) );
		add_action( 'post_action_pscw_duplicate', array( $this, 'pscw_duplicate' ) );
		add_action( 'post_action_pscw_go_design', array( $this, 'pscw_go_design' ) );
		add_filter( 'post_row_actions', array( $this, 'post_add_action' ), 20, 2 );
		add_filter( 'manage_pscw-size-chart_posts_columns', array( $this, 'custom_post_columns' ) );
		add_action( 'manage_pscw-size-chart_posts_custom_column', array( $this, 'show_custom_columns' ) );
		add_action( 'wp_ajax_pscw_search_product', array( $this, 'pscw_ajax_search_product' ) );
		add_action( 'wp_ajax_pscw_search_term', array( $this, 'pscw_ajax_search_term' ) );

		/* Button repair migrate data */
		add_action( 'manage_posts_extra_tablenav', array( $this, 'button_migrate_all_data' ) );
		add_action( 'wp_ajax_pscw_migrate_data', array( $this, 'migrate_size_chart' ) );
		add_action( 'admin_notices', array( $this, 'notice_migrate_size_chart'), 9999 );

		/* Go to Customize when add new size chart*/
		add_action( 'load-post-new.php', array( $this, 'add_new_size_chart' ) );
	}

	public function button_migrate_all_data( $type ) {
		global $typenow;

		if ( get_option( 'pscw_maybe_re_migrate' ) && $type === 'top' && $typenow === 'pscw-size-chart' ) {
			printf( '<div class="button" id="pscw_migrate_all_data">%s</div>', esc_html__( 'Remigrate size chart', 'product-size-chart-for-woo' ) );
		}
	}

	public function migrate_size_chart() {
		if (isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'pscw_nonce' )) {
			/* Filter query size chart to migrate */
			$size_charts = get_posts(apply_filters('pscw_migrate_size_chart_query', array(
					'post_type'  => 'pscw-size-chart',
					'numberposts' => - 1,
					'meta_query' => array(
						array(
							'key'     => 'woo_sc_size_chart_data',
							'compare' => 'EXISTS',
						),
					)
				))
			);

			foreach( $size_charts as $size_chart ) {
				$woo_sc_size_chart_data = get_post_meta( $size_chart->ID, 'woo_sc_size_chart_data', true );
				pscw_migrate_size_chart_data($size_chart, $woo_sc_size_chart_data);
			}

			wp_send_json_success();
		}
		die;
	}

	public function notice_migrate_size_chart() {
		$screen = get_current_screen();
		if ( $screen->id === 'edit-pscw-size-chart' && get_option('pscw_maybe_re_migrate') ) {
			?>
            <div class="notice notice-info">
                <p><?php _e( 'We’ve added a new option to easily re-migrate your Size Chart table from version 1.x to 2.x, improving data accuracy and consistency', 'product-size-chart-for-woo' ); ?></p>
            </div>
			<?php
		}
	}
	public function add_new_size_chart() {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
		if ( $post_type === 'pscw-size-chart' ) {
			$user_id  = get_current_user_id();
			$new_post = array(
				'post_title'   => 'Size chart untitled',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'pscw-size-chart',
				'post_author'  => $user_id,
			);

			$post_id = wp_insert_post( $new_post );
			if ( 0 !== $post_id ) {
				$random_product = $this->get_random_product();
				$url            = admin_url( 'customize.php' ) . '?url=' . esc_url( get_permalink( $random_product[0] ) ) . '&pscw_id=' . $post_id . '&autofocus[panel]=pscw_size_chart_customizer&autofocus[section]=pscw_customizer_design&pscw_sizechart_mode=1';
				wp_safe_redirect( $url );
			}
		}
	}

	public function get_random_product() {
		$random_product = wc_get_products( array(
			'type'    => array( 'simple', 'variable' ),
			'status'  => 'publish',
			'catalog_visibility'=> 'visible',
			'orderby' => 'rand',
			'return'  => 'ids',
			'limit'   => 1
		) );

		if ( empty( $random_product ) ) {
			$product = new \WC_Product_Simple();
			$product->set_name( esc_html__( 'Product Size Chart Preview', 'product-size-chart-for-woo' ) );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_price( 0 );
			$product->set_regular_price( 0 );

			$product->save();
			$random_product[] = $product->get_id();
		}

		return $random_product;
	}


	public function pscw_post_type() {
		$icon_url = esc_url( PSCW_CONST_F['img_url'] . 'sc_logo.png' );
		$label    = array(
			'name'          => esc_html__( 'Size Chart', 'product-size-chart-for-woo' ),
			'singular_name' => esc_html__( 'Size Chart', 'product-size-chart-for-woo' ),
			'add_new'       => esc_html__( 'Add New', 'product-size-chart-for-woo' ),
			'all_items'     => esc_html__( 'All Size Charts', 'product-size-chart-for-woo' ),
			'add_new_item'  => esc_html__( 'Add Item', 'product-size-chart-for-woo' )
		);
		$args     = array(
			'labels'              => $label,
			'description'         => esc_html__( 'Product Size Chart', 'product-size-chart-for-woo' ),
			'supports'            => array(
				'title',
				'revisions',
			),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => "5",
			'menu_icon'           => $icon_url,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'post'

		);
		register_post_type( 'pscw-size-chart', $args );
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self : self::$instance;
	}

	public function pscw_meta_box() {
		add_meta_box( 'design_size_chart', __( 'Design', 'product-size-chart-for-woo' ),
			[ $this, 'pscw_design' ], 'pscw-size-chart', 'normal' );
		add_meta_box( 'assign_size_chart', __( 'Assign', 'product-size-chart-for-woo' ),
			[ $this, 'pscw_assign' ], 'pscw-size-chart', 'normal' );
//		$screen_id = get_current_screen()->id;
//		if ( $screen_id === 'pscw-size-chart' ) {
//			add_meta_box( 'configure_size_chart', esc_html__( 'Configure for the size chart', 'product-size-chart-for-woo' ), array(
//				$this,
//				'pscw_configure'
//			), 'pscw-size-chart', 'normal', 'high' );
//		}
	}
	public function pscw_design( $post ) {
		?>
        <div class="vi-ui form pscw-design-wrap"><?php
		if ( $post->post_status !== 'auto-draft' ) {
			$random_product = $this->get_random_product();
			$url            = admin_url( 'customize.php' ) . '?url=' . esc_url( get_permalink( $random_product[0] ) ) . '&pscw_id=' . $post->ID . '&autofocus[panel]=pscw_size_chart_customizer&autofocus[section]=pscw_customizer_design&pscw_sizechart_mode=1';
			?>
            <a target="_blank" class="button pscw-design-btn-go" href="<?php echo esc_url( $url ); ?>">
				<?php esc_html_e( 'Go to design', 'product-size-chart-for-woo' ) ?>
            </a>
			<?php
		} else {
			esc_html_e( 'Please publish size chart to see url design', 'product-size-chart-for-woo' );
		}
		?></div><?php
	}

	public function pscw_assign( $post ) {
		$pscw_data            = get_post_meta( $post->ID, 'pscw_data', true );
		if ( ! isset( $pscw_data['all_products'] ) && isset( $pscw_data['assign'] ) ) {
			$pscw_assign               = $pscw_data['assign'] ?? 'none';
			$condition                 = $pscw_data['condition'] ?? [];
			$pscw_data['all_products'] = '';
			switch ( $pscw_assign ) {
				case 'all':
					$pscw_data['all_products'] = 1;
					break;
				case 'product_cat':
				case 'products':
					$pscw_data['include_'.$pscw_assign] = $condition;
					break;
			}
		}
		$all_product = $pscw_data['all_products'] ?? '1';
		$assign= Data::get_assign();
		wp_nonce_field( 'woo_sc_check_nonce', 'woo_sc_nonce', false );
		?>
        <div class="vi-ui form pscw-assign-wrap">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'All products', 'product-size-chart-for-woo' ); ?></th>
                    <td>
                        <div class="vi-ui checkbox toggle">
                            <input type="hidden" name="pscw_data[all_products]" id="assign-all-product-val"  value="<?php echo esc_attr( $all_product ) ?>">
                            <input type="checkbox" id="assign-all-product"  <?php checked( $all_product, 1 ) ?>><label></label>
                        </div>
                        <p class="description">
							<?php esc_html_e( 'Enable this to use this size chart for every product.', 'product-size-chart-for-woo' ); ?>
                        </p>
                    </td>
                </tr>
				<?php
				foreach ( $assign as $k => $v ) {
					$inc_name      = 'include_' . $k;
					$inc_condition = $pscw_data[ $inc_name ] ?? [];
					$all_data      = $v['all_data'] ?? '';
					$class         = [ 'pscw-assign-all-product-class pscw-assign-' . $k ];
					?>
                    <tr class="<?php echo esc_attr( implode( ' ', array_merge( $class, [ 'pscw-assign-include' ] ) ) ) ?>">
                        <th><?php echo esc_html( $v['inc_title'] ) ?></th>
                        <td>
                            <?php
                            if (!empty($v['is_pro'])){
	                            Data::upgrade_button();
                            }else{
                                ?>
                            <select name="pscw_data[<?php echo esc_attr( $inc_name ) ?>][]" id="<?php echo esc_attr( $inc_name ) ?>" class="pscw-option-select2"
                                    data-placeholder_select2="<?php echo esc_attr( $v['placeholder'] ?? '' ); ?>"
                                    data-type_select2="<?php echo esc_attr( $k ) ?>" multiple>
	                            <?php
	                            if ( is_array( $all_data ) && ! empty( $all_data ) ) {
		                            foreach ( $all_data as $i => $j ) {
			                            ?>
                                        <option value="<?php echo esc_attr( $i ) ?>" <?php selected( in_array( $i, $inc_condition ), true ) ?>><?php echo wp_kses_post( $j ) ?></option>
			                            <?php
		                            }
	                            } elseif ( is_array( $inc_condition ) && ! empty( $inc_condition ) ) {
		                            foreach ( $inc_condition as $id ) {
			                            $title = Data::get_item_name( $id, $k );
			                            if ( $title === false ) {
				                            continue;
			                            }
			                            ?>
                                        <option value="<?php echo esc_attr( $id ) ?>" selected><?php echo wp_kses_post( $title ) ?></option>
			                            <?php
		                            }
	                            }
	                            ?>
                                </select>
                                <?php
                            }
                            ?>
                            <p class="description">
								<?php echo esc_html( $v['inc_des'] ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="<?php echo esc_attr( implode( ' ', array_merge( $class, [ 'pscw-assign-exclude' ] ) ) ) ?>">
                        <th><?php echo esc_html( $v['exc_title'] ) ?></th>
                        <td>
                            <?php Data::upgrade_button(); ?>
                            <p class="description">
								<?php echo esc_html( $v['exc_des'] ); ?>
                            </p>
                        </td>
                    </tr>
					<?php
				}
				?>
                <tr>
                    <th><?php esc_html_e( 'Countries', 'product-size-chart-for-woo' ); ?></th>
                    <td>
			            <?php Data::upgrade_button(); ?>
                        <p class="description">
				            <?php esc_html_e( 'Please select countries to show this size chart. Leave empty to apply to all.', 'product-size-chart-for-woo' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
		<?php
	}

	public function pscw_configure( $post_id ) {
		wp_nonce_field( 'woo_sc_check_nonce', 'woo_sc_nonce' );
		$assign_options = array(
			'none'               => esc_html__( 'None', 'product-size-chart-for-woo' ),
			'all'                => esc_html__( 'All Products', 'product-size-chart-for-woo' ),
			'products'           => esc_html__( 'Products', 'product-size-chart-for-woo' ),
			'product_cat'        => esc_html__( 'Product Categories', 'product-size-chart-for-woo' ),
			'combined'           => esc_html__( 'Combined (Premium)', 'product-size-chart-for-woo' ),
			'product_type'       => esc_html__( 'Product Type (Premium)', 'product-size-chart-for-woo' ),
			'product_visibility' => esc_html__( 'Product Visibility (Premium)', 'product-size-chart-for-woo' ),
			'product_tag'        => esc_html__( 'Product Tags (Premium)', 'product-size-chart-for-woo' ),
			'shipping_class'     => esc_html__( 'Product Shipping Class (Premium)', 'product-size-chart-for-woo' ),
		);
		$random_product = $this->get_random_product();
		$pscw_data      = get_post_meta( $post_id->ID, 'pscw_data', true );
		$pscw_assign    = $pscw_data['assign'] ?? 'none';
		$pscw_condition = $pscw_data['condition'] ?? [];
		?>
        <input type="hidden" name="prevent_delete_meta_movetotrash" id="prevent_delete_meta_movetotrash"
               value="<?php echo esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) . $post_id->ID ) ); ?>">
        <div class="vi-ui segment" id="pscw_configure">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Design', 'product-size-chart-for-woo' ); ?></th>
                    <td>
						<?php if ( $post_id->post_status !== 'auto-draft' ) : ?>
							<?php
							$url = admin_url( 'customize.php' ) . '?url=' . esc_url( get_permalink( $random_product[0] ) ) . '&pscw_id=' . $post_id->ID . '&autofocus[panel]=pscw_size_chart_customizer&autofocus[section]=pscw_customizer_design&pscw_sizechart_mode=1';
							?>
                            <a target="_blank"
                               href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Go to design', 'product-size-chart-for-woo' ) ?></a>
						<?php else: ?>
							<?php esc_html_e( 'Please publish size chart to see url design', 'product-size-chart-for-woo' ); ?>
						<?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Select countries to show', 'product-size-chart-for-woo' ); ?></th>
                    <td>
                        <a class="vi-ui pink button" target="_blank" href="https://1.envato.market/zN1kJe">Upgrade This
                            Feature</a>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Assign', 'product-size-chart-for-woo' ); ?></th>
                    <td>
                        <div class="pscw_assign_wrap">
                            <select name="pscw_assign" id="pscw_assign" class="pscw_assign">
								<?php foreach ( $assign_options as $key => $val ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key == $pscw_assign ); ?> <?php disabled( in_array( $key, [
										'combined',
										'product_type',
										'product_visibility',
										'product_tag',
										'shipping_class'
									] ) ) ?>><?php echo esc_html( $val ); ?></option>
								<?php endforeach; ?>

                            </select>
                            <div class="pscw_assign_pane <?php echo 'products' === $pscw_assign ? 'active' : '' ?>"
                                 data-option="products">
                                <select name="pscw_assign_products[]" id="pscw_assign_products" multiple>
									<?php
									if ( 'products' === $pscw_assign ) {
										foreach ( $pscw_condition as $val ) {
											$value = pscw_get_value_combined( 'products', $val );
											if ( count( $value ) === 2 ) {
												?>
                                                <option selected
                                                        value="<?php echo esc_attr( $value[0] ); ?>"><?php echo esc_html( $value[1] ); ?></option>
												<?php
											}
										}
									}

									?>
                                </select>
                            </div>
                            <div class="pscw_assign_pane <?php echo 'product_cat' === $pscw_assign ? 'active' : '' ?>"
                                 data-option="product_cat">
                                <select name="pscw_assign_product_cat[]" id="pscw_assign_product_cat"
                                        multiple>
									<?php
									if ( 'product_cat' === $pscw_assign ) {
										foreach ( $pscw_condition as $val ) {
											$value = pscw_get_value_combined( 'product_cat', $val );
											if ( count( $value ) === 2 ) {
												?>
                                                <option value="<?php echo esc_attr( $value[0] ); ?>"
                                                        selected><?php echo esc_html( $value[1] ); ?></option>
												<?php
											}
										}
									}
									?>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
		<?php
	}

	public function pscw_ajax_search_product() {
		if ( ! check_ajax_referer( 'woo_sc_check_nonce', 'nonce', false ) && ! check_ajax_referer( 'pscw_nonce', 'nonce', false )  ) {
			die();
		}
		$keyword = isset( $_REQUEST['key_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key_search'] ) ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$args = [
			'status'    => 'publish',
			'type'    => array_keys(Data::get_all_data( 'product_type' )),
			'limit'  => 50,
			'return'  => 'ids',
			's'      => $keyword
		];
		$products = wc_get_products($args);
		$found_products = array();
		if ( !empty($products)) {
			foreach ($products as $product){
				$product = wc_get_product($product);
				$found_products[] = [
					'id'   => $product->get_id(),
					'text' => $product->get_name()
				];
			}
		}
		wp_send_json( $found_products );
		die;
	}

	function pscw_ajax_search_term() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'pscw_nonce' ) ) {
			$search_key = isset( $_POST['key_search'] ) ? sanitize_text_field( wp_unslash( $_POST['key_search'] ) ) : '';
			$taxonomy   = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
			$return     = [];

			$args = [
				'taxonomy'   => $taxonomy,
				'orderby'    => 'id',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'all',
				'name__like' => $search_key,
			];

			$terms = get_terms( $args );

			if ( is_array( $terms ) && count( $terms ) ) {
				foreach ( $terms as $term ) {
					$return['results'][] = [ 'id' => $term->slug, 'text' => $term->name ];
				}
			}

			wp_send_json( $return );
		}
		die;
	}

	public function pscw_save_data_meta_box( $post_id ) {
		if ( ! current_user_can( "edit_post", $post_id ) ) {
			return $post_id;
		}
		if ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ( ! isset( $_POST['woo_sc_nonce'] ) ) ||
		     ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['woo_sc_nonce'] ) ), 'woo_sc_check_nonce' ) ) ) {
			return $post_id;
		}

//		$pscw_assign          = isset( $_POST['pscw_assign'] ) ? sanitize_text_field( wp_unslash( $_POST['pscw_assign'] ) ) : 'none';
//		$pscw_allow_countries = isset( $_POST['pscw_allow_countries'] ) ? wc_clean( wp_unslash( $_POST['pscw_allow_countries'] ) ) : [];
//		$pscw_condition       = [];
//
//		switch ( $pscw_assign ) {
//			case 'all':
//				break;
//			case 'products':
//				$pscw_condition   = isset( $_POST['pscw_assign_products'] ) ? wc_clean( wp_unslash( $_POST['pscw_assign_products'] ) ) : [];
//				break;
//			case 'product_cat':
//				$pscw_condition   = isset( $_POST['pscw_assign_product_cat'] ) ? wc_clean( wp_unslash( $_POST['pscw_assign_product_cat'] ) ) : [];
//				break;
//		}
//
//		$pscw_data = array(
//			'assign'          => $pscw_assign,
//			'allow_countries' => $pscw_allow_countries,
//			'condition'       => $pscw_condition,
//		);
//
//		if ( empty( get_post_meta( $post_id, 'pscw_interface', true ) ) ) {
//			update_post_meta( $post_id, 'pscw_interface', $this->default_size_chart_interface() );
//		}
//		update_post_meta( $post_id, "pscw_data", $pscw_data );

		$pscw_data= isset($_POST['pscw_data'])? wc_clean( wp_unslash( $_POST['pscw_data'] ) ) : [];
		update_post_meta( $post_id, "pscw_data", $pscw_data );
		return $post_id;
	}

	public function pscw_duplicate() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( isset( $_GET['pscw_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['pscw_nonce'] ) ), 'pscw_nonce' ) ) {
			$dup_id = ! empty( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
			if ( $dup_id ) {
				$current_post = get_post( $dup_id );

				$args             = [
					'post_title' => esc_html__( 'Copy of ', 'product-size-chart-for-woo' ) . $current_post->post_title,
					'post_type'  => $current_post->post_type,
				];
				$new_id           = wp_insert_post( $args );
				$dup_post_meta    = get_post_meta( $dup_id, 'pscw_data', true );
				$dub_interface    = get_post_meta( $dup_id, 'pscw_interface', true );
				update_post_meta( $new_id, 'pscw_data', $dup_post_meta );
				update_post_meta( $new_id, 'pscw_interface', $dub_interface );
				wp_safe_redirect( admin_url( "post.php?post={$new_id}&action=edit" ) );
				exit;
			}
		}
	}

	public function pscw_go_design() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( isset( $_GET['pscw_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['pscw_nonce'] ) ), 'pscw_nonce' ) ) {
			$sc_id = ! empty( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
			if ( $sc_id ) {
				$random_product = $this->get_random_product();

				$url = admin_url( 'customize.php' ) . '?url=' . esc_url( get_permalink( $random_product[0] ) ) . '&pscw_id=' . $sc_id . '&autofocus[panel]=pscw_size_chart_customizer&autofocus[section]=pscw_customizer_design&pscw_sizechart_mode=1';

				wp_safe_redirect( $url );

				exit;
			}
		}
	}

	public function post_add_action( $actions, $post ) {
		if ( "pscw-size-chart" == $post->post_type ) {
			$nonce                     = wp_create_nonce( 'pscw_nonce' );
			$href1                     = admin_url( "post.php?action=pscw_duplicate&id={$post->ID}&pscw_nonce={$nonce}" );
			$href2                     = admin_url( "post.php?action=pscw_go_design&id={$post->ID}&pscw_nonce={$nonce}" );
			$actions['pscw_duplicate'] = "<a href='{$href1}'>" . esc_html__( 'Duplicate', 'product-size-chart-for-woo' ) . "</a>";
			if ( $post->post_status !== 'auto-draft' ) {
				$actions['pscw_go_design'] = "<a href='{$href2}' target='_blank'> " . esc_html__( 'Design', 'product-size-chart-for-woo' ) . "</a>";
			}
		}

		return $actions;
	}

	public function custom_post_columns( $columns ) {
		$columns['short-code'] = esc_html__( 'Shortcode', 'product-size-chart-for-woo' );
		unset( $columns['date'] );

		return $columns;
	}

	public function show_custom_columns( $name ) {
		global $post;
		switch ( $name ) {
			case 'short-code':
				?>
                <div class="vi-ui icon input">
                    <input type="text" class="woo_sc_short_code" readonly
                           value="[PSCW_SIZE_CHART ID=<?php echo "'" . esc_attr( $post->ID ) . "'"; ?>]">
                    <i class="copy icon"></i>
                    <span class="woo_sc_copied"><?php esc_html_e( 'Copied', 'product-size-chart-for-woo' ); ?></span>
                </div>
				<?php
				break;
		}
	}


}