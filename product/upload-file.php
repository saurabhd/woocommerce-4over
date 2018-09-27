<?php

function before_single_product_new($product_id, $variation_id, $quantity, $set)
    {

       /* global $post;
        global $woocommerce;

        if (get_post_meta($post->ID, '_wpf_umf_upload_enable', true) != 1) {
            return false;
        }

        // Ignore multiple upload boxes for quantity
        if (get_post_meta($post->ID, '_wpf_umf_quantity_dependent', true) == 1) {
            add_filter('pre_option_wpf_umf_upload_procedure', function() { return 'single'; });
        }

        global $wpf_uploads_instance;

        $mp = $wpf_uploads_instance;

        // Html upload post
        $html_post_response = $mp->upload_html_post();
*/
        /*
        $cart_product = $this->cart_product_data;
        $products[0] = $cart_product;
        */

        global $wpf_uploads_instance;

        $mp = $wpf_uploads_instance;

        //If item is not in cart, redirect to product page
        //if (empty($cart_info)){ 
            //wp_redirect(get_permalink($post->ID));
            $unique_product_key = WC_Cart::generate_cart_id( $product_id, $variation_id);
            $cart_info[$unique_product_key]['product_id'] = $product_id;//$_GET['product_id'];
            $cart_info[$unique_product_key]['variation_id'] = $variation_id; //$_GET['vpid'];
            $cart_info[$unique_product_key]['quantity'] = $quantity; //$_GET['quantity'];

        //}

        if (is_array($cart_info)) {

            foreach ($cart_info AS $key => $product_cart_info) {

                $product = wc_get_product($product_cart_info['product_id']);

                // For variation display
                $variation = null;
                //$variation = $woocommerce->cart->get_item_data($product_cart_info, true);
                if (isset($product_cart_info['variation_id']) && !empty($product_cart_info['variation_id'])) {

                    $variation = WPF_Uploads_Before::get_variation_data($product_cart_info['variation_id']);

                }

                $products[$key] = array(
                    'product_id' => $product_cart_info['product_id'],
                    'variation_id' => $product_cart_info['variation_id'],
                    'qty' => $product_cart_info['quantity'],
                    'name' => $product->get_title(),
                );

                $cart_product_data = array(
                    'product' => $product,
                    'cart_info' => $product_cart_info,
                    'variation' => $variation,
                );

            }

        }

        // Upload boxes
        $upload_products = WPF_Uploads::create_upload_boxes_array($products);

        if(!empty($set)){
            //remove particular upload box when have multiple quantity
            $count_set = count($upload_products[$variation_id.'#'.$unique_product_key]['boxes']);
            if($count_set > 0){
                foreach ($upload_products[$variation_id.'#'.$unique_product_key]['boxes'] as $key => $product_temp) {
                    //echo $key.'->'.$set;
                    if($key != $set){
                        unset($upload_products[$variation_id.'#'.$unique_product_key]['boxes'][$key]);
                    }
                }
            }
            
        }

       // print_r($upload_products);

        // Current uploads
        if (isset($_SESSION['wpf_umf_temp_data']))
            $current_uploads = $_SESSION['wpf_umf_temp_data'];

        $upload_mode = 'before';

        if (is_array($upload_products)) {

            include_once(plugin_dir_path(__DIR__). 'pages/upload-boxes-custom.php');
        }

    }

?>