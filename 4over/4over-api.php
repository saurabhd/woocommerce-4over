<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}


/* 	$args = array( 
		method => GET | POST | etc,
		
	)	
*/
function rpep_4o_routes($type, $args = []){
	
	if( get_option('4over_api_environment') == 'test'){
		$uri = 'https://sandbox-api.4over.com';
	}

	if( get_option('4over_api_environment') == 'live'){
		$uri = 'https://api.4over.com';
	}

	$separator = '?';
	
	switch ($type) {
		
		// Organizations https://api-users.4over.com/?page_id=47
		case 'get_organizations':
			$uri .= '/organizations';
			$method = 'GET';
			break;
			
		// Addresses https://api-users.4over.com/?page_id=52
		// INCOMPLETE: Still needs address args.
/*		case 'create_address': 
			if (!empty($args['organizations_uuid']) 
				&& !empty($args['users_uuid'])
				&& !empty($args['contacts_uuid'])			   
			) {
				$uri .= "/organizations/" . $args['organizations_uuid'] . "/users/" . $args['users_uuid'] .  "/contacts/" . $args['contacts_uuid'] . "/addresses";
			} else {
				return 'This request is missing at lease one required variable.';	
			}
			$method = 'POST';
			break; //*/ 
		case 'get_address':
			if (!empty($args['address_uuid'])) {
				$uri .= "/addresses/" . $args['address_uuid'];
			} else {
				return 'This request is missing address_uuid.';	
			}
			$method = 'GET';
			break;
/*		case 'update_address':
			if (!empty($args['address_uuid'])) {
				$uri .= "/addresses/" . $args['address_uuid'];
			} else {
				return 'This request is missing at lease one required variable.';	
			}
			$method = 'PUT';
			break; //*/
/*		case 'remove_address':
			if (!empty($args['address_uuid'])) {
				$uri .= "/addresses/" . $args['address_uuid'];
			} else {
				return 'This request is missing at lease one required variable.';	
			}
			$method = 'DELETE';
			break; //*/	
			
		// Print Products https://api-users.4over.com/?page_id=86	
		case 'get_cats':
			if (!empty($args['category_uuid'])){
				// Gets details of a specific category.
				$uri .= '/printproducts/categories/' . $args['category_uuid'];
			} else {
				// Gets a list of all categories.
				$uri .= '/printproducts/categories';
			}
			return rpep_4over_curl($uri,'GET', NULL, $separator);
		case 'get_products':
			if (!empty($args['product_uuid'])){
				// Gets details of a specific product.
				$uri .= '/printproducts/products/' . $args['product_uuid'];
			} elseif (!empty($args['category_uuid'])){
				// Gets products of a specific category.
				$uri .= '/printproducts/categories/' . $args['category_uuid'] . '/products';
			} else {
				// Gets all products. That's a lot!
				$uri .= '/printproducts/products';
			}
			return rpep_4over_curl($uri,'GET', NULL, $separator);
		case 'get_products_feed':
			$url .= '/printproducts/productsfeed';
			$method = 'GET';
		case 'get_options':
			if (!empty($args['product_uuid'])){
				// Gets a list of all options available to a specific product.
				$uri .= '/printproducts/products/' . $args['product_uuid'] . '/optiongroups';				
			} elseif (!empty($args['optiongroup_uuid'])){
				// Gets a list of all options in a specific option group.
				$uri .= '/printproducts/optiongroups/' . $args['optiongroup_uuid'] . '/options';
			} else {
				// Gets all option groups system-wide. Who needs that many?
				$uri .= '/printproducts/optiongroups';
			}
			$method = 'GET';
			break;
		case 'get_turnaround_options':
			$uri .= '/printproducts/products/' . $args['product_uuid'] . '/optiongroups';				
			$method = 'GET';
			break;
		case 'get_base_prices':
			if (!empty($args['product_uuid'])){
				$uri .= '/printproducts/products/' . $args['product_uuid'] . '/baseprices';
			} else {
				return 'This request is missing product_uuid.';
			}
			$method = 'GET';
			break;
		case 'get_product_price':
			if (!empty($args['product_uuid'])){
				$parameter = http_build_query($args);
				$uri .= '/printproducts/productquote?'.$parameter;
				$separator = '&';
			} else {
				return 'This request is missing product_uuid.';
			}
			$method = 'GET';
			break;
		case 'get_file_requirements':
				$uri .= '/requirements/products';
			$method = 'POST';
			break;
		case 'process_order':
			$uri .= '/orders';
			$method = 'POST';
			break;
		case 'order_status':
			$uri .= '/orders/'.$args['job_id'].'/status';
			$method = 'GET';
			break;
		case 'order_tracking':
			$uri .= '/orders/'.$args['job_id'].'/tracking';
			$method = 'GET';
			break;
		case 'upload_file':
			$uri .= '/files';
			$method = 'POST';
			break;
		
		// Shipping
		case 'shipping_quote':
			$uri .= '/shippingquote';
			
			$required_args = ['product_uuid', 'runsize_uuid', 'turnaround_uuid', 'colorspec_uuid', 'option_uuids', 'address', 'city', 'state', 'country', 'zipcode'];
			$other_accepted_args = ['address2', 'sets'];
			
			// Stop if missing arguments and trash unneccesary args.
			foreach ($args as $key => $value){
				if (!in_array($key, $required_args)){ 
					$missing_args .= $key . ' '; // Get a list of all missing required arguments.
				} elseif (!in_array($key, $required_args) && !in_array($key, $other_accepted_args)){ 
					unset($args[$key]); // Not acceptable arg, so trash it before it ends up in the curl.
				}
				if (!empty($missing_args)){ // If req'd arg is missing, then stop everything!
					return 'The following required arguments are missing: ' . $missing_args; 
				}
			}
			$url_args = $args; // Change to variable that will feed into curl.
			$method = 'POST';
			break;
			
		// Address Validation
		case 'validate_address':
			$uri .= '/addressvalidation';
			
			$required_args = ['address', 'city', 'state', 'country', 'zipcode'];
			$other_accepted_args = ['address2'];
			
			// Stop if missing arguments and trash unneccesary args.
			foreach ($args as $key => $value){
				if (!in_array($key, $required_args)){ 
					$missing_args .= $key . ' '; // Get a list of all missing required arguments.
				} elseif (!in_array($key, $required_args) && !in_array($key, $other_accepted_args)){ 
					unset($args[$key]); // Not acceptable arg, so trash it before it ends up in the curl.
					
				} elseif ($key == 'address2'){
					$args['address'] .= ' ' . $value;
					unset($args['address2']);
				}
				if (!empty($missing_args)){ // If req'd arg is missing, then stop everything!
					return 'The following required arguments are missing: ' . $missing_args; 
				}
			}
			$url_args = $args; // Change to variable that will feed into curl.
			$method = 'POST';
			break;
			
			
		// Bad Type
		default:
			return 'Request type not recognized.';//Do not pass go!
	}
	if( $method == 'POST' && !empty($args)){
		return rpep_4over_curl($uri,$method, $args);
	}else{
		return rpep_4over_curl($uri,$method, NULL, $separator);	
	}
	

}

// @json : post data
	
function rpep_4over_curl($uri,$method, $json = NULL, $separator = '?') {

	$public_key = get_option('4over_api_username');
	$private_key = get_option('4over_api_password');
	
	if ($method == 'GET' || $method == 'DELETE'){
		//Authenticate
		$url = $uri. $separator . 'apikey='. $public_key . '&signature=' . hash_hmac('sha256', $method, hash('sha256', $private_key)) . '&max=500';
		
		$args = array(
		    'method' => $method,
			'timeout' => 20		    
		);
		$response_call = wp_remote_request( $url, $args);

		$response = wp_remote_retrieve_body($response_call);

		return (json_decode($response, true));
	}

	if ($method == 'POST'){
		//Authenticate

		$url = $uri. '?' . 'apikey='. $public_key . '&signature=' . hash_hmac('sha256', $method, hash('sha256', $private_key)) . '&max=500';

		$args = array(
		    'method' => $method,
		    'timeout' => 20,
			'headers' => array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($json)),
			'body' => $json
		    );
		$response_call = wp_remote_request( $url, $args);

		$response = wp_remote_retrieve_body($response_call);
		
		return (json_decode($response, true));
	}
	
	return ('HTTP Method is not GET!');
		
}

?>