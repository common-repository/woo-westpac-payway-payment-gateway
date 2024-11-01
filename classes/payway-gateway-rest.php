<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	/**
     * WC_Payway_Gateway_REST class.
     *
     * @extends WC_Payment_Gateway
     */
	
	class WC_Payway_Gateway_REST extends WC_Payment_Gateway
	{
		private static $log;
		
	
		/**
         * __construct function.
         *
         * @access public
         * @return void
         */
		public function __construct()
		{
			$this -> id = 'paywaynet';
			$this -> method_title = __( 'PayWay NET', 'woo_payway_net' );
			$this -> method_description   = __( 'PayWay NET', 'woo_payway_net' );
			
			$this -> has_fields = false;
 
			$this -> init_form_fields();
			$this -> init_settings();
			
		
			// Default values
			$this->enabled				= isset( $this->settings['enabled'] ) && $this->settings['enabled'] == 'yes' ? 'yes' : $this->default_enabled;
			$this->title 				= sanitize_text_field($this->settings['title'], 'woo_payway_net' );
			$this->description  		= sanitize_text_field($this->settings['description'], 'woo_payway_net' );
			$this->order_button_text  	= __( 'Pay securely with PayWay', 'woo_payway_net' );
		 
		
			add_action( 'woocommerce_receipt_paywaynet', array( $this, 'receipt_page' ) );
			
				
			// Supports
            $this->supports = array(
            						'products',
							);

            // Logs
			if ( $this->debug ) {
				$this->log = new WC_Logger();
			}

			// WC version
			$this->wc_version = get_option( 'woocommerce_version' );
		

			// works only if WooCommerce verison is > 2.0.0
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
			{
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} 
			else 
			{
				add_action('admin_notices', array(&$this, 'version_check'));
			}

			
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			
			 add_action( 'woocommerce_api_wc_payway_gateway_rest', array( $this, 'push_payway_rest_callback' ) );
	
			//Include the PayWay REST API
			wp_enqueue_script('Payway_script','https://api.payway.com.au/rest/v1/payway.js', false);
		}
   
		//Admin form functionality
		function init_form_fields()
		{
			include ( PAYWAYRESTPLUGINPATH . 'includes/payway-rest-admin.php' );
		}
		
		public function admin_options()
		{
			echo '<h3>'.__('PayWay NET Payment Gateway', 'woo_payway_net').'</h3>';
			echo '<p>'.__('Enter your PayWay details below.','woo_payway_net').'</p>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this -> generate_settings_html();
			echo '</table>';
 
		}
		
		function process_payment($order_id)
		{
        
			$order = new WC_Order( $order_id );
			return array(
                'result'    => 'success',
            	'redirect'	=> $order->get_checkout_payment_url( true )
            );
		
		}
	
		public static function log( $message ) 
		{
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}

			self::$log->add( 'woo_payway_net', $message );

		}
	
		public function receipt_page($order)
		{
			echo $this -> generate_PayWay_form($order);
		}
	
		function generate_PayWay_form($order_id)
		{
 
			global $woocommerce;
			$order = new WC_Order($order_id);
			
			$txnid = $order_id.'_'.date("ymds");
 
			$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
	 
			$productinfo = "Order $order_id";

			$PayWay_args = array(
				'amount' => $order -> order_total,
				'order_id' => $order_id,
				'productinfo' => $productinfo,
				'firstname' => $order -> billing_first_name,
				'lastname' => $order -> billing_last_name,
				'address1' => $order -> billing_address_1,
				'address2' => $order -> billing_address_2,
				'city' => $order -> billing_city,
				'state' => $order -> billing_state,
				'country' => $order -> billing_country,
				'zipcode' => $order -> billing_zip,
				'email' => $order -> billing_email,
				'phone' => $order -> billing_phone
			);
 
			$PayWay_args_array = array();
			foreach($PayWay_args as $key => $value)
			{
				$PayWay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
		
			return '<form action='.WC()->api_request_url( get_class( $this ) ).' method="post" 	id="PayWay_payment_form">
            ' . implode('', $PayWay_args_array) . '
            <div id="payway-credit-card"></div>
			<input type="hidden" name="action" value="push_payway_rest" />
			<input type="submit" class="button-alt"  disabled="true" id="submit_PayWay_payment_form" value="'.__('Pay via PayWay', 'payway').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'payway').'</a>
			</form>
            <script type="text/javascript">
			
				var submit = document.getElementById(\'submit_PayWay_payment_form\');
				payway.createCreditCardFrame({
				publishableApiKey: \''.$this->settings['publishable_key'].'\',
					onValid: function() { submit.disabled = false; },
				onInvalid: function() { submit.disabled = true; }
				});	
			</script>
            ';
				
			
		}
		/**
	* Send the Payment token to PayWay REST
	**/
	public function push_payway_rest_callback()
	{
		$singleUseTokenId 	= $_REQUEST['singleUseTokenId'];
		$order_id 			= $_REQUEST['order_id'];
		$merchantId 		= sanitize_text_field($this->settings['customer-merchant']);
		$secret_key			= sanitize_text_field($this->settings['secret_key']);

		//Checking valid order...
		
		if($order_id != '')
		{
			  
			$order = new WC_Order($order_id);
					  $amount = $_REQUEST['amount'];
			
			//Checking order total...
			if ($amount == $order -> order_total)
			{
				
				
				$status = $_REQUEST['status'];

				$productinfo = "Order $order_id";
			
				$service_url = 'https://api.payway.com.au/rest/v1/transactions';
			
				$curl = curl_init($service_url);
				$curl_post_data = array(
				'customerNumber' => $order->get_user_id(),
				'singleUseTokenId' => $singleUseTokenId,
				'transactionType' => 'payment',
				'principalAmount' => $amount,
				'currency' =>	'aud',
				'orderNumber' => $order_id,
				'merchantId' => $merchantId);
					
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_USERNAME, $secret_key);
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				
				//Opening Curl socket...
				
				//$fp = fopen(dirname(__FILE__).'/errorlog.txt', 'w');
				//curl_setopt($curl, CURLOPT_VERBOSE, 1);
				//curl_setopt($curl, CURLOPT_STDERR, $fp); 
				
				curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
				$curl_response = curl_exec($curl);
				if ($curl_response === false) {
					$info = curl_getinfo($curl);
					curl_close($curl);
					die('Error occured during curl exec. Additioanl info: ' . var_export($info));
				}
				else
				{
					$json = json_decode($curl_response,true);
					if ($json['status'] == 'approved')
					{
							$order -> payment_complete();
							$order -> add_order_note('PayWay payment successful. Receipt # '.$json['receiptNumber'].'. Transaction Id'. $json['transactionId']);
							$order -> add_order_note('message');
							
							wp_redirect($order->get_checkout_order_received_url());
					}
					else
					{
						$order -> add_order_note('The transaction has been declined. Transaction Id : '. $json['transactionId'].'. Response Code: '.$json['responseCode'].' Resonse Text : '.$json['responseText']);
						
						//if (function_exists('wc_add_notice'))
						//{
						wc_add_notice(__('The transaction has been declined. Transaction Id : '. $json['transactionId'].'. Response Code: '.$json['responseCode'].' Resonse Text : '.$json['responseText'].'. Please try again.'));
						
						//}
						//else */
						wp_redirect ( $order->get_cancel_order_url());	
					}	
				}
			}			
		}
	}
}