<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
function BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_options_page() {
    // Require admin privs
    if ( !current_user_can( 'manage_options' ) ) {
        return false;
    }
    $new_options = array();
    // Which tab is selected?
    $possible_screens = array(
        'default' => esc_html( __( 'Standard', 'blocks-product-editor-for-woocommerce' ) ),
    );
    $possible_screens = apply_filters( 'blocks_product_editor_for_woocommerce_settings_tabs', $possible_screens );
    asort( $possible_screens );
    $current_screen = ( isset( $_GET['tab'] ) && isset( $possible_screens[$_GET['tab']] ) ? sanitize_url( $_GET['tab'] ) : 'default' );
    if ( isset( $_POST['Submit'] ) ) {
        // Nonce verification
        check_admin_referer( 'blocks-product-editor-for-woocommerce-update-options' );
        // Standard options screen
        if ( 'default' == $current_screen ) {
            $new_options['enable_blocks_product_editor'] = ( !empty( $_POST['BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_enable_blocks_product_editor'] ) ? 'on' : '' );
        }
        $new_options = apply_filters( 'blocks_product_editor_for_woocommerce_get_save_options', $new_options, $current_screen );
        // Get all existing Blocks Product Editor options
        $existing_options = get_option( 'blocks-product-editor-for-woocommerce_options', array() );
        // Merge $new_options into $existing_options to retain Blocks Product Editor options from all other screens/tabs
        if ( $existing_options ) {
            $new_options = array_merge( $existing_options, $new_options );
        }
        if ( false !== get_option( 'blocks-product-editor-for-woocommerce_options' ) ) {
            update_option( 'blocks-product-editor-for-woocommerce_options', $new_options );
        } else {
            $deprecated = '';
            $autoload = 'no';
            add_option(
                'blocks-product-editor-for-woocommerce_options',
                $new_options,
                $deprecated,
                $autoload
            );
        }
        ?>
        <div class="updated">
            <p><?php 
        _e( 'Settings saved.' );
        ?></p>
        </div>
    <?php 
    } else {
        if ( isset( $_POST['Reset'] ) ) {
            // Nonce verification
            check_admin_referer( 'blocks-product-editor-for-woocommerce-update-options' );
            delete_option( 'blocks-product-editor-for-woocommerce_options' );
        }
    }
    $existing_options = get_option( 'blocks-product-editor-for-woocommerce_options', array() );
    $options = stripslashes_deep( get_option( 'blocks-product-editor-for-woocommerce_options', array() ) );
    ?>

    <div class="wrap">

        <h1><?php 
    _e( 'Blocks Product Editor Settings', 'blocks-product-editor-for-woocommerce' );
    ?></h1>

        <?php 
    settings_errors();
    ?>

        <?php 
    if ( blocks_product_editor_for_woocommerce_freemius_init()->is_not_paying() ) {
        echo '<section><h1>' . esc_html__( 'Awesome Premium Features', 'blocks-product-editor-for-woocommerce' ) . '</h1>';
        echo esc_html__( 'Per product gutenberg product editor and more.', 'blocks-product-editor-for-woocommerce' );
        echo ' <a href="' . esc_attr( blocks_product_editor_for_woocommerce_freemius_init()->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade Now!', 'blocks-product-editor-for-woocommerce' ) . '</a>';
        echo '</section>';
    }
    ?>

        <h2 class="nav-tab-wrapper">
            <?php 
    if ( $possible_screens ) {
        foreach ( $possible_screens as $s => $sTitle ) {
            ?>
                <a href="<?php 
            echo admin_url( 'options-general.php?page=blocks-product-editor-for-woocommerce&tab=' . esc_attr( $s ) );
            ?>" class="nav-tab<?php 
            if ( $s == $current_screen ) {
                echo ' nav-tab-active';
            }
            ?>"><?php 
            echo esc_html( $sTitle );
            ?></a>
            <?php 
        }
    }
    ?>
        </h2>

        <form id="blocks-product-editor-for-woocommerce_admin_form" method="post" action="">

            <?php 
    wp_nonce_field( 'blocks-product-editor-for-woocommerce-update-options' );
    ?>

            <table class="form-table">

                <?php 
    if ( 'default' == $current_screen ) {
        ?>
                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Enable Gutenberg Editor", 'blocks-product-editor-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input class="checkbox" name="BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_enable_blocks_product_editor" type="checkbox" <?php 
        echo ( !isset( $options['enable_blocks_product_editor'] ) || !empty( $options['enable_blocks_product_editor'] ) ? 'checked' : '' );
        ?>>
                                    <p><?php 
        _e( "Enable the Gutenberg editor for WooCommerce products.", 'blocks-product-editor-for-woocommerce' );
        ?></p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Show Product Categories in REST", 'blocks-product-editor-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !blocks_product_editor_for_woocommerce_freemius_init()->is__premium_only() || !blocks_product_editor_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo 'disabled';
        }
        ?> class="checkbox" name="BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_show_in_rest_cat" type="checkbox" <?php 
        echo ( !isset( $options['show_in_rest_cat'] ) || !empty( $options['show_in_rest_cat'] ) ? 'checked' : '' );
        ?>>
                                    <p><?php 
        _e( "Show Product Categories in REST for WooCommerce product categories.", 'blocks-product-editor-for-woocommerce' );
        ?></p>
                                    <?php 
        if ( blocks_product_editor_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p><?php 
            echo sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'blocks-product-editor-for-woocommerce' ), '<a href="' . esc_attr( blocks_product_editor_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' );
            ?></p>
                                    <?php 
        }
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php 
        _e( "Show Product Tags in REST", 'blocks-product-editor-for-woocommerce' );
        ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input <?php 
        if ( !blocks_product_editor_for_woocommerce_freemius_init()->is__premium_only() || !blocks_product_editor_for_woocommerce_freemius_init()->can_use_premium_code() ) {
            echo 'disabled';
        }
        ?> class="checkbox" name="BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_show_in_rest_tag" type="checkbox" <?php 
        echo ( !isset( $options['show_in_rest_tag'] ) || !empty( $options['show_in_rest_tag'] ) ? 'checked' : '' );
        ?>>
                                    <p><?php 
        _e( "Show Product Tags in REST for WooCommerce product tags.", 'blocks-product-editor-for-woocommerce' );
        ?></p>
                                    <?php 
        if ( blocks_product_editor_for_woocommerce_freemius_init()->is_not_paying() ) {
            ?>
                                        <p><?php 
            echo sprintf( __( '%1$sUpgrade Now!%2$s to enable this feature.', 'blocks-product-editor-for-woocommerce' ), '<a href="' . esc_attr( blocks_product_editor_for_woocommerce_freemius_init()->get_upgrade_url() ) . '" target="_blank">', '</a>' );
            ?></p>
                                    <?php 
        }
        ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                <?php 
    }
    ?>
                <?php 
    do_action( 'blocks_product_editor_for_woocommerce_print_options', $options, $current_screen );
    ?>

            </table>

            <?php 
    if ( blocks_product_editor_for_woocommerce_freemius_init()->is_not_paying() ) {
        ?>
                <h2><?php 
        _e( "Want more features?", 'blocks-product-editor-for-woocommerce' );
        ?></h2>
                <p><?php 
        echo sprintf( __( 'Install the %1$sPRO plugin version%2$s!', 'blocks-product-editor-for-woocommerce' ), '<a target="_blank" href="' . esc_attr( blocks_product_editor_for_woocommerce_freemius_init()->get_upgrade_url() ) . '">', '</a>' );
        ?></p>

            <?php 
    }
    ?>

            <p class="submit">
                <input class="button-primary" type="submit" name="Submit" value="<?php 
    _e( 'Save Changes', 'blocks-product-editor-for-woocommerce' );
    ?>" />
                <input id="BLOCKS_PRODUCT_EDITOR_FOR_WOOCOMMERCE_reset_options" type="submit" name="Reset" onclick="return confirm('<?php 
    _e( 'Are you sure you want to delete all Blocks Product Editor options?', 'blocks-product-editor-for-woocommerce' );
    ?>')" value="<?php 
    _e( 'Reset', 'blocks-product-editor-for-woocommerce' );
    ?>" />
            </p>

        </form>

        <p class="alignleft"><?php 
    echo sprintf( __( 'If you like <strong>Blocks Product Editor for WooCommerce</strong> please leave us a %1$s rating. A huge thanks in advance!', 'blocks-product-editor-for-woocommerce' ), '<a href="https://wordpress.org/support/plugin/blocks-product-editor-for-woocommerce/reviews?rate=5#new-post" target="_blank">★★★★★</a>' );
    ?></p>


    </div>

<?php 
}
