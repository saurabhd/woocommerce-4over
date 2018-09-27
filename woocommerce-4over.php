<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/*
Plugin Name: Woocommerce 4over API
Plugin URI: http://www.addwebsolution.com
Description: This plugin creates Woocommerce product using 4OVER api.
Version: 1.0.0
Author: AddWeb Solution Pvt. Ltd.
Author URI: http://www.addwebsolution.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
WC tested up to: 3.2
*/

include_once(ABSPATH.'wp-admin/includes/plugin.php');

add_action( 'wp_enqueue_scripts', 'woo4over_register_scripts' );

function woo4over_register_scripts(){
    global $post;

    wp_enqueue_style( 'custom_front_css', plugins_url('/assets/css/front.css', __FILE__));
    wp_enqueue_script( 'custom-files', plugins_url('/assets/js/files.js', __FILE__), array('jquery'), '20151215', true);
    wp_enqueue_script( 'custom-js', plugins_url('/assets/js/custom.js', __FILE__), array( 'jquery' ), false, true );
    wp_localize_script( 
        'custom-js', 
        'custom_ajax', 
        apply_filters( 'wc_country_select_params', 
            array(
                'countries' => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
                'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'wc_shipping_multiple_address' ),
                'ajaxurl' => admin_url( 'admin-ajax.php' ), 
                'current_url' => get_permalink($post),
            ) 
        )
    );
    wp_enqueue_script( 'bootstrap-modal-js', plugins_url('/assets/bootstrap-modal/bootstrap-modal.min.js', __FILE__), array( 'jquery' ), false, false );
    wp_enqueue_style( 'bootstrap-modal-css',
        plugins_url('/assets/bootstrap-modal/bootstrap-modal.min.css', __FILE__),
        array(),
        wp_get_theme()->get('Version')
    );
}

if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) { //Make sure Woocommerce is active
	require_once('4over/4over-api.php'); 
    require_once('4over/UI/4over-options-page.php');
    require_once('options-pages/option-page.php');
    require_once('4over/insert_product_variation.php');
    require_once('order/meta-box.php');
    require_once('product/upload-file.php');
    require 'inc/wsma-templates.php';
    require 'inc/shipping-address-table.php';
    require 'inc/common-functions.php';
    require 'inc/4over_shipping.php';
	//require_once('inc/custom-post-types.php');
}

function woo4over_meta_box()
{
    add_meta_box("4over-meta-box", "4over product mapping", "meta_box_markup_4over", "product", "normal", "high");
}

if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	add_action("add_meta_boxes", "woo4over_meta_box");
}

function meta_box_markup_4over($meta_boxes)
{
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");

    global $post;

    $product_type = get_post_meta($post->ID, "radiant_product_type", true); 

    if($product_type == '4over'){
        $normal_checked = '';
        $radiant_checked = 'checked';
        $display = 'block';
    }else{
        $normal_checked = 'checked';
        $radiant_checked = '';
        $display = 'none';
    }   

    ?>

    <div id="product_selection_wrapper">
        <label><b>Woocommerce product type</b></label><br><br>
        <input type="radio" class="radiant_product_type" name="radiant_product_type" value="normal" <?php echo $normal_checked; ?> > WooCommerce<br>
        <input type="radio" class="radiant_product_type" name="radiant_product_type" value="4over" <?php echo $radiant_checked; ?> > 4Over API

    </div>

    <div id="product_type_markup_wrapper" style="display: <?php echo $display; ?>;">
        
        <div id="TextBoxesGroup">
           
            <div id="TextBoxDiv1">

                <label for="meta-box-dropdown">Select Category</label>
                <select name="category-uuid-selector[]" id="category-uuid-selector1" class="category-uuid-selector" rel="1">
                    <option>Loading options</option>
                </select>

                <br><br>

                <label for="meta-box-dropdown">Select Product</label>
                <select name="product-uuid-selector[]" id="product-uuid-selector1" class="product-uuid-selector" rel="1">
                    <option>Loading options</option>
                </select>

            </div>
            </br>

        </div>

        <input type='button' value='Add more' id='addButton'>
        <input type='button' value='Remove' id='removeButton'>

    </div>
    <?php  
}

