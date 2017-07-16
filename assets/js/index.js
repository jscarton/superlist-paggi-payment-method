jQuery(document).ready(function(){

    jQuery("#number_part_1").mask("9999");
    jQuery("#number_part_2").mask("9999");
    jQuery("#number_part_3").mask("9999");
    jQuery("#number_part_4").mask("9999");           
    jQuery("#cc_ccv").mask("999");
    if(this.value == "AMEX"){
      jQuery("#cc_number_part_4").mask("999");
      jQuery("#cc_ccv").mask("9999");
    }
    if(this.value == 'DINERS'){
      jQuery("#number_part_4").mask("99");
    }

    jQuery(".sp-CC").keyup(this,function(){
      if (parseInt(jQuery(this).data('numberpart'))<4 && this.value.length==4 && this.value.replace("_").length==4)
        jQuery("#number_part_"+(parseInt(jQuery(this).data('numberpart'))+1)).focus();
        var payu_number = "";
        var payu_expiry = jQuery('#validade_1').val()+'/'+jQuery('#validade_2').val();;
        jQuery(".numberPart").each(function(){
            payu_number = payu_number+jQuery(this).val();
        });
        jQuery('#validade-full').val(payu_expiry);
        jQuery('#full-card-number').val(payu_number);
    });

    jQuery("#cc_valid_1").mask("99");
    jQuery("#cc_valid_2").mask("9999");
    jQuery(".irPA").click(this, function(){
      jQuery("#number_part_1").mask("9999");
      jQuery("#number_part_2").mask("9999");
      jQuery("#number_part_3").mask("9999");
      jQuery("#number_part_4").mask("9999");
      jQuery("#cc_ccv").mask("999");

      if(this.value == 'AMEX'){
        jQuery("#number_part_4").mask("999");
        jQuery("#cc_ccv").mask("9999");
      }
      if(this.value == 'DINERS'){
        jQuery("#number_part_4").mask("99");
      }
      jQuery("#cc_type_value").val(this.value);
      switch (this.value)
      {
        case "MASTERCARD":  
          jQuery("#cc_type_1").addClass("superlist-border-orange-x2").addClass('superlist-border-round-10px');
          jQuery("#cc_type_2").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_3").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_4").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_5").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');              
          break;
        case "VISA":       
          jQuery("#cc_type_2").addClass("superlist-border-orange-x2").addClass('superlist-border-round-10px');
          jQuery("#cc_type_1").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_3").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_4").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_5").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          break;
        case "DINERS":      
          jQuery("#cc_type_3").addClass("superlist-border-orange-x2").addClass('superlist-border-round-10px');
          jQuery("#cc_type_1").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_2").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_4").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_5").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          break;
        case "ELO":         
          jQuery("#cc_type_4").addClass("superlist-border-orange-x2").addClass('superlist-border-round-10px');
          jQuery("#cc_type_1").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_3").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_2").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_5").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          break;
        case "AMEX":        
          jQuery("#cc_type_5").addClass("superlist-border-orange-x2").addClass('superlist-border-round-10px');
          jQuery("#cc_type_1").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_3").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_4").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          jQuery("#cc_type_2").removeClass("superlist-border-orange-x2").removeClass('superlist-border-round-10px');
          break;
      }
    });
});