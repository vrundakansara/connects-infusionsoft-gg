// JavaScript Document

jQuery(document).on("change keyup paste keydown","#infusionsoft_api_key", function(e) {
	var val = jQuery(this).val();
	if( val !== "" )
		jQuery("#auth-infusionsoft").removeAttr('disabled');
	else
		jQuery("#auth-infusionsoft").attr('disabled','true');
});

jQuery(document).on( "click", "#auth-infusionsoft", function(e){
	e.preventDefault();
	jQuery(".smile-absolute-loader").css('visibility','visible');
	var infusionsoft_api_key = jQuery("#infusionsoft_api_key").val();
	var infusionsoft_app = jQuery("#infusionsoft_app").val();
	
	var action = 'update_infusionsoft_authentication';
	var data = {action:action,infusionsoft_api_key:infusionsoft_api_key,infusionsoft_app:infusionsoft_app};
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType: 'JSON',
		success: function(result){
      console.log(result);
			if(result.status == "success" ){
				jQuery(".bsf-cnlist-mailer-help").hide();
				jQuery("#save-btn").removeAttr('disabled');
				jQuery("#infusionsoft_api_key").closest('.bsf-cnlist-form-row').hide();
				jQuery("#infusionsoft_app").closest('.bsf-cnlist-form-row').hide();
				jQuery("#auth-infusionsoft").closest('.bsf-cnlist-form-row').hide();
				jQuery(".infusionsoft-list").html(result.message);

			} else {
				jQuery(".infusionsoft-list").html('<span class="bsf-mailer-success">'+result.message+'</span>');
			}
			jQuery(".smile-absolute-loader").css('visibility','hidden');
		}
	});
	e.preventDefault();
});

jQuery(document).on( "click", "#disconnect-infusionsoft", function(){
															
	if(confirm("Are you sure? If you disconnect, your previous campaigns syncing with Infusionsoft will be disconnected as well.")) {
		var action = 'disconnect_infusionsoft';
		var data = {action:action};
		jQuery(".smile-absolute-loader").css('visibility','visible');
		jQuery.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			dataType: 'JSON',
			success: function(result){
      console.log(result);
				
				jQuery("#save-btn").attr('disabled','true');
				if(result.message == "disconnected" ){
					jQuery("#infusionsoft_app").val('');
					jQuery("#infusionsoft_api_key").val('');
					jQuery(".infusionsoft-list").html('');
					jQuery("#disconnect-infusionsoft").replaceWith('<button id="auth-infusionsoft" class="button button-secondary auth-button" disabled="true">Authenticate Infusionsoft</button><span class="spinner" style="float: none;"></span>');
					jQuery("#auth-infusionsoft").attr('disabled','true');
				}
				jQuery('.bsf-cnlist-form-row').fadeIn('300');
				jQuery(".bsf-cnlist-mailer-help").show();
				jQuery(".smile-absolute-loader").css('visibility','hidden');
			}
		});
	}
	else {
		return false;
	}
});