function woo4over_save_custom_meta_box($post_id, $post, $update)
{
	global $wpdb;


    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "product";
    if($slug != $post->post_type)
        return $post_id;

//    print_r($_POST['category-uuid-selector']);

    $categories = $_POST["category-uuid-selector"];
    $products = $_POST["product-uuid-selector"];

    if(!empty($_POST["product-uuid-selector"]) && $_POST['product-uuid-selector'][0] != 'Loading options' && !empty($_POST["product-uuid-selector"][0]) && $_POST['radiant_product_type'] == '4over')
    {
        
        //delete existing UUID
        $query = "DELETE FROM $wpdb->postmeta WHERE post_id = '".$post_id."' and meta_key like %s ";
        $wpdb->query($wpdb->prepare ($query, '%4over_category_uuid%'));
        $query = "DELETE FROM $wpdb->postmeta WHERE post_id = '".$post_id."' and meta_key like %s ";
        $wpdb->query($wpdb->prepare ($query, '%4over_product_uuid%'));

        update_post_meta($post_id, "radiant_product_type", "4over"); 

        woo4over_delete_product_attributes_variations($post_id);

        $product_count = count($products);

        for ($i=1; $i <= $product_count; $i++) {

            if(!empty($products[$i-1]) && $products[$i-1] != 'Loading options'){
                    
                $category_uuid = $categories[$i-1];
                $product_uuid = $products[$i-1];

                update_post_meta($post_id, "4over_category_uuid_".$i, $category_uuid);
                update_post_meta($post_id, "4over_product_uuid_".$i, $product_uuid);    

                $args['product_uuid'] = $product_uuid;

                $product_data = rpep_4o_routes('get_products', $args);
                $product_name = $product_data['product_description'];

                $response_array = rpep_4o_routes('get_base_prices', $args);
                $final_response = $response_array['entities'];
                $final_response['product_name'] = $product_name;

                $for_attribute_array = rpep_4o_routes('get_options', $args);
                $final_response_attributes = $for_attribute_array['entities'];

                woo4over_create_product_attributes_array($post_id, $final_response_attributes);

                $product = woo4over_create_product_arg($final_response, $product_uuid);
                woo4over_insert_product($product, $post_id);  

            }
        }

    }   
    
}
add_action("save_post", "woo4over_save_custom_meta_box", 10, 3);

function woo4over_create_product_arg($final_response, $product_uuid){

    $shipping_cost = get_option( '4over_shipping_cost'); 
    $markup_fee = get_option( '4over_markup');

    if(!empty($final_response)){
        
        $i = 0;
        $product['sku'] = $product_uuid;
        $product['product_name'] = $final_response['product_name'];
        $product['available_attributes'][0] = 'runsize';
        $product['available_attributes'][1] = 'colorspec';

        $j = 0;

        foreach ($final_response as $key => $value) {
            
            foreach ($value as $key1 => $value1) {

                $product['variations'][$j]['attributes']['runsize'] = array('value'=> $value['runsize'], 'uuid'=> $value['runsize_uuid']);
                $product['variations'][$j]['attributes']['colorspec'] = array('value'=> $value['colorspec'], 'uuid'=> $value['colorspec_uuid']);

                $markup_fee_price = ($value['product_baseprice'] + $shipping_cost) * $markup_fee / 100; 
                
                $updated_price = $value['product_baseprice'] + $shipping_cost + $markup_fee_price;
                
                $product['variations'][$j]['price'] = $updated_price;

            }

            $j++;
        }

        return $product;

    }
}

