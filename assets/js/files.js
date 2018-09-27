jQuery( '.add-files' ).on( 'click', function() {

	if(jQuery('.variation_id').val() == ""){

		alert('Please select variation');
		return false;
	}

	if(jQuery('.variation_id').val() != ''){
		jQuery.ajax({
		        method: "POST",
		        //url: '?show=uploads&vpid='+jQuery('.variation_id').val()+'&product_id='+jQuery('input[name=product_id]').val()+'&quantity='+jQuery('input[name=quantity]').val(),
		        url: '/product-set?vid='+jQuery('.variation_id').val()+'&pid='+jQuery('input[name=product_id]').val()+'&q='+jQuery('input[name=quantity]').val()+'&set='+jQuery(this).attr('rel'),
		        //url: ajaxurl,
				success : function( response ) {

					jQuery('.files-wrapper').html(response).find('.mk-header, #mk-footer').remove();
					jQuery('.input-text.qty').addClass('grey').prop( "readonly", true );
					jQuery('.variations select').addClass('grey').prop( "disabled", true );
					jQuery('.plus, .minus, .wpf-umf-ufp-cart-items').hide();
					//jQuery('.start_over_button').show();
					//jQuery('.add-files').hide();
					
					jQuery('html, body').animate({scrollTop: jQuery(".product-tabs").offset().top-100}, 1000);

				}
		});
	}

});