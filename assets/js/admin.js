// Replace Variation ID with 4over product name

jQuery( document ).ready( function() {
	jQuery( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
		var $panels = jQuery( this ).find( '.woocommerce_variation' );

		$panels.each( function() {
			var product_name = jQuery( this ).find( '[class^="4over_product_textfield"]' ).val();
			if ( product_name ) {
				jQuery( this ).find( 'h3 > strong' ).append( '<span style="display:inline-block;min-width:8em;"> - ' + product_name + '</span><br>');
			}
		} );
	} );

	jQuery( '.edit-order' ).on( 'click', function() {
		var $container = jQuery(this).closest( ".order_data_column_container" );
		$container.find('.edit-order-item').show();
	});

	jQuery( '.process-order' ).on( 'click', function() {

		var $container = jQuery(this).closest( ".order_data_column_container" );

		var $post_id = $container.find('.post_id').val();
		var $order_id = $container.find('.order_id').val();
		var $variation_id = $container.find('.variation_id').val();
		var $variation_product_uuid = $container.find('.variation_product_uuid').val();
		var $colorspec = $container.find('.pa_colorspec').val();
		var $runsize = $container.find('.pa_runsize').val();
		var $shipping_address = $container.find('.shipping_address').html();
		var $billing_address = $container.find('.billing_address').html();
		var $shipping_methods = $container.find('.shipping_methods').val();
		var $store_address = $container.find('.store_address').html();
		var $currency_code = $container.find('.currency_code').val();

		jQuery.ajax({
	        method: "POST",
	        url: ajaxurl,
	        data: { 'action':'radiant_process_order', 'post_id':$post_id, 'variation_id':$variation_id, 'colorspec_uuid':$colorspec, 'runsize_uuid':$runsize, 'product_uuid':$variation_product_uuid, 'shipping_address':$shipping_address, 'billing_address':$billing_address, 'order_id':$order_id, 'store_address':$store_address, 'currency_code':$currency_code},
			
			success : function( response ) {
				response = JSON.parse(response);
				var $order_id_response = response.order_id;
				
				if(response.status != 'error'){
					$container.find('.order_error_wrapper').hide();
					$container.find('.job-id').html($order_id_response);
					$container.find('.order-status').val('Printing');
				}
				if(response.status == 'error'){
					$container.find('.order_error').html(response.status_text);
					$container.find('.order_error_message').html(response.message);
					$container.find('.order_error_wrapper').show();
				}

			}
	      });

	});


	jQuery( '.check-item-status' ).on( 'click', function() {

		var $container = jQuery(this).closest( ".order_data_column_container" );

		var $job_id = $container.find('.job-id').html();
		var $post_id = $container.find('.post_id').val();
		var $variation_id = $container.find('.variation_id').val();

		jQuery.ajax({
	        method: "POST",
	        url: ajaxurl,
	        data: { 'action':'ajax_order_item_action', 'type': 'order_status', 'job_id':$job_id, 'post_id':$post_id, 'variation_id':$variation_id },
			
			success : function( response ) {
				response = JSON.parse(response);
				var $response_status_code_uuid = response.status_code_uuid;
				
				if(response.response_status_code_uuid != ''){
					$container.find('.order-item-status').html(response.status);
				}
				if(response.status == 'error'){
					$container.find('.order-item-status').html(response.message);
				}

			}
	      });

	});

	jQuery( '.check-item-tracking' ).on( 'click', function() {

		var $container = jQuery(this).closest( ".order_data_column_container" );

		var $job_id = $container.find('.job-id').html();
		var $post_id = $container.find('.post_id').val();
		var $variation_id = $container.find('.variation_id').val();

		jQuery.ajax({
	        method: "POST",
	        url: ajaxurl,
	        data: { 'action':'ajax_order_item_action', 'type': 'order_tracking', 'job_id':$job_id, 'post_id':$post_id, 'variation_id':$variation_id },
			
			success : function( response ) {
				response = JSON.parse(response);
				var $response_status_code_uuid = response.status_code_uuid;
				
				if(response.response_status_code_uuid != ''){
					$container.find('.tracking-number').html(response.status);
				}
				if(response.status == 'error'){
					$container.find('.tracking-number').html(response.message);
				}

			}
	      });

	});	

	jQuery( '.reset-art' ).on( 'click', function() {

		var $container = jQuery(this).closest( ".order_data_column_container" );

		var $post_id = $container.find('.post_id').val();
		var $variation_id = $container.find('.variation_id').val();
		var $product_unique_key = $container.find('.product_unique_key').val();
		var $upload_file_index = $container.find('.upload_file_index').val();

		jQuery.ajax({
	        method: "POST",
	        url: ajaxurl,
	        data: { 'action':'ajax_order_item_action', 'type': 'reset_art', 'post_id':$post_id, 'product_unique_key':$product_unique_key, 'variation_id':$variation_id, 'upload_file_index':$upload_file_index },
			
			success : function( response ) {
				response = JSON.parse(response);
				
				if(response.status == 'success'){
					$container.find('.customer_art_wrapper').hide();
				}

			}
	      });

	});	

	jQuery('.upload').on('click', function() {
	    
	    /*alert('development under progress');
	    return false;*/

	    var $container = jQuery(this).closest( ".order_data_column_container" );
	    var $file_wrapper = jQuery(this).closest( ".customer_art_wrapper" );
	    
	    
	    var $post_id = $container.find('.post_id').val();
	    var $product_id = $container.find('.product_id').val();
	    var $variation_id = $container.find('.variation_id').val();
		var $product_unique_key = $container.find('.product_unique_key').val();
		var $upload_set_index = jQuery(this).data('upload-set-index');
		var $upload_type_name = jQuery(this).data('upload-type-name');
		var $upload_file_index = $container.find('.upload_file_index').val();   

	   //alert('Under development');
	    var file_data = jQuery(this).prev('#sortpicture').prop('files')[0];   
	    var form_data = new FormData();                  
	    form_data.append('file', file_data);
	    form_data.append('action', 'ajax_order_item_action');
	    form_data.append('type', 'upload_file');
	    form_data.append('product_id', $product_id);
	    form_data.append('variation_id', $variation_id);
	    form_data.append('product_unique_key', $product_unique_key);
	    form_data.append('upload_set_index', $upload_set_index);
	    form_data.append('upload_type_name', $upload_type_name);
	    form_data.append('upload_file_index', $upload_file_index);
	    form_data.append('post_id', $post_id);

	    //console.log(form_data);                             
	    jQuery.ajax({
	    	method: "POST",
	        url: ajaxurl,
	        data: form_data,          
	        cache: false,
	        contentType: false,
	        processData: false,
	        success : function(response){
	            //alert(php_script_response); // display response from the PHP script, if any
	        }
	     });

	});

	jQuery('.radiant_product_type').on('change', function() {
        if(this.value == '4over'){
        	jQuery('#product_type_markup_wrapper').show();
        	jQuery("#category-uuid-selector1").prop('required',true);
        	jQuery("#product-uuid-selector1").prop('required',true);
	    }else{
	    	jQuery('#product_type_markup_wrapper').hide();
	    	jQuery("#category-uuid-selector1").prop('required',false);
        	jQuery("#product-uuid-selector1").prop('required',false);
	    }
	});


} );