function woo4over_create_product_attributes_array($post_id, $final_response){

    $skip_options = array('Product Category', 'Size', 'Stock', 'Turn Around Time'); //'Colorspec', 'Runsize',
    $available_attributes = array();

    $i = 0;
    foreach ($final_response as $key => $value) {
            
        if(!in_array($value['product_option_group_name'], $skip_options) && is_array($value)){
            
            $available_attributes[$i] = array('attribute_slug' => sanitize_title($value['product_option_group_name']));


            $attribute['attribute_name'] = sanitize_title($value['product_option_group_name']);
            $attribute['attribute_label'] = $value['product_option_group_name'];

            if (!taxonomy_exists( wc_attribute_taxonomy_name( $attribute['attribute_name'] ) ) ) {
                process_add_attribute($attribute);
                //print 'in';
            }

            $j = 0;
            $options = $value['options'];
            foreach ($options as $key1 => $value1) {
                
                $option_name = $value1['option_name'];
                $option_uuid = $value1['option_uuid'];

                $available_attributes[$i]['options'][$j] = array('value' => $option_name, 'uuid' => $option_uuid);
                $j++;
            }
            $i++;
        }
    }

    if(!empty($available_attributes)){
        foreach ($available_attributes as $attribute) // Go through each attribute
        {   
            $values = array(); // Set up an array to store the current attributes values.
            $uuids = array();

            foreach ($attribute['options'] as $value) // Loop each variation in the file
            {
                    $values[] = $value['value'];
                    $uuids[$value['value']] = $value['uuid'];
            }

            // Store the values to the attribute on the new post, for example without variables:
            // wp_set_object_terms(23, array('small', 'medium', 'large'), 'pa_size');
            $term_taxonomy_ids = wp_set_object_terms($post_id, $values, 'pa_' . $attribute['attribute_slug']);
            
            if(!empty($values)){

                foreach ($values as $attribute_value) // Loop through the variations attributes
                {   
                    $attribute_title_to_slug = sanitize_title($attribute_value);
                    $attribute_term = get_term_by('slug', $attribute_title_to_slug, 'pa_'.$attribute['attribute_slug']); // We need too
                    update_term_meta($attribute_term->term_id, 'uuid', $uuids[$attribute_value]);
                }
            }

        }
    }
    
    
    $product_attributes_data = array(); // Setup array to hold our product attributes data

    if(!empty($available_attributes)){
        foreach ($available_attributes as $attribute) // Loop round each attribute
        {

            $product_attributes_data['pa_'.$attribute['attribute_slug']] = array( // Set this attributes array to a key to using the prefix 'pa'

                'name'         => 'pa_'.$attribute['attribute_slug'],
                'value'        => '',
                'is_visible'   => '1',
                'is_variation' => '1',
                'is_taxonomy'  => '1'

            );
        }

        update_post_meta($post_id, '_product_attributes', $product_attributes_data); 
        // Attach the above array to the new posts meta data key '_product_attributes'
    }
    
}

function process_add_attribute($attribute)
{
    global $wpdb;

    $attribute['attribute_type'] = 'select';
    $attribute['attribute_orderby'] = 'menu_order';
    $attribute['attribute_public'] = 0;

    $wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

    do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );

    flush_rewrite_rules();
    delete_transient( 'wc_attribute_taxonomies' );

    $attribute_id = get_attribute_id_from_name( $attribute['attribute_name']);

    return $attribute_id;
}

