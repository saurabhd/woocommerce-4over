<?php


// Add meta box
add_action( 'add_meta_boxes', 'woo4over_tracking_box' );
function woo4over_tracking_box() {
    add_meta_box(
        'woo4over-items',
        'Order Processing and Management',
        'order_proceesing_meta_box_callback',
        'shop_order',
        'normal',
        'high'
    );
}

// Callback
function order_proceesing_meta_box_callback( $post )
{
    
    // Get order data
    $order = getOrderDetailById($post->ID);
    $order_data = $order['order'];

    $customer_files_array_data = get_post_meta($post->ID, '_wpf_umf_uploads', true);
    $order_shipping_address = order_get_shipping_address($post);
    $shipping_address_i = 0;

    if(!empty($customer_files_array_data)){
        foreach ($customer_files_array_data as $key => $value) {
            $pid = explode('#', $key);
            $customer_files_array[$pid[0]] = $value;
        }
    }

    $order_status_internal_options = array('Processing', 'Awaiting Response', 'Design', 'Printing', 'Shipped', 'On Hold');

    $store_state_country = wc_get_base_location();
    $site_name = get_bloginfo('name');
    $current_user = wp_get_current_user();
    $currency_code = get_option('woocommerce_currency');

    $store_address['company'] = $site_name;
    $store_address['firstname'] = $current_user->user_firstname;
    $store_address['lastname'] = $current_user->user_lastname;
    $store_address['address'] = get_option( 'woocommerce_store_address', '' );
    $store_address['address2'] = get_option( 'woocommerce_store_address_2', '' );
    $store_address['city'] = get_option( 'woocommerce_store_city', '' );
    $store_address['state'] = $store_state_country['state'];
    $store_address['postcode'] = get_option( 'woocommerce_store_postcode', '' );
    $store_address['country'] = $store_state_country['country'];

    $output = '';

    foreach($order_data['line_items'] as $product){

        $product_id = $product["product_id"];
        $variation_id = $product["variation_id"]; 

        $products[] = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'qty' => 1,
            );

        $upload_boxes = WPF_Uploads::create_upload_boxes_array($products);

        $variation_product_uuid = get_post_meta($product["variation_id"], '_4over_product_uuid', true);

        $order_store_data = json_decode(get_post_meta($post->ID, '_4over_order_'.$product["variation_id"].'_response', true), true);
        $job_ids = $order_store_data['job_ids'];
        $job_id = $job_ids[0];
        $order_status = json_decode(get_post_meta($post->ID, '_4over_order_status_'.$product["variation_id"], true), true);
        $order_tracking = json_decode(get_post_meta($post->ID, '_4over_order_tracking_'.$product["variation_id"], true), true); 

        $product_unique_key = WC_Cart::generate_cart_id( $product_id, $variation_id);
        
        $output .= '<div class="panel-wrap woocommerce">';
            
            $output .= '<div id="order_data" class="panel">';
                
            for($product_i = 1; $product_i <= $product['quantity']; $product_i++){

                $output .= '<div class="order_data_column_container">';

                $output .= '<div><h3><a href="'.$product["product_url"].'">'.$product["name"].'</a></h3></div>';
                $output .= '<div class="order_data_column">';
                $output .= '<input type="hidden" class="post_id" value="'.$post->ID.'">';
                $output .= '<input type="hidden" class="order_id" value="'.$order_data['order_number'].'">';
                $output .= '<input type="hidden" class="product_unique_key" value="'.$product_unique_key.'">';
                $output .= '<input type="hidden" class="currency_code" value="'.$currency_code.'">';

                $output .= '<p><b>Variation ID: </b>'.$product["variation_id"].'</p>';
                $output .= '<input type="hidden" class="variation_id" value="'.$variation_id.'">';                
                $output .= '<p>'.$product["meta"].'</p>';
                $output .= '<p><b>Qty.: </b>1</p>'; //$product["quantity"]
                $output .= '<p><b>Notes: </b>'.$order_data['note'].'</p>';
                $output .= '</div>';

                $output .= '<div class="order_data_column">';
                $output .= '<p><b>Turnaround: </b>4-5 Business Days (?)</p>';
                $output .= '<p><b>Shipping: </b>'.$order_data['shipping_methods'].'</p>';
                $output .= '<p><b>Ship From: </b>'.$store_address['postcode'].'</p>';

                $output .= '<span style="display:none;" class="store_address">'.json_encode($store_address).'</span>';
                $output .= '<p><b>Estimate in-hand: </b>'.get_post_meta($post->ID, 'order_inhanddate', true).'</p>';
                //$output .= '<p><b>Ship to: </b>'.$order_data['shipping_address_formatted'].'</p>';
                $output .= '<p><b>Ship to: </b>'. WC()->countries->get_formatted_address($order_shipping_address[$shipping_address_i]['destination'] ) .'</p>';
                $output .= '<input type="hidden" class="shipping_methods" value="'.$order_data['shipping_methods'].'">';
                $output .= '<span style="display:none;" class="shipping_address">'.json_encode($order_shipping_address[$shipping_address_i]['destination']).'</span>';
                $output .= '<span style="display:none;" class="billing_address">'.json_encode($order_data['billing_address']).'</span>';
                
                $output .= '</div>';

                $order_status_internal = get_post_meta( $post->ID, '_4over_order_status_internal_'.$variation_id, true );

                $output .= '<div class="order_data_column" style="padding-right:0px;">';
                $output .= '<p><b>Item Status: </b><select class="order-status">';
                foreach ($order_status_internal_options as $value) {
                    if($value == $order_status_internal){
                        $selected = 'selected';
                    }else{
                        $selected = '';
                    }
                    $output .= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                }
                
                $output .= '</select></p>';
                $output .= '<p><b>Ext. Order Num: </b><span class="job-id">'.$job_id.'</span></p>';

                $output .= '<p><b>Item Status: </b><span class="order-item-status">'.$order_status['message'].'</span> <a class="check-item-status" style="cursor:pointer;"><img src="'.plugins_url('/assets/images/refresh.png', dirname(__FILE__)).'" /></a></p>';

                $output .= '<p><b>Tracking Number: </b><span class="tracking-number">'.$order_tracking['message'].'</span> <a class="check-item-tracking" style="cursor:pointer;"><img src="'.plugins_url('/assets/images/refresh.png', dirname(__FILE__)).'" /></a></p>';


                $output .= '<input type="hidden" class="variation_product_uuid" value="'.$variation_product_uuid.'">';
                if(!empty($product['variation_attributes'])){
                    foreach($product['variation_attributes'] as $attribute_taxonomy => $term_slug){
                        $taxonomy = str_replace('attribute_', '', $attribute_taxonomy );
                        // The name of the attribute
                        $attribute_name = get_taxonomy( $taxonomy )->labels->singular_name;
                        // The term name (or value) for this attribute
                        $attribute_id = get_term_by( 'slug', $term_slug, $taxonomy )->term_id;
                        $uuid = get_term_meta($attribute_id, 'uuid', true);
                        $output .= '<input type="hidden" class="'.$taxonomy.'" value="'.$uuid.'">';
                    }
                }

                $files_data_array = $customer_files_array[$variation_id];
                $files = '';

                $output .= '<div class="customer_art_wrapper">';

                if(!empty($files_data_array)) { 


                    $product_files = $files_data_array[$product_i];
                    
                    $file_i = 1; 
                    foreach ($product_files as $key => $value) {
                        
                            $j = 1;
                            foreach ($value as $key1 => $value2) {

                                        $files_data = explode('/', $value2['path']);
                                        $upload_dir = wp_upload_dir();
                                        $file_name = $files_data[count($files_data)-1];
                                        $file_full_path = $upload_dir['baseurl'].'/umf/'.$post->ID.'/'.$file_name;
                                        $files = '<p><b>Customer Art Set '.$product_i.' '.$value2['type'].': </b><a target="_blank" href="'.$file_full_path.'">'.$value2['name'].'</a></p>';
                                        $output .= $files;
                                        
                                        $j++;
                            }

                            $file_i++;
                    }

                    
                    if(!empty($product_files)){
                        $output .= '<p><a class="reset-art" style="cursor:pointer;"><b>Reset Art Uploader</b></a></p>';
                    }
                }

                $output .= '<input type="hidden" class="upload_file_index" value="'.$product_i.'">';
                    $output .= '</div>';

                $output .= '</div>';

                $output .= '<div class="clear"></div>';

                $output .= '<div style="text-align:right;" class="4over-order-buttons">';
                $output .= '<input class="edit-order" type="button" value="Process via 4over" />&nbsp;&nbsp;&nbsp;';
                $output .= '<input class="process-order-manually" type="button" value="Process manually" />';
                $output .= '</div>';

                $output .= '<div class="edit-order-item" style="display:none;">';

                    $product_name = explode('-', $product['name']);
                    $output .= '<div class="order_data_column">';
                        $output .= '<p><b>Product:</b><input type="text" value="'.$product_name[0].'" disabled></p>';
                        $output .= '';

                        if(!empty($product['variation_attributes'])){
                            foreach($product['variation_attributes'] as $attribute_taxonomy => $term_slug){
                                $taxonomy = str_replace('attribute_', '', $attribute_taxonomy );
                                $attribute_name = get_taxonomy( $taxonomy )->labels->singular_name;
                                $term_name = get_term_by( 'slug', $term_slug, $taxonomy )->name;
                                $output .= '<p><b>'.$attribute_name.':</b> '.'<input type="text" value="'.$term_name.'" disabled></p>';
                            }
                        }
                        $output .= '<p><b>Turnaround:</b><input type="text" value="" disabled></p>';
                        $output .= '<p><b>Shipping:</b><input type="text" value="" disabled></p>';
                    $output .= '</div>';

                    $output .= '<div class="order_data_column">';
                    
                        $output .= '<p><b>Shipping To:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['first_name'].' '.$order_shipping_address[$shipping_address_i]['destination']['last_name'].'" disabled></p>';
                        $output .= '<p><b>Company:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['company'].'" disabled></p>';
                        $output .= '<p><b>Address:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['address_1'].'" disabled></p>';
                        $output .= '<p><b>Address 2:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['address_2'].'" disabled></p>';
                        $output .= '<p><b>City:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['city'].'" disabled></p>';
                        $output .= '<p><b>State:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['state'].'" disabled></p>';
                        $output .= '<p><b>Zip Code:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['postcode'].'" disabled></p>';
                        $output .= '<p><b>Country:</b><input type="text" value="'.$order_shipping_address[$shipping_address_i]['destination']['country'].'" disabled></p>';

                    $output .= '</div>';      

                    $output .= '<div class="order_data_column">';
                        $output .= '<p><b>Expected in-hand:</b></p>';
                        $output .= '<p><b>Printing Cost:</b></p>';
                        $output .= '<p><b>Shipping Cost:</b></p>';
                        $output .= '<p><b>Margin:</b></p>';

                        //if(!empty($files_data_array)) { 

                            $output .= '<div class="customer_art_wrapper">';

                            $product_files = $files_data_array[$product_i];

                            foreach ($upload_boxes[$variation_id]['boxes'][1] as $key => $value) {

                                    if(array_key_exists($key, $product_files)){
                                        $loop_file = $product_files[$key][1];
                                        $files_data = explode('/', $loop_file['path']);
                                        $upload_dir = wp_upload_dir();
                                        $file_name = $files_data[count($files_data)-1];
                                        $file_full_path = $upload_dir['baseurl'].'/umf/'.$post->ID.'/'.$file_name;
                                        $files = '<p><b>Customer Art Set '.$product_i.' '.$loop_file['type'].': </b><a target="_blank" href="'.$file_full_path.'">'.$loop_file['name'].'</a></p>';
                                        $output .= $files;
                                        /*$output .= '<input id="sortpicture" type="file" name="sortpic" />
                                                    <input type="button" class="upload" data-upload-set-index="'.$key.'"value="Upload" data-upload-type-name="'.$value['title'].'"  />';*/

                                    }else{
                                        $output .=  '<p><b>Customer Art Set '.$product_i.' '.$value['title'].': </b></p>'; 
                                        //temp
                                        $output .= '<input id="sortpicture" type="file" name="sortpic" />
                                                    <input type="button" class="upload" data-upload-set-index="'.$key.'"value="Upload" data-upload-type-name="'.$value['title'].'"  />';
                                        
                                    }
                                
                            }

                            $output .= '</div>';
                            /*foreach ($product_files as $key => $value) {
                                
                                    foreach ($value as $key1 => $value2) {

                                                $files_data = explode('/', $value2['path']);
                                                $upload_dir = wp_upload_dir();
                                                $file_name = $files_data[count($files_data)-1];
                                                $file_full_path = $upload_dir['baseurl'].'/umf/'.$post->ID.'/'.$file_name;
                                                $files = '<p><b>Customer Art Set '.$product_i.' '.$value2['type'].': </b><a target="_blank" href="'.$file_full_path.'">'.$value2['name'].'</a></p>';
                                                $output .= $files;
                                                
                                    }

                            }*/

                            
                        //}
                        
                        $output .= '<p><b>Notes:</b><textarea></textarea></p>';

                        $output .= '<input class="process-order" type="button" value="Process" />&nbsp;&nbsp;&nbsp;';

                    $output .= '</div>';                                  

                $output .= '</div>';

                $output .= '<div class="order_error_wrapper error_4over" style="display:none;">';
                $output .= '<div class="order_error"></div>';
                $output .= '<div class="order_error_message"></div>';
                $output .= '</div>';

                $output .= '</div>';

                $shipping_address_i++;
            }

            $output .= '</div>';

        $output .= '</div>';

        /*$output .= '<div class="panel-wrap woocommerce order-data-expand">';
            
            $output .= '<div id="order_data" class="panel">';
            
                $output .= '<div class="order_data_column_container">';

                $output .= '<div class="order_data_column">';
                $output .= '<p><b>Category: </b> <select><option>--Select Category--</option></select></p>';
                $output .= '<p><b>Product: </b> <select><option>--Select Product--</option></select></p>';
                $output .= '<p><b>Quantity: </b> <select><option>--Select Quantity--</option></select></p>';
                $output .= '<p><b>Color: </b> <select><option>--Select Color--</option></select></p>';
                $output .= '<p><b>Turnaround: </b> <select><option>--Select Turnaround--</option></select></p>';
                $output .= '<p><b>Shipping: </b> <select><option>--Select Shipping--</option></select></p>';
                $output .= '</div>';

                $output .= '<div class="order_data_column">';
                $output .= '<p><b>Shipping To: </b> <input type="text" /></p>';
                $output .= '<p><b>Company: </b> <input type="text" /></p>';
                $output .= '<p><b>Address: </b> <input type="text" /></p>';
                $output .= '<p><b>Address 2: </b> <input type="text" /></p>';
                $output .= '<p><b>City: </b> <input type="text" /></p>';
                $output .= '<p><b>State: </b> <select><option>--Select State--</option></select></p>';
                $output .= '<p><b>Zip Code: </b> <input type="text" /></p>';
                $output .= '<p><b>Country: </b> <select><option>Select Country--</option></select></p>';
                $output .= '</div>';

                $output .= '<div class="order_data_column" style="padding-right:0px;">';
                $output .= '<p><b>Expected in-hand: </b> ****</p>';
                $output .= '<p><b>Printing Cost: </b> ****</p>';
                $output .= '<p><b>Shipping Cost: </b> ****</p>';
                $output .= '<p><b>Margin: </b>****</p>';
                $output .= '<p><b>Art Front: </b> <input type="file" /></p>';
                $output .= '<p><b>Art Back: </b> <input type="file" /></p>';
                $output .= '<p><b>Notes: </b><input type="text" /></p>';
                $output .= '</div>';


                $output .= '<div class="clear"></div>';

                $output .= '<div style="text-align:right;" class="4over-order-buttons">';
                $output .= '<input type="button" value="Process" />&nbsp;&nbsp;&nbsp;';
                $output .= '</div>';            

                $output .= '</div>';
        
            $output .= '</div>';
        
        $output .= '</div>';*/

    }

    echo $output;
}

