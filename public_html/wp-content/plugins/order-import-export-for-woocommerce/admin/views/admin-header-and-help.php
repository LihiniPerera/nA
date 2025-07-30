<?php
$ds_obj = Wbte\Oimpexp\Ds\Wbte_Ds::get_instance(WT_O_IEW_VERSION);
$wf_admin_view_path=plugin_dir_path(WT_O_IEW_PLUGIN_FILENAME).'admin/views/';

echo $ds_obj->get_component('header', array(
	'values' => array(
		'plugin_logo' => WT_O_IEW_PLUGIN_URL . 'assets/images/plugin_img.png',
		'plugin_name' => esc_html__('WebToffee Import Export', 'order-import-export-for-woocommerce'),
		'developed_by_txt' => esc_html__('Developed by', 'order-import-export-for-woocommerce')

	),
	'class' => array(''),
));

echo $ds_obj->get_component('help-widget', array(
	'values' => array(
		'items' => array(
			array('title' => esc_html__('FAQ', 'order-import-export-for-woocommerce'), 'icon' => 'chat-1', 'href' => 'https://wordpress.org/plugins/order-import-export-for-woocommerce/#:~:text=Exported%20coupon%20CSV-,FAQ,-Does%20this%20plugin', 'target' => '_blank'),
			array('title' => esc_html__('Setup guide', 'order-import-export-for-woocommerce'), 'icon' => 'book', 'href' => 'https://www.webtoffee.com/category/basic-plugin-documentation/#:~:text=WooCommerce%20customers%20list-,Order%20Import/Export,-Order/Coupon/Subscription', 'target' => '_blank'),
			array('title' => esc_html__('Contact support', 'order-import-export-for-woocommerce'), 'icon' => 'headphone', 'href' => 'https://wordpress.org/support/plugin/order-import-export-for-woocommerce/', 'target' => '_blank'),
			array('title' => esc_html__('Request a feature', 'order-import-export-for-woocommerce'), 'icon' => 'light-bulb-1'),
		),
		'hover_text' => esc_html__('Help', 'order-import-export-for-woocommerce'),
	)
));

include $wf_admin_view_path."top_upgrade_header.php";
