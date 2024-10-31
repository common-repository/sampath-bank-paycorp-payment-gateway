<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: Sampath Bank paycorp IPG
Plugin URI: sampathipg.oganro.net
Description: Sampath Bank Paycorp Payment Gateway from Oganro (Pvt)Ltd.
Version: 1.1
Author: Oganro
Author URI: www.oganro.com
*/

//-----------------------------------------------------
// Initiating Methods to run on plugin activation
// ----------------------------------------------------
register_activation_hook( __FILE__, 'jal_install_3' );


global $jal_db_version;
$jal_db_version = '1.0';

//-----------------------------------------------------
// Methods to create database table
// ----------------------------------------------------
function jal_install_3() {
	
	$plugin_path = plugin_dir_path( __FILE__ );
	$file = $plugin_path.'includes/auth.php';
  	if(file_exists($file)){
  		include 'includes/auth.php';
  		$auth = new Auth();
  		$auth->check_auth();
  		if ( !$auth->get_status() ) {
  			deactivate_plugins( plugin_basename( __FILE__ ) );
			if($auth->get_code() == 2){
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/plugin/profile'>www.oganro.com/profile</a> and change the domain" ,"Activation Error","ltr" );
			}else{
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com'>www.oganro.com</a> for more info" ,"Activation Error","ltr" );
			}
		}
  	}else{
  		deactivate_plugins( plugin_basename( __FILE__ ) );
  		wp_die( "<h1>Buy serial key to activate this plugin</h1><br><img src=".site_url('wp-content/plugins/sampath_paycorp_ipg/support.jpg')." style='width:700px;height:auto;' /><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
  	}
	
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'sampath_bank_ipg';
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$sql = "CREATE TABLE $table_name (
	id int(9) NOT NULL AUTO_INCREMENT,
	transaction_id VARCHAR(30) NOT NULL,
	merchant_reference_no VARCHAR(20) NOT NULL,
	transaction_type_code VARCHAR(20) NOT NULL,
	currency_code VARCHAR(10) NOT NULL,
	amount VARCHAR(20) NOT NULL,
	status VARCHAR(6) NOT NULL,
	or_date DATE NOT NULL,
	message Text NOT NULL,
	settlement_date VARCHAR(100) NOT NULL,
	auth_code VARCHAR(100) NOT NULL,
	cvc_response VARCHAR(100) NOT NULL,
	UNIQUE KEY id (id)
	) $charset_collate;";


	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );
}

//-----------------------------------------------------
// Initiating Methods to run after plugin loaded
// ----------------------------------------------------
add_action('plugins_loaded', 'woocommerce_sampath_bank_gateway', 0);