function get_attribute_id_from_name( $name ){
    global $wpdb;
    $attribute_id = $wpdb->get_col("SELECT attribute_id
    FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
    WHERE attribute_name LIKE '$name'");
    return reset($attribute_id);
}

function get_file_requirements($options = array()){

    /*$options['product_uuid'] = "7752b4be-f43e-4ec3-be78-d9994456d267";
    $options['option_uuids'] = array("6237a36b-b046-4ef6-8fed-6cb9c22a5ece", "32d3c223-f82c-492b-b915-ba065a00862f"); */

    $args = array('products' => array($options));

    $response = rpep_4o_routes('get_file_requirements', json_encode($args));

    $response_entity = $response['entities'][0]['file_requirements'];

    foreach ($response_entity as $key => $value) {
        $i = 0;
        foreach ($value as $key1 => $value1) {
            if($i == 1){
                $upload_box_name = $value1;
                attach_upload_box($product_id, $variation_id, $upload_box_name);
            }
            $i++;
        }
    }
}

function attach_upload_box($product_id = null, $variation_id = null, $upload_box_name = null){

    $product_id = 16252; //to do
    $variation_id = 16254; //to do
    $upload_box_name = "Front mask";

    $data = get_post_meta($product_id, '_wpf_umf_upload_set', true);
    
    $option_data = get_option('wpf_umf_default_upload_set', true);

    $updated_option_data = array();
    foreach ($option_data as $key => $value) {
        $updated_option_data[$key] = $value['title'];
    }
    
    $upload_box_id = array_search($upload_box_name, $updated_option_data);

    if(!empty($upload_box_id)){
        if(is_array($data[$upload_box_id]['variation_show'])){
            array_push($data[$upload_box_id]['variation_show'], $variation_id);
        }else{
            $data[$upload_box_id]['variation_show'] = array($variation_id);
        }
    }
    
    /*
    $variation_id_1 = 16253;

    $upload_box_id = array_search("Back mask",$updated_option_data);

    if(!empty($upload_box_id)){
        if(is_array($data[$upload_box_id]['variation_show'])){
            array_push($data[$upload_box_id]['variation_show'], $variation_id_1);
        }else{
            $data[$upload_box_id]['variation_show'] = array($variation_id_1);
        }
    }*/

    update_post_meta($product_id, '_wpf_umf_upload_set', $data);
    
}

function get_product_turnaround($args = array()){

    $for_turnaround_array = rpep_4o_routes('get_turnaround_options', $args);
    $final_response_turnaround = $for_turnaround_array['entities'];

    if(!empty($final_response_turnaround)){
        foreach ($final_response_turnaround as $key => $value) {
            if($value['product_option_group_name'] == 'Turn Around Time'){
                $data = $value['options'];
                print_r($data);exit;
            }
        }
    }

}

function get_product_price($args = array()){

    /*$args['product_uuid'] = 'e786abb3-1dcd-4771-85bb-6c27d578b825';
    $args['colorspec_uuid'] = '13abbda7-1d64-4f25-8bb2-c179b224825d';
    $args['runsize_uuid'] =  'b7d68b88-db18-469d-97df-9c11d710ed32';
    $args['tunraroundtime_uuid']  = '9f85d0c4-d344-4088-a719-86f8f84d504d';
    $args['option_uuid'] = '071c7709-a420-478c-b766-a9d837afe6c3';*/

    $response = rpep_4o_routes('get_product_price', $args);

    print_r($response);
    exit;


}

// Add Variation Settings
add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );
// Save Variation Settings
add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
/**
 * Create new fields for variations
 *
*/
function variation_settings_fields( $loop, $variation_data, $variation ) {
    // Text Field
    woocommerce_wp_text_input( 
        array( 
            'id'          => '_4over_product_name[' . $variation->ID . ']', 
            'class'       => '4over_product_textfield',
            'label'       => __( '4over product name', 'woocommerce' ), 
            'desc_tip'    => 'true',
            'value'       => get_post_meta( $variation->ID, '_4over_product_name', true )
        )
    );

    woocommerce_wp_text_input( 
        array( 
            'id'          => '_4over_product_uuid[' . $variation->ID . ']', 
            'class'       => '4over_product_textfield',
            'label'       => __( '4over product UUID', 'woocommerce' ), 
            'desc_tip'    => 'true',
            'value'       => get_post_meta( $variation->ID, '_4over_product_uuid', true )
        )
    );
}
/**
 * Save new fields for variations
 *
*/
function save_variation_settings_fields( $post_id ) {
    // Text Field
    $text_field = $_POST['_4over_product_name'][ $post_id ];
    if( ! empty( $text_field ) ) {
        update_post_meta( $post_id, '_4over_product_name', esc_attr( $text_field ) );
    }

    $uuid_field = $_POST['_4over_product_uuid'][ $post_id ];
    if( ! empty( $uuid_field ) ) {
        update_post_meta( $post_id, '_4over_product_uuid', esc_attr( $uuid_field ) );
    }

}


// Add New Variation Settings
add_filter( 'woocommerce_available_variation', 'load_variation_settings_fields' );

/**
 * Add custom fields for variations
 *
*/
function load_variation_settings_fields( $variations ) {
    
    // duplicate the line for each field
    $variations['_4over_product_name'] = get_post_meta( $variations[ 'variation_id' ], '_4over_product_name', true );

    $variations['_4over_product_uuid'] = get_post_meta( $variations[ 'variation_id' ], '_4over_product_uuid', true );
    
    return $variations;
}

function woo4over_admin_enqueue($hook) {
 
    wp_enqueue_script( 'custom_admin_script', plugins_url('/assets/js/admin.js',__FILE__ ));
    wp_enqueue_style( 'custom_admin_css', plugins_url('/assets/css/admin.css',__FILE__ ));
    
}
add_action( 'admin_enqueue_scripts', 'woo4over_admin_enqueue' );

/***************** Procuct Customize Structure*****************/

add_action('woocommerce_after_single_product_summary', 'add_customization_structure');

function add_customization_structure() {
    global $product;  
    echo '<input type="hidden" id="product_id" name="pid" value="'.$product->get_id().'">';
    echo '<div class="customize_print"></div>';
}
/***************** Procuct Customize Structure END*****************/

