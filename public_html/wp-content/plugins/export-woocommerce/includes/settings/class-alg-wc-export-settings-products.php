<?php
/**
 * Export WooCommerce - Products Section Settings
 *
 * @version 2.3.0
 * @since   1.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Settings_Products' ) ) :

class Alg_WC_Export_Settings_Products extends Alg_WC_Export_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 2.2.4
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = 'products';
		$this->desc = __( 'Products', 'export-woocommerce' );

		if (
			isset(
				$_GET['page'],
				$_GET['tab'],
				$_GET['section']
			) &&
			'wc-settings'   === $_GET['page'] &&
			'alg_wc_export' === $_GET['tab'] &&
			'products'      === $_GET['section']
		){
			add_action( 'admin_footer', array( $this, 'alg_wc_export_admin_add_js_product_setting' ) );
			add_action( 'woocommerce_after_settings_alg_wc_export', array( $this, 'after_settings' ), PHP_INT_MAX );
		}

		add_action( 'wp_ajax_alg_wc_export_admin_product_ajax_download',        array( $this, 'alg_wc_export_admin_product_ajax_download' ) );
		add_action( 'wp_ajax_nopriv_alg_wc_export_admin_product_ajax_download', array( $this, 'alg_wc_export_admin_product_ajax_download' ) );

		add_action( 'wp_ajax_alg_wc_export_admin_product_ajax_download_start',        array( $this, 'alg_wc_export_admin_product_ajax_download_start' ) );
		add_action( 'wp_ajax_nopriv_alg_wc_export_admin_product_ajax_download_start', array( $this, 'alg_wc_export_admin_product_ajax_download_start' ) );

		parent::__construct();
	}

	/**
	 * after_settings.
	 *
	 * @version 2.1.0
	 * @since   1.0.0
	 */
	function after_settings() {
		echo (
			'<button name="save" class="button-primary wc-product-external-save-button" type="button" value="' . esc_attr( __( 'Save changes', 'woocommerce' ) ) . '" style="margin-right:10px;">' .
				esc_html( __( 'Save changes', 'woocommerce' ) ) .
			'</button>' .
			apply_filters(
				'alg_wc_export',
				'<p>' .
					sprintf(
						'Please upgrade to <a href="%s" target="_blank">Export WooCommerce Pro plugin</a> to add more than one additional export field.',
						'https://wpfactory.com/item/export-woocommerce/'
					) .
				'</p>',
				'settings'
			)
		);
		echo '<button type="button" id="alg-wc-export-preview-btn" class="button-secondary preview-btn" data-limit="5" value="Preview" title="Might be different from actual export!"  style="margin-right:10px;">' .
			esc_html( __( 'Preview', 'woocommerce' ) ) .
		'</button>';

		$download_file_type = get_option('alg_export_products_fields_file_type', 'csv');
		$download_url       = '';

		$start_date = get_option( 'alg_export_products_fields_from_date', '' );
		$end_date   = get_option( 'alg_export_products_fields_end_date', '' );
		$date_args  = '';
		$date_args .= ( ! empty( $start_date ) ? '&start_date=' . $start_date : '' );
		$date_args .= ( ! empty( $end_date )   ? '&end_date='   . $end_date   : '' );

		if ( 'csv' == $download_file_type ) {
			$download_url = home_url( '/?alg_export='     . $this->id . $date_args );
		}
		if ( 'xml' == $download_file_type ) {
			$download_url = home_url( '/?alg_export_xml=' . $this->id . $date_args );
		}

		if (
			'yes' == get_option('alg_wc_export_ajax_download', 'no') &&
			'csv' == $download_file_type
		) {
			echo '<a type="button" id="alg-wc-export-export-ajax-btn" class="button-secondary" value="Export" style="margin-right:10px;" href="javascript:;">' .
				esc_html( __( 'Export', 'woocommerce' ) ) .
			'</a>';
		} else {
			echo '<a type="button" id="alg-wc-export-export-btn" class="button-secondary" value="Export" style="margin-right:10px;" href="'.$download_url.'">' .
				esc_html( __( 'Export', 'woocommerce' ) ) .
			'</a>';
		}

		echo '<div id="alg-wc-export-preview-content-area" style="margin-top:10px;overflow-y:scroll;"></div>';
	}

	/**
	 * add_settings.
	 *
	 * @version 2.3.0
	 * @since   1.0.0
	 */
	function get_settings() {
		$settings = array(
			array(
				'title' => __( 'Export Products Options', 'export-woocommerce' ),
				'type'  => 'title',
				'desc'  => __('From the left table, select the fields you want to export, to change the order, simply add fields to the right table and order using drag & drop, once finished, click on Save Button below to save your changes first, then preview or export directly'),
				'id'    => 'alg_wc_export_products_options',
			),
			array(
				'title'    => __( 'Export Products Fields', 'export-woocommerce' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields. Hold "Control + A" to select all fields.', 'export-woocommerce' ),
				'id'       => 'alg_export_products_fields',
				'default'  => alg_wc_export()->fields_helper->get_product_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => alg_wc_export()->fields_helper->get_product_export_fields(),
				'css'      => 'height:300px;min-width:300px;',
			),
			array(
				'title'    => __( 'Export Products Sorted Fields', 'export-woocommerce' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields. Hold "Control + A" to select all fields.', 'export-woocommerce' ),
				'id'       => 'alg_export_products_fields_sorted',
				'type'     => 'text',
				'css'      => 'min-width:300px;display:none',
			),
			array(
				'title'    => __( 'Export Product Attribute', 'export-woocommerce' ),
				'desc_tip' => __( 'Hold "Control" key to select multiple fields. Hold "Control + A" to select all attributes.', 'export-woocommerce' ),
				'id'       => 'alg_export_products_attribute',
				'default'  => alg_wc_export()->fields_helper->get_product_export_default_fields_ids(),
				'type'     => 'multiselect',
				'options'  => alg_wc_export()->fields_helper->get_product_export_attribute(),
				'css'      => 'height:100px;min-width:100px;display:none',
			),
			array(
				'title'    => __( 'Variation Product on a New Row', 'export-woocommerce' ),
				'desc_tip' => __( 'Export variation product data on a separate row.', 'export-woocommerce' ),
				'desc'     => __( 'Enabled', 'export-woocommerce' ),
				'id'       => 'alg_export_products_variation_newline',
				'type'     => 'checkbox',
				'default'  => 'no',
			),
			array(
				'title'             => __( 'Additional Export Products Meta Fields', 'export-woocommerce' ),
				'desc_tip'          => __( 'Save changes after you change this number.', 'export-woocommerce' ),
				'id'                => 'alg_export_products_fields_additional_total_number',
				'default'           => 1,
				'type'              => 'number',
				'custom_attributes' => apply_filters(
					'alg_wc_export',
					array( 'step' => '1', 'min' => '1', 'max' => '1' ),
					'settings_array'
				),
			),
		);
		$total_number = apply_filters( 'alg_wc_export', 1, 'value_export_products' );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'   => __( 'Meta Field', 'export-woocommerce' ) . ' #' . $i,
					'id'      => 'alg_export_products_fields_additional_enabled_' . $i,
					'desc'    => __( 'Enabled', 'export-woocommerce' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				array(
					'desc'    => __( 'Title', 'export-woocommerce' ),
					'id'      => 'alg_export_products_fields_additional_title_' . $i,
					'type'    => 'text',
					'default' => '',
				),
				array(
					'desc'     => __( 'Value', 'export-woocommerce' ),
					'desc_tip' => __( 'Enter product meta key to retrieve (can be custom field name).', 'export-woocommerce' ),
					'id'       => 'alg_export_products_fields_additional_value_' . $i,
					'type'     => 'text',
					'default'  => '',
				),
			) );
		}

		$settings = array_merge( $settings, array(
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_export_products_options',
			),
			array(
				'title' => __( 'Export Products Filter Options', 'export-woocommerce' ),
				'type'  => 'title',
				'id'    => 'alg_wc_export_products_filter_options',
				'desc'  => '<p></p>',
			),
			array(
				'title'    => __( 'Date Filter', 'export-woocommerce' ),
				'desc_tip' => __( 'Select any date filter.', 'export-woocommerce' ),
				'desc'     => '',
				'id'       => 'alg_export_products_fields_date_filter',
				'default'  => '',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => array(
					''                     => __( 'All time', 'export-woocommerce' ),
					'year'                 => __( 'This Year', 'export-woocommerce' ),
					'last_month'           => __( 'Last month', 'export-woocommerce' ),
					'this_month'           => __( 'This month', 'export-woocommerce' ),
					'last_7_days'          => __( 'Last 7 days', 'export-woocommerce' ),
					'last_14_days'         => __( 'Last 14 days', 'export-woocommerce' ),
					'last_30_days'         => __( 'Last 30 days', 'export-woocommerce' ),
					'last_3_months'        => __( 'Last 3 months', 'export-woocommerce' ),
					'last_6_months'        => __( 'Last 6 months', 'export-woocommerce' ),
					'last_12_months'       => __( 'Last 12 months', 'export-woocommerce' ),
					'last_24_months'       => __( 'Last 24 months', 'export-woocommerce' ),
					'last_36_months'       => __( 'Last 36 months', 'export-woocommerce' ),
					'same_days_last_month' => __( 'Same days last month', 'export-woocommerce' ),
					'same_days_last_year'  => __( 'Same days last year', 'export-woocommerce' ),
					'last_year'            => __( 'Last year', 'export-woocommerce' ),
					'yesterday'            => __( 'Yesterday', 'export-woocommerce' ),
					'today'                => __( 'Today', 'export-woocommerce' ),
					'custom_date_range'    => __( 'Custom Date Range', 'export-woocommerce' ),
				),
			),
			array(
				'title'             => __( 'Custom Date Range', 'export-woocommerce' ),
				'desc'              => '<span id="todatewrapper"></span>',
				'desc_tip'          => __( 'Enter from date and end date.', 'export-woocommerce' ),
				'id'                => 'alg_export_products_fields_from_date',
				'type'              => 'text',
				'default'           => '',
				'placeholder'       => 'From date',
				'class'             => 'yesdatepicker alg_export_date_from',
				'custom_attributes' => array( 'display' => 'date' ),
			),
			array(
				'title'    => __( 'Custom date range', 'export-woocommerce' ),
				'desc'     => __( 'End Date', 'export-woocommerce' ),
				'desc_tip' => __( 'Enter end date.', 'export-woocommerce' ),
				'id'       => 'alg_export_products_fields_end_date',
				'type'     => 'text',
				'class'    => 'alg_export_date_to',
				'default'  => '',
			),
			array(
				'title'    => __( 'Download File Type', 'export-woocommerce' ),
				'desc_tip' => __( 'Select file type.', 'export-woocommerce' ),
				'desc'     => '',
				'id'       => 'alg_export_products_fields_file_type',
				'default'  => 'csv',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => array(
					'csv' => __( 'CSV', 'export-woocommerce' ),
					'xml' => __( 'XML', 'export-woocommerce' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'alg_wc_export_products_filter_options',
			),
		) );
		return $settings;
	}

	/**
	 * add_settings.
	 *
	 * @version 2.2.4
	 * @since   1.0.0
	 */
	function alg_wc_export_admin_add_js_product_setting() {
		$sorted_html       = '';
		$all_fields        = alg_wc_export()->fields_helper->get_product_export_fields();
		$fields_ids_sorted = get_option( 'alg_export_products_fields_sorted', array() );
		if ( ! empty( $fields_ids_sorted ) ) {
			if ( false !== strpos( $fields_ids_sorted, ',' ) ) {
				$fields_ids = explode( ',', $fields_ids_sorted );
			} else {
				$fields_ids = array( $fields_ids_sorted );
			}

			foreach ( $fields_ids as $id ) {
				$sorted_html .= '<li class="ui-state-default" data-optionid="' . $id . '" data-optiontext="' . $all_fields[ $id ] . '">' .
					'<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' .
					$all_fields[ $id ] .
					'<span class="dashicons dashicons-remove remove_sort_item" onclick="removeli(\'' . $id . '\');"></span>' .
				'</li>';
			}
			$sorted_html = addslashes( $sorted_html );
		}
		$export_option_end_date_saved = get_option( 'alg_export_products_fields_end_date', '' );
		?>
		<script>
			jQuery( document ).ready(function() {
				if(jQuery("select#alg_export_products_fields").length > 0){
					var select_product = jQuery("select#alg_export_products_fields");
					select_product.wrap('<div class="subject-info-box-1"></div>');
					var product_arrange_html = '<div class="subject-info-arrows text-center">\
												  <div class="button-wrapper">\
												  <input type="button" id="btnAllRight" value=">>" class="btn btn-default" ><br >\
												  <input type="button" id="btnRight" value=">" class="btn btn-default" style="display: none;"><br>\
												  <input type="button" id="btnAllLeft" value="<<" class="btn btn-default" title="Remove all"><br>\
												  </div>\
												</div>\
												<div class="subject-info-box-2">\
												 <ul id="sortable" class="sortable_list"><?php echo wp_kses_post( $sorted_html ); ?>\
												 </ul>\
												</div>';
					jQuery(product_arrange_html).insertAfter("div.subject-info-box-1");
					jQuery( "#sortable" ).sortable({
						update: function( event, ui ) {
							get_ordered_value();
						}
					});

					jQuery("#btnAllLeft").click(function (e) {
						jQuery("ul.sortable_list").find('li').remove();
						jQuery("#alg_export_products_fields_sorted").val('');
						get_ordered_value();

					});

					jQuery("#btnAllRight").click(function (e) {
						var selectedOpts = jQuery("select#alg_export_products_fields option:selected");
						if (selectedOpts.length == 0) {
						  alert("Nothing to move.");
						  e.preventDefault();
						}

					selectedOpts.each(function() {
						if(jQuery("ul.sortable_list").find('[data-optionid='+this.value+']').length <= 0){
							var lihtml = '<li class="ui-state-default" data-optionid="'+this.value+'" data-optiontext="'+this.text+'"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'+this.text+'<span class="dashicons dashicons-remove remove_sort_item" onclick="removeli(\''+this.value+'\');"></span></li>';
							jQuery("ul.sortable_list").append(lihtml);
						}
					});
					get_ordered_value();
					e.preventDefault();
				  });
				}

				if(jQuery("span#todatewrapper").length > 0){
					jQuery("span#todatewrapper").html('<input name="alg_export_products_fields_end_date_alternative" id="alg_export_products_fields_end_date_alternative" type="text" style="" value="<?php echo esc_js( $export_option_end_date_saved ); ?>" class="yesdatepicker" placeholder="To date" display="date">');
				}

				jQuery("input#alg_export_products_fields_end_date_alternative").change(function(){
					jQuery('input#alg_export_products_fields_end_date').val(jQuery(this).val());
				});

				var end_date_val = jQuery("input#alg_export_products_fields_end_date_alternative").val();
				jQuery('input#alg_export_products_fields_end_date').val(end_date_val);

			});

			function removeli(val){
				jQuery("ul.sortable_list").find('[data-optionid='+val+']').remove();
				get_ordered_value();
			}

			function get_ordered_value(){
				var optionid="";
				jQuery("#alg_export_products_fields_sorted").val('');
				jQuery("ul.sortable_list li").each(function(i) {
					if (optionid=='')
						optionid = jQuery(this).attr('data-optionid');
					else
						optionid += "," + jQuery(this).attr('data-optionid');
				});
				jQuery("#alg_export_products_fields_sorted").val(optionid);
				return optionid;
			}

			jQuery('body').on('click', 'a.page-numbers', function(e) {
				e.preventDefault();
				var href = jQuery(this).attr('href');
				var withoutHash = href.substr(1, href.length);
				load_next_product_preview_page(withoutHash);
			});

		</script>
		<style>
		select#alg_export_products_fields {
			max-width: 100%;
		}
		.subject-info-box-1,
		.subject-info-box-2 {
			float: left;
			width: 40%;
		}
		.subject-info-box-1 {
			margin-right: 20px;
		}
		.subject-info-box-2 {
			margin-left: 20px;
		}

		.subject-info-arrows{
			float: left;
			width: 10%;
			position: relative;
			height: 300px;
		}
		.subject-info-arrows input{
			width: 70%;
			margin-bottom: 5px;
		}
		.button-wrapper{
			top: 33%;
			position: absolute;
			transform: translateY(-50%);
			-ms-transform: translateY(-50%);
			width: 120px;
		}
		.sortable_list{
			margin-top: 0px;
			height: 300px;
			border: 1px solid #000;
			overflow: scroll;
			overflow-x: hidden;
		}
		span.ui-icon.ui-icon-arrowthick-2-n-s{
			float: left;
			margin-right: 20px;
		}
		.sortable_list li{
			padding: 5px;
			cursor: move;
		}
		.remove_sort_item{
			float: right;
			color: red;
			cursor: pointer;
		}
		label[for="alg_export_products_fields_sorted"] {
			display: none !important;
		}
		.paginate-ajax-alg-preview .page-numbers{
			color: #2271b1;
			border-color: #2271b1;
			background: #f6f7f7;
			vertical-align: top;

			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 2.15384615;
			min-height: 30px;
			margin: 0;
			padding: 0 10px;
			cursor: pointer;
			border-width: 1px;
			border-style: solid;
			-webkit-appearance: none;
			border-radius: 3px;
			white-space: nowrap;
			box-sizing: border-box;
		}
		.paginate-ajax-alg-preview .page-numbers.current{
			background: #0071a1;
			border-color: #0071a1;
			color: #fff;
		}
		.paginate-ajax-alg-preview{
			text-align: center;
			margin-bottom: 12px;
		}

		#alg-wc-overlay-id{
			display: none;
		}

		.alg-wc-overlay {
			background-color: black;
			background-color: rgba(0,0,0,.8);
			position: fixed;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			/* opacity: 0.2; */
			/* also -moz-opacity, etc. */
			z-index: 100;
		}
		.alg-wc-progress {
			position: absolute;
			top: 50%;
			left: 50%;
			width:400px;
			height:20px;
			margin:-10px 0 0 -150px;
			z-index:101;
			opacity:1.0;
			border: 1px solid #149bdf;
		}

		.alg-wc-progress-striped .alg-wc-bar {
			background-color: #149bdf;
			background-image: -webkit-gradient(linear, 0 100%, 100% 0, color-stop(0.25, rgba(255, 255, 255, 0.15)), color-stop(0.25, transparent), color-stop(0.5, transparent), color-stop(0.5, rgba(255, 255, 255, 0.15)), color-stop(0.75, rgba(255, 255, 255, 0.15)), color-stop(0.75, transparent), to(transparent));
			background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
			background-image: -moz-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
			background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
			background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
			-webkit-background-size: 40px 40px;
			-moz-background-size: 40px 40px;
			-o-background-size: 40px 40px;
			background-size: 40px 40px;

			-webkit-animation: progress-bar-stripes 2s linear infinite;
			-moz-animation: progress-bar-stripes 2s linear infinite;
			-ms-animation: progress-bar-stripes 2s linear infinite;
			-o-animation: progress-bar-stripes 2s linear infinite;
			animation: progress-bar-stripes 2s linear infinite;

		}

		.alg-wc-progress .alg-wc-bar {
			float: left;
			width: 0;
			height: 100%;
			font-size: 12px;
			color: #ffffff;
			text-align: center;
			text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
			background-color: #0e90d2;
			background-image: -moz-linear-gradient(top, #149bdf, #0480be);
			background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#149bdf), to(#0480be));
			background-image: -webkit-linear-gradient(top, #149bdf, #0480be);
			background-image: -o-linear-gradient(top, #149bdf, #0480be);
			background-image: linear-gradient(to bottom, #149bdf, #0480be);
			background-repeat: repeat-x;
			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff149bdf', endColorstr='#ff0480be', GradientType=0);
			-webkit-box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.15);
			-moz-box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.15);
			box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.15);
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			-webkit-transition: width 0.6s ease;
			-moz-transition: width 0.6s ease;
			-o-transition: width 0.6s ease;
			transition: width 0.6s ease;

		}
		.alg-wc-overlay .alg-wc-file-download-per-text{
			color: #fff;
			font-size: 24px;
			position: absolute;
			top: 53%;
			left: 50%;
			width: 400px;

			margin: -10px 0 0 -150px;
			z-index: 101;
			opacity: 1.0;
			text-align: center;
			line-height: 1.5;
		}
		.alg-wc-overlay .alg-wc-file-download-text{
			color: #fff;
			font-size: 20px;
			position: absolute;
			top: 56%;
			left: 50%;
			width: 400px;

			margin: -10px 0 0 -150px;
			z-index: 101;
			opacity: 1.0;
			text-align: center;
			line-height: 1.5;
		}

		</style>
		<script>
			jQuery( document ).ready(function() {
				jQuery('body').on('click', 'a#alg-wc-export-export-ajax-btn', function(e) {
					jQuery('#alg-wc-overlay-id').show();
					jQuery('#alg-wc-bar-percentage').width('0%');
					jQuery('.alg-wc-file-download-per-text').html('0%');
					var downloaddata = {
						'action'	: 'alg_wc_export_admin_product_ajax_download_start',
						'nonce'		: alg_wc_export_admin_own_js.nonce
					};
					jQuery.ajax( {
						type: "POST",
						url: woocommerce_admin.ajax_url,
						data: downloaddata,
						success: function( response ) {
							response = response.trim();
							response = jQuery.parseJSON( response );
							if ( response.success ) {

								recursive_ajax_download( response.total_page, 1 , response.file_path, response.file_url );
								jQuery('#alg-wc-bar-percentage').width(response.progress + '%');
								jQuery('.alg-wc-file-download-per-text').html(parseInt(response.progress) + '%');
							}

						},
					} );
				});
			});

			function recursive_ajax_download( totalpage, currentpage, filepath, file_url ) {
					var filepath = filepath.replace(/\/\//g, "/");
					var downloaddata = {
						'action'		: 	'alg_wc_export_admin_product_ajax_download',
						'file_path'		: 	filepath,
						'file_url'		: 	file_url,
						'total_page'	: 	totalpage,
						'current_page'	: 	currentpage,
						'nonce'			: 	alg_wc_export_admin_own_js.nonce
					};
					jQuery.ajax( {
						type: "POST",
						url: woocommerce_admin.ajax_url,
						data: downloaddata,
						success: function( response ) {
							response = response.trim();
							response = jQuery.parseJSON( response );
							if(response.is_end) {
								location.href = response.file_url;
								// window.open(response.file_url, '_blank');
								jQuery('#alg-wc-overlay-id').hide();
							} else {
								var crpage = parseInt(response.current_page) + 1;
								recursive_ajax_download(response.total_page, crpage, response.file_path, response.file_url);
							}
							if(response.success) {
								jQuery('#alg-wc-bar-percentage').width(response.progress + '%');
								jQuery('.alg-wc-file-download-per-text').html(parseInt(response.progress) + '%');
							}

						},
					} );
			}
		</script>

		<div class="alg-wc-overlay mouse-events-off" id="alg-wc-overlay-id">
			<div class="alg-wc-progress alg-wc-progress-striped alg-wc-active">
				<div class="alg-wc-bar" id="alg-wc-bar-percentage" style="width: 0%;"></div>
			</div>
			<div class="alg-wc-file-download-per-text">0%</div>
			<div class="alg-wc-file-download-text"> Export is in progress. Please don't close window till file download. </div>
		</div>
		<?php

	}

	/**
	 * alg_wc_export_admin_product_ajax_download_start.
	 *
	 * @version 2.0.10
	 * @since   2.0.10
	 */
	function alg_wc_export_admin_product_ajax_download_start() {

		if (
			! current_user_can('manage_options') ||
			! wp_verify_nonce( $_POST['nonce'], 'alg-wc-export-ajax-nonce' )
		) {
			exit;
		}

		$totalpage   = 1;
		$nonce       = $_POST['nonce'];
		$dest        = $this->create_temp_folder();
		$file_name   = $dest['path'] . 'product_csv-' . time() . '-'. $nonce . '.csv';
		$file_url    = $dest['url'] . 'product_csv-' . time() . '-'. $nonce . '.csv';
		$count_pages = wp_count_posts( $post_type = 'product' );

		if ( ! empty( $count_pages ) ) {
			$block_size = (int) get_option( 'alg_wc_export_wp_query_block_size', 1024 );
			$total      = $count_pages->publish;
			if ( $total > 0 ){
				if ( $block_size >= $total ) {
					$totalpage = 1;
				} else {
					$totalpage = ceil( $total / $block_size );
				}
			}
		}

		$progress_completed = ( 1 / ($totalpage + 1) ) * 100;

		$file_name = preg_replace( '/\\\\/', '/', $file_name );

		echo json_encode(
			array(
				'success'    => true,
				'total_page' => $totalpage,
				'file_path'  => $file_name,
				'file_url'   => $file_url,
				'progress'   => $progress_completed,
			),
			JSON_UNESCAPED_SLASHES
		);
		die;
	}

	/**
	 * alg_wc_export_admin_product_ajax_download.
	 *
	 * @version 2.0.10
	 * @since   2.0.10
	 */
	function alg_wc_export_admin_product_ajax_download() {

		if (
			! current_user_can( 'manage_options' ) ||
			! wp_verify_nonce( $_POST['nonce'], 'alg-wc-export-ajax-nonce' )
		) {
			exit;
		}

		$progress_completed = 0;
		$block_size         = (int) get_option( 'alg_wc_export_wp_query_block_size', 1024 );
		$current_page       = $_POST['current_page'];
		$total_page         = $_POST['total_page'];
		$isend              = false;

		if ( $current_page >= $total_page ){
			$isend = true;
		}
		if ( 1 == $current_page ) {
			$start = 0;
		}

		if ( $current_page > 1 ) {
			$start  = ( $current_page - 1 ) * $block_size;
		}

		$progress_completed = ( ( $current_page + 1 ) / ( $total_page + 1 ) ) * 100;

		$tool_id   = 'products';
		$data      = alg_wc_export()->core->export( $tool_id, false, $current_page, $start, true );
		$file_name = $_POST['file_path'];
		$file_url  = $_POST['file_url'];

		$data = apply_filters( 'alg_export_data_csv', $data, $tool_id );

		$csv  = '';
		if ( is_array( $data ) ) {

			$wrap = get_option( 'alg_export_csv_wrap', '' );
			$sep  = $wrap . get_option( 'alg_export_csv_separator', ',' ) . $wrap;
			foreach ( $data as $row ) {
				$row = array_map( array( $this, 'removeComma' ), $row );
				$csv .= $wrap . implode( $sep, $row ) . $wrap . PHP_EOL;
			}
			if ( 'yes' === get_option( 'alg_export_csv_add_utf_8_bom', 'yes' ) ) {
				$csv = "\xEF\xBB\xBF" . $csv; // UTF-8 BOM
			}
		}

		$fp = fopen( $file_name , 'a' );
		fwrite( $fp, $csv ); // Write information to the file
		fclose( $fp );

		$file_name = preg_replace( '/\\\\/', '', $file_name );

		echo json_encode(
			array(
				'success'      => true,
				'total_page'   => $total_page,
				'current_page' => $current_page,
				'file_path'    => $file_name,
				'file_url'     => $file_url,
				'is_end'       => $isend,
				'progress'     => $progress_completed,
			),
			JSON_UNESCAPED_SLASHES
		);
		die;
	}

	/**
	 * create_temp_folder.
	 *
	 * @version 2.0.10
	 * @since   2.0.10
	 */
	function create_temp_folder() {

		$upload_dir  = wp_upload_dir();
		$destination = $upload_dir['basedir'] . '/alg_wc_export_temp/';
		$url         = $upload_dir['baseurl'] . '/alg_wc_export_temp/';

		if ( ! file_exists( $destination ) ) {
			mkdir( $destination , 0775, true );
		}
		return array( 'path' => $destination, 'url' => $url );
	}

	/**
	 * removeComma.
	 *
	 * @version 2.0.10
	 * @since   2.0.10
	 */
	function removeComma( $v ) {
		return str_replace( ',', ' ', $v );
	}

}

endif;

return new Alg_WC_Export_Settings_Products();