// Saving
add_action( 'save_post', 'woo4over_save_meta_box_data' );

function woo4over_save_meta_box_data( $post_id ) {

    // Only for shop order
    if ( 'shop_order' != $_POST[ 'post_type' ] )
        return $post_id;

    // Check if our nonce is set (and our cutom field)
    if ( ! isset( $_POST[ 'tracking_box_nonce' ] ) && isset( $_POST['tracking_box'] ) )
        return $post_id;

    $nonce = $_POST[ 'tracking_box_nonce' ];

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce ) )
        return $post_id;

    // Checking that is not an autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $post_id;

    // Check the user’s permissions (for 'shop_manager' and 'administrator' user roles)
    if ( ! current_user_can( 'edit_shop_order', $post_id ) && ! current_user_can( 'edit_shop_orders', $post_id ) )
        return $post_id;

    // Saving the data
    update_post_meta( $post_id, '_tracking_box', sanitize_text_field( $_POST[ 'tracking_box' ] ) );
}

if (!function_exists('getOrderDetailById')) {
    //to get full order details
    function getOrderDetailById($id, $fields = null, $filter = array()) {
        if (is_wp_error($id))
            return $id;
        // Get the decimal precession
        $dp = (isset($filter['dp'])) ? intval($filter['dp']) : 2;
        $order = wc_get_order($id); //getting order Object
        $order_data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'created_at' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'updated_at' => $order->get_date_modified()->date('Y-m-d H:i:s'),
            'completed_at' => !empty($order->get_date_completed()) ? $order->get_date_completed()->date('Y-m-d H:i:s') : '',
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => wc_format_decimal($order->get_total(), $dp),
            'subtotal' => wc_format_decimal($order->get_subtotal(), $dp),
            'total_line_items_quantity' => $order->get_item_count(),
            'total_tax' => wc_format_decimal($order->get_total_tax(), $dp),
            'total_shipping' => wc_format_decimal($order->get_total_shipping(), $dp),
            'cart_tax' => wc_format_decimal($order->get_cart_tax(), $dp),
            'shipping_tax' => wc_format_decimal($order->get_shipping_tax(), $dp),
            'total_discount' => wc_format_decimal($order->get_total_discount(), $dp),
            'shipping_methods' => $order->get_shipping_method(),
            'order_key' => $order->get_order_key(),
            'payment_details' => array(
                'method_id' => $order->get_payment_method(),
                'method_title' => $order->get_payment_method_title(),
                'paid_at' => !empty($order->get_date_paid()) ? $order->get_date_paid()->date('Y-m-d H:i:s') : '',
            ),
            'billing_address' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'formated_state' => WC()->countries->states[$order->get_billing_country()][$order->get_billing_state()], //human readable formated state name
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'formated_country' => WC()->countries->countries[$order->get_billing_country()], //human readable formated country name
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone()
            ),
            'shipping_address_formatted' => $order->get_formatted_shipping_address(),
            'shipping_address' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'formated_state' => WC()->countries->states[$order->get_shipping_country()][$order->get_shipping_state()], //human readable formated state name
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
                'formated_country' => WC()->countries->countries[$order->get_shipping_country()] //human readable formated country name
            ),
            'note' => $order->get_customer_note(),
            'customer_ip' => $order->get_customer_ip_address(),
            'customer_user_agent' => $order->get_customer_user_agent(),
            'customer_id' => $order->get_user_id(),
            'view_order_url' => $order->get_view_order_url(),
            'line_items' => array(),
            'shipping_lines' => array(),
            'tax_lines' => array(),
            'fee_lines' => array(),
            'coupon_lines' => array(),
        );
        //getting all line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_id = null;
            $product_sku = null;
            // Check if the product exists.
            if (is_object($product)) {
                $product_id = $product->get_id();
                $product_sku = $product->get_sku();
            }

             // Only for product variation
            if($product->is_type('variation')){
                 // Get the variation attributes
                $variation_attributes = $product->get_variation_attributes();
                // Loop through each selected attributes
                
            }

            $order_data['line_items'][] = array(
                'id' => $item_id,
                'subtotal' => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
                'subtotal_tax' => wc_format_decimal($item['line_subtotal_tax'], $dp),
                'total' => wc_format_decimal($order->get_line_total($item, false, false), $dp),
                'total_tax' => wc_format_decimal($item['line_tax'], $dp),
                'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp),
                'quantity' => wc_stock_amount($item['qty']),
                'tax_class' => (!empty($item['tax_class']) ) ? $item['tax_class'] : null,
                'name' => $item['name'],
                'product_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product->get_parent_id() : $product_id,
                'variation_id' => (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? intval($product_id) : 0,
                'variation_attributes' => $product->get_variation_attributes(),
                'product_url' => get_permalink($product_id),
                'product_thumbnail_url' => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail', TRUE)[0],
                'sku' => $product_sku,
                'meta' => wc_display_item_meta($item, array('echo' => false))
            );
        }
        //getting shipping
        foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
            $order_data['shipping_lines'][] = array(
                'id' => $shipping_item_id,
                'method_id' => $shipping_item['method_id'],
                'method_title' => $shipping_item['name'],
                'total' => wc_format_decimal($shipping_item['cost'], $dp),
            );
        }
        //getting taxes
        foreach ($order->get_tax_totals() as $tax_code => $tax) {
            $order_data['tax_lines'][] = array(
                'id' => $tax->id,
                'rate_id' => $tax->rate_id,
                'code' => $tax_code,
                'title' => $tax->label,
                'total' => wc_format_decimal($tax->amount, $dp),
                'compound' => (bool) $tax->is_compound,
            );
        }
        //getting fees
        foreach ($order->get_fees() as $fee_item_id => $fee_item) {
            $order_data['fee_lines'][] = array(
                'id' => $fee_item_id,
                'title' => $fee_item['name'],
                'tax_class' => (!empty($fee_item['tax_class']) ) ? $fee_item['tax_class'] : null,
                'total' => wc_format_decimal($order->get_line_total($fee_item), $dp),
                'total_tax' => wc_format_decimal($order->get_line_tax($fee_item), $dp),
            );
        }
        //getting coupons
        foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
            $order_data['coupon_lines'][] = array(
                'id' => $coupon_item_id,
                'code' => $coupon_item['name'],
                'amount' => wc_format_decimal($coupon_item['discount_amount'], $dp),
            );
        }
        return array('order' => apply_filters('woocommerce_api_order_response', $order_data, $order, $fields));
    }
}

