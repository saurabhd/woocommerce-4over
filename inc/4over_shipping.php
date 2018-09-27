<?php 
	
if( get_option('4over_api_environment') == 'test'){
	define( 'API_ENDPOINT_4OVER_TEST', 'https://sandbox-api.4over.com/' );
}

if( get_option('4over_api_environment') == 'live'){
	define( 'API_ENDPOINT_4OVER_LIVE', 'https://api.4over.com/' );
}

define( 'PUBLIC_KEY_4OVER', get_option('4over_api_username') );
define( 'PRIVATE_KEY_4OVER', get_option('4over_api_password') );


/********* Get Shipping Prices *************/
function woo4over_shipping_methods($api_params, $method="POST") {
	$option = woo4over_get_product_turnaround($api_params);
	if(count($option) > 0) {
		$api_params['product_info']['turnaround_uuid'] = $option['option_uuid'];
		$option_get = str_replace(API_ENDPOINT_4OVER_TEST.'printproducts/products/', '', $option['option_prices']);
		$option_arr = explode('/', $option_get);
		$api_params['product_info']['option_uuids'][] = $option_arr[(count($option_arr)-2)];
	}
	$url = API_ENDPOINT_4OVER_TEST. 'shippingquote?' . 'apikey='. PUBLIC_KEY_4OVER . '&signature=' . hash_hmac('sha256', $method, hash('sha256', PRIVATE_KEY_4OVER)) . '&max=500';
	$json = json_encode($api_params);

	// Send the request & save response to $resp
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
	
	// Close request to clear up some resources
	return (json_decode($response, true));
}

/************ Get Product TurnArround ************/
function woo4over_get_product_turnaround($api_params, $method="GET") {

	$url = API_ENDPOINT_4OVER_TEST. 'printproducts/products/'.$api_params['product_info']['product_uuid'].'?' . 'apikey='. PUBLIC_KEY_4OVER . '&signature=' . hash_hmac('sha256', $method, hash('sha256', PRIVATE_KEY_4OVER)) . '&max=500';
		
	$args = array(
	    'method' => $method,
	    'timeout' => 20
	);
	$response_call = wp_remote_request( $url, $args);

	$response = wp_remote_retrieve_body($response_call);

	$arrr = [];
	if(count(json_decode($response, true)['product_option_groups']) > 0) {
		foreach (json_decode($response, true)['product_option_groups'] as $product_option_groups) {
			if(str_replace(' ', '_', strtolower(trim($product_option_groups['product_option_group_name']))) == 'turn_around_time') {
				foreach ($product_option_groups['options'] as $options) {
					if(count($options) > 0) {
						$flag = 0;
						foreach ($api_params['product_info'] as $attr_option => $attr_uuid) {
							if((isset($options[$attr_option])) && ($options[$attr_option] == $api_params['product_info'][$attr_option])) {
								$flag = 1;
							}
							else {
								$flag = 0;
							}
						}
						if($flag == 1) {
							return $options;
						}
					}
				}		
			}
		}
	}				
	return $options;
}