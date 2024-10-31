<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*
Plugin Name: Seylan Bank IPG
Plugin URI: seylanipg.oganro.net
Description: Seylan Bank Payment Gateway from Oganro (Pvt)Ltd.
Version: 1.1
Author: Oganro
Author URI: www.oganro.com
*/

//-----------------------------------------------------
// Initiating Methods to run on plugin activation
// ----------------------------------------------------
register_activation_hook( __FILE__, 'jal_install_seylan' );


global $jal_db_version;
$jal_db_version = '1.0';

//-----------------------------------------------------
// Methods to create database table
// ----------------------------------------------------
function jal_install_seylan() {
	
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

	$table_name = $wpdb->prefix . 'seylan_bank_ipg';
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}


	$sql = "CREATE TABLE $table_name ( 
	`id` INT NOT NULL AUTO_INCREMENT,
	`transaction_type_code` VARCHAR(256) NOT NULL ,
	`transaction_id` INT NOT NULL ,
	`amount` VARCHAR(20) NOT NULL ,
	`currency_code` VARCHAR(256) NOT NULL ,
	`merchant_reference_no` VARCHAR(256) NOT NULL ,
	`status` INT NOT NULL , `pg_error_code` INT NOT NULL ,
	`pg_error_detail` VARCHAR(256) NOT NULL ,
	`pg_error_msg` VARCHAR(256) NOT NULL ,
	 UNIQUE KEY id (id) ) $charset_collate; ";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );
}

//-----------------------------------------------------
// Initiating Methods to run after plugin loaded
// ----------------------------------------------------
add_action('plugins_loaded', 'woocommerce_seylan_bank_gateway', 0);


