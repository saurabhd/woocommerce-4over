jQuery(document).ready(function($) {

	
	$(document).on('click', '.single-product .next-click', function(e) {
		e.preventDefault();
		var set = $(this).attr('data-set');
		$('.set'+ (set-1)).removeClass('activeSet');
		$('.set'+ (set-1)).removeClass('disabled');
		$('.set'+ set).removeClass('disabled');
		$('.set'+ set).addClass('activeSet');
		$('.set'+ (set-1) + ' .set-wrapper').hide();
		$('.set'+ set + ' .set-wrapper').show();
		/*$('.set'+ (set-1) + ' .next-click').hide();
		$('.set'+ set + ' .next-click').show();*/
	});

	//Add Mulitple Shipping Address
	$(document).on('click', 'body.single-product .customize_button button.start_customization, body.single-product .modify-address-button', function(e) {
		e.preventDefault();
		var color = $('option:selected', 'select[name="attribute_pa_colorspec"]').text();
		var price = $('.woocommerce-variation-price .woocommerce-Price-amount').text();
		
		$('input[name="quantity"]').attr('readonly', true);
		$('input[name="quantity"]').attr('style', 'color:gray');
		var form_data = $('form.variations_form').find("input[name!=add-to-cart], select").serialize() + '&price='+price+'&color='+color;
		load_available_address(custom_ajax.ajaxurl, form_data, e);
		//$('html, body').animate({scrollTop: $(".customize_print").offset().top});
		$('.variations_form select option:not(:selected)').prop('disabled', true); //disabled select box options
		//$('.variations_form select').prop('disabled', true); //disabled select box
		
	});

	function load_available_address(ajaxurl, form_data, e) {
		form_data = form_data + '&action=add_new_address';
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: form_data,
			success: function(response) {
				$(this).remove();
				$('div.customize_button').html('<a class="button start_over" href="'+custom_ajax.current_url+'">Start From Over</a>');
				$('div.customize_print').html(response.html);
				$('body').append(response.scripts);
			},
			error: function(error_response) {
				console.log(error_response);
			}
		});
		e.stopImmediatePropagation();
    	return false;
	}

	// Save Shipping Address
	$(document).on('click', 'input#use_address', function(e) {
		e.preventDefault();
		var form = $(this).parents('form.wcms-address-form');
		var id = $(this).parents('form.wcms-address-form').find('#address_id').val();
		var data = form.serialize() + "&action=woo4over_save_to_address_book";
		var color = $('option:selected', 'select[name="attribute_pa_colorspec"]').text();
		var price = $('.woocommerce-variation-price .woocommerce-Price-amount').text();
		var form_data = $('form.variations_form').find("input[name!=add-to-cart], select").serialize() + '&price='+price+'&color='+color;
		$.ajax({
			url: custom_ajax.ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				if(response.ack == 'ERR') {
					alert(response.message);
				}
				else {
					$('#wpbootstrapModal').modal('hide');
					$('#wpbootstrapModal .modal-body .add_new_shipping_form').remove();
					load_available_address(custom_ajax.ajaxurl, form_data, e);
					console.log(response);
				}
			},
			error: function(error_response) {
				console.log('error_res' + error_response);
			}
		});
		e.stopImmediatePropagation();
    	return false;
	});

	//Load Stored Address
	$(document).on('change', '.wcms-address-form #ms_addresses', function() {
		if($(this).val() != "") {
			$(this).parents('form.wcms-address-form').find('#shipping_first_name').val($('option:selected', this).attr('data-shipping_first_name'));
			$(this).parents('form.wcms-address-form').find('#shipping_last_name').val($('option:selected', this).attr('data-shipping_last_name'));
			$(this).parents('form.wcms-address-form').find('#shipping_company').val($('option:selected', this).attr('data-shipping_company'));
			$(this).parents('form.wcms-address-form').find('#shipping_country').val($('option:selected', this).attr('data-shipping_country'));
			$(this).parents('form.wcms-address-form').find('#shipping_address_1').val($('option:selected', this).attr('data-shipping_address_1'));
			$(this).parents('form.wcms-address-form').find('#shipping_address_2').val($('option:selected', this).attr('data-shipping_address_2'));
			$(this).parents('form.wcms-address-form').find('#shipping_city').val($('option:selected', this).attr('data-shipping_city'));
			$(this).parents('form.wcms-address-form').find('#shipping_state').val($('option:selected', this).attr('data-shipping_state'));
			$(this).parents('form.wcms-address-form').find('#shipping_postcode').val($('option:selected', this).attr('data-shipping_postcode'));
		}
		else {
			$(this).parents('form.wcms-address-form').trigger('reset');
		}
	});

	//Select Shipping Address for different Quantity Sets
	$(document).on('change', 'form[id^=address_form_] select.shipping', function(e) {
		e.preventDefault();
		if($("option:selected", this).val() != '' && $("option:selected", this).val() != 'newaddress') {
			var form = $(this).parents('form[id^=address_form_]');
			$.ajax({
				url: custom_ajax.ajaxurl,
				type: 'POST',
				data: form.serialize() + "&action=set_shipping_address",
				success: function(response) {
					if(response == 'true') {
						getShippingAddressData(custom_ajax.ajaxurl, form, e);
					}
				},
				error: function(error_response) {
					console.log('error_res' + error_response);
				}
			});
			e.stopImmediatePropagation();
    		return false;
		}
		else if($("option:selected", this).val() == 'newaddress') {
			woo4over_add_new_shipping_address(custom_ajax.ajaxurl, e);
		}
	});

	//Get Shipping Address Data
	function getShippingAddressData(ajaxurl, form, e) {
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: form.serialize() + '&action=load_shipping_data',
			success: function(response) {
				$(form).find('select.shipping_method option').remove();
				$(form).find('select.shipping_method').append(response);
				// $('div.customize_print').html(response);
			},
			error: function(error_response) {
				console.log(JSON.stringify(error_response));
			}
		});
		e.stopImmediatePropagation();
    	return false;
	}

	// Load Add new Shipping From
	function woo4over_add_new_shipping_address(ajaxurl, e) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {action: 'add_new_shipping_address'},
			success: function(response) {
				$('#wpbootstrapModal .modal-body').html(response);
				$('#wpbootstrapModal').modal('show');
			},
			error: function(error_response) {
				console.log('error_res' + JSON.stringify(error_response));
			}
		});
		e.stopImmediatePropagation();
    	return false;
	}

	//Select Shipping Address for different Quantity Sets
	function save_product_data(ajax_url, form_data, e) {
		$.ajax({
			url: ajax_url,
			type: 'POST',
			data: form_data,
			success: function(response) {
				console.log(response);
			},
			error: function(error_response) {
				console.log(error_response);
			}
		});
		e.stopImmediatePropagation();
    	return false;
	}
	$(document).on('click', 'form[id^=address_form_] input[name="set_addresses_data"]', function(e) {
		e.preventDefault();
		var form = $(this).parents('form[id^=address_form_]');
		var form_data = form.serialize() + '&action=save_product_set_data';
		save_product_data(custom_ajax.ajaxurl, form_data, e);
	});
	$(document).on('change', 'select.4over_shipping_price', function(e) {
		e.preventDefault();
		var form = $(this).parents('form[id^=address_form_]');
		var form_data = form.serialize() + '&action=save_product_set_data';
		save_product_data(custom_ajax.ajaxurl, form_data, e);
	});


	$(document).on('click', 'a.switchTab', function(e) {
		var active_class = $(this).attr('data-active');
		var deactive_class = $(this).attr('data-deactive');
		$(this).parents('div.wsma-dataset').find('.'+active_class).attr('style', 'display:block');
		$(this).parents('div.wsma-dataset').find('.'+deactive_class).attr('style', 'display:none');
	});

	//Delete Product From Cart
	$(document).on('click', '.delete-product-line-item', function(e) {
		var product_id = $(this).data("product-id");
		var unique_key = $(this).data("product-unique-key");
		var index = $(this).data("index");
		var vid = $(this).data("variation-id");
		// $("#delete_variation_id").val( variation_id );
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: custom_ajax.ajaxurl,
			data: {action: 'woo4over_remove_item_from_cart', product_id: product_id, unique_key: unique_key, index: index, vid: vid},
			success: function (res) {
				if (res == 'true') {
					location.reload();
				}
			}
		});

	});

    $(document).on('change', '.wcms-address-form select#shipping_country, input.country_to_state', function() {
    	/* State/Country select boxes */
	    var states_json = custom_ajax.countries.replace(/&quot;/g, '"');
	    var states = $.parseJSON( states_json );
	    var country = $(this).val();
	    var statebox = $(this).parents('div.address-column').find('#billing_state, #shipping_state, #calc_shipping_state');
        var parent = statebox.parent();

        var input_name = statebox.attr('name');
        var input_id = statebox.attr('id');
        var value = statebox.val();
        placeholder = statebox.attr( 'placeholder' ) || statebox.attr( 'data-placeholder' ) || '';
        console.log(statebox);
     	if (states[country]) {
            if (states[country].length == 0) {
            	statebox.parent().hide();
            	statebox.replaceWith('<input type="hidden" class="hidden" name="' + input_name + '" id="' + input_id + '" value="" placeholder="' + placeholder + '" />');
            }
            else {
            	var options = '';
                var state = states[country];
                for(var index in state) {
                    options = options + '<option value="' + index + '">' + state[index] + '</option>';
                }
                statebox.parent().show();
                if (statebox.is('input')) {
                    // Change for select
                    statebox.replaceWith('<select name="' + input_name + '" id="' + input_id + '" class="state_select" placeholder="' + placeholder + '"></select>');
                    statebox = $(this).closest('div').find('#billing_state, #shipping_state, #calc_shipping_state');
                }
                statebox.html( '<option value="">' + custom_ajax.i18n_select_state_text + '</option>' + options);
				statebox.val(value);
            }
        }
    });

    $(document).on('click', 'button.custom-add-to-cart', function(e) {
    	console.log('add to cart clicked');
    	$('form.variations_form').submit();
    });

    $(document).on('click', 'button.custom-checkout', function(e) {
    	$('#quick-checkout-flag').val(1); 
    	$('form.variations_form').submit();
    });


    /**** Strart From Over ****/
    $(document).on('click', '.customize_button .start_over', function(e) {
    	e.preventDefault();
    	$.ajax({
    		url: custom_ajax.ajaxurl,
    		type: 'POST',
    		data: {action: 'start_from_over', form_action: 'distroy_session'},
    		success: function(response) {
    			if(response == 'true') {
    				$('form.variations_form')[0].reset();
    				window.location.reload(true);
    			}
    			else {
    				$('form.variations_form')[0].reset();
    				window.location.reload(true);
    			}
    		}
    	});
    });

    /**** Get 4over Shipping AJAX ********/

    $(document).on('change', 'select.shipping_method', function(e) {
    	e.preventDefault();
		var form = $(this).parents('form[id^=address_form_]');
		var form_data = form.serialize() + '&action=load_4over_shipping';
    	$.ajax({
    		url: custom_ajax.ajaxurl,
    		type: 'POST',
    		data: form_data,
    		success: function(response) {
    			$(form).find('select.4over_shipping_price').removeAttr('disabled');
    			$(form).find('select.4over_shipping_price option').remove();
				$(form).find('select.4over_shipping_price').append(response.shipping_option);
				$(form).parents('div.wsma-dataset').find('.js-shipblock .detailDate').text(response.shipping_time);
				$(form).parents('div.wsma-dataset').find('.js-shipblock .detailDelivery').text(response.delivery_time);
				if($(form).find('.addr_form_row_data input[name="4over_shipping_tax"]').length > 0) {
					$(form).find('.addr_form_row_data input[name="4over_shipping_tax"]').append('<input type="hidden" name="4over_shipping_tax" value="'+response.shipping_tax+'">');
				}
				else {
					$(form).find('.addr_form_row_data').append('<input type="hidden" name="4over_shipping_tax" value="'+response.shipping_tax+'">');
				}
				
    		},
    		error: function(error_response) {

    		}
    	});
    });

    /*************** Update Checkout on 4over price selection ****************/
    // $(document).on('change', 'select.4over_shipping_price', function(e) {
    // 	$( 'body' ).trigger( 'update_checkout' );
    // });
    if($('body.woocommerce-cart').length > 0) {
    	console.log('update checkout');
		$( document.body ).trigger( 'update_checkout' );
    	//update_shipping_method
    }

});