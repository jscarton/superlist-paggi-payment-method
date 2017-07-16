<?php
$wc_autoship_path = dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-autoship/';
require_once( $wc_autoship_path . 'classes/wc-autoship.php' );
require_once( $wc_autoship_path . 'classes/payment-gateway/wc-autoship-payment-gateway.php' );
require_once( $wc_autoship_path . 'classes/payment-gateway/wc-autoship-payment-response.php' );
require_once( $wc_autoship_path . 'classes/wc-autoship-customer.php' );

class SuperlistPaggiPaymentGateway extends WC_Autoship_Payment_Gateway{

	private $generated_token=false;

	public function __construct() {
		// WooCommerce fields
		$this->id = 'superlist_paggi';
		$this->icon = '';
		$this->order_button_text = __( 'Checkout with your Credit Card', 'superlist-paggi' );
		$this->has_fields = true;
		$this->method_title = __( "Superlist + Paggi Payment Gateway ", 'superlist-paggi' );
		$this->method_description = __( 
			"Paggi payment method supporting creditcard tokenization for safe store of payment information",
			'superlist-paggi'
		);
		$this->description = $this->method_description;
		//$this->notify_url = admin_url( '/admin-ajax.php?action=wc_autoship_paypal_payments_ipn_callback' );
		// WooCommerce settings
		$this->init_form_fields();
		$this->init_settings();
		// Assign settings
		$this->title=__( 'Checkout with your Credit Card', 'superlist-paggi' );
		$settingObj=new SuperlistPaggiSettings();
		$this->plugin_settings = new SuperlistPaggiBase($settingObj->getPluginSettings());
		$this->minimum_order_amount=floatval($settingObj->minimum_order_amount);
		// Supports
		$this->supports = array(
			'refunds'
		);
		// Payment gateway hooks
		add_action( 
			'woocommerce_update_options_payment_gateways_' . $this->id, 
			array( $this, 'process_admin_options' )
		);
//		add_action(
//			'woocommerce_api_wc_autoship_paypal_gateway',
//			array( $this, 'api_callback' )
//		);
	}

	public function init_form_fields()
	{
		$this->form_fields = array(
								'enabled' => array(
									'title'   => __( 'Enable/Disable', 'wc-autoship' ),
									'type'    => 'checkbox',
									'label'   => __( 'Enable ' . $this->method_title, 'wc-autoship' ),
									'default' => 'yes',
									'id'      => 'superlist_paggi_method_enabled'
								),
								'title'   => array(
									'title'       => __( 'Checkout Title', 'wc-autoship' ),
									'type'        => 'text',
									'description' => __( 
										'This controls the title which the user sees during checkout.', 'wc-autoship'
									),
									'default'     => __( 'PayPal', 'wc-autoship' ),
									'desc_tip'    => true,
									'id'          => 'superlist_paggi_checkout_title'
								)
							);
	}

	public function payment_fields() {
		$current_user = wp_get_current_user();
		$token        = null;
		if ($current_user) {
			$token = $this->retrieveTokenFromDB($current_user->ID);
		}
		include dirname( dirname( __FILE__ ) ) . '/templates/frontend/payment-fields.php';
	}
	/**
	* Get the field names posted by the payment_fields form
	* @return string[]
	*/
	public function get_payment_field_names(){
		$field_names = [
			"superlist-paggi-use-this",
			"superlist-paggi-card-name",
			"superlist-paggi-number",
			"superlist-paggi-expiry",
			"superlist-paggi-cvc"
		];
	}