function woocommerce_seylan_bank_gateway(){
	
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Seylan extends WC_Payment_Gateway{
  	
    public function __construct(){
    	
	  $plugin_dir = plugin_dir_url(__FILE__);
      $this->id = 'seylanipg';	  
	  $this->icon = apply_filters('woocommerce_Paysecure_icon', ''.$plugin_dir.'seylan.jpg');
      $this->medthod_title = 'SeylanIPG';
      $this->has_fields = false;
 
      $this->init_form_fields();
      $this->init_settings(); 	  
      $this->title 					= $this -> settings['title'];
      $this->description 			= $this -> settings['description'];
	  $this->pg_domain 				= $this -> settings['pg_domain'];	  
      $this->pg_instance_id 		= $this -> settings['pg_instance_id'];
	  $this->merchant_id 			= $this -> settings['merchant_id'];
	  $this->currency_code 			= $this -> settings['currency_code'];
	  $this->hash_key 				= $this -> settings['hash_key'];	
	  $this->sucess_responce_code	= $this-> settings['sucess_responce_code'];	  
	  $this->responce_url_sucess	= $this-> settings['responce_url_sucess'];
	  $this->responce_url_fail		= $this-> settings['responce_url_fail'];	  	  
	  $this->checkout_msg			= $this-> settings['checkout_msg'];	  
	   
      $this->msg['message'] 	= "";
      $this->msg['class'] 		= "";
 
      add_action('init', array(&$this, 'check_SeylanIPG_response'));	  
	  	  
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        	add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
		} else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        
      add_action('woocommerce_receipt_'.$this->id, array(&$this, 'receipt_page'));
	 
   }
	
    function init_form_fields(){
 		
       $this -> form_fields = array(
                'enabled' 	=> array(
                    'title' 		=> __('Enable/Disable', 'ogn'),
                    'type' 			=> 'checkbox',
                    'label' 		=> __('Enable Seylan IPG Module.', 'ognro'),
                    'default' 		=> 'no'),
					
                'title' 	=> array(
                    'title' 		=> __('Title:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('This controls the title which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Seylan IPG', 'ognro')),
				
				'description'=> array(
                    'title' 		=> __('Description:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('This controls the description which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Seylan IPG', 'ognro')),	
					
				'pg_domain' => array(
                    'title' 		=> __('PG Domain:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('IPG data submiting to this URL', 'ognro'),
                    'default' 		=> __('https://www.paystage.com/AccosaPG/verify.jsp', 'ognro')),	
					
				'pg_instance_id' => array(
                    'title' 		=> __('PG instance ID:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Unique ID for the merchant acc, given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
				
				'merchant_id' => array(
                    'title' 		=> __('Merchant Id:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('collection of intiger numbers, given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
						
					
				'currency_code' => array(
                    'title' 		=> __('Currency Code:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Three character ISO code of the currency such as 356 for INR . ', 'ognro'),
                    'default' 		=> __(get_woocommerce_currency(), 'ognro')),
				
				
				'hash_key' => array(
                    'title' 		=> __('Hash Key:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Hash Key . ', 'ognro'),
                    'default' 		=> __('', 'ognro')),
							
								
				'checkout_msg' => array(
                    'title' 		=> __('Checkout Message:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('Message display when checkout'),
                    'default' 		=> __('Thank you for your order, please click the button below to pay with the secured Seylan Bank payment gateway.', 'ognro')),		
				
				'sucess_responce_code' => array(
                    'title' 		=> __('Sucess responce code :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('50020 - Sucess, 50097 - Test Transaction passed ', 'ognro'),
                    'default' 		=> __('50020', 'ognro')),	
				
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
		
		
		echo '<h3>'.__('Seylan bank online payment gateway', 'ognro').'</h3>';
        echo '<p>'.__('<a target="_blank" href="http://www.oganro.com/">Oganro</a> is a fresh and dynamic web design and custom software development company with offices based in East London, Essex, Brisbane (Queensland, Australia) and in Colombo (Sri Lanka).').'</p>';
       // echo'<a href="http://www.oganro.com/wordpress-plug-in-support" target="_blank"><img class="wpimage" alt="payment gateway" src="../wp-content/plugins/seylan-bank-ipg/plug-inimg.jpg" width="100%"></a>';
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
    	
    	
        global $wpdb; 
        global $woocommerce;
 
        $order = new WC_Order($order_id);
		$productinfo = "Order $order_id"; 
		
		$currency_code 	= $this->PurchaseCurrency;
		$curr_symbole 	= get_woocommerce_currency();		
								
		$table_name = $wpdb->prefix . 'seylan_bank_ipg';		
		$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_reference_no = '".$order_id."'" );		
		if($check_oder > 0){
			$check_oder;
			$wpdb->update( 
				$table_name, 
				array(					

					'transaction_type_code'     => '', 
					'transaction_id'     		=> '', 
					'amount'        			=> ($order->order_total),
					'currency_code' 			=> $this->currency_code, 					
					'status' 					=> ''
 
				), 
				array( 'merchant_reference_no' => $order_id ));   
				
		}else{
			
				$wpdb->insert(
				$table_name, 
				array( 

					'transaction_type_code'     => '', 
					'transaction_id'     		=> '', 
					'amount'        			=> ($order->order_total),
					'currency_code' 			=> $this->currency_code, 					
					'status' 					=> '',
					'merchant_reference_no'     => $order_id
					),
					array( '%s', '%d' ) );
		}	
  
		$order_format_value = ($order->order_total*100);

		$perform = 'initiatePaymentCapture#sale';
		$messageHash = $this -> pg_instance_id."|".$this -> merchant_id."|".$perform."|".$this -> currency_code."|".$order_format_value."|".$order_id."|".$this -> hash_key ."|";
		$message_hash = "CURRENCY:7:".base64_encode(sha1($messageHash, true));
        
          $form_args = array(		  
		  'pg_instance_id' => $this -> pg_instance_id,
          'merchant_id'   => $this -> merchant_id,          
          'perform'   => $perform,    
          'amount' => $order_format_value,
          'currency_code' => $this -> currency_code,
          'merchant_reference_no' => $order_id,
		  'message_hash' => $message_hash, 
		  'order_desc' => 'Test',
		  );
		  
        $form_args_array = array();
        foreach($form_args as $key => $value){
          $form_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }
        return '<p>'.$percentage_msg.'</p>
		<p>Total amount will be <b>'.$curr_symbole.' '.number_format(($order->order_total)).'</b></p>
		<form action="'.$this->pg_domain.'" method="post" id="merchantForm">
             ' . implode('', $form_args_array) . ' 
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
    function check_SeylanIPG_response(){
        global $woocommerce;		
		if(isset($_POST['merchant_reference_no']) && isset($_POST['transaction_id'])){	
			
			$transactionTypeCode=$_POST["transaction_type_code"];
			$installments		=$_POST["installments"];
			$transactionId		=$_POST["transaction_id"];

			$amount				=$_POST["amount"];
			$exponent			=$_POST["exponent"];
			$currencyCode		=$_POST["currency_code"];
			$merchantReferenceNo=$_POST["merchant_reference_no"];

			$status				=$_POST["status"];
			$eci				=$_POST["3ds_eci"];
			$pgErrorCode		=$_POST["pg_error_code"];

			$pgErrorDetail		=$_POST["pg_error_detail"];
			$pgErrorMsg			=$_POST["pg_error_msg"];

			$messageHash		=$_POST["message_hash"];


			$messageHashBuf		= $this->pg_instance_id."|".$this->merchant_id."|".$transactionTypeCode."|".$installments."|".$transactionId."|".$amount."|".$exponent."|".$currencyCode."|".$merchantReferenceNo."|".$status."|".$eci."|".$pgErrorCode."|".$this->hash_key."|";

			$messageHashClient 	= "13:".base64_encode(sha1($messageHashBuf, true));

			$hashMatch			=false;
			if ($messageHash==$messageHashClient){
			  $hashMatch=true;
			} else {
			  $hashMatch=false;
			}
			
			$order_id = $merchantReferenceNo;			
			if($order_id != ''){				
				$order 	= new WC_Order($order_id);				
				if($status == $this->sucess_responce_code && $hashMatch ){
				global $wpdb;
				global	$message_hash;				
				$table_name = $wpdb->prefix . 'seylan_bank_ipg';				
				$wpdb->update( 
				$table_name, 
				array( 
				  'transaction_id' 			=> $transactionId,         
				  'transaction_type_code'   => $transactionTypeCode,    
				  'status' 					=> $status,
				  'pg_error_code'     		=> $pgErrorCode, 
				  'pg_error_detail'        	=> $pgErrorDetail,
				  'pg_error_msg'     		=> $pgErrorMsg
				), 
				array( 'merchant_reference_no' => $order_id ));             
                                	
                    $order->add_order_note('Seylan payment successful<br/>Unnique Id from Seylan IPG: '.$transactionId);
                    $order->add_order_note($this->msg['message']);
                    $woocommerce->cart->empty_cart();
					
					$mailer = $woocommerce->mailer();

					$admin_email = get_option( 'admin_email', '' );

					$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$order_id.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));	
					$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );					
										
										
					$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$order_id.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));	
					$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );

					$order->payment_complete();							

					wp_redirect( $this->responce_url_sucess, 200 ); exit;
					
				}else{
					$order->update_status('failed');
                    $order->add_order_note('Failed - Code'.$pgErrorCode);
                    $order->add_order_note($this->msg['message']);
					
					global $wpdb;		
					$table_name = $wpdb->prefix . 'seylan_bank_ipg';	
					$wpdb->update( 
					$table_name, 
					array( 
					  'transaction_id' 			=> $transactionId,         
					  'transaction_type_code'   => $transactionTypeCode,    
					  'status' 					=> $status,
					  'pg_error_code'     		=> $pgErrorCode, 
					  'pg_error_detail'        	=> $pgErrorDetail,
					  'pg_error_msg'     		=> $pgErrorMsg
					), 
					array( 'merchant_reference_no' => $order_id ));
					
					wp_redirect( $this->responce_url_fail, 200 ); exit;
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


	if(isset($_POST['merchant_reference_no']) && isset($_POST['transaction_id'])){	
		$WC = new WC_Seylan();
	}

   
   function woocommerce_add_seylan_gateway($methods) {
       $methods[] = 'WC_Seylan';
       return $methods;
   }

	 	
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_seylan_gateway' );
}

