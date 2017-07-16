<?php use Roots\Sage\Assets;?>
<?php $current_user=wp_get_current_user(); ?>
<fieldset class="superlist-text">
        <div class="row">            
            <?php
                $spcc= new SuperlistPaggiCreditCard();
                $existing=$spcc->retrieveCardDataByUserId($current_user->ID);
                if (!is_null($existing))
                    $current_val=$existing->data->id;
                else
                    $current_val="new";
                if ($current_val!="new")
                {
            ?>
            <div class="col-md-5 col-sm-5 col-xs-12">
                <div class="cont_central2 ">
                    <div class="row">
                        <div class="col-xs-4 col-sm-4 col-md-4">
                            <h4 class="titH">Atual 
                                <input type="radio" id="sim-dos" name='superlist-paggi-use-this' value="<?php echo $existing->data->id;?>" checked>
                                <label for="sim-dos"><span></span></label>
                            </h4>
                        </div>

                        <div class="col-xs-8 col-sm-8 col-md-8">
                            <img src="<?php echo Assets\asset_path("images/card-logos/".$existing->data->payment_method.".png"); ?>" alt="" class="imaPA" style="margin:0;float:right; height:auto!important;">
                            <input type="hidden" id="stored_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/".$existing->data->payment_method.".png"); ?>"/> 
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-12 superlist-text interlineado">
                            <p style="margin-bottom:5px;">Número do cartão: <?php echo $existing->data->payment_maskednumber ?></p>
                            <p style="margin-bottom:5px;">Titular do cartão: <?php echo strtoupper($existing->data->payer_name); ?></p>                            
                        </div>

                    </div>
                </div>
            </div>
            <?php } else { ?>
                <div class="col-md-5">
                <div class="cont_central_gris2">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <h4 style="color:#a1a3a5;">O usuário não tem um método de pagamento armazenado</h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="col-md-7 col-sm-7 col-xs-12">
                <div class="cont_central_gris2" style="opacity:1;">

                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <h4 style="color:#a1a3a5;">
                                Alterar cartão de crédito 
                                <input type="radio" id="nao-dos" name='superlist-paggi-use-this' value="new">
                                <label for="nao-dos"><span></span></label>
                            </h4>

                        </div>
					 
                     <div class="col-md-12 centPA">
                        <div class="col-xs-1 col-sm-1 col-md-1"></div>
                        <div class="col-xs-2 col-sm-2 col-md-2">
                            <div class="marcoIma"> <img src="<?php echo Assets\asset_path("images/card-logos/MASTERCARD.png"); ?>" alt="" class="imgCenter tamImaFP"></div>
                            <input type="radio" class="irPA" name="superlist-paggi-card-type" value="MASTERCARD" id="mastercard_payment_method">
                            <input type="hidden" id="mastercard_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/MASTERCARD.png"); ?>"/>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2">
                            <div class="marcoIma"> <img src="<?php echo Assets\asset_path("images/card-logos/VISA.png"); ?>" alt="" class="imgCenter tamImaFP"></div>
                            <input type="radio" class="irPA" name="superlist-paggi-card-type" value="VISA" id="visa_payment_method">
                            <input type="hidden" id="visa_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/VISA.png"); ?>"/>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2">
                            <div class="marcoIma"> <img src="<?php echo Assets\asset_path("images/card-logos/DINERS.png"); ?>" alt="" class="imgCenter tamImaFP"></div>
                            <input type="radio" class="irPA" name="superlist-paggi-card-type" value="DINERS" id="diners_payment_method">
                            <input type="hidden" id="diners_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/DINERS.png"); ?>"/>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2">
                            <div class="marcoIma"> <img src="<?php echo Assets\asset_path("images/card-logos/ELO.png"); ?>" alt="" class="imgCenter tamImaFP"></div>
                            <input type="radio" class="irPA" name="superlist-paggi-card-type" value="ELO" id="elo_payment_method">
                            <input type="hidden" id="elo_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/ELO.png"); ?>"/>
                        </div>

                        <div class="col-xs-2 col-sm-2 col-md-2">
                            <div class="marcoIma"> <img src="<?php echo Assets\asset_path("images/card-logos/AMEX.png"); ?>" alt="" class="imgCenter tamImaFP"></div>
                            <input type="radio" class="irPA" name="superlist-paggi-card-type" value="AMEX" id="amex_payment_method">
                            <input type="hidden" id="amex_payment_method_img_url" value="<?php echo Assets\asset_path("images/card-logos/AMEX.png"); ?>"/>
                        </div>
                        <div class="col-xs-1 col-sm-1 col-md-1"></div>
                    </div>
					<div class="col-md-12" style="display: inline-block;">
                        <div class="col-sm-6 col-md-6  col-lg-8">
                            <label for="" class="gris superlist-text-xs">Número de cartão</label>
                            <div class="col-md-12" style="padding-left:0px!important;">
                                <div class="col-xs-6 col-sm-3  col-md-3" style="padding-left:0px!important"><input type="text" data-numberpart="1" class="sp-inputEP numberPart sp-CC text-center" id="number_part_1"></div>
                                <div class="col-xs-6 col-sm-3  col-md-3" style="padding-left:0px!important"><input type="text" data-numberpart="2" class="sp-inputEP numberPart sp-CC text-center" id="number_part_2"></div>
                                <div class="col-xs-6 col-sm-3  col-md-3" style="padding-left:0px!important"><input type="text" data-numberpart="3" class="sp-inputEP numberPart sp-CC text-center" id="number_part_3"></div>
                                <div class="col-xs-6 col-sm-3  col-md-3" style="padding-left:0px!important"><input type="text" data-numberpart="4" class="sp-inputEP numberPart sp-CC text-center" id="number_part_4"></div>                                    
                                <input type="hidden" name="superlist-paggi-number" id="full-card-number">
                            </div>

                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-4" style="padding-left: 10px; display:inline-block;">
                            <label for="" class="gris superlist-text-xs">Cód. do segurança</label>
                            <input type="text" class="sp-inputEP text-center sp-CC" name="superlist-paggi-cvc" id="cc_ccv">
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6" style="display:inline-block;">
                            <label for="" class="gris superlist-text-xs">Nome do titular</label>
                            <input type="text" class="sp-inputEP sp-CC" name="superlist-paggi-card-name" id="superlist-paggi-card-name">
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6" style="display:inline-block !important; ">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <label for="" class="gris superlist-text-xs">Validade do cartão</label>
                            </div>
                            <div class="col-sm-2 col-md-4 col-xs-5">
                                <input type="text" class="sp-inputEP text-center sp-CC" name="validade_1" id="validade_1"> 
                            </div>
                            <div class="col-sm-1 col-md-1 col-lg-1 gris col-xs-1">/</div>
                            <div class="col-sm-3 col-md-6 col-xs-6">
                                <input type="text" class="sp-inputEP text-center sp-CC" name="validade_2" id="validade_2">
                            </div>
                            <input type="hidden" name="superlist-paggi-expiry" id="validade-full">
                             <input type="hidden" name="superlist-paggi-dni" value="<?php echo $current_user->billing_cpf; ?>">
                        </div>

                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</fieldset>
<script src="<?php echo SUPERLIST_PAGGI_ROOT_URL?>assets/js/index.js"></script>