	public function process_payment( $order_id ) {

		global $wpdb;
		$woocommerce       = WC();
		$paggi_credit_card = null;
		$payment_method_id = null;		
		$order             = new WC_Order( $order_id );
		$customer          = $order->get_user();

		if (!$customer){
			wc_add_notice(
				__( 'Error: guest checkout is not currently supported', 'superlist-paggi' ),
				'error'
			);
			return;
		}

		$total          = $order->get_total();
		$total_shipping = $order->get_total_shipping();
		$total_tax      = $order->get_total_tax();
		$precision      = get_option( 'woocommerce_price_num_decimals' );
		
		try{

			if ($total < $this->minimum_order_amount){
				throw new Exception("valor mínimo para compras R$ ".number_format($this->minimum_order_amount,2,',','.'), 500);
			}

			$CUSTOMER_ID          = $customer->ID;	
			$customer_billing_dni = $this->is_natural_person($customer)? 
									$customer->billing_cpf :
									$customer->billing_cnpj;
			$customer_billing_dni = str_replace([".","-","/"],["","",""], $customer_billing_dni);		
			
			if ($this->is_new_credit_card()){

				$PAYER_NAME         = strtoupper(trim($_POST['superlist-paggi-card-name']));
				$CREDIT_CARD_NUMBER = trim(str_replace(" ", "", $_POST['superlist-paggi-number']));				
				$CREDIT_CARD_CVC    = trim($_POST['superlist-paggi-cvc']);
				$PAYMENT_METHOD     = trim(strtoupper($_POST['superlist-paggi-card-type']));
				
				$expiration_date    = trim(str_replace(" ", "", $_POST['superlist-paggi-expiry']));
				list($CREDIT_CARD_EXPIRATION_MONTH,
					 $CREDIT_CARD_EXPIRATION_YEAR) = explode('/', $expiration_date);

				if(!$this->is_natural_person($customer)){

					if( $_POST['superlist-paggi-cpf'] !== ''){
						$PAYER_DNI = trim(str_replace([".","-"],["",""], $_POST['superlist-paggi-cpf']));
					} else {						
						throw new Exception("O campo cpf não pode estar em branco", 500);
					}
				} else {
					$PAYER_DNI = trim($customer_billing_dni);
				}

				$credit_card_hash = hash('sha512', $PAYMENT_METHOD.$PAYER_NAME.
										 $CREDIT_CARD_NUMBER.$CREDIT_CARD_CVC.
										 $expiration_date.$customer->ID);
				if($this->credit_card_exists_on_db($credit_card_hash)) {

					$token = $this->retrieveTokenFromDB($customer->ID, $credit_card_hash);
					if (!$token){
						throw new Exception("Error retrieving the stored payment method", 500);
					}
					$this->generated_token  = $token->data->credit_card_token_id;
					$PAYER_NAME             = $token->data->payer_name;
					$PAYMENT_METHOD         = $token->data->payment_method;
					$payment_method_id      = $token->data->id;
					$is_default_credit_card = intval($token->data->default_credit_card);

					$paggi_credit_card     = new SuperlistPaggiCreditCard();
					$paggi_credit_card->id = $this->generated_token;

					if(!$is_default_credit_card){
						$this->set_as_default_credit_card($customer->ID, $payment_method_id);
					}
				} else {

					$paggi_credit_card     = $this->create_credit_card($customer, [
												"payer_name"            => $PAYER_NAME,
												"credit_card_number"    => $CREDIT_CARD_NUMBER,
												"credit_card_exp_month" => $CREDIT_CARD_EXPIRATION_MONTH,
												"credit_card_exp_year"  => $CREDIT_CARD_EXPIRATION_YEAR,
												"credit_card_cvc"       => $CREDIT_CARD_CVC,
												"credit_card_type"      => $PAYMENT_METHOD,
												"payer_dni"             => $PAYER_DNI												
										 	]);
					$this->generated_token = $paggi_credit_card->id;
					$order->add_order_note( __( 'Superlist+Paggi: new payment method saved ('.$PAYER_DNI.').', 'superlist-paggi' ) );

					$payment_method_id = $this->storeTokenOnDB($customer->ID, 
															   $paggi_credit_card,
															   $credit_card_hash);//store token on table
					$this->set_as_default_credit_card($customer->ID, $payment_method_id);
				}
			} else {

				$token = $this->retrieveTokenFromDB($customer->ID);
				if (!$token){
					throw new Exception("Error retrieving the stored payment method", 500);
				}
				$this->generated_token = $token->data->credit_card_token_id;
				$PAYER_NAME            = $token->data->payer_name;
				$PAYMENT_METHOD        = $token->data->payment_method;
				$payment_method_id     = $token->data->id;

				$paggi_credit_card     = new SuperlistPaggiCreditCard();
				$paggi_credit_card->id = $this->generated_token;
			}
			//now make the charge to the stored card
			add_post_meta( $order->id, 'paggi_dni_'.time(),$customer_billing_dni); 
			
			$amount               = intval(strval(floatval($total)*100));		
			$response_transaction = $paggi_credit_card->charge($amount, "SUPERLIST ORDER # {$order->id}");
			
			$order->add_order_note( __( 'Superlist+Paggi: using this payment method.'.$PAYER_NAME, 'superlist-paggi' ) );
			add_post_meta( $order->id, 'paggi_responsex_'.time(), json_encode($response_transaction) );
						
			if($this->transaction_approved($response_transaction)){
				
				// Payment has been successful
				$order->add_order_note( __( 'Superlist+Paggi payment completed.', 'superlist-paggi' ) );
				$order->add_order_note( __( 'transactionId:'.$response_transaction->id, 'superlist-paggi' ) );
				$order->add_order_note( __( 'transactionId:'.$response_transaction->status, 'superlist-paggi' ) );
				
				$order->payment_complete( $response_transaction->id);// Mark order as Paid				
				$woocommerce->cart->empty_cart();// Empty the cart (Very important step)
				
				add_post_meta( $order->id, 'superlist_payment_method', $this->id);
				add_post_meta( $order->id, 'superlist_payment_flag'  , $PAYMENT_METHOD);
				
				if($this->is_new_credit_card()){
					
					//$this->deleteTokenFromDB($CUSTOMER_ID);//ensure only one token by customer										
					//$payment_method_id = $this->storeTokenOnDB($customer->ID, $paggi_credit_card);//store token on table
					
					//create the autoship customer 					
					$wc_autoship_customer = new WC_Autoship_Customer( $customer->ID );
					$payment_method_data = [];
					$wc_autoship_customer->store_payment_method($this->id, $payment_method_id, $payment_method_data);
				}
				add_post_meta( $order->id, 'superlist_paggi_token_id', $payment_method_id);
				$this->mark_as_successful_credit_card($payment_method_id);
				
				// Redirect to thank you page
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} elseif ($this->transaction_on_hold($response_transaction)) {

				$order->update_status("wc-on-hold");
				$woocommerce->cart->empty_cart();
				wp_mail("developers@superlist.com", "[URGENT] MANUAL CHECK REQUIRED FOR CUSTOMER ORDER(".$order->id.")", "error message".json_encode($response_transaction),[],[]);
				
				if($this->is_new_credit_card()){
					
					//$this->deleteTokenFromDB($CUSTOMER_ID);//ensure only one token by customer										
					//$payment_method_id = $this->storeTokenOnDB($customer->ID, $paggi_credit_card);//store token on table
					
					//create the autoship customer 					
					$wc_autoship_customer = new WC_Autoship_Customer( $customer->ID );
					$payment_method_data = [];
					$wc_autoship_customer->store_payment_method($this->id, $payment_method_id, $payment_method_data);
				}
				$order->add_order_note( __( 'transactionId:'.$response_transaction->id, 'superlist-paggi' ) );
				$order->add_order_note( __( 'transactionId:'.$response_transaction->status, 'superlist-paggi' ) );
				add_post_meta( $order->id, 'superlist_payment_method', $this->id);
				add_post_meta( $order->id, 'superlist_payment_flag'  , $PAYMENT_METHOD);
				add_post_meta( $order->id, 'superlist_paggi_token_id', $payment_method_id);

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {

				if($this->transaction_declined($response_transaction)){
					throw new Exception('Transação rejeitada. Entre em contato com a instituição emissora do seu cartão.', 500);
				}elseif (!isset($response_transaction->acquirer_message)){
					throw new Exception( $this->getResponseCodeMessage($response_transaction->acquirer_code),500);
				}else{
					throw new Exception($response_transaction->acquirer_message, 500);
				}
			}
		}
		catch(Exception $e)
		{
			throw new Exception( $e->getMessage() );
		}
		return;
	}
	
	/**
	 * Process an order using a stored payment method
	 * @param WC_Order $order
	 * @param WC_Autoship_Customer $customer
	 * @return WC_Autoship_Payment_Response
	 */
	public function process_stored_payment( WC_Order $order, WC_Autoship_Customer $customer ) {
		
		// init WC por si las fly
		WC();
		if ($this->plugin_settings->local_test_mode=='yes') {
			$order->add_order_note( __( 'Superlist+Paggi: local test mode ON.', 'superlist-paggi' ) );
			$order->payment_complete(time());
			return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
		}

		var_dump($order->id);
		var_dump($order->get_total());
		var_dump($this->minimum_order_amount);

		// Create payment response
		$payment_response = new WC_Autoship_Payment_Response();
		//validate minimun ammount of purchase
		if ($order->get_total() < $this->minimum_order_amount){

			$payment_response->success = false;
			$payment_response->status  = "101 - valor mínimo para compras R$ ".number_format($this->minimum_order_amount,2,',','.');
			return $payment_response;
		}
		
		$paggi_credit_card = new SuperlistPaggiCreditCard();//initialize the Paggi Integration		
		$user              = $customer->get_user();
		$token             = $this->retrieveTokenFromDB($user->ID);//get the stored token
		if (!$token) {			
			wp_mail( "juan.scarton@superlist.com", "autoship:TOKEN NOT FOUND", "token not found for CUSTOMER_ID=".$user->ID, [], []);
			// No billing ID
			$payment_response->success = false;
			$payment_response->status  = "SUPERLIST: TOKEN NOT FOUND";
			return $payment_response;
		
		} else {

			try{				
				wp_mail( "juan.scarton@superlist.com", "autoship: TRYING", "trying payment for CUSTOMER_ID=".$user->ID." total=".$order->get_total(), [], []);
				
				$paggi_credit_card->id = $token->data->credit_card_token_id;
				$customer_billing_dni  = $this->is_natural_person($user)?
										 $user->billing_cpf:
										 $user->billing_cnpj;
				$customer_billing_dni  = str_replace([".","-"],["",""], $customer_billing_dni);
				add_post_meta( $order->id, 'using_billing_dni', $customer_billing_dni );
				
				//now make the charge to the stored card										
				$amount               = intval(strval(floatval($order->get_total())*100));
				$response_transaction = $paggi_credit_card->charge($amount, "SUPERLIST RECURRING ORDER # {$order->id}", true);
				add_post_meta( $order->id, 'paggi_response', json_encode($response_transaction));				
				
				if ($this->transaction_approved($response_transaction)) {

					add_post_meta( $order->id, 'superlist_payment_method', $this->id);
					add_post_meta( $order->id, 'superlist_payment_flag'  , $token->data->payment_method);
					
					// Payment success
					$payment_response->success        = true;
					$payment_response->transaction_id = $response_transaction->id;
					$payment_response->payment_id     = $token->data->id;
					$payment_response->status         = "Order processed with Superlist Paggi Payment Gateway";
					wp_mail( "juan.scarton@superlist.com", "autoship:SUCCESS", "SUCCESS for CUSTOMER_ID=".$user->ID, [], []);
					
					return $payment_response;				
				} else {

					$eMessage="";
					if (!isset($response_transaction->acquirer_message)){
						$eMessage = $this->getResponseCodeMessage($response_transaction->acquirer_code);
					}else {
						$eMessage = $this->getResponseCodeMessage($response_transaction->acquirer_message);
					}
					wp_mail( "juan.scarton@superlist.com", "autoship:ERROR", "Error processing payment for CUSTOMER_ID=".$user->ID." with message:".$eMessage, [], []);
					$payment_response->success = false;
					$payment_response->status  = $eMessage;
					
					return $payment_response;
				}
				
			}
			catch(Exception $e)
			{
				// Payment failed
				$payment_response->success = false;
				$payment_response->status = $e->getMessage();
				wp_mail( "juan.scarton@superlist.com", "autoship:EXCEPTION", "For CUSTOMER_ID:".$user->ID." Message:".$e->getMessage(), [], []);
			}
		}
		return $payment_response;
	}

	public function store_payment_method( WC_Autoship_Customer $customer, $payment_fields = array() ) {
		try{
			
			if (isset($_POST['superlist-paggi-use-this']) && $_POST['superlist-paggi-use-this']=='new')
			{
				//initialize the Paggi Integration
				$CreditCardAPI= new SuperlistPaggiCreditCard();
				//store the new credit card
				$user=$customer->get_user();
				$CUSTOMER_ID=$user->ID;
				//Set new credit card data
				$PAYER_NAME=trim($_POST['superlist-paggi-card-name']);
				$CREDIT_CARD_NUMBER=trim(str_replace(" ","",$_POST['superlist-paggi-number']));
				if(empty($_POST['superlist-paggi-expiry'])){
					$expiration_date = trim($_POST['validade_1'].'-'.$_POST['validade_2']);
				}else{
					$expiration_date=trim(str_replace([" ","/"],["","-"],$_POST['superlist-paggi-expiry']));
				}
				$CREDIT_CARD_EXPIRATION_DATE=date ("Y/m",strtotime("28-".$expiration_date));
				$PAYMENT_METHOD=trim(strtoupper($_POST['superlist-paggi-card-type']));
				$customer_billing_dni=(intval($user->billing_persontype)==1)?$user->billing_cpf:$user->billing_cnpj;
				if (intval($user->billing_persontype)!=1 && intval($user->billing_persontype)!=2)
					throw new Exception("Erro: tipo de pessoa desconhecido",500);
				$PAYER_DNI=isset($_POST['superlist-paggi-dni'])?trim(strtoupper($_POST['superlist-paggi-dni'])):$customer_billing_dni;
				$data=[
				'user_id'=>$CUSTOMER_ID,
				'cardholder'=>$PAYER_NAME,
				'cardnumber'=>$CREDIT_CARD_NUMBER,
				'expiration'=>$CREDIT_CARD_EXPIRATION_DATE,
				'method'=>$PAYMENT_METHOD,
				'dni'=>$PAYER_DNI
				];
				//wp_mail("juan.scarton@superlist.com","changing payment method attempt",json_encode($data),[],[]);
				$response=$CreditCardAPI->createCreditCard($CUSTOMER_ID,$PAYER_NAME,$CREDIT_CARD_NUMBER,$CREDIT_CARD_EXPIRATION_DATE,$PAYMENT_METHOD,$PAYER_DNI);	
				//wp_mail("juan.scarton@superlist.com","changing payment method response",json_encode($response),[],[]);
				//check if there is a previous card
				$token= $this->retrieveTokenFromDB($CUSTOMER_ID);
				if ($response->code=="SUCCESS" && isset($response->creditCardToken))
				{
					//remove previous card from db
					if (!is_null($token))
					{
						$y=$this->deleteTokenFromDB($CUSTOMER_ID);
					
					}
					//store token on table
					$payment_method_id=$this->storeTokenOnDB($response);
					//create the autoship customer 
					$payment_method_data = [];
					$customer->store_payment_method($this->id, $payment_method_id, $payment_method_data);
					
					return true;
				}
			}
			else{
				//return get_permalink($this->plugin_settings->edit_method_page_id);
			}
		}
		catch (Exception $e){
			//return false;
			//exit;
		}
		//return false;
	}
	
	public function validate_fields() {
		return true;
	}
	
	/**
	 * Get the payment method description for a customer in HTML format
	 * @param WC_Autoship_Customer $customer
	 * @return string
	 */
	public function get_payment_method_description( WC_Autoship_Customer $customer ) {
		$payment_method_data = $customer->get_payment_method_data();
		if ( empty( $payment_method_data ) ) {
			return '';
		}
		$description = array( '<div class="paypal-description">' );
		$description[] = '<img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" '
				. 'alt="PayPal" /><br />';
		if ( isset( $payment_method_data['email'] ) ) {
			$description[] = ' <span>' . esc_html( $payment_method_data['email'] ) . '</span>';
		}
		$description[] = '</div>';
		return implode( '', $description );
	}

	public function process_admin_options() {
		parent::process_admin_options();
	}

	private function retrieveTokenFromDB($user_id, $credit_card_hash=false){

		global $wpdb;
		$table_name = $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
		$and_query  = ' AND default_credit_card = 1';
		if($credit_card_hash){
			$and_query = " AND hash = '$credit_card_hash'";
		}
		$rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = $user_id".$and_query);
		if ($rs)
			return new SuperlistPaggiBase(['data'=>$rs]);
		return NULL;
	}

	private function deleteTokenFromDB($user_id)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
		$response=$wpdb->delete( $table_name, array( 'payer_id' => $user_id ) );
		return $response;
	}