/***************** Procuct Customize Button*****************/
add_action('woocommerce_after_add_to_cart_form', 'add_customization_button');
function add_customization_button() {
    global $product;
    $postmetas = get_post_meta( $product->get_id());

    if(isset($postmetas['4over_product_uuid_1']) && count($postmetas['4over_product_uuid_1']) > 0) {
        echo '<div class="customize_button">
        
                <button class="next_button button wc-variation-selection-needed start_customization" rel="1">Next</button>
            </div>';
        echo '<a class="start_over_button button" style="display: none;" href="<?php the_permalink(); ?>">Start Over</a>';
    }
}
/***************** Procuct Customize Button END*****************/

add_action('wp_ajax_nopriv_add_new_shipping_address', 'save_address_book');
add_action('wp_ajax_add_new_shipping_address', 'save_address_book');

function save_address_book() {
    global $woocommerce;
    
    $checkout   = $woocommerce->checkout;
    $addresses = [];
    $shipFields = $woocommerce->countries->get_address_fields( $woocommerce->countries->get_base_country(), 'shipping_' );
    $shipping_form = '';
    ob_start();
    echo '<div class="add_new_shipping_form">';
    echo wc_get_template(
        'address-form.php',
        array(
            'checkout'      => $checkout,
            'addresses'     => $addresses,
            'shipFields'    => $shipFields
        ),
        'multi-shipping',
        plugin_dir_path(__FILE__) .'templates/'
    );
    echo '</div>';
    $output = ob_get_clean();
    echo $output;
    wp_die();
}


/************* Get Set Address Form ********************/
add_action('wp_ajax_nopriv_add_new_address', 'get_address_book');
add_action('wp_ajax_add_new_address', 'get_address_book');
function get_address_book() {
    global $woocommerce, $current_user;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product = wc_get_product($product_id);
    $contents = [];
    foreach ($product->get_variation_attributes() as $attr => $variation_attributes_val) {
        $contents[str_replace('pa_', '', $attr)] = isset($_POST['attribute_'.$attr]) ? $_POST['attribute_'.$attr] : 0;      
    }
    $contents['page'] = 'product_page'; 
    $contents['product_id'] = $product_id;
    $contents['set'] = 0;
    $contents['quantity'] = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;     
    $contents['user'] = $current_user;      
    $contents['variation_id'] = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0; 
    $contents['color_type'] = isset($_POST['color']) ? $_POST['color'] : '-';   
    $contents['price'] = isset($_POST['price']) ? $_POST['price'] : 0;              
    $contents['image_upload'] = isset($_POST['attribute_pa_upload-art']) ? $_POST['attribute_pa_upload-art'] : 0;               
    $data = woo4over_products_sets_template($contents);
    wp_send_json($data, 200);
    wp_die();
    
}


/**************************** Save Set Address Form **************************/
add_action('wp_ajax_nopriv_set_shipping_address', 'set_shipping_address');
add_action('wp_ajax_set_shipping_address', 'set_shipping_address');

function set_shipping_address() {
    global $woocommerce;
    $product_id = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
    $variation_id = isset($_POST['vid']) ? intval($_POST['vid']) : 0;
    $quantity = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
    $set = isset($_POST['set']) ? intval($_POST['set']) : 0;
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    if (isset($_POST['shipping_address_action']) && $_POST['shipping_address_action'] == 'save' ) {
        $data_rel = woo4over_get_shipping_methods_address($product_id, $variation_id, $quantity, $set, $items);
        if(isset($_SESSION['cart_item_addresses']) && !empty($_SESSION['cart_item_addresses'])) {
            $_SESSION['cart_item_addresses'] = array_merge($_SESSION['cart_item_addresses'], $data_rel['data']);
        }   
        else {
            $_SESSION['cart_item_addresses'] = $data_rel['data'];
        } 
        $_SESSION['address_relationships'] = $data_rel['rel'];
        $_SESSION['wcms_item_addresses'] = $data_rel['rel'];
        echo 'true';
        wp_die();
    }
    echo 'false';
    wp_die();
}

/************************* Save Set Address Form END *******************************/