function order_get_shipping_address( $post ) {

    $packages = get_post_meta( $post->ID, '_wcms_packages', true );
    return $packages;

}

/*
 * The AJAX handler function
 */
function radiant_ajax_process_order() {
  
  $response = array();

  $args = file_get_contents(plugins_url('/data/order.json', __FILE__ )); // Get json from sample file
  
  $temp_array = json_decode($args, true);

  $post_id = intval($_POST['post_id']);

  $temp_array['order_id'] = intval($_POST['order_id']);
  $temp_array['jobs'][0]['product_uuid'] = $_POST['product_uuid'];
  $temp_array['jobs'][0]['runsize_uuid'] = $_POST['runsize_uuid'];
  $temp_array['jobs'][0]['colorspec_uuid'] = $_POST['colorspec_uuid'];

  $shipping_address = json_decode(stripcslashes($_POST['shipping_address']), true);
  $billing_address = json_decode(stripcslashes($_POST['billing_address']), true);
  $store_address = json_decode(stripcslashes($_POST['store_address']), true);

  if(!empty($shipping_address['company'])){
  $temp_array['jobs'][0]['ship_to']['company'] = $shipping_address['company'];
  }
  $temp_array['jobs'][0]['ship_to']['firstname'] = $shipping_address['first_name'];
  $temp_array['jobs'][0]['ship_to']['lastname'] = $shipping_address['last_name'];
  $temp_array['jobs'][0]['ship_to']['address'] = $shipping_address['address_1'];
  $temp_array['jobs'][0]['ship_to']['address2'] = $shipping_address['address_2'];
  $temp_array['jobs'][0]['ship_to']['city'] = $shipping_address['city'];
  $temp_array['jobs'][0]['ship_to']['state'] = $shipping_address['state'];
  $temp_array['jobs'][0]['ship_to']['zipcode'] = $shipping_address['postcode'];
  $temp_array['jobs'][0]['ship_to']['country'] = $shipping_address['country'];

  if(!empty($store_address['company'])){
  $temp_array['jobs'][0]['ship_from']['company'] = $store_address['company'];
  }
  $temp_array['jobs'][0]['ship_from']['firstname'] = $store_address['firstname'];
  $temp_array['jobs'][0]['ship_from']['lastname'] = $store_address['lastname'];
  $temp_array['jobs'][0]['ship_from']['address'] = $store_address['address'];
  $temp_array['jobs'][0]['ship_from']['address2'] = $store_address['address2'];
  $temp_array['jobs'][0]['ship_from']['city'] = $store_address['city'];
  $temp_array['jobs'][0]['ship_from']['state'] = $store_address['state'];
  $temp_array['jobs'][0]['ship_from']['zipcode'] = $store_address['postcode'];
  $temp_array['jobs'][0]['ship_from']['country'] = $store_address['country'];

  //using billing phone/email here
  /*$temp_array['jobs'][0]['ship_to']['email'] = $billing_address['email'];
  $temp_array['jobs'][0]['ship_to']['phone'] = $billing_address['phone'];*/

  if($_POST['shipping_methods'] == 'Free Shipping'){  
      $temp_array['jobs'][0]["shipper"]["shipping_method"] = "FREE UPS Ground";
      $temp_array['jobs'][0]["shipper"]["shipping_code"] = "03f";
      $temp_array['jobs'][0]["ship_from_facility"] = "DAY";
  }

  $temp_array['payment']['requested_currency']['currency_code'] = $_POST['currency_code']; 
  $temp_array['payment']['order_id'] = $_POST['order_id'];

  $temp_array['payment']['billing_info']['first_name'] = $billing_address['first_name'];
  $temp_array['payment']['billing_info']['last_name'] = $billing_address['last_name'];
  $temp_array['payment']['billing_info']['address1'] = $billing_address['address_1'];
  $temp_array['payment']['billing_info']['address2'] = $billing_address['address_2'];
  $temp_array['payment']['billing_info']['city'] = $billing_address['city'];
  $temp_array['payment']['billing_info']['state'] = $billing_address['state'];
  $temp_array['payment']['billing_info']['zip'] = $billing_address['postcode'];
  $temp_array['payment']['billing_info']['country'] = $billing_address['country'];

  $customer_files_array_data = get_post_meta($post_id, '_wpf_umf_uploads', true);

    if(!empty($customer_files_array_data)){
        foreach ($customer_files_array_data as $key => $value) {
            $pid = explode('#', $key);
            $customer_files_array[$pid[0]] = $value;
        }
    }

    $saved_files_array = array();
    $files_data_array = $customer_files_array[intval($_POST['variation_id'])];
    $files = '';

    $i = 1; 
    foreach ($files_data_array as $key => $value) {
        
        if(count($value[1]) > 0){
            $j = 1;
            foreach ($value[1] as $key1 => $value2) {

                        $files_data = explode('/', $value2['path']);
                        $upload_dir = wp_upload_dir();
                        $file_name = $files_data[count($files_data)-1];
                        $file_full_path = $upload_dir['baseurl'].'/umf/'.$post->ID.'/'.$file_name;
                        $files = '<p><b>Customer Art Set '.$i.': </b><a target="_blank" href="'.$file_full_path.'">'.$value2['name'].'</a></p>';

                        $file_full_path = 'http://4c1ry23h0oe33ui1npjos4r1.wpengine.netdna-cdn.com/wp-content/uploads/2017/07/large-logo-1.png'; // hard code temp

                        $file_args = array();
                        $files_args['path'] = array($file_full_path);
                        $json_args = json_encode($files_args);
                        $file_api_response = rpep_4o_routes('upload_file', $json_args);
                        
                        $saved_files_array[$key]['files'][$j] = array(
                            'file_name' => $value2['name'],
                            'file_uuid' => $file_api_response['files'][0]['file_uuid']
                            );

                        $j++;
            }
        }
        $i++;
    }

    if(!empty($saved_files_array)){
        update_post_meta( $post_id, '_4over_order_'.intval($_POST['variation_id']).'_files', json_encode($saved_files_array) );
    }

  $process_files_data = json_decode(get_post_meta($post_id, '_4over_order_'.intval($_POST['variation_id']).'_files', true), true);  

  if(!empty($process_files_data)){

    $temp_array['jobs'][0]["skip_files"] = "false";

    $file_i = 0;

    foreach ($process_files_data as $key => $value) {
        if($file_i == 0){
            $temp_array['jobs'][0]['files']['fr']  = $value['files'][1]['file_uuid'];
        }else{
            $temp_array['jobs'][0]['files']['bk']  = $value['files'][1]['file_uuid'];
        }
        
        $file_i++;
    }
  }   

  $args = json_encode($temp_array);

  $api_response = rpep_4o_routes('process_order', $args);

  if(!empty($api_response)){

    $response = array();
    $response = $api_response;
      if($api_response['order_status'] == 'Success'){
        $response['order_id'] = $api_response['job_ids']['0'];
        update_post_meta( $post_id, '_4over_order_status_internal_'.intval($_POST['variation_id']), 'Printing' );
      }

      update_post_meta( $post_id, '_4over_order_'.intval($_POST['variation_id']).'_response', json_encode($response) );
  }

  echo json_encode($response);
  exit;
}

