<?php
		
		$this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woo_payway_net'),
                    'type' => 'checkbox',
                    'label' => __('Enable Wetpac Payway NET Payment Gateway.', 'woo_payway_net'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'woo_payway_net'),
                    'type'=> 'text',
                    'description' => __('This controls the title which user sees during checkout.', 'woo_payway_net'),
                    'default' => __('PayWay', 'woo_payway_net')),
                
				'description' => array(
                    'title' => __('Description:', 'woo_payway_net'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which user sees during checkout.', 'woo_payway_net'),
                    'default' => __('Pay securely by Credit or Debit card through PayWay Secure Servers.', 'woo_payway_net')),
                
				'publishable_key' => array(
                    'title' => __('PayWay Publishable Key', 'woo_payway_net'),
                    'type' => 'text',
                    'description' => __('This key is available at Setup NET -> REST API in the  PayWay account."')),
                
				'secret_key' => array(
                    'title' => __('PayWay Secret Key', 'woo_payway_net'),
                    'type' => 'text',
                    'description' => __('This key is available at Setup NET -> REST API in the PayWay account."')),
				
				'customer-merchant' => array(
					'title' => __( 'Merchant ID', 'woo_payway_net' ),
					'type' => 'text',
					'description' => __( 'Either use TEST (for test transactions) or 08-digit live Westpac merchant ID, provided by PayWay.', 'woo_payway_net' ),
					'default' => 'TEST'
				)
            );