/************************* Show Multiple Shipping Details *************************/
add_action('wp_ajax_load_shipping_data', 'load_multiple_shipping_data');
add_action('wp_ajax_nopriv_load_shipping_data', 'load_multiple_shipping_data');
// 
function load_multiple_shipping_data() {
    global $woocommerce;        
    // $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 0;
    $addresses = isset($_SESSION['cart_item_addresses']) ? $_SESSION['cart_item_addresses'] : [];
    $product_id = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
    $variation_id = isset($_POST['vid']) ? intval($_POST['vid']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $set = isset($_POST['set']) ? intval($_POST['set']) : 0;
    $packages = woo4over_get_shipping_rates($product_id, $variation_id, $addresses, $quantity, $set);
    echo woo4over_render_get_shipping_menthods($packages);
    add_filter('wc_ms_cart_is_eligible', function (){ return 1;});
    wp_die();
}

/************************* Show Multiple Shipping Details END *************************/


/********************** Load 4Over Shipping Charges ************************/
add_action('wp_ajax_load_4over_shipping', 'load_4over_shipping_methods');
add_action('wp_ajax_nopriv_load_4over_shipping', 'load_4over_shipping_methods');

function load_4over_shipping_methods() {
    $response = woo4over_attribute_manager($_POST);
    wp_send_json($response, 200);
    wp_die();       
}

add_action( 'woocommerce_cart_calculate_fees', 'woo4over_add_4over_shipping_check_total' );

function woo4over_add_4over_shipping_check_total( $cart_object ) {
    global $woocommerce;
    $total_tax = 0;
    $total_shipping = 0;
    if(count($cart_object) > 0) {
        foreach ($cart_object->cart_contents as $cart_data) {
            $cart_key = WC_Cart::generate_cart_id($cart_data['product_id'], $cart_data['variation_id']);
            if(!empty($_SESSION[$cart_key])){
                foreach ($_SESSION[$cart_key] as $session_key => $session_cart_data) {
                    list($uniqe_key, $set) = explode('#', $session_key);
                    if(isset($session_cart_data['shipping_4over']['facilities'][0]['shipping_options']) && count($session_cart_data['shipping_4over']['facilities'][0]['shipping_options'])) {
                        foreach ($session_cart_data['shipping_4over']['facilities'][0]['shipping_options'] as $shipping_options) {
                            if($session_cart_data['items'][$set]['4over_shipping_price'] == $shipping_options['service_code']) {
                                $total_shipping = $total_shipping + $shipping_options['service_price'];
                            }
                        }
                    }
                    if(isset($session_cart_data['shipping_4over']['facilities'][0]['total_tax']) && !empty($session_cart_data['shipping_4over']['facilities'][0]['total_tax'])) {
                        $total_tax = $total_tax + $session_cart_data['shipping_4over']['facilities'][0]['total_tax'];
                    }
                }
            }
        }
    }
    if($total_shipping != 0) {
        $woocommerce->cart->add_fee( '4over Shipping Fee', $total_shipping, true, 'standard' );
    }
    if($total_tax != 0) {
        $woocommerce->cart->add_fee( '4over Tax Fee', $total_tax, true, 'standard' );
    }
}

function woo4over_attribute_manager($content_data, $selected='') {
    global $current_user;
    $contents['product_info'] = [];
    $product_id = isset($content_data['pid']) ? intval($content_data['pid']) : 0;
    if($product_id == 0) {
        $product_id = isset($content_data['product_id']) ? intval($content_data['product_id']) : 0;
    }
    $variation_id = isset($content_data['vid']) ? intval($content_data['vid']) : 0;
    if($variation_id == 0) {
        $variation_id = isset($content_data['variation_id']) ? intval($content_data['variation_id']) : 0;
    }
    $set = isset($content_data['set']) ? intval($content_data['set']) : 0;
    //$shipping_price_4over = isset($contents['items'][$set]['4over_shipping_price']) ? $contents['items'][$set]['4over_shipping_price'] : '';
    $items = isset($content_data['items']) ? $content_data['items'] : [];
    $product = wc_get_product($product_id);
    $cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);
    $product_uuid = get_post_meta( $product_id, '4over_product_uuid_1', true );
    $contents['product_info']['product_uuid'] = $product_uuid;
    $contents['product_info']['sets'] = 1;
    if(count($product->get_variation_attributes()) > 0) {
        foreach ($product->get_variation_attributes() as $attr => $variation_attributes_val) {
            //echo $attr.'->'.$attr_val.'->'.$term->term_id.'<br>';
            if($attr == 'pa_runsize' || $attr = 'pa_colorspec'){
                $attr_val = $content_data[str_replace('pa_', '', $attr)];

                $term = get_term_by( 'slug', sanitize_title($attr_val), $attr);
                $term_uuid = get_term_meta($term->term_id, 'uuid', true);
                
                if($term_uuid != '')
                    $contents['product_info'][str_replace('pa_', '', $attr).'_uuid'] = $term_uuid;

            }
        }
    }
    $selected_address = [];
    $addresses = woo4over_get_user_addresses($current_user->ID);
    if($items && count($items) > 0) {
        foreach ($items as $item) {
            $selected_address = $addresses[$item['shipping']['address'][0]];
        }
    }
    $contents['shipping_address']['address'] = $selected_address['shipping_address_1'];
    $contents['shipping_address']['address2'] = $selected_address['shipping_address_2'];
    $contents['shipping_address']['city'] = $selected_address['shipping_city'];
    $contents['shipping_address']['state'] = $selected_address['shipping_state'];
    $contents['shipping_address']['country'] = $selected_address['shipping_country'];
    $contents['shipping_address']['zipcode'] = $selected_address['shipping_postcode'];

    //Get 4over Shipping Response
    $data = woo4over_shipping_methods($contents);
    $_SESSION[$cart_key][$cart_key.'#'.$set]['shipping_4over'] =  isset($data['job']) ? $data['job'] : [];
    // $_SESSION[$cart_key][$cart_key.'#'.$set]['items'][$set]['4over_shipping_price'] = $shipping_price_4over;
    $structure_var['shipping_options'] = isset($data['job']['facilities'][0]['shipping_options']) ? $data['job']['facilities'][0]['shipping_options'] : [];
    $time = isset($data['job']['facilities'][0]['production_estimate']) ? $data['job']['facilities'][0]['production_estimate'] : '';
    $total_tax = isset($data['job']['facilities'][0]['total_tax']) ? $data['job']['facilities'][0]['total_tax'] : 0;
    $html = woo4over_get_4over_shipping_structure($structure_var, $selected);
    $shipping_time = date_create($time);
    $delivery_time = date_create($time);
    $response = [
        'shipping_option' => $html,
        'shipping_time' => date_format($shipping_time, 'l, d F Y'),
        'delivery_time' => date_format($delivery_time, 'l, d F Y'),
        'shipping_tax' => $total_tax
    ];
    return $response;
}

