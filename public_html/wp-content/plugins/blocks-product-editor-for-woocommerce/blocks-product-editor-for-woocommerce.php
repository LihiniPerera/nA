<?php

/*
Plugin Name: Blocks Product Editor for WooCommerce
Plugin URI: https://wordpress.org/plugins/blocks-product-editor-for-woocommerce
Description: Blocks Product Editor for WooCommerce plugin enables the Gutenberg Editor for WooCommerce products.
Version: 1.0.4
WC requires at least: 5.5.0
WC tested up to: 9.6.0
Author: ethereumicoio
Author URI: https://ethereumico.io
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: blocks-product-editor-for-woocommerce
Domain Path: /languages
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Explicitly globalize to support bootstrapped WordPress
global 
    $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_basename,
    $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options,
    $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_dir,
    $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_url_path,
    $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_product
;
if ( !function_exists( 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_deactivate' ) ) {
    function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_deactivate() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }

}
if ( version_compare( phpversion(), '7.0', '<' ) ) {
    add_action( 'admin_init', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_deactivate' );
    add_action( 'admin_notices', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_admin_notice' );
    function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_admin_notice() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="error"><p><strong>Blocks Product Editor for WooCommerce</strong> requires PHP version 7.0 or above.</p></div>';
        if ( isset( $_GET['activate'] ) ) {
            unset($_GET['activate']);
        }
    }

} else {
    /**
     * Check if WooCommerce is active
     * https://wordpress.stackexchange.com/a/193908/137915
     **/
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action( 'admin_init', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_deactivate' );
        add_action( 'admin_notices', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_admin_notice_woocommerce' );
        function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_admin_notice_woocommerce() {
            if ( !current_user_can( 'activate_plugins' ) ) {
                return;
            }
            echo '<div class="error"><p><strong>Blocks Product Editor for WooCommerce</strong> requires WooCommerce plugin to be installed and activated.</p></div>';
            if ( isset( $_GET['activate'] ) ) {
                unset($_GET['activate']);
            }
        }

    } else {
        if ( function_exists( 'blocks_product_editor_for_woocommerce_freemius_init' ) ) {
            blocks_product_editor_for_woocommerce_freemius_init()->set_basename( false, __FILE__ );
        } else {
            // Create a helper function for easy SDK access.
            function blocks_product_editor_for_woocommerce_freemius_init() {
                global $blocks_product_editor_for_woocommerce_freemius_init;
                if ( !isset( $blocks_product_editor_for_woocommerce_freemius_init ) ) {
                    // Activate multisite network integration.
                    if ( !defined( 'WP_FS__PRODUCT_10616_MULTISITE' ) ) {
                        define( 'WP_FS__PRODUCT_10616_MULTISITE', true );
                    }
                    // Include Freemius SDK.
                    require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
                    $blocks_product_editor_for_woocommerce_freemius_init = fs_dynamic_init( array(
                        'id'              => '10616',
                        'slug'            => 'blocks-product-editor-for-woocommerce',
                        'type'            => 'plugin',
                        'public_key'      => 'pk_49af5e42d776e001f4d9ec828811d',
                        'is_premium'      => false,
                        'has_addons'      => false,
                        'has_paid_plans'  => true,
                        'trial'           => array(
                            'days'               => 7,
                            'is_require_payment' => true,
                        ),
                        'has_affiliation' => 'all',
                        'menu'            => array(
                            'slug'   => 'blocks-product-editor-for-woocommerce',
                            'parent' => array(
                                'slug' => 'options-general.php',
                            ),
                        ),
                        'is_live'         => true,
                    ) );
                }
                return $blocks_product_editor_for_woocommerce_freemius_init;
            }

            // Init Freemius.
            blocks_product_editor_for_woocommerce_freemius_init();
            // Signal that SDK was initiated.
            do_action( 'blocks_product_editor_for_woocommerce_freemius_init_loaded' );
            // ... Your plugin's main file logic ...
            $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_basename = plugin_basename( dirname( __FILE__ ) );
            $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
            $plugin_url_path = untrailingslashit( plugin_dir_url( __FILE__ ) );
            // HTTPS?
            $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_url_path = ( is_ssl() ? str_replace( 'http:', 'https:', $plugin_url_path ) : $plugin_url_path );
            // Set plugin options
            $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options = get_option( 'blocks-product-editor-for-woocommerce_options', array() );
            require $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_dir . '/vendor/autoload.php';
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_activate_blocks_product(  $can_edit, $post_type  ) {
                global $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options;
                if ( !isset( $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options['enable_blocks_product_editor'] ) || !empty( $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options['enable_blocks_product_editor'] ) ) {
                    if ( $post_type == 'product' ) {
                        $can_edit = true;
                    }
                }
                return $can_edit;
            }

            add_filter(
                'use_block_editor_for_post_type',
                'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_activate_blocks_product',
                20,
                2
            );
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_register_catalog_meta_boxes() {
                global $current_screen;
                // Make sure gutenberg is loaded before adding the metabox
                if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
                    add_meta_box(
                        'catalog-visibility',
                        __( 'Catalog visibility', 'blocks-product-editor-for-woocommerce' ),
                        'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_product_data_visibility',
                        'product',
                        'side'
                    );
                }
            }

            add_action( 'add_meta_boxes', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_register_catalog_meta_boxes' );
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_product_data_visibility(  $post  ) {
                $thepostid = $post->ID;
                $product_object = ( $thepostid ? wc_get_product( $thepostid ) : new WC_Product() );
                $current_visibility = $product_object->get_catalog_visibility();
                $current_featured = wc_bool_to_string( $product_object->get_featured() );
                $visibility_options = wc_get_product_visibility_options();
                ?>
                <div class="misc-pub-section" id="catalog-visibility">
                    <?php 
                echo esc_html__( 'Catalog visibility:', 'woocommerce' );
                ?>
                    <?php 
                if ( !blocks_product_editor_for_woocommerce_freemius_init()->is__premium_only() || !blocks_product_editor_for_woocommerce_freemius_init()->can_use_premium_code() || blocks_product_editor_for_woocommerce_freemius_init()->is_not_paying() ) {
                    ?>
                        <p><?php 
                    echo sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'blocks-product-editor-for-woocommerce' ), '<a href="' . esc_attr( blocks_product_editor_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' );
                    ?></p>
                    <?php 
                } else {
                    ?>
                        <strong id="catalog-visibility-display">
                            <?php 
                    echo ( isset( $visibility_options[$current_visibility] ) ? esc_html( $visibility_options[$current_visibility] ) : esc_html( $current_visibility ) );
                    if ( 'yes' === $current_featured ) {
                        echo ', ' . esc_html__( 'Featured', 'woocommerce' );
                    }
                    ?>
                        </strong>

                        <a href="#catalog-visibility" class="edit-catalog-visibility hide-if-no-js"><?php 
                    esc_html_e( 'Edit', 'woocommerce' );
                    ?></a>
                        <div id="catalog-visibility-select" class="hide-if-js">

                            <input type="hidden" name="current_visibility" id="current_visibility" value="<?php 
                    echo esc_attr( $current_visibility );
                    ?>" />
                            <input type="hidden" name="current_featured" id="current_featured" value="<?php 
                    echo esc_attr( $current_featured );
                    ?>" />

                            <?php 
                    echo '<p>' . esc_html__( 'This setting determines which shop pages products will be listed on.', 'woocommerce' ) . '</p>';
                    foreach ( $visibility_options as $name => $label ) {
                        echo '<input type="radio" name="_visibility" id="_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $current_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <label for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</label><br />';
                    }
                    echo '<br /><input type="checkbox" name="_featured" id="_featured" ' . checked( $current_featured, 'yes', false ) . ' /> <label for="_featured">' . esc_html__( 'This is a featured product', 'woocommerce' ) . '</label><br />';
                    ?>
                            <p>
                                <a href="#catalog-visibility" class="save-post-visibility hide-if-no-js button"><?php 
                    esc_html_e( 'OK', 'woocommerce' );
                    ?></a>
                                <a href="#catalog-visibility" class="cancel-post-visibility hide-if-no-js"><?php 
                    esc_html_e( 'Cancel', 'woocommerce' );
                    ?></a>
                            </p>
                        </div>
                    <?php 
                }
                ?>
                </div>
<?php 
            }

            // Disable new WooCommerce product template (from Version 7.7.0)
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_reset_product_template(  $post_type_args  ) {
                if ( !isset( $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options['enable_blocks_product_editor'] ) || !empty( $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options['enable_blocks_product_editor'] ) ) {
                    if ( array_key_exists( 'template', $post_type_args ) ) {
                        unset($post_type_args['template']);
                    }
                }
                return $post_type_args;
            }

            add_filter( 'woocommerce_register_post_type_product', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_reset_product_template' );
            // can_use_premium_code__premium_only
            //----------------------------------------------------------------------------//
            //                               Admin Options                                //
            //----------------------------------------------------------------------------//
            if ( is_admin() ) {
                include_once $BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_plugin_dir . '/blocks-product-editor-for-woocommerce.admin.php';
            }
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_add_menu_link() {
                $page = add_options_page(
                    __( 'Blocks Product Editor Settings', 'blocks-product-editor-for-woocommerce' ),
                    __( 'Blocks Product Editor', 'blocks-product-editor-for-woocommerce' ),
                    'manage_options',
                    'blocks-product-editor-for-woocommerce',
                    'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options_page'
                );
            }

            add_filter( 'admin_menu', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_add_menu_link' );
            // Place in Option List on Settings > Plugins page
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_actlinks(  $links, $file  ) {
                // Static so we don't call plugin_basename on every plugin row.
                static $this_plugin;
                if ( !$this_plugin ) {
                    $this_plugin = plugin_basename( __FILE__ );
                }
                if ( $file == $this_plugin ) {
                    $settings_link = '<a href="options-general.php?page=blocks-product-editor-for-woocommerce">' . __( 'Settings' ) . '</a>';
                    array_unshift( $links, $settings_link );
                    // before other links
                }
                return $links;
            }

            add_filter(
                'plugin_action_links',
                'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_actlinks',
                10,
                2
            );
            //----------------------------------------------------------------------------//
            //                                   L10n                                     //
            //----------------------------------------------------------------------------//
            function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_load_textdomain() {
                /**
                 * Localise.
                 */
                load_plugin_textdomain( 'blocks-product-editor-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
            }

            add_action( 'plugins_loaded', 'BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_load_textdomain' );
        }
        //if ( ! function_exists( 'blocks_product_editor_for_woocommerce_freemius_init' ) ) {
    }
    // WooCommerce activated
}
// PHP version