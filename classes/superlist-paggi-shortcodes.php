<?php

class SuperlistPaggiShortcodes{

	public function register()
	{
		add_shortcode( 'superlist-paggi-test', [$this,"do_test"] );
		add_shortcode( 'superlist-paggi-checkout', [$this,"superlist_paggi_checkout"] );
		add_shortcode( 'superlist-paggi-retrieve', [$this,"superlist_paggi_test_retrieve"] );		
	}

	public function do_test($attr)
	{
		global $wpdb;
		//try to create a credit card
		$response1=false;
		$response2=false;
		try{

			$paggi_customer = new SuperlistPaggiCustomer([
				"name" 	   => "Pepe Veras",
  				"email"    => "pepe@paggi.com",
  				"document" => "1677854933",
  				"phone"    => "11987654321",
			]);			
			if(!$paggi_customer->exists()){
				$paggi_customer->id = $paggi_customer->create();
				echo 'customer id: '.$paggi_customer->id;
			}
			$credit_card     = new SuperlistPaggiCreditCard();
			$credit_card->id = $credit_card->create(
										$paggi_customer->id,
										$paggi_customer->name,
										"4111111111111111", "04", "2020", "123", "Visa",
										$paggi_customer->document
						);
			echo '<br>';
			echo 'card id: '.$credit_card->id;
			echo '<pre>';
			var_dump($wpdb->get_row( "SELECT * FROM {$wpdb->prefix}superlist_paggi_creditcards WHERE payer_id = '{$paggi_customer->customer_id}'"));
			echo '</pre>';
		} catch (Exception $e) {
			
			echo '<pre>';
			var_dump($e);
			echo '</pre>';
		}
		//try to charge a customer
		try{
			$response2 = $paggi_customer->charge(250);
		}
		catch (Exception $e)
		{
			var_dump($e);
		}

		//try to charge a credit card
		try{
			$response2 = $credit_card->charge(100);
		}
		catch (Exception $e)
		{
			var_dump($e);
		}	
	} 
	public function superlist_paggi_checkout($attr)
	{		
		try{
			$gateway= new SuperlistPaggiAutoshipPaymentGateway();
			$res=$gateway->process_stored_payment(new WC_Order(7481),new WC_Autoship_Customer(1));
			var_dump($res);
		}
		catch (Exception $ex)
		{
			var_dump($ex);
		}
	} 

	public function superlist_paggi_test_retrieve($attr)
	{
		global $wpdb;
		$table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
		$rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = 11");
		$token=new SuperlistPaggiBase(['data'=>$rs]);
		var_dump($token->data->payment_method);		
	}
}