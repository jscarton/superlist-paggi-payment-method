<?php

class SuperlistPaggiCreditCard extends SuperlistPaggiBase
{
    public function __construct()
    {
        parent::__construct([]);
        $settings=new SuperlistPaggiSettings();
        $this->plugin_settings=$settings->getPluginSettings();
        $this->endpoints=$settings->getEndpoints($this->plugin_settings->test_mode);
        $this->credentials=$settings->getPaggiCredentials($this->plugin_settings->test_mode);
    }

    public function create($CUSTOMER_ID, $PAYER_NAME,
                           $CREDIT_CARD_NUMBER, $CREDIT_CARD_EXPIRATION_MONTH,
                           $CREDIT_CARD_EXPIRATION_YEAR, $CREDIT_CARD_CVC,
                           $PAYMENT_METHOD, $PAYER_DNI, $BILLING_ADDRESS){
        
        $request_data = array(
            "customer_id" => $CUSTOMER_ID,
            "name"        => $PAYER_NAME,
            "number"      => $CREDIT_CARD_NUMBER,
            "month"       => $CREDIT_CARD_EXPIRATION_MONTH,
            "year"        => $CREDIT_CARD_EXPIRATION_YEAR,
            "cvc"         => $CREDIT_CARD_CVC,
            "default"     => 'true',
            "validate"    => false,
            "address"     => $BILLING_ADDRESS
        );        

        try {
            $serviceRequest = curl_init($this->endpoints->create_card);
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
                $this->id                   = $response->id;
                $this->paggi_customer_id    = $response->customer_id;
                $this->payment_method       = strtoupper($response->brand);
                $this->payment_maskednumber = $response->bin.'*******'.$response->last4;
                $this->payer_name           = $response->name;
                $this->payer_dni            = $PAYER_DNI;

                return $this->id;
            } else {
                throw new Exception("NO RESPONSE", 500);                
            }
        } catch (Exception $e){
            throw new Exception($e->getMessage(), 500);            
        }
    }

    public function charge($amount, $descriptor, $recurring=false){

        $response = null;
        $request_data = array(
          "amount"               => $amount,
          "card_id"              => $this->id,
          "statement_descriptor" => $descriptor,
          "force"                => false,
          "risk_analysis"        => $this->plugin_settings->risk_analysis==='yes'?true:false
        );
        if($recurring){
            $request_data['risk_analysis'] = $this->plugin_settings->risk_analysis_recurring==='yes'?true:false;
        }
             

        try {
            $serviceRequest = curl_init($this->endpoints->charge_credit_card);
            curl_setopt($serviceRequest, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($serviceRequest, CURLOPT_POSTFIELDS, json_encode($request_data));
            curl_setopt($serviceRequest, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . base64_encode($this->credentials->api_key),
                'Content-Type: application/json',
                'X-Forwarded-For: ' . $this->getUserIP(),
                )
            );
            curl_setopt($serviceRequest, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($serviceRequest));
            curl_close($serviceRequest);
            
            if(isset($response)){
                return $response;
            } else {
                throw new Exception("NO RESPONSE", 500);                
            }
        } catch (Exception $e){
            throw new Exception($e->getMessage(), 500);            
        }
    }
    
    private function getUserIP()
    {
        if ( ! empty( $_SERVER['HTTP_X_SUCURI_CLIENTIP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_X_SUCURI_CLIENTIP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function deleteCreditCard($credit_card_id) {
        return true;
    }

    public function dumpIt()
    {
        $arrDump=[
            "settings"=>$this->plugin_settings->dump(),
            "endpoints"=>$this->endpoints->dump(),
            "credentials"=>$this->credentials->dump()
        ];
        return json_encode($arrDump);
    }

    public function retrieveCardDataByUserId($user_id)
    {
        global $wpdb;
        $table_name= $wpdb->prefix . SuperlistPaggiSetup::PREFIX."creditcards";
        $rs=$wpdb->get_row( "SELECT * FROM $table_name WHERE payer_id = $user_id AND default_credit_card = 1");
        if ($rs)
            return new SuperlistPaggiBase(['data'=>$rs]);
        return NULL;
    }
}