add_filter('woocommerce_add_to_cart_redirect', 'woo4over_redirect_to_checkout', 10, 2);

function woo4over_redirect_to_checkout($redirect_url) {
   
  if (isset($_REQUEST['quick-checkout-flag']) && $_REQUEST['quick-checkout-flag'] == 1) {
     global $woocommerce;
     $redirect_url = wc_get_checkout_url();
     wp_redirect($redirect_url);
     exit;
  }
  //return $redirect_url;
}

// create the checkbox form fields and add them before the cart button
add_action( 'woocommerce_before_add_to_cart_button', 'woo4over_before_add_to_cart_form', 10, 0 );

function woo4over_before_add_to_cart_form(){
?>

<input type="hidden" name="quick-checkout-flag" id="quick-checkout-flag" value="0" />

    <?php
}

function woo4over_auto_complete_order( $order_id ) {

    $order = getOrderDetailById($order_id);
    $order_data = $order['order'];

    if(!empty($order_id) && !empty($order_data)){
        session_start();
        foreach($order_data['line_items'] as $product){

            $product_id = $product["product_id"];
            $variation_id = $product["variation_id"]; 
            $cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);
            
            unset($_SESSION[$cart_key]);
            unset($_SESSION[$cart_key.'#image_upload']);
        }
    }
  
}
add_action( 'woocommerce_thankyou', 'woo4over_auto_complete_order', 10, 2);


function woo4over_validate_add_cart_item( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {
    
    $passed = true;
    // do your validation, if not met switch $passed to false
    if(!empty(WC()->cart->cart_contents)){
        foreach( WC()->cart->cart_contents as $prod_in_cart ) {
            if($prod_in_cart['variation_id'] == $variation_id){
                wc_add_notice( __( 'Item with same options already exist in cart.', 'textdomain' ), 'error' );
                $passed = false;
            }
        }
    }
    
    return $passed;

}
add_filter( 'woocommerce_add_to_cart_validation', 'woo4over_validate_add_cart_item', 10, 5);