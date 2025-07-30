<?php
/**
 * Export WooCommerce - Import Class
 *
 * @version 2.2.5
 * @since   1.7.5
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_WC_Export_Import' ) ) :

class Alg_WC_Export_Import {

	/**
	 * dir.
	 */
	public $dir = 'alg_wc_export_import';

	/**
	 * filetype.
	 */
	public $filetype = 'csv';

	/**
	 * matched_column.
	 */
	public $matched_column = array();

	/**
	 * decoded_xml.
	 */
	public $decoded_xml = array();

	/**
	 * Constructor.
	 *
	 * @version 2.2.5
	 *
	 * @todo    (v2.2.5) `session_start( [ 'read_and_close' => true ] )`?
	 * @todo    (v2.2.5) add more import tools, e.g., customers?
	 */
	function __construct() {

		// Start session
		if (
			! session_id() &&
			! headers_sent()
		) {
			session_start();
		}

		// Hooks
		add_action( 'woocommerce_settings_tabs_alg_wc_export', array($this, 'settings_tab') );
		add_action( 'woocommerce_update_options_alg_wc_export', array($this, 'update_settings') );
		add_action( 'admin_notices', array($this, 'general_admin_notice') );
		add_action( 'upload_mimes', array($this, 'add_upload_xml') );

	}

	/**
	 * add_upload_xml.
	 */
	function add_upload_xml( $mimes ) {
		$mimes = array_merge( $mimes, array( 'xml' => 'application/xml' ) );
		return $mimes;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @version 2.2.5
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	function settings_tab() {

		global $current_section;
		if ( 'import' !== $current_section ) {
			return;
		}

		$GLOBALS['hide_save_button'] = 1;
		$file_in_session = $this->get_file_from_session();
		$isuploaded = (isset($_GET['uploadedtmp']) ? true : false);
		if($file_in_session && $isuploaded){
			woocommerce_admin_fields( $this->get_match_fields() );
			?>
			<p class="submit">
			<button name="save" class="button-primary export-woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Run Import', 'export-woocommerce' ); ?>"><?php esc_html_e( 'Run Import', 'export-woocommerce' ); ?></button>
			<input type="hidden" name="alg_export_upload_actioned" id="alg_export_upload_actioned" value="run_import">
			</p>
			<?php
		}else{
			woocommerce_admin_fields( $this->get_settings() );

			?>
			<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="alg_export_upload_import_file"><?php esc_attr_e( 'Select File', 'export-woocommerce' ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<input type="file" class="button-secondary" id="alg_export_upload_import_file" name="alg_export_upload_import_file" accept=".csv, .xml">
				</td>
			</tr>
			</tbody>
			</table>
			<p class="submit">
			<button name="save" class="button-primary export-woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Upload File', 'export-woocommerce' ); ?>"><?php esc_html_e( 'Upload File', 'export-woocommerce' ); ?></button>
			<input type="hidden" name="alg_export_upload_actioned" id="alg_export_upload_actioned" value="upload">
			</p>
		<?php
		}
		?>
		<?php

	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @version 2.2.5
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	function update_settings() {

		global $current_section;
		if ( 'import' !== $current_section ) {
			return;
		}

		if (
			! empty( $_REQUEST['alg_export_upload_actioned'] ) &&
			'run_import' === $_REQUEST['alg_export_upload_actioned']
		) {
			$this->run_import();
			$this->clear_session_file();
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=alg_wc_export&section=import&importdone=1' ) );
			exit;
		} else {
			$this->save_file_to_tmp();
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=alg_wc_export&section=import&uploadedtmp=1' ) );
			exit;
		}

	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	function get_settings() {

		$settings[] = array(
			'title'    => __( 'Import', 'export-woocommerce' ),
			'type'     => 'title',
			'id'       => 'alg_wc_export_import_options',
			'desc'     => __( 'Import products by csv/xml', 'export-woocommerce' ),
		);

		$settings[] = array(
			 'type' => 'sectionend',
			 'id' => 'alg_wc_export_import_section_end'
		);

		return apply_filters( 'alg_wc_export_import_settings', $settings );
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	function get_match_fields() {
		$settings[] = array(
			'title'    => __( 'Import', 'export-woocommerce' ),
			'type'     => 'title',
			'id'       => 'alg_wc_export_import_options',
			'desc'     => __( 'Choose match fields and run import', 'export-woocommerce' ),
		);

		$this->filetype = $this->get_current_filetype();
		if(strtolower($this->filetype)=='xml'){
			$heads = $this->get_options_from_xml_head();
		}else{
			$heads = $this->get_options_from_csv_head();
		}

		$fields = alg_wc_export()->fields_helper->get_product_export_fields();
		$allowedfields = alg_wc_export()->fields_helper->get_product_export_import_fields_ids();
		if(!empty($fields)){
			foreach($fields as $filedname => $fieldTitle){

				if(in_array($filedname, $allowedfields))
				{
					$fieldTitlesanitize = trim($fieldTitle, "\\xef\\xbb\\xbf");
					$fieldTitlesanitize = str_replace("\xEF\xBB\xBF",'',$fieldTitlesanitize);

					if(strtolower($this->filetype)=='xml'){
						$fldnamewithproduct = (strpos($filedname, "product-") === 0 ? $filedname : "product-" . $filedname);
						$default = $this->get_default_selectted($heads, $fldnamewithproduct, true);
					}else{
						$default = $this->get_default_selectted($heads, $fieldTitlesanitize);
					}
					$settings[] =   array(
								'title'    => __( $fieldTitle, 'export-woocommerce' ),
								'type'     => 'select',
								'id'       => 'alg_wc_export_import_options_' . $filedname,
								'default'  => $default,
								'options'  => $heads,
								'desc'     => '',
							);
				}
			}
		}

		$settings[] = array(
			 'type' => 'sectionend',
			 'id' => 'alg_wc_export_import_section_end'
		 );

		return apply_filters( 'alg_wc_export_import_match_fields', $settings );
	}

	/**
	 * get_default_selectted.
	 */
	function get_default_selectted( $haystack, $needle, $xml = false ) {
		$return = 0;
		if(!empty($haystack) && is_array($haystack)){

			if($xml){
				$haystack = array_map(function($item) {
				$item = (strpos($item, "product-") === 0 ? $item : "product-" . $item);
				return $item;
			}, $haystack);
			}else{
			$haystack = array_map(function($item) {
				$item = str_replace("\xEF\xBB\xBF",'',$item);
				return trim($item, "\\xef\\xbb\\xbf");
			}, $haystack);
			}
			$return = array_search($needle, $haystack);
		}
		return $return;
	}

	/**
	 * save_file_to_tmp.
	 */
	function save_file_to_tmp() {
		if(@$_POST['alg_export_upload_actioned'] && $_POST['alg_export_upload_actioned']=='upload'){
			if(isset($_FILES['alg_export_upload_import_file'])){
				if(!empty($_FILES['alg_export_upload_import_file']['name'])){
					$this->create_tmp_dir();
					$file_name = $_FILES['alg_export_upload_import_file']['name'];
					$file_temp = $_FILES['alg_export_upload_import_file']['tmp_name'];

					$image_data = file_get_contents( $file_temp );
					$filename = basename( $file_name );

					$file_type = wp_check_filetype($file_name);
					$filename = uniqid() . '-' . time() . '.' . $file_type['ext'];

					$upload = wp_upload_dir();
					$upload_dir = trailingslashit($upload['basedir']);
					$file = $upload_dir . '/' . $this->dir . '/' . $filename;

					@file_put_contents( $file, $image_data );
					$this->save_file_to_session($file);
				}
			}
		}
	}

	/**
	 * get_current_filetype.
	 */
	function get_current_filetype() {
		$file = $this->get_file_from_session();
		if($file){
			$file_name = basename($file);
			$filetype = wp_check_filetype($file_name);
			return $filetype['ext'];
		}
		return '';
	}

	/**
	 * save_file_to_session.
	 */
	function save_file_to_session($file) {
		$_SESSION['alg_export_upload_import_file'] = $file;
		// For Rest API
		session_write_close();
	}

	/**
	 * get_file_from_session.
	 */
	function get_file_from_session() {
		if(isset($_SESSION['alg_export_upload_import_file']) && !empty($_SESSION['alg_export_upload_import_file'])){
			if(file_exists($_SESSION['alg_export_upload_import_file'])){
				return $_SESSION['alg_export_upload_import_file'];
			}
		}
		return false;
	}

	/**
	 * create_tmp_dir.
	 */
	function create_tmp_dir(){
		$upload = wp_upload_dir();
		$upload_dir = trailingslashit($upload['basedir']);
		$upload_dir = $upload_dir . '/' . $this->dir;
		if (! is_dir($upload_dir)) {
			wp_mkdir_p( $upload_dir );
		}
	}

	/**
	 * get_options_from_csv_head.
	 */
	function get_options_from_csv_head(){
		$file_in_session = $this->get_file_from_session();
		$buffer = array();
		$buffer[] = 'Choose field from imported file';
		if($file_in_session)
		{
			$fd = fopen ($file_in_session, "r");
			$counter = 0;
			while ( ! feof ( $fd ) )
			{
				if ( $counter === 1 )
					break;

				$prebuffer = fgetcsv ( $fd, 5000 );
				$buffer = array_merge($buffer, $prebuffer);
				++$counter;
			}

			fclose ($fd);
		}
		return $buffer;
	}

	/**
	 * get_options_from_xml_head.
	 */
	function get_options_from_xml_head(){
		$buffer[] = 'Choose field from imported file';
		$this->decoded_xml = $this->xml_file_decode();
		if(isset($this->decoded_xml['item']) && !empty($this->decoded_xml['item'])){
			$counter = 0;
			if(isset($this->decoded_xml['item'][0]))
			{
				foreach($this->decoded_xml['item'] as $item){
					if ( $counter === 1 )
						break;

					$prebuffer = array_keys($item);

					++$counter;
				}
			}
			else
			{
				$prebuffer = array_keys($this->decoded_xml['item']);
			}
			$buffer = array_merge($buffer, $prebuffer);
		}
		return $buffer;
	}

	/**
	 * xml_file_decode.
	 */
	function xml_file_decode(){
		$file_in_session = $this->get_file_from_session();
		$xmlfile = file_get_contents($file_in_session);
		// Convert xml string into an object
		$xmlstring = @simplexml_load_string($xmlfile);

		// Convert into json
		$encoded = json_encode($xmlstring);
		// Convert into associative array
		$elements = json_decode($encoded, true);
		return $elements;
	}

	/**
	 * get_options_from_csv_all.
	 */
	function get_options_from_csv_all(){
		$file_in_session = $this->get_file_from_session();
		$buffer = array();
		if($file_in_session)
		{
			$fd = fopen ($file_in_session, "r");
			$counter = 0;
			while ( ! feof ( $fd ) )
			{

				$buffer[] = fgetcsv ( $fd, 5000 );
				++$counter;
			}

			fclose ($fd);
		}
		return $buffer;
	}

	/**
	 * get_options_from_xml_all.
	 */
	function get_options_from_xml_all(){

		$this->decoded_xml = $this->xml_file_decode();
		$buffer = array();

		$counter = 0;
		if(isset($this->decoded_xml['item']) && !empty($this->decoded_xml['item'])){
			if(isset($this->decoded_xml['item'][0]))
			{
				foreach($this->decoded_xml['item'] as $item){

					$buffer[] = array_values($item);
					++$counter;
				}
			}else{
				$buffer[] = array_values($this->decoded_xml['item']);
			}
		}

		return $buffer;
	}

	/**
	 * run_import.
	 */
	function run_import(){
		$this->filetype = $this->get_current_filetype();
		if($this->filetype == 'xml'){
			$lines = $this->xml_parser();
		}else if($this->filetype == 'csv'){
			$lines = $this->csv_parser();
		}

		$this->matched_column = $this->match_column_with_field();

		if(!empty($this->filetype)){
			if(is_array($lines) && !empty($lines)){
				$cnt = 0;
				foreach($lines as $line){

					if((($cnt > 0 && $this->filetype == 'csv') || $this->filetype == 'xml') && !empty($line)){
						$this->insert_product($line);
					}
					$cnt++;
				}
			}
		}
	}

	/**
	 * insert_product.
	 */
	function insert_product($line){

		$product_id                = (!empty($this->matched_column['product-id']) ? $line[$this->matched_column['product-id'] - 1] : 0);
		$product_name              = (!empty($this->matched_column['product-name']) ? $line[$this->matched_column['product-name'] - 1] : '');
		$product_sku               = (!empty($this->matched_column['product-sku']) ? $line[$this->matched_column['product-sku'] - 1] : '');
		$product_stock             = (!empty($this->matched_column['product-stock']) ? $line[$this->matched_column['product-stock'] - 1] : '');
		$product_stock_quantity    = (!empty($this->matched_column['product-stock-quantity']) ? $line[$this->matched_column['product-stock-quantity'] - 1] : '');
		$product_regular_price     = (!empty($this->matched_column['product-regular-price']) ? $line[$this->matched_column['product-regular-price'] - 1] : '');
		$product_sale_price        = (!empty($this->matched_column['product-sale-price']) ? $line[$this->matched_column['product-sale-price'] - 1] : '');
		$product_price             = (!empty($this->matched_column['product-price']) ? $line[$this->matched_column['product-price'] - 1] : '');
		$product_type              = (!empty($this->matched_column['product-type']) ? $line[$this->matched_column['product-type'] - 1] : 'simple');
		$product_image_url         = (!empty($this->matched_column['product-image-url']) ? $line[$this->matched_column['product-image-url'] - 1] : '');
		$product_short_description = (!empty($this->matched_column['product-short-description']) ? $line[$this->matched_column['product-short-description'] - 1] : '');
		$product_description       = (!empty($this->matched_column['product-description']) ? $line[$this->matched_column['product-description'] - 1] : '');
		$product_status            = (!empty($this->matched_column['product-status']) ? $line[$this->matched_column['product-status'] - 1] : 'publish');
		$product_width             = (!empty($this->matched_column['product-width']) ? $line[$this->matched_column['product-width'] - 1] : '');
		$product_length            = (!empty($this->matched_column['product-length']) ? $line[$this->matched_column['product-length'] - 1] : '');
		$product_height            = (!empty($this->matched_column['product-height']) ? $line[$this->matched_column['product-height'] - 1] : '');
		$product_weight            = (!empty($this->matched_column['product-weight']) ? $line[$this->matched_column['product-weight'] - 1] : '');
		$product_downloadable      = (!empty($this->matched_column['product-downloadable']) ? $line[$this->matched_column['product-downloadable'] - 1] : '');
		$product_virtual           = (!empty($this->matched_column['product-virtual']) ? $line[$this->matched_column['product-virtual'] - 1] : '');
		$product_sold_individually = (!empty($this->matched_column['product-sold-individually']) ? $line[$this->matched_column['product-sold-individually'] - 1] : '');
		$product_manage_stock      = (!empty($this->matched_column['product-manage-stock']) ? $line[$this->matched_column['product-manage-stock'] - 1] : '');
		$product_stock_status      = (!empty($this->matched_column['product-stock-status']) ? $line[$this->matched_column['product-stock-status'] - 1] : '');
		$product_backorders        = (!empty($this->matched_column['product-backorders']) ? $line[$this->matched_column['product-backorders'] - 1] : '');
		$product_featured          = (!empty($this->matched_column['product-featured']) ? $line[$this->matched_column['product-featured'] - 1] : '');
		$product_visibility        = (!empty($this->matched_column['product-visibility']) ? $line[$this->matched_column['product-visibility'] - 1] : '');

		$postdata = array();

		if(!empty($product_description)){
			$postdata['post_content'] = $product_description;
		}
		if(!empty($product_short_description)){
			$postdata['post_excerpt'] = $product_short_description;
		}
		if(!empty($product_name)){
			$postdata['post_title'] = $product_name;
		}
		if(!empty($product_status)){
			$postdata['post_status'] = $product_status;
		}
		$postdata['post_type'] = 'product';

		$product_id = wp_insert_post( $postdata, false );

		if ( is_wp_error( $product_id ) ) {
			$error_string = $post_id->get_error_message();
		}

		if($product_id > 0){
			if(!empty($product_type)){
				wp_set_object_terms( $product_id, $product_type, 'product_type' );
			}

			if(!empty($product_visibility)){
				update_post_meta( $product_id, '_visibility', $product_visibility );
			}
			if(!empty($product_stock_status)){
				update_post_meta( $product_id, '_stock_status', $product_stock_status);
			}

			if(!empty($product_downloadable)){
				update_post_meta( $product_id, '_downloadable', $product_downloadable );
			}
			if(!empty($product_virtual)){
				update_post_meta( $product_id, '_virtual', $product_virtual );
			}
			if(!empty($product_regular_price)){
				update_post_meta( $product_id, '_regular_price', $product_regular_price );
			}
			if(!empty($product_sale_price)){
				update_post_meta( $product_id, '_sale_price', $product_sale_price );
			}

			if(!empty($product_featured)){
				update_post_meta( $product_id, '_featured', $product_featured );
			}
			if(!empty($product_weight)){
				update_post_meta( $product_id, '_weight', $product_weight );
			}
			if(!empty($product_length)){
				update_post_meta( $product_id, '_length', $product_length );
			}
			if(!empty($product_width)){
				update_post_meta( $product_id, '_width', $product_width );
			}
			if(!empty($product_height)){
				update_post_meta( $product_id, '_height', $product_height );
			}
			if(!empty($product_sku)){
				update_post_meta( $product_id, '_sku', $product_sku );
			}
			if(!empty($product_price)){
				update_post_meta( $product_id, '_price', $product_price );
			}
			if(!empty($product_sold_individually)){
				update_post_meta( $product_id, '_sold_individually', $product_sold_individually );
			}
			if(!empty($product_manage_stock)){
				update_post_meta( $product_id, '_manage_stock', $product_manage_stock );
			}
			if(!empty($product_backorders)){
				update_post_meta( $product_id, '_backorders', $product_backorders );
			}
			if(!empty($product_stock_quantity)){
				update_post_meta( $product_id, '_stock', $product_stock_quantity );
			}
			if(!empty($product_stock)){
				update_post_meta( $product_id, '_stock', $product_stock );
			}
		}
	}

	/**
	 * csv_parser.
	 */
	function csv_parser(){
		return $this->get_options_from_csv_all();
	}

	/**
	 * xml_parser.
	 */
	function xml_parser(){
		return $this->get_options_from_xml_all();
	}

	/**
	 * match_column_with_field.
	 */
	function match_column_with_field(){
		$return = array();
		if(isset($_REQUEST['alg_export_upload_actioned']) && !empty($_REQUEST['alg_export_upload_actioned']) && $_REQUEST['alg_export_upload_actioned']=='run_import'){
			$allowedfields = alg_wc_export()->fields_helper->get_product_export_import_fields_ids();
			if(is_array($allowedfields) && !empty($allowedfields)){
				foreach($allowedfields as $field){
					$request_field_name = 'alg_wc_export_import_options_' . $field;
					$return[$field] = $_REQUEST[$request_field_name];
				}
			}
		}
		return $return;
	}

	/**
	 * general_admin_notice.
	 */
	function general_admin_notice(){
		global $pagenow;
		$importdone = (isset($_GET['importdone']) ? true : false);
		if ( $pagenow == 'admin.php' && $importdone ) {
			 echo '<div class="notice notice-success is-dismissible">
				 <p>'.__( 'Import done successfully !', 'export-woocommerce' ).'</p>
			 </div>';
		}
	}

	/**
	 * clear_session_file.
	 */
	function clear_session_file(){
		if(isset($_SESSION['alg_export_upload_import_file'])){
			$file_in_session = $this->get_file_from_session();
			@unlink($file_in_session);
			$_SESSION['alg_export_upload_import_file'] = '';
		}
	}

}

endif;

return new Alg_WC_Export_Import();