function woocommerce_sampath_bank_gateway(){
	
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Sampath extends WC_Payment_Gateway{
  	
    public function __construct(){
    	
	  $plugin_dir = plugin_dir_url(__FILE__);
      $this->id = 'SampathIPG';	  
	  $this->icon = apply_filters('woocommerce_Paysecure_icon', ''.$plugin_dir.'sampath.jpg');
      $this->medthod_title = 'SampathIPG';
      $this->has_fields = false;
 
      $this->init_form_fields();
      $this->init_settings(); 
	  
      $this->title 					= $this -> settings['title'];
      $this->description 			= $this -> settings['description'];
      $this->client_id 				= $this -> settings['client_id'];
	  $this->customer_id 			= $this -> settings['customer_id'];
	  $this->transaction_type 		= $this -> settings['transaction_type'];
	  $this->currency_code 			= $this -> settings['currency_code'];	  	  
	  $this->hmac_secret 			= $this -> settings['hmac_secret'];	  
      $this->auth_token 			= $this-> settings['auth_token'];
      $this->liveurl 				= $this-> settings['pg_domain'];
	  $this->sucess_responce_code	= $this-> settings['sucess_responce_code'];	  
	  $this->responce_url_sucess	= $this-> settings['responce_url_sucess'];
	  $this->responce_url_fail		= $this-> settings['responce_url_fail'];	  	  
	  $this->checkout_msg			= $this-> settings['checkout_msg'];	  
	   
      $this->msg['message'] 	= "";
      $this->msg['class'] 		= "";
 
      add_action('init', array(&$this, 'check_SampathIPG_response'));	  
	  	  
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        	add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
		} else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        
      add_action('woocommerce_receipt_SampathIPG', array(&$this, 'receipt_page'));
	 
   }
	
    function init_form_fields(){
 		
       $this -> form_fields = array(
                'enabled' 	=> array(
                    'title' 		=> __('Enable/Disable', 'ogn'),
                    'type' 			=> 'checkbox',
                    'label' 		=> __('Enable Sampath IPG Module.', 'ognro'),
                    'default' 		=> 'no'),
					
                'title' 	=> array(
                    'title' 		=> __('Title:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('This controls the title which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Sampath IPG', 'ognro')),
				
				'description'=> array(
                    'title' 		=> __('Description:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('This controls the description which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Sampath IPG', 'ognro')),	
					
				'pg_domain' => array(
                    'title' 		=> __('PG Domain:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('IPG data submiting to this URL', 'ognro'),
                    'default' 		=> __('https://sampath.paycorp.com.au/rest/service/proxy', 'ognro')),	
					
				'client_id' => array(
                    'title' 		=> __('PG Client Id:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Unique ID for the merchant acc, given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
				
				'customer_id' => array(
                    'title' 		=> __('PG Customer Id:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('collection of intiger numbers, given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
				
				'transaction_type' => array(
                    'title' 		=> __('PG Transaction Type:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Indicates the transaction type, given by bank.', 'ognro'),
                    'default' 		=> __('PURCHASE', 'ognro')),
				
				'hmac_secret' => array(
                    'title' 		=> __('HMAC Secret:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Collection of mix intigers and strings , given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
					
				'auth_token' => array(
                    'title' 		=> __('Auth Token:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Collection of mix intigers and strings , given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
					
				'currency_code' => array(
                    'title' 		=> __('currency:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Three character ISO code of the currency such as LKR,USD. ', 'ognro'),
                    'default' 		=> __(get_woocommerce_currency(), 'ognro')),
					
				'sucess_responce_code' => array(
                    'title' 		=> __('Sucess responce code :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('00 - Transaction Passed', 'ognro'),
                    'default' 		=> __('00', 'ognro')),	  
								
				'checkout_msg' => array(
                    'title' 		=> __('Checkout Message:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('Message display when checkout'),
                    'default' 		=> __('Thank you for your order, please click the button below to pay with the secured Sampath Bank payment gateway.', 'ognro')),		
					
				'responce_url_sucess' => array(
                    'title' 		=> __('Sucess redirect URL :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('After payment is sucess redirecting to this page.'),
                    'default' 		=> __('http://your-site.com/thank-you-page/', 'ognro')),
				
				'responce_url_fail' => array(
                    'title' 		=> __('Fail redirect URL :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('After payment if there is an error redirecting to this page.', 'ognro'),
                    'default' 		=> __('http://your-site.com/error-page/', 'ognro'))	
            );
    }
 
    //----------------------------------------
    //	Generate admin panel fields
    //----------------------------------------
	public function admin_options(){
		
		$plugin_path = plugin_dir_path( __FILE__ );
		$file = $plugin_path.'includes/auth.php';
	  	if(file_exists($file)){
	  		include 'includes/auth.php';
	  		$auth = new Auth();
	  		$auth->check_auth();
	  		if ( !$auth->get_status() ) {
	  			deactivate_plugins( plugin_basename( __FILE__ ) );
	  			wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com'>www.oganro.com</a> for more info" ,"Activation Error","ltr" );
	  		}
	  	}else{
	  		deactivate_plugins( plugin_basename( __FILE__ ) );
	  		wp_die( "<h1>Buy serial key to activate this plugin</h1><br><img src=".site_url('wp-content/plugins/sampath_paycorp_ipg/support.jpg')." style='width:700px;height:auto;' /><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
	  	}
		
	   echo '<style type="text/css">
		.wpimage {
		margin:3px;
		float:left;
		}		
		</style>';
    	echo '<h3>'.__('Sampath bank online payment gateway', 'ognro').'</h3>';
        echo '<p>'.__('<a target="_blank" href="http://www.oganro.com/">Oganro</a> is a fresh and dynamic web design and custom software development company with offices based in East London, Essex, Brisbane (Queensland, Australia) and in Colombo (Sri Lanka).').'</p>';
        //echo'<a href="http://www.oganro.com/support-tickets" target="_blank"><img src="/wp-content/plugins/sampath-bank-ipg/plug-inimg.jpg" alt="payment gateway" class="wpimage"/></a>';
        
        echo '<table class="form-table">';        
        $this->generate_settings_html();
        echo '</table>'; 
    }
	

    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }

    //----------------------------------------
    //	Generate checkout form
    //----------------------------------------
    function receipt_page($order){        		
		global $woocommerce;
        $order_details = new WC_Order($order);
        
        echo $this->generate_ipg_form($order);		
		echo '<br>'.$this->checkout_msg.'</b>';        
    }
    	
    public function generate_ipg_form($order_id){
    	
    	
    	include 'au.com.gateway.client/GatewayClient.php'; 
    	include 'au.com.gateway.client.config/ClientConfig.php';
    	include 'au.com.gateway.client.component/RequestHeader.php';
    	include 'au.com.gateway.client.component/CreditCard.php'; 
    	include 'au.com.gateway.client.component/TransactionAmount.php'; 
    	include 'au.com.gateway.client.component/Redirect.php'; 
    	include 'au.com.gateway.client.facade/BaseFacade.php'; 
    	include 'au.com.gateway.client.facade/Payment.php'; 
    	include 'au.com.gateway.client.payment/PaymentInitRequest.php'; 
    	include 'au.com.gateway.client.payment/PaymentInitResponse.php';
    	include 'au.com.gateway.client.root/PaycorpRequest.php'; 
    	include 'au.com.gateway.client.utils/IJsonHelper.php'; 
    	include 'au.com.gateway.client.helpers/PaymentInitJsonHelper.php'; 
    	include 'au.com.gateway.client.utils/HmacUtils.php'; 
    	include 'au.com.gateway.client.utils/CommonUtils.php';
    	include 'au.com.gateway.client.utils/RestClient.php'; 
    	include 'au.com.gateway.client.enums/TransactionType.php'; 
    	include 'au.com.gateway.client.enums/Version.php'; 
    	include 'au.com.gateway.client.enums/Operation.php';
    	include 'au.com.gateway.client.facade/Vault.php'; 
    	include 'au.com.gateway.client.facade/Report.php'; 
    	include 'au.com.gateway.client.facade/AmexWallet.php'; 
 
        global $wpdb;
        global $woocommerce;
        
        $order          = new WC_Order($order_id);
		$productinfo    = "Order $order_id";		
        $currency_code  = $this -> currency_code;		
		$curr_symbole 	= get_woocommerce_currency();		
		
		/* $messageHash = $this -> pg_instance_id."|".$this -> merchant_id."|".$this -> perform."|".$currency_code."|".(($order -> order_total) * 100)."|".$order_id."|".$this	-> hash_key."|";
		$message_hash = "CURRENCY:7:".base64_encode(sha1($messageHash, true)); */
		
						
		$table_name = $wpdb->prefix . 'sampath_bank_ipg';		
		$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_reference_no = '".$order_id."'" );
        
		if($check_oder > 0){
			$wpdb->update( 
				$table_name, 
				array( 
					'transaction_id' 		=> '',					
					'transaction_type_code' => '',
					'currency_code' 		=> $this->currency_code,
					'amount' 				=> ($order->order_total),
					'status' 				=> 0000,
					'or_date' 				=> date('Y-m-d'),
					'message' 				=> '',
					'settlement_date' 		=> '',
					'auth_code' 			=> '',
					'cvc_response' 			=> ''
				), 
				array( 'merchant_reference_no' => $order_id ));								
		}else{
			
			$wpdb->insert(
				$table_name, 
				array( 
					'transaction_id'		=> '', 
					'merchant_reference_no'	=> $order_id, 
					'transaction_type_code'	=> '', 
					'currency_code'			=> $this->currency_code, 
					'amount'				=> $order->order_total, 
					'status'				=> 0000,
					'or_date' 				=> date('Y-m-d'), 
					'message'				=> '', 
					'settlement_date'		=> '', 
					'auth_code'				=> '', 
					'cvc_response'			=> ''
					),
					array( '%s', '%d' ) );					
		}		
		
		date_default_timezone_set('Asia/Colombo');
				
		$ClientConfig = new ClientConfig();
		$ClientConfig->setServiceEndpoint($this -> liveurl);
		$ClientConfig->setAuthToken($this->auth_token);
		$ClientConfig->setHmacSecret($this->hmac_secret);
		
		$Client = new GatewayClient($ClientConfig);
		
		$initRequest = new PaymentInitRequest();
		$initRequest->setClientId($this->client_id);
		$initRequest->setTransactionType($this->transaction_type);
		$initRequest->setClientRef($order_id);
		
		$transactionAmount = new TransactionAmount();
		$transactionAmount->setTotalAmount(0);
		$transactionAmount->setServiceFeeAmount(0);
		$transactionAmount->setPaymentAmount((($order -> order_total ) * 100 ));
		$transactionAmount->setCurrency($this->currency_code);
		$initRequest->setTransactionAmount($transactionAmount);
		
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$actual_link = $protocol."".$_SERVER[HTTP_HOST]."".$_SERVER[REQUEST_URI];
		$redirect = new Redirect();
		$redirect->setReturnUrl($actual_link);
		$redirect->setReturnMethod("POST");
		$initRequest->setRedirect($redirect);
		
		$initResponse = $Client->payment()->init($initRequest);
		
       
        return '<p>'.$percentage_msg.'</p>
		<p>Total amount will be <b>'.$curr_symbole.' '.number_format($order->order_total,2).'</b></p>
			<form action="'.$initResponse->getPaymentPageUrl().'" method="get" id="merchantForm">
			<input type="hidden" value="'.$initResponse->getReqid().'" name="reqid"/>
			<input type="submit" class="button-alt" id="submit_ipg_payment_form" value="'.__('Pay via Credit Card', 'ognro').'" /> 
			<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'ognro').'</a>
			</form>';
        
    }
    	
    function process_payment($order_id){
        $order = new WC_Order($order_id);
        return array('result' => 'success', 'redirect' => add_query_arg('order',           
		   $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
        );
    }
 
   	 
    //----------------------------------------
    //	Save response data and redirect
    //----------------------------------------
    function check_SampathIPG_response(){
    	
    	
        global $wpdb;
        global $woocommerce;
        
        
		if(isset($_POST['clientRef']) && isset($_POST['reqid'])){	
			

			include 'au.com.gateway.client/GatewayClient.php';
			include 'au.com.gateway.client.config/ClientConfig.php';
			include 'au.com.gateway.client.component/RequestHeader.php';
			include 'au.com.gateway.client.component/CreditCard.php';
			include 'au.com.gateway.client.component/TransactionAmount.php';
			include 'au.com.gateway.client.component/Redirect.php';
			include 'au.com.gateway.client.facade/BaseFacade.php';
			include 'au.com.gateway.client.facade/Payment.php';
			include 'au.com.gateway.client.root/PaycorpRequest.php';
			include 'au.com.gateway.client.root/PaycorpResponse.php';
			include 'au.com.gateway.client.payment/PaymentCompleteRequest.php';
			include 'au.com.gateway.client.payment/PaymentCompleteResponse.php';
			include 'au.com.gateway.client.utils/IJsonHelper.php';
			include 'au.com.gateway.client.helpers/PaymentCompleteJsonHelper.php';
			include 'au.com.gateway.client.utils/HmacUtils.php';
			include 'au.com.gateway.client.utils/CommonUtils.php';
			include 'au.com.gateway.client.utils/RestClient.php';
			include 'au.com.gateway.client.enums/TransactionType.php';
			include 'au.com.gateway.client.enums/Version.php';
			include 'au.com.gateway.client.enums/Operation.php';
			include 'au.com.gateway.client.facade/Vault.php';
			include 'au.com.gateway.client.facade/Report.php';
			include 'au.com.gateway.client.facade/AmexWallet.php';
			
			date_default_timezone_set('Asia/Colombo');
			
			$ClientConfig = new ClientConfig();
			$ClientConfig->setServiceEndpoint($this -> liveurl);
			$ClientConfig->setAuthToken($this->auth_token);
			$ClientConfig->setHmacSecret($this->hmac_secret);
			
			$Client = new GatewayClient($ClientConfig);
			
			$completeRequest = new PaymentCompleteRequest();
			$completeRequest->setClientId($this->client_id);
			$completeRequest->setReqid($_POST['reqid']);
			
			$completeResponse = $Client->payment()->complete($completeRequest);
			
			$order_id = $completeResponse->getClientRef();
			
			if($order_id != ''){				
				$order 	= new WC_Order($order_id);
				
				
				if($this->sucess_responce_code == $completeResponse->getResponseCode()){
						
				$table_name = $wpdb->prefix . 'sampath_bank_ipg';	
				$wpdb->update( 
				$table_name, 
				array( 
					'transaction_id' => $completeResponse->getTxnReference(),					
					'transaction_type_code' => $completeResponse->getTransactionType(),					
					'status' => $completeResponse->getResponseCode(),
					'message' => $completeResponse->getResponseText(),
					'settlement_date' => $completeResponse->getSettlementDate(),
					'auth_code' => $completeResponse->getAuthCode(),
					'cvc_response' => $completeResponse->getCvcResponse()
				), 
				array( 'merchant_reference_no' => $completeResponse->getClientRef() ));
									
                    $order->add_order_note('Sampath payment successful<br/>Unnique Id from Sampath IPG: '.$completeResponse->getTxnReference());
                    $order->add_order_note($this->msg['message']);
                    $woocommerce->cart->empty_cart();
					
					$mailer = $woocommerce->mailer();

					$admin_email = get_option( 'admin_email', '' );

					$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$completeResponse->getTxnReference().' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));	
					$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );					
										
										
					$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$completeResponse->getTxnReference().' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));	
					$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );

					$order->payment_complete();
					wp_redirect( $this->responce_url_sucess ); exit;
					
				}else{	
					
					global $wpdb;
                    $order->update_status('failed');
                    $order->add_order_note('Failed - Code'.$completeResponse->getResponseCode());
                    $order->add_order_note($this->msg['message']);
							
					$table_name = $wpdb->prefix . 'sampath_bank_ipg';	
					$wpdb->update( 
					$table_name, 
					array( 
						'transaction_id' => $completeResponse->getTxnReference(),				
						'transaction_type_code' => $completeResponse->getTransactionType(),					
						'status' => $completeResponse->getResponseCode(),
						'message' => $completeResponse->getResponseText(),
						'settlement_date' => $completeResponse->getSettlementDate(),
						'auth_code' => $completeResponse->getAuthCode(),
						'cvc_response' => $completeResponse->getCvcResponse()
					), 
					array( 'merchant_reference_no' => $completeResponse->getClientRef() ));
					
					wp_redirect( $this->responce_url_fail ); exit();
				}				 
			}
			
		}
    }
    
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';            
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }            
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
    
}


	if(isset($_POST['clientRef']) && isset($_POST['reqid'])){
		$WC = new WC_Sampath();
	}

   
   function woocommerce_add_sampath_gateway($methods) {
       $methods[] = 'WC_Sampath';
       return $methods;
   }
	 	
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sampath_gateway' );
}

