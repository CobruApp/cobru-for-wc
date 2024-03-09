<?php
/**
 * Plugin Name: WooCommerce Cobru Gateway
 * Plugin URI: https://cobru.co/desarrolladores/
 * Description: WooCommerce implementation of Cobru API Gateway
 * Author: COBRU.CO
 * Author URI: https://github.com/0kCobru/woocommerce
 * Text Domain: cobru
 * Version: 1.2.0
 * 
 */

include 'classes/class-wc-cobru-api.php';
include 'classes/class-wc-gateway-cobru.php';
include 'classes/class-wc-cobru-checkout.php';
include 'classes/class-wc-cobru-rest-api.php';

define( 'COBRU_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
add_filter( 'woocommerce_payment_gateways', 'cobru_add_gw' );

/**
 * Add cobru gateway to WooCommerce.
 *
 * @param array $gateways List of gateways.
 *
 * @return array New list of gateways.
 */
function cobru_add_gw( $gateways ) {
	$gateways[] = 'WC_Gateway_Cobru';
	return $gateways;
}

/**
 * Loads plugin's language file.
 */
function cobru_load_language() {
	$plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages';
	load_plugin_textdomain( 'cobru', false, $plugin_rel_path );
}
add_action('plugins_loaded', 'cobru_load_language');