/**
 * alg-wc-export-admin-own-js.
 *
 * @version 2.0.9
 * @since   1.1.0
 */

jQuery( document ).ready( function() {
	jQuery( 'button.wc-product-external-save-button' ).click(function(){
		// jQuery('form#mainform').submit();
		jQuery('button.woocommerce-save-button').click();
	});
	
	jQuery( 'button.wc-customers_from_orders-external-save-button' ).click(function(){
		jQuery('button.woocommerce-save-button').click();
	});
	
	
	jQuery( 'button#alg-wc-export-preview-btn' ).click(function(){
		
		var action = 'alg_wc_export_admin_product_preview';
		
		if( alg_wc_export_admin_own_js.section == 'customers_from_orders' ) {
			
			action = 'alg_wc_export_admin_customers_from_orders_preview';
		}
		var data = {
			'action': action,
			'page': '1',
			'nonce': alg_wc_export_admin_own_js.nonce
		};
		jQuery.ajax( {
			type: "POST",
			url: woocommerce_admin.ajax_url,
			data: data,
			success: function( response ) {
				response = response.trim();
				jQuery('#alg-wc-export-preview-content-area').html('');
				jQuery('#alg-wc-export-preview-content-area').html(response);
			},
		} );
	
	});
	
	jQuery( 'select#alg_export_products_fields_date_filter' ).change(function(){
		var data = {
			'action': 'alg_wc_export_admin_product_change_date_filter',
			'value': jQuery(this).val(),
			'nonce': alg_wc_export_admin_own_js.nonce
		};
		jQuery.ajax( {
			type: "POST",
			url: woocommerce_admin.ajax_url,
			data: data,
			success: function( response ) {
				response = response.trim();
				var obj = jQuery.parseJSON( response );
				jQuery('#alg_export_products_fields_from_date').val(obj.start_date);
				jQuery('#alg_export_products_fields_end_date').val(obj.end_date);
				jQuery('#alg_export_products_fields_end_date_alternative').val(obj.end_date);
			},
		} );
	
	});
	
	jQuery( 'select#alg_export_customers_from_orders_fields_date_filter' ).change(function(){
		var data = {
			'action': 'alg_wc_export_admin_product_change_date_filter',
			'value': jQuery(this).val(),
			'nonce': alg_wc_export_admin_own_js.nonce
		};
		jQuery.ajax( {
			type: "POST",
			url: woocommerce_admin.ajax_url,
			data: data,
			success: function( response ) {
				response = response.trim();
				var obj = jQuery.parseJSON( response );
				jQuery('#alg_export_customers_from_orders_fields_from_date').val(obj.start_date);
				jQuery('#alg_export_customers_from_orders_fields_end_date').val(obj.end_date);
				jQuery('#alg_export_customers_from_orders_fields_end_date_alternative').val(obj.end_date);
			},
		} );
	
	});
	
} );

function load_next_product_preview_page(pagenumber){
		var data = {
			'action': 'alg_wc_export_admin_product_preview',
			'page': pagenumber,
			'nonce': alg_wc_export_admin_own_js.nonce
		};
		jQuery.ajax( {
			type: "POST",
			url: woocommerce_admin.ajax_url,
			data: data,
			success: function( response ) {
				response = response.trim();
				jQuery('#alg-wc-export-preview-content-area').html('');
				jQuery('#alg-wc-export-preview-content-area').html(response);
			},
		} );
}

function load_next_customers_from_orders_preview_page(pagenumber){
	
		var data = {
			'action': 'alg_wc_export_admin_customers_from_orders_preview',
			'page': pagenumber,
			'nonce': alg_wc_export_admin_own_js.nonce
		};
		jQuery.ajax( {
			type: "POST",
			url: woocommerce_admin.ajax_url,
			data: data,
			success: function( response ) {
				response = response.trim();
				jQuery('#alg-wc-export-preview-content-area').html('');
				jQuery('#alg-wc-export-preview-content-area').html(response);
			},
		} );
}