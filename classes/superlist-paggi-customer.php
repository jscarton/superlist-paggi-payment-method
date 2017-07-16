<?php

class SuperlistPaggiCustomer extends SuperlistPaggiBase {

    public function __construct(){
        
        parent::__construct([]);
        $settings=new SuperlistPaggiSettings();
        $this->plugin_settings=$settings->getPluginSettings();
        $this->endpoints=$settings->getEndpoints($this->plugin_settings->test_mode);
        $this->credentials=$settings->getPaggiCredentials($this->plugin_settings->test_mode);
    }

    public function create($customer_data){
        
        $this->validate_fields();
        $request_data = array(
            "name"     => $customer_data['name'],
            "email"    => $customer_data['email'],
            "document" => $customer_data['document'],
            "phone"    => $customer_data['phone'],
            "address"  => $customer_data['address']
        );

        try {
            
            $serviceRequest = curl_init($this->endpoints->create_customer);
            curl_setopt($serviceRequest, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($serviceRequest, CURLOPT_POSTFIELDS, json_encode($request_data));
            curl_setopt($serviceRequest, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . base64_encode($this->credentials->api_key),
                'Content-Type: application/json',
                )
            );
            curl_setopt($serviceRequest, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($serviceRequest));
            curl_close($serviceRequest);
                       
            if(isset($response)){
                return $response->id;
            } else {
                throw new Exception("NO RESPONSE", 500);                
            }
        } catch (Exception $e){
            throw new Exception($e->getMessage(), 500);            
        }
    }    

    private function validate_fields(){
        return true;
    }

    public function exists($superlist_user_id){

        global $wpdb;
        $table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
        $rs=$wpdb->get_row( "SELECT id FROM $table_name WHERE payer_id = $superlist_user_id LIMIT 1");
        
        if ($rs) return true;        
        return false;
    }

    public function charge($amount){

        $response = null;
        $request_data = array(
          "amount"        => $amount,
          "customer_id"   => $this->id,
          "force"         => false,
          "risk_analysis" => $this->plugin_settings->risk_analysis==='yes'?true:false        
        );

        echo '<br>request data charge customer';
        echo '<pre>';
        var_dump(json_encode($request_data));
        echo '</pre>';

        $serviceRequest = curl_init($this->endpoints->charge_customer);
        curl_setopt($serviceRequest, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($serviceRequest, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($serviceRequest, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . base64_encode($this->credentials->api_key),
            'Content-Type: application/json',
            )
        );
        curl_setopt($serviceRequest, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($serviceRequest));
        curl_close($serviceRequest);
        echo '<pre>';
        var_dump($response);
        echo '</pre>';

        return $response;
    }

    public function retrieve_paggi_customer_id($superlist_user_id){

        global $wpdb;
        $table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
        $rs=$wpdb->get_row( "SELECT paggi_customer_id FROM $table_name WHERE payer_id = $superlist_user_id LIMIT 1");
        if ($rs)
            return $rs->paggi_customer_id;
        return NULL;
    }
}