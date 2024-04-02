<?php
/**
 * Cobru para WooCommerce
 * 
 * Plugin oficial del API de Cobru para ser usado con WooCommerce en Wordpress.
 * 
 * @link              https://cobru.co/desarrolladores/
 * @since             1.0.0
 * @package           cobru-for-wc
*
 * @wordpress-plugin 
 * Plugin Name: 	Cobru for WC
 * Plugin URI: 		https://cobru.co/desarrolladores/
 * Description: 	Plugin oficial del API de Cobru para ser usado con WooCommerce en Wordpress.
 * Author: 			COBRU.CO
 * Author URI: 		https://github.com/CobruApp/cobru-for-wc
 * Version: 		1.2.6-eventu
 * License: 		GPLv3
 * License URI: 	https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: 	cobru-for-wc
 * Domain Path:  	/languages
*/

include 'classes/class-wc-cobru-api.php';
include 'classes/class-wc-gateway-cobru.php';
include 'classes/class-wc-cobru-checkout.php';
include 'classes/class-wc-cobru-rest-api.php';

define( 'COBRU_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'COBRU_PLUGIN_VER', '1.2.6');

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
	load_plugin_textdomain( 'cobru-for-wc', false, $plugin_rel_path );
}
add_action('plugins_loaded', 'cobru_load_language');