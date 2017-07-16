<?php

class SuperlistPaggiSetup{
	const PREFIX = 'superlist_paggi_';
	const STATUS_ACTIVE = 1;
	const STATUS_PAUSED = 0;
	const STATUS_INACTIVE = -1;
	
	public static function activate() {
		$settings = self::default_settings();
		foreach ( $settings as $name => $value ) {
			add_option( $name, $value );
		}

		self::_create_tables();
		//self::unschedule_autoship();
		//self::schedule_autoship();
	}
	
	public static function deactivate() {
		//self::unschedule_autoship();
	}
	
	public static function uninstall() {
		// Delete tables
		self::_delete_tables();
		// Delete autoship metadata
		$settings = self::default_settings();
		foreach ( $settings as $name => $value ) {
			delete_option( $name );
		}
	}
	public static function default_settings() {
		$default_settings = array();
		return $default_settings;
	}
	
	private static function _create_tables()
    {
        global $wpdb;   
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $prefix = $wpdb->prefix . SuperlistPaggiSetup::PREFIX;        
        $wpdb->hide_errors();
        $db_version = get_option('superlist_paggi_db_version');

        if(!isset($db_version) or $db_version==FALSE){
	        $create_sql=
	        "CREATE TABLE {$prefix}creditcards (
	        id 						integer auto_increment,
	        credit_card_token_id 	varchar(50)  not null,
	        payer_name 				varchar(70)  not null,
	        payer_id 				integer not  null,
	        payer_dni 				varchar(20)  not null,
	        payment_method			varchar(20)  not null,
	        payment_maskednumber	varchar(20)  not null,
	        paggi_customer_id       varchar(50)  not null,	        
	        PRIMARY KEY(id)
	        );";
	        dbDelta($create_sql);
	        $wpdb->show_errors();
		} elseif($db_version == '0.0.1') {

			$alter_table_sql = "
				ALTER TABLE {$prefix}creditcards
					ADD COLUMN hash VARCHAR(255) NOT NULL DEFAULT '0' AFTER paggi_customer_id,
					ADD COLUMN default_credit_card TINYINT NOT NULL DEFAULT '1' AFTER hash,
					ADD COLUMN successful_payment TINYINT NOT NULL DEFAULT '0' AFTER default_credit_card;
			";
			$wpdb->query($alter_table_sql);
	        $wpdb->show_errors();
		}
		update_option( 'superlist_paggi_db_version', SUPERLIST_PAGGI_PAYMENTS_VERSION );
	}
	
	private static function _delete_tables() {
		global $wpdb;
	
		$prefix = $wpdb->prefix . SuperlistPaggiSetup::PREFIX;
		$wpdb->query( "DROP TABLE {$prefix}creditcards" );
	}	
}