	private function storeTokenOnDB($user_id, $paggi_credit_card, $credit_card_hash){

		global $wpdb;
		$table_name = $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
		$data       = [
						"credit_card_token_id" => $paggi_credit_card->id,
						"payer_name"           => $paggi_credit_card->payer_name,
						"payer_id"             => $user_id,
						"payer_dni"            => $paggi_credit_card->payer_dni,
						"payment_method"       => $paggi_credit_card->payment_method,
						"payment_maskednumber" => $paggi_credit_card->payment_maskednumber,
						"paggi_customer_id"    => $paggi_credit_card->paggi_customer_id,
						"hash"                 => $credit_card_hash,
						"default_credit_card"  => 0
					  ];
		$format     = [
						"%s",
						"%s",
						"%d",
						"%s",
						"%s",
						"%s",
						"%s",
						"%s",
						"%d"
					  ];
		$wpdb->insert($table_name,$data,$format);
		return $wpdb->insert_id;
	}

	private function getResponseCodeMessage($response_code)
	{
		$response_messages=[
			"ERROR"=>"Ocorreu um erro geral.",
			"APPROVED"=>"A transação foi aprovada.",
			"ANTIFRAUD_REJECTED"=>"A transação foi rejeitada pelo sistema anti fraude.",
			"PAYMENT_NETWORK_REJECTED"=>"A rede financeira rejeitou a transação.",
			"ENTITY_DECLINED"=>"A transação foi rejeitada pela rede financeira. Por favor, informe-se no seu banco ou na sua operadora de cartão de crédito.",
			"INTERNAL_PAYMENT_PROVIDER_ERROR"=>"Ocorreu um erro no sistema tentando processar o pagamento.",
			"INACTIVE_PAYMENT_PROVIDER"=>"O fornecedor de pagamentos não estava ativo.",
			"DIGITAL_CERTIFICATE_NOT_FOUND"=>"A rede financeira relatou um erro na autenticação.",
			"INVALID_EXPIRATION_DATE_OR_SECURITY_CODE"=>"O código de segurança ou a data de expiração estava inválido.",
			"INVALID_RESPONSE_PARTIAL_APPROVAL"=>"Tipo de resposta inválida. A entidade financeira aprovou parcialmente a transação e deve ser cancelado automaticamente pelo sistema.",
			"INSUFFICIENT_FUNDS"=>"A conta não tinha crédito suficiente.",
			"CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS"=>"O cartão de crédito não estava autorizado para transações pela Internet.",
			"INVALID_TRANSACTION"=>"A rede financeira relatou que a transação foi inválida.",
			"INVALID_CARD"=>"O cartão é inválido.",
			"EXPIRED_CARD"=>"O cartão já expirou.",
			"RESTRICTED_CARD"=>"O cartão apresenta uma restrição.",
			"CONTACT_THE_ENTITY"=>"Você deve entrar em contato com o banco.",
			"REPEAT_TRANSACTION"=>"Deve-se repetir a transação.",
			"ENTITY_MESSAGING_ERROR"=>"A rede financeira relatou um erro de comunicações com o banco.",
			"BANK_UNREACHABLE"=>"O banco não se encontrava disponível.",
			"EXCEEDED_AMOUNT"=>"A transação excede um montante estabelecido pelo banco.",
			"NOT_ACCEPTED_TRANSACTION"=>"A transação não foi aceita pelo banco por algum motivo.",
			"ERROR_CONVERTING_TRANSACTION_AMOUNTS"=>"Ocorreu um erro convertendo os montantes para a moeda de pagamento.",
			"EXPIRED_TRANSACTION"=>"A transação expirou.",
			"PENDING_TRANSACTION_REVIEW"=>"A transação foi parada e deve ser revista, isto pode ocorrer por filtros de segurança.",
			"PENDING_TRANSACTION_CONFIRMATION"=>"A transação está pendente de confirmação.",
			"PENDING_TRANSACTION_TRANSMISSION"=>"A transação está pendente para ser transmitida para a rede financeira. Normalmente isto se aplica para transações com formas de pagamento em dinheiro.",
			"PAYMENT_NETWORK_BAD_RESPONSE"=>"A mensagem retornada pela rede financeira é inconsistente.",
			"PAYMENT_NETWORK_NO_CONNECTION"=>"Não foi possível realizar a conexão com a rede financeira.",
			"PAYMENT_NETWORK_NO_RESPONSE"=>"A rede financeira não respondeu.",
		];
		if (isset($response_messages[$response_code]))
			return $response_messages[$response_code];
		else
			return $response_messages["ERROR"];
	}

