<?php
/*** load classes ***/
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-base.php";
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-settings.php";
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-setup.php";
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-credit-card.php";
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-customer.php";
require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-shortcodes.php";
//require_once SUPERLIST_PAGGI_ROOT."classes/superlist-paggi-payment-gateway.php";

/*** enqueue styles and scripts ***/
/*
function superlist_payu_styles_and_scripts() {
	wp_enqueue_style( 'superlist-payu-css', SUPERLIST_PAYU_ROOT_URL."assets/css/style.css", false );
}
add_action( 'wp_enqueue_scripts', 'superlist_payu_styles_and_scripts' );
*/
