<?php

/**
 * @param (array) $contents ('addresses', 'set', 'product_id', 'variation_id', 'quantity' etc....)
 * @return String
 */
function woo4over_shipping_address_set_form($contents) {
    global $current_user;
    $product = wc_get_product($contents['product_id']);
    $wc_ship_multiple = new WC_Ship_Multiple;
    $wc_ms_package = new WC_MS_Packages($wc_ship_multiple);
    $cart_key = WC_Cart::generate_cart_id($contents['product_id'], $contents['variation_id']);
    $key = $contents['set'];
    $shipping_option = '<option value="">Select One</option>';
    $selected_address = '';
    $selected_method = '';
    if(isset($_SESSION[$cart_key][$cart_key.'#'.$key]['items']) && !empty($_SESSION[$cart_key][$cart_key.'#'.$key]['items'])) {
        $items = $_SESSION[$cart_key][$cart_key.'#'.$key]['items'];
        foreach ($items as $item) {
            $selected_address = isset($item['shipping']['address'][0]) ? $item['shipping']['address'][0] : '';
            $selected_method = isset($item['shipping_methods'][0]) ? $item['shipping_methods'][0] : '';
        }
        $selected_user_addr = woo4over_get_shipping_methods_address($contents['product_id'], $contents['variation_id'], $contents['quantity'], $key, $items);
                
        $packages = woo4over_get_shipping_rates($contents['product_id'], $contents['variation_id'], $selected_user_addr['data'], $contents['quantity'], $key);
        $shipping_option = woo4over_render_get_shipping_menthods($packages, $selected_method);
        if(is_cart() && $woocommerce->cart->cart_contents_count != 0) {
           if(isset($_SESSION['packages']) && !empty($_SESSION['packages'])) {
                $_SESSION['packages'][] = $packages[0];
           }
           else {
                $_SESSION['packages'][] = $packages[0];
           } 
           if(count($_SESSION['packages']) > $contents['quantity']) {
                foreach ($_SESSION['packages'] as $package_key => $packages_data) {
                    if(($package_key + 1) > $contents['quantity']) {
                        unset($_SESSION['packages'][$package_key]);
                    } 
                }
            }
        }
    }
    $html = '<div class="js-shipblock col-md-8 mk-col mk-col-8-12">';
        $html .= '<form method="post" action="" id="address_form_' . $key . '">';
            global $woocommerce;
            foreach ( $contents['addresses'] as $x => $addr ) {
                if ( empty( $addr ) )
                    continue;

                $address_fields = $woocommerce->countries->get_address_fields( $addr['shipping_country'], 'shipping_' );

                $address = array();
                $formatted_address = false;

                foreach ( $address_fields as $field_name => $field ) {
                    $addr_key = str_replace('shipping_', '', $field_name);
                    $address[$addr_key] = ( isset($addr[$field_name]) ) ? $addr[$field_name] : '';
                }

                if (! empty($address) ) {
                    $formatted_address  = wcms_get_formatted_address( $address );
                    $json_address       = json_encode($address);
                }

                if ( ! $formatted_address )
                    continue;

                $html .= '<div style="display: none;">';
                    $html .= '<input type="hidden" name="addresses[]" value="' . $x . '" />';
                    $html .= '<textarea style="display:none;">' . $json_address . '</textarea>';
                $html .= '</div>';
            }

            $html .= '<fieldset>';
                $html .= '<ul class="p-0">';
                    $html .= '<li class="row mb-2 mb-sm-4">';
                        $html .= '<label for="radius" class="col-sm-6 mb-0 colorgray mb-1 mb-sm-0">Order Date</label>';
                        $html .= '<div class="detailDate col-sm-6 type-150 text-left text-sm-right font-400">';
                            $html .= date('d/m/Y');                                   
                        $html .= '</div>';
                    $html .= '</li>';
                    $html .= '<li class="row mb-2 mb-sm-4">';
                        $html .= '<label for="shipping" class="col-sm-6 mb-0 colorgray mb-1 mb-sm-0">Shipping Address</label>';
                        $html .= '<div class="col-sm-6">';
                            $html .= '<select class="form-control shipping" data-addy="1" name="items['.$key.'][shipping][address][]">';
                                $html .= '<option value="">Select One</option>';
                                foreach ( $contents['addresses'] as $addr_key => $address ) {
                                    $selected_match = 'selected="selected"';
                                    if(trim($selected_address) != trim($addr_key)) {
                                        $selected_match = '';
                                    }
                                    $formatted = $address['shipping_first_name'] .' '. $address['shipping_last_name'] .',';
                                    $formatted .= ' '. $address['shipping_address_1'] .' '. $address['shipping_address_2'] .',';
                                    $formatted .= ' '. $address['shipping_city'] .', '. $address['shipping_state'];

                                    $html .= '<option value="'. $addr_key .'" ' . $selected_match . '>'. $formatted .'</option>';
                                }
                                $html .= '<option value="newaddress">+ Add New Address</option>';
                            $html .= '</select>';
                        $html .= '</div>';
                    $html .= '</li>';
                    $html .= '<li class="row mb-2 mb-sm-4">';
                        $html .= '<label for="shipping_method" class="col-sm-6 mb-0 colorgray mb-1 mb-sm-0">Shipping Methods</label>';
                        $html .= '<div class="col-sm-6">';
                            $html .= '<select class="form-control shipping_method" data-addy="1" name="items['.$key.'][shipping_methods][]">';
                                $html .= $shipping_option;
                            $html .= '</select>';
                        $html .= '</div>';
                    $html .= '</li>';

                    $html .= '<li class="row mb-2 mb-sm-4">';
                        $html .= '<label for="billing" class="col-sm-6 mb-0 colorgray mb-1 mb-sm-0">4Over Shipping Method</label>';
                        $html .= '<div class="col-sm-6">';
                            $html .= '<select class="form-control 4over_shipping_price" data-addy="1" name="items['.$key.'][4over_shipping_price]">';
                                $html .= $contents['shipping_option_4over'];
                            $html .= '</select>';
                        $html .= '</div>';
                    $html .= '</li>';
                $html .= '</ul>';
            $html .= '</fieldset>';
            $html .= '<div class="form-row addr_form_row_data">';
                $html .= '<input type="hidden" name="shipping_type" value="item" />';
                $html .= '<input type="hidden" name="shipping_address_action" value="save" />';
                $html .= '<input type="hidden" name="set" value="'.$key.'" />';
                $html .= '<input type="hidden" name="pid" value="'.$contents['product_id'].'" />';
                if($contents['variation_id'] && $contents['quantity']) {
                    $html .= '<input type="hidden" name="vid" value="'.$contents['variation_id'].'" />';
                    $html .= '<input type="hidden" name="qty" value="'.$contents['quantity'].'" />';
                }
                foreach ($product->get_variation_attributes() as $attr => $variation_attributes_val) {  
                    $html .= '<input type="hidden" name="'.str_replace('pa_', '', $attr).'" value="'.$contents[str_replace('pa_', '', $attr)].'" />';
                }
                if($contents['page'] == 'cart_page'){
                    $next_label = 'Save';
                }else{
                    $next_label = 'Next';
                }
                $html .= '<div class="set-shipping-addresses">';
                    $html .= '<input class="button alt next-click" type="button" name="set_addresses_data" value="'. __($next_label, 'wc_shipping_multiple_address') .'" data-set="'.$key.'"  />';
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</form>';
    $html .= '</div>';
    return $html;
}

/** Create structure for 4over shipping mehtods **/
function woo4over_get_4over_shipping_structure($vars, $selected='') {
    if(isset($vars['shipping_options']) && count($vars['shipping_options'])) {
        $html = '<option value="">Select Option</option>';
        foreach ($vars['shipping_options'] as $shipping_options) {
            $selected_item = '';
            if($selected == $shipping_options['service_code']) {
                $selected_item = 'selected="selected"';
            }
            $html .= '<option data-price="'.$shipping_options['service_price'].'" value="'.$shipping_options['service_code'].'" '.$selected_item.'>'.$shipping_options['service_name'].' - $'.$shipping_options['service_price'].'</option>';
        }
        return $html;
    }
    return '<option value="">Select Option</option>';
}