add_action( 'wp_ajax_radiant_process_order', 'radiant_ajax_process_order' );


function ajax_order_item_ajax_handler(){

    $post_id = intval($_POST['post_id']);
    $product_id = intval($_POST['product_id']);
    $variation_id = intval($_POST['variation_id']);
    $product_unique_key = $_POST['product_unique_key'];

    switch ($_POST['type']) {

        case 'order_status':
            $args['job_id'] = $_POST['job_id'];
            $api_response = rpep_4o_routes('order_status', $args);

            if(!empty($api_response)){

                $response = array();
                $response = $api_response;
                  if(!empty($api_response['entities'])){
                    $response['status'] = $api_response['entities']['0']['status'];
                  }
                update_post_meta( $post_id, '_4over_order_status_'.intval($_POST['variation_id']), json_encode($response) );  
            }
            break;

        case 'order_tracking':
            $args['job_id'] = $_POST['job_id'];
            $api_response = rpep_4o_routes('order_tracking', $args);
            
            if(empty($api_response)){
                $response['status'] = 'error';
                $response['message'] = 'Not available';
            }

            if(!empty($api_response)){

                $response = array();
                $response = $api_response;
                  if(!empty($api_response)){
                    $response['tracking'] = $api_response['tracking'];
                  }
            }
            update_post_meta( $post_id, '_4over_order_tracking_'.intval($_POST['variation_id']), json_encode($response) ); 
            break;

        case 'upload_file':

        if(!empty($_FILES['file'])){
            
            $customer_files_array_data = get_post_meta($post_id, '_wpf_umf_uploads', true);
            $upload_sets = get_option('wpf_umf_default_upload_set');
            
            if(empty($customer_files_array_data)){
                $customer_files_array_data = array();
            }
            
            $upload = new WPF_Uploads_Upload('', $_FILES['file'], $post_id, $variation_id.'#'.$product_unique_key, intval($_POST['upload_file_index']), intval($_POST['upload_set_index']), 1, get_option('wpf_umf_upload_path'), $upload_sets[1], 'before');

            $data = $upload->upload_local();

            $response = array(
                'name' => $upload->file_main_name,
                'extension' => strtolower($upload->file_extension),
                'path' =>  $upload->full_file_path,
                'thumb' => $upload->thumb,
                'status' => 'on-hold',
                'type' => $_POST['upload_type_name']
            );
            
            $customer_files_array_data[$variation_id.'#'.$product_unique_key][intval($_POST['upload_file_index'])][intval($_POST['upload_set_index'])][1] = $response;
            
            ksort($customer_files_array_data[$variation_id.'#'.$product_unique_key][intval($_POST['upload_file_index'])]);
            
            update_post_meta($post_id, '_wpf_umf_uploads', $customer_files_array_data);
            
        }
            
        break;

        case 'reset_art':
            $customer_files_array_data = get_post_meta(intval($_POST['post_id']), '_wpf_umf_uploads', true);
            unset($customer_files_array_data[intval($_POST['variation_id']).'#'.$_POST['product_unique_key']][intval($_POST['upload_file_index'])]);
            update_post_meta(intval($_POST['post_id']), '_wpf_umf_uploads', $customer_files_array_data);
            $response['status'] = 'success';
        break;


        default:
            # code...
            break;
    }
    

  echo json_encode($response);
  exit;

}

add_action( 'wp_ajax_ajax_order_item_action', 'ajax_order_item_ajax_handler' );