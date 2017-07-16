<?php


require_once('superlist-paggi-setup.php');

class SuperlistPaggiSettings {

	//account data for testing
	const SUPERLIST_PAGGI_STAGING_API_KEY="cd1e5f13-3f70-4007-85f3-df7997448829";

	//endpoints staging
	const SUPERLIST_PAGGI_STAGING_GET_CARD_BY_ID     = 'https://staging-online.paggi.com/api/v4/cards/';
	const SUPERLIST_PAGGI_STAGING_GET_CUSTOMER_BY_ID = 'https://staging-online.paggi.com/api/v4/customers/';
	const SUPERLIST_PAGGI_STAGING_CREATE_CUSTOMER    = 'https://staging-online.paggi.com/api/v4/customers';
	const SUPERLIST_PAGGI_STAGING_CHARGE_CUSTOMER    = 'https://staging-online.paggi.com/api/v4/charges';
	const SUPERLIST_PAGGI_STAGING_CREATE_CARD        = 'https://staging-online.paggi.com/api/v4/cards';
	const SUPERLIST_PAGGI_STAGING_CHARGE_CREDIT_CARD = 'https://staging-online.paggi.com/api/v4/charges';

	//endpoints production
	const SUPERLIST_PAGGI_PRODUCTION_GET_CARD_BY_ID     = 'https://online.paggi.com/api/v4/cards/';
	const SUPERLIST_PAGGI_PRODUCTION_GET_CUSTOMER_BY_ID = 'https://online.paggi.com/api/v4/customers/';
	const SUPERLIST_PAGGI_PRODUCTION_CREATE_CUSTOMER    = 'https://online.paggi.com/api/v4/customers';
	const SUPERLIST_PAGGI_PRODUCTION_CHARGE_CUSTOMER    = 'https://online.paggi.com/api/v4/charges';
	const SUPERLIST_PAGGI_PRODUCTION_CREATE_CARD        = 'https://online.paggi.com/api/v4/cards';
	const SUPERLIST_PAGGI_PRODUCTION_CHARGE_CREDIT_CARD = 'https://online.paggi.com/api/v4/charges';
			
	public function register() {
		add_filter(
			'woocommerce_settings_tabs_array',
			array( $this, 'add_settings_tab' ),
			50,
			1
		);
		
		add_action( 
			'woocommerce_settings_tabs_superlist_paggi', 
			array( $this, 'settings_tab' )
		);
		
		add_action( 
			'woocommerce_update_options_superlist_paggi', 
			array( $this, 'update_options' )
		);
	}
	
	public function add_settings_tab( $tabs ) {
		$tabs['superlist_paggi'] = __( 'Superlist Paggi', 'superlist-paggi' );
		return $tabs;
	}
	
	public function get_settings() {
		$superlist_paggi_settings = array(
			array(
				'name' => __( 'Superlist Paggi Payment Gateway Settings', 'superlist-paggi' ),
				'type' => 'title',
				'desc' => __( 'Enter general system settings for Superlist Paggi.', 'superlist-paggi' ),
				'id' => 'superlist_paggi_settings'
			),
			array(
				'name' => __( 'API Key', 'superlist-paggi' ),
				'desc' => __( 'Type your Paggi\'s API Key.', 'superlist-paggi' ),
				'desc_tip' => false,
				'type' => 'text',
				'id' => 'superlist_paggi_api_key'
			),
			array(
				'name' => __( 'Enable Test Mode', 'superlist-paggi' ),
				'desc' => __( 'Select to enable Paggi Test Mode.', 'superlist-paggi' ),
				'desc_tip' => true,
				'type' => 'checkbox',
				'id' => 'superlist_paggi_test_mode'
			),
			array(
				'name' => __( 'Enable Risk Analysis', 'superlist-paggi' ),
				'desc' => __( 'Select to enable Paggi Risk Analysis.', 'superlist-paggi' ),
				'desc_tip' => true,
				'type' => 'checkbox',
				'id' => 'superlist_paggi_risk_analysis'
			),
			array(
				'name' => __( 'Enable Risk Analysis for Recurring Lists', 'superlist-paggi' ),
				'desc' => __( 'Select to enable Paggi Risk Analysis for Recurring List.', 'superlist-paggi' ),
				'desc_tip' => true,
				'type' => 'checkbox',
				'id' => 'superlist_paggi_risk_analysis_recurring'
			),
			array(
				'type' => 'sectionend',
				'id' => 'superlist_paggi_section_end'
			)
		);
		$settings = apply_filters( 'superlist_paggi_settings', $superlist_paggi_settings );		
		return $settings;
	}
	
	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}
	
	public function update_options() {
		woocommerce_update_options( $this->get_settings() );
	}

	public function __get($name)
	{
		return get_option("superlist_paggi_".$name);
	}


	public function getEndpoints($is_sandbox="no")
	{
		if ($is_sandbox=="yes")
		{
			return [
				'get_card_by_id'=>self::SUPERLIST_PAGGI_STAGING_GET_CARD_BY_ID     ,
				'get_customer_by_id'=>self::SUPERLIST_PAGGI_STAGING_GET_CUSTOMER_BY_ID ,
				'create_customer'=>self::SUPERLIST_PAGGI_STAGING_CREATE_CUSTOMER    ,
				'charge_customer'=>self::SUPERLIST_PAGGI_STAGING_CHARGE_CUSTOMER    ,
				'create_card'=>self::SUPERLIST_PAGGI_STAGING_CREATE_CARD        ,
				'charge_credit_card'=>self::SUPERLIST_PAGGI_STAGING_CHARGE_CREDIT_CARD ,
			];
		}
		else
		{
			return [
				'get_card_by_id'=>self::SUPERLIST_PAGGI_PRODUCTION_GET_CARD_BY_ID     ,
				'get_customer_by_id'=>self::SUPERLIST_PAGGI_PRODUCTION_GET_CUSTOMER_BY_ID ,
				'create_customer'=>self::SUPERLIST_PAGGI_PRODUCTION_CREATE_CUSTOMER    ,
				'charge_customer'=>self::SUPERLIST_PAGGI_PRODUCTION_CHARGE_CUSTOMER    ,
				'create_card'=>self::SUPERLIST_PAGGI_PRODUCTION_CREATE_CARD        ,
				'charge_credit_card'=>self::SUPERLIST_PAGGI_PRODUCTION_CHARGE_CREDIT_CARD ,
			];	
		}
	}

	public function getPaggiCredentials($is_sandbox="no")
	{
		if ($is_sandbox=="yes")
		{
			return [
				'api_key'=>self::SUPERLIST_PAGGI_STAGING_API_KEY,
			];
		}
		else
		{
			return [
				'api_key'=>$this->api_key,
			];	
		}
	}
	
	public function getPluginSettings()
	{
		return [			
			'test_mode'=>$this->test_mode,
			'risk_analysis'=>$this->risk_analysis,
			'risk_analysis_recurring' => $this->risk_analysis_recurring,		
		];
	}
	
}