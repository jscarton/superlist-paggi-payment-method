<?php
/*
Plugin Name: Woocommerce Paggi Payments method
Plugin URI: http://superlist.com
Description: Add paggi payments as woocommerce payment gateway
Version: 0.0.2
Author: Juan Scarton
Author URI: http://github.com/jscarton
License: GPLV3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SUPERLIST_PAGGI_PAYMENTS_VERSION', '0.0.2' );
define( 'SUPERLIST_PAGGI_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SUPERLIST_PAGGI_ROOT_URL', plugin_dir_url( __FILE__ ) );
include_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once SUPERLIST_PAGGI_ROOT."includes/superlist-paggi-loader.php";
//echo '<div class="error">';
//var_dump(is_plugin_active( 'woocommerce-autoship/wc-autoship.php' ));
//echo '</div>';

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) &&  is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' )) {
	
	function superlist_paggi_activate() {
		SuperlistPaggiSetup::activate();
	}
	register_activation_hook( __FILE__, 'superlist_paggi_activate' );
	function superlist_paggi_deactivate() {
		SuperlistPaggiSetup::deactivate();
	}
	register_deactivation_hook( __FILE__, 'superlist_paggi_deactivate' );
	function superlist_paggi_uninstall() {
		SuperlistPaggiSetup::uninstall();
	}
	register_uninstall_hook( __FILE__, 'superlist_paggi_uninstall' );
	
	//register shortcodes
	$shortcodes=new SuperlistPaggiShortcodes();
	$shortcodes->register();

	function superlist_paggi_load_gateway_class() {
		// Initialize WooCommerce
		if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) && function_exists( 'WC' ) ) {
			WC();
			// Include gateway class
			require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-payment-gateway.php";
		}
	}
	add_action( 'plugins_loaded', 'superlist_paggi_load_gateway_class' );

	function superlist_paggi_payments_register_gateway( $methods ) {
		if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) ) {
			$methods[] = 'SuperlistPaggiPaymentGateway';
		}
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'superlist_paggi_payments_register_gateway' );

	function superlist_paggi_payments_load_for_functions()
	{
		require_once SUPERLIST_PAGGI_ROOT."includes/superlist-paggi-loader.php";		
	}
	add_action ("superlist_paggi_payments_functions_init",'superlist_paggi_payments_load_for_functions');
	
	if ( is_admin() ) {
		// Register admin settings
		$settings = new SuperlistPaggiSettings();
		$settings->register();
		
	}
}