	private function transaction_approved($transaction_data){
		return ($transaction_data->status == 'approved');
	}

	private function transaction_declined($transaction_data){		
		return (in_array($transaction_data->status, ['declined', 'not_cleared']));
	}	

	private function transaction_on_hold($transaction_data){
		return (in_array($transaction_data->status, ['registered', 'pre_approved', 'cleared', 'captured']));
	}
	

	private function is_new_credit_card(){
		return (isset($_POST['superlist-paggi-use-this']) && $_POST['superlist-paggi-use-this']=='new');
	}

	private function is_natural_person($customer){
		return intval($customer->billing_persontype)==1;
	}

	private function create_credit_card($superlist_customer, $credit_card_data){
		
		$paggi_customer = new SuperlistPaggiCustomer();			
		if($paggi_customer->exists($superlist_customer->ID)){

			$paggi_customer->id = $paggi_customer->retrieve_paggi_customer_id($superlist_customer->ID);
			if(is_null($paggi_customer->id)){
				throw new Exception("Error retrieving the stored payment method", 500);
			}
		} else {
			
			$customer_phone     = str_replace(
									[".", "-", " ", "(", ")"],
									[ "",  "",  "",  "",  ""],
									get_user_meta( $superlist_customer->ID, 'billing_cellphone', true)
								  );
			$paggi_customer->id = $paggi_customer->create([
									"name" 	   => $credit_card_data['payer_name'],
									"email"    => $superlist_customer->user_email,
									"document" => $credit_card_data['payer_dni'],
									"phone"    => $customer_phone,
									"address"  => $this->retrieve_customer_address($superlist_customer, 'shipping')
								  ]);		
		}

		$paggi_credit_card     = new SuperlistPaggiCreditCard();
		$paggi_credit_card->id = $paggi_credit_card->create(
									$paggi_customer->id,
									$credit_card_data['payer_name'],
									$credit_card_data['credit_card_number'],
									$credit_card_data['credit_card_exp_month'],
									$credit_card_data['credit_card_exp_year'],
									$credit_card_data['credit_card_cvc'],
									$credit_card_data['credit_card_type'],
									$credit_card_data['payer_dni'],
									$this->retrieve_customer_address($superlist_customer, 'billing')								
						   		 );

		return $paggi_credit_card;
	}

	private function retrieve_customer_address($superlist_customer, $type){

		$address = [
			"street"       => trim(get_user_meta( $superlist_customer->ID, "{$type}_address_1", true).' '.
								   get_user_meta( $superlist_customer->ID, "{$type}_number", true).' '.
								   get_user_meta( $superlist_customer->ID, "{$type}_address_2", true)),
			"neighborhood" => trim(get_user_meta( $superlist_customer->ID, "{$type}_neighborhood", true)),
			"city"         => trim(get_user_meta( $superlist_customer->ID, "{$type}_city", true)),
			"state"        => trim(get_user_meta( $superlist_customer->ID, "{$type}_state", true)),
			"zip"          => trim(get_user_meta( $superlist_customer->ID, "{$type}_postcode", true))
		];

		return $address;
	}

	private function credit_card_exists_on_db($credit_card_hash){
		
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
		
		$rs=$wpdb->get_row( "SELECT 1 FROM $table_name WHERE hash = '$credit_card_hash'");
		if ($rs)
			return true;
		return false;
	}

	private function set_as_default_credit_card($superlist_id, $payment_method_id){

		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";	
		
		//sets all customer credit cards to 0. This ensure that only one will be setting up as default
		$wpdb->update( 
			$table_name, 
			array(
			 	'default_credit_card' => 0
			), 
			array( 
				'payer_id' => $superlist_id
			), 
			array( '%d' ),
			array( '%d' ) 
		);

		//and then only one is setting up as default
		$wpdb->update( 
			$table_name, 
			array(
			 	'default_credit_card' => 1
			), 
			array( 
				'id' => $payment_method_id
			), 
			array( '%d' ),
			array( '%d' ) 
		);	
	}

	private function mark_as_successful_credit_card($payment_method_id){
		
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";	
		
		$wpdb->update( 
			$table_name, 
			array(
			 	'successful_payment' => 1
			), 
			array( 
				'id' => $payment_method_id
			), 
			array( '%d' ),
			array( '%d' ) 
		);
	}
}
