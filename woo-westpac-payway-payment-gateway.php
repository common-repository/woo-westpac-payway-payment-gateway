<?php
/*
Plugin Name: Westpac PayWay NET Payment Gateway for WooCommerce
Plugin URI: http://www.quickwee.com
Description: The plugin gives the functionality of processing Credit and Debit Cards on WooCommerce using Westpac PayWay NET.
Version: 2.0
Author: Quickwee
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//intialize the plugin
add_action('plugins_loaded', 'woocommerce_payway_gateway_rest_init', 0);

function woocommerce_payway_gateway_rest_init()
{
	
	if(!class_exists('WC_Payment_Gateway')) return;
 	
	/**
     * Defines
     */
    define( 'PAYWAYRESTSUPPORTURL' , 'http://quickwee.com/' );
    define( 'PAYWAYRESTDOCSURL' , 'http://quickwee.com/');
    define( 'PAYWAYRESTPLUGINPATH', plugin_dir_path( __FILE__ ) );
    define( 'PAYWAYRESTPLUGINURL', plugin_dir_url( __FILE__ ) );


	include('classes/payway-gateway-rest.php');

	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_payway_gateway($methods) 
	{
		$methods[] = 'WC_Payway_Gateway_REST';
		return $methods;
	}
 
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_payway_gateway' );
	

}
	
	
			

