<?php

//https://wordpress.stackexchange.com/questions/82032/how-do-i-only-load-a-plugin-js-on-its-settings-pages

add_action( 'admin_init', 'rpep_admin_init' );
add_action( 'admin_menu', 'rpep_admin_menu' );

function rpep_admin_init() {
    /* Register our script. */
	if ($_GET['post_type'] == 'product' ||  $_GET['action'] == 'edit' ){
	    wp_register_script( 'rpep_4over_uuid_finder', plugins_url('/uuid_finder_ajax_custom.js', __FILE__) );
	}
}

function rpep_admin_menu() {
    /* Using registered $page handle to hook script load */
	$page = 'rpep-sub-page-4over';
//    add_action('admin_print_scripts-' . $page, 'rpep_admin_scripts');
    add_action('admin_print_scripts', 'rpep_admin_scripts');
}

function rpep_admin_scripts() {
    /*
     * It will be called only on your plugin admin page, enqueue our script here
     */
	wp_enqueue_script( 'rpep_4over_uuid_finder');
	wp_enqueue_script( 'rpep_4over_uuid_finder_custom');
}
	
//AJAX function to get all categories
add_action( 'wp_ajax_rpep_get_4over_list', 'rpep_get_4over_list' );
function rpep_get_4over_list() {
	// set the parameters for the API call
	switch ($_POST['type']){
		case 'category':
			$route = 'get_cats';
			$next = 'product';
			break;
		case 'product':
			$route = 'get_products';
			$args['category_uuid'] = sanitize_text_field($_POST['uuid']);
//			$next = 'optiongroup';
			$next = 'no-more';
			break;
		case 'optiongroup':
			$route = 'get_options';
			$args['product_uuid'] = sanitize_text_field($_POST['uuid']);
			$next = 'option';
			break;
		case 'option':
			$route = 'get_options';
			$args['optiongroup_uuid'] = sanitize_text_field($_POST['uuid']);
			$next = 'no-more';
			break;
		default:
			$next = 'no-more';
			break;
	}
	
	//Run API Call
	$results = rpep_4o_routes($route, $args);
	$results['next'] = $next;
	
	//Assign 'rpep_uuid' value
	$results = rpep_prep_entitites($results,'uuid');
	
	//Assign 'rpep_title' value
	$results = rpep_prep_entitites($results);
	
	//Alphabetize based on rpep_title
//	$results = rpep_abc_entities($results);
	
	//Convert to JSON object, echo, and die
	wp_send_json(json_encode($results));

}

//Search for type as a string in keys of entities. If found, then copy it's value to a new key called rpep_$type.
//This makes sure that the info needed for recursion in the JS file is always in the same place, regardless of the 4over route.
function rpep_prep_entitites($fourover_query_results,$type = 'title'){
	
	if (is_array($fourover_query_results['entities'])){
		foreach ($fourover_query_results['entities'] as $entitykey => $entityarray){
			if (is_array($entityarray)){
				foreach ($entityarray as $metakey => $metavalue){
					
					if ($type === 'title'){
					
						// Check to see if there is a field containing the word "name" in the key.
						// Even if the title has already been set, then the name will override it.
						if (strpos($metakey, 'name') !== false){
							//... if so, then make that name value the title.
							$fourover_query_results['entities'][$entitykey]['rpep_title'] = $metavalue;
						} 

							// Continue if this is the description.
						elseif (empty($fourover_query_results['entities'][$entitykey]['rpep_title']) && strpos($metakey, 'desc') !== false){
							// ...set Title same as the Description.
							$fourover_query_results['entities'][$entitykey]['rpep_title'] = $metavalue;
						}
						
						// if title still isn't set, then give default title
						if (empty($fourover_query_results['entities'][$entitykey]['rpep_title'])){
				//			$fourover_query_results['entities'][$entitykey]['rpep_title'] = 'Untitled';
						}	
					} else {
							// Continue if this isn't already set & if type string is found.
						if (!$fourover_query_results['entities'][$entitykey]['rpep_'.$type] && strpos($metakey, $type) !== false){
							// copy over the variable within the array
							$fourover_query_results['entities'][$entitykey]['rpep_'.$type] = $metavalue;
						}						
					}
				}
			
			}
		}
	}
	
	
	
	//Send it back. We don't want your stupid payload anymore.
	return ($fourover_query_results);
}

function rpep_abc_entities($fourover_query_results){
	//sort this & return
	usort($fourover_query_results['entities'], function ($a,$b){
		return strcmp($a["rpep_title"], $b["rpep_title"]);
	}	 
		 );
	// Comparison funciton for sorting entities... no clue how this works, but it does.
/*	function rpep_sort_entities($a, $b) {
		return strcmp($a["rpep_title"], $b["rpep_title"]);
	}
	
	//sort this & return
	usort($fourover_query_results['entities'], 'rpep_sort_entities');
*/}

