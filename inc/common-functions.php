<?php 

function woo4over_get_user_addresses( $user, $include_default = true ) {
    if (! $user instanceof WP_User ) {
        $user = new WP_User( $user );
    }

    if ($user->ID != 0) {
        $addresses = get_user_meta($user->ID, 'wc_other_addresses', true);

        if (! $addresses) {
            $addresses = array();
        }

        if ( $include_default ) {
	        $default_address = woo4over_get_user_default_address( $user->ID );

	        if ( $default_address['address_1'] && $default_address['postcode'] ) {
		        $addresses += array( $default_address );
	        }
        }
    } else {
        // guest address - using sessions to store the address
        $addresses = isset($_SESSION['user_addresses']) ? $_SESSION['user_addresses'] : [];
    }

    return woo4over_array_sort( $addresses, 'shipping_first_name' );
}

/** Get Default Address Of Current User **/
function woo4over_get_user_default_address( $user_id ) {
    $default_address = array(
        'shipping_first_name' 	=> get_user_meta( $user_id, 'shipping_first_name', true ),
        'shipping_last_name'	=> get_user_meta( $user_id, 'shipping_last_name', true ),
        'shipping_company'		=> get_user_meta( $user_id, 'shipping_company', true ),
        'shipping_address_1'	=> get_user_meta( $user_id, 'shipping_address_1', true ),
        'shipping_address_2'	=> get_user_meta( $user_id, 'shipping_address_2', true ),
        'shipping_city'			=> get_user_meta( $user_id, 'shipping_city', true ),
        'shipping_state'		=> get_user_meta( $user_id, 'shipping_state', true ),
        'shipping_postcode'		=> get_user_meta( $user_id, 'shipping_postcode', true ),
        'shipping_country'		=> get_user_meta( $user_id, 'shipping_country', true ),
        'default_address'       => true
    );

    // backwards compatibility
    $default_address['first_name'] 	= $default_address['shipping_first_name'];
    $default_address['last_name']	= $default_address['shipping_last_name'];
    $default_address['company']		= $default_address['shipping_company'];
    $default_address['address_1']	= $default_address['shipping_address_1'];
    $default_address['address_2']	= $default_address['shipping_address_2'];
    $default_address['city']		= $default_address['shipping_city'];
    $default_address['state']		= $default_address['shipping_state'];
    $default_address['postcode']	= $default_address['shipping_postcode'];
    $default_address['country']     = $default_address['shipping_country'];

    return $default_address;
}

/** Natural Short Array **/
function woo4over_array_sort($array, $on, $order=SORT_ASC) {
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort( $sortable_array, SORT_NATURAL | SORT_FLAG_CASE );
                break;
            case SORT_DESC:
                arsort( $sortable_array, SORT_NATURAL | SORT_FLAG_CASE  );
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

/***  Clear Shipping package cache **/
function woo4over_clear_packages_cache($woocommerce) {

    $woocommerce->cart->calculate_totals();
    $packages = $woocommerce->cart->get_shipping_packages();

    foreach ( $packages as $idx => $package ) {
        $package_hash   = 'wc_ship_' . md5( json_encode( $package ) );
        delete_transient( $package_hash );
    }
}


/**** Add Modal ****/
add_action('wp_footer', 'woo4over_custom_modal');
function woo4over_custom_modal() {
	?><!-- Modal Starts -->
    <div class="modal" id="wpbootstrapModal" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <!-- <div class="modal-header">
                    <h4 class="modal-title">Bootstrap Modal Title</h4>
                </div> -->
                <!-- Modal Body -->
                <div class="modal-body">
                    
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button class="btn btn-default" type="button" data-dismiss="modal">Close</button>
                </div>
                </div>
            </div>
        </div>
    </div><?php
}

//Get Default Shipping Address
function get_user_default_shipping_address($user_id) {
	return get_user_meta( $user_id, 'wc_other_addresses');
}

/**************************** Save Shipping Address *****************************/
add_action('wp_ajax_nopriv_woo4over_save_to_address_book', 'woo4over_save_address_book');
add_action('wp_ajax_woo4over_save_to_address_book', 'woo4over_save_address_book');

function woo4over_save_address_book() {
	global $woocommerce;
    $checkout   = $woocommerce->checkout;
    $user       = wp_get_current_user();

    $address    = $_POST['address'];
    $shipFields = $woocommerce->countries->get_address_fields( $address['shipping_country'], 'shipping_' );
    $errors     = array();

    foreach ( $shipFields as $key => $field ) {

        if ( isset($field['required']) && $field['required'] && empty($address[$key]) ) {
            $errors[] = $key;
        }

        if (! empty($address[$key]) ) {

            // Validation rules
            if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
                foreach ( $field['validate'] as $rule ) {
                    switch ( $rule ) {
                        case 'postcode' :
                            $address[ $key ] = strtoupper( str_replace( ' ', '', $address[ $key ] ) );

                            if ( ! WC_Validation::is_postcode( $address[ $key ], $address[ 'shipping_country' ] ) ) :
                                $errors[] = $key;
                                wc_add_notice( __( 'Please enter a valid postcode/ZIP.', 'wc_shipping_multiple_address' ), 'error' );
                            else :
                                $address[ $key ] = wc_format_postcode( $address[ $key ], $address[ 'shipping_country' ] );
                            endif;
                            break;
                        case 'phone' :
                            $address[ $key ] = wc_format_phone_number( $address[ $key ] );

                            if ( ! WC_Validation::is_phone( $address[ $key ] ) ) {
                                $errors[] = $key;

                                if ( function_exists('wc_add_notice') )
                                    wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid phone number.', 'wc_shipping_multiple_address' ), 'error' );
                                else
                                    $woocommerce->add_error('<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid phone number.', 'wc_shipping_multiple_address' ));
                            }

                            break;
                        case 'email' :
                            $address[ $key ] = strtolower( $address[ $key ] );

                            if ( ! is_email( $address[ $key ] ) ) {
                                $errors[] = $key;

                                if ( function_exists('wc_add_notice') )
                                    wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid email address.', 'wc_shipping_multiple_address' ), 'error' );
                                else
                                    $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid email address.', 'wc_shipping_multiple_address' ) );
                            }

                            break;
                        case 'state' :
                            // Get valid states
                            $valid_states = $woocommerce->countries->get_states( $address[ 'shipping_country' ] );
                            if ( $valid_states )
                                $valid_state_values = array_flip( array_map( 'strtolower', $valid_states ) );

                            // Convert value to key if set
                            if ( isset( $valid_state_values[ strtolower( $address[ $key ] ) ] ) )
                                $address[ $key ] = $valid_state_values[ strtolower( $address[ $key ] ) ];

                            // Only validate if the country has specific state options
                            if ( is_array($valid_states) && sizeof( $valid_states ) > 0 )
                                if ( ! in_array( $address[ $key ], array_keys( $valid_states ) ) ) {
                                    $errors[] = $key;

                                    if ( function_exists('wc_add_notice') )
                                        wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . __( 'is not valid. Please enter one of the following:', 'wc_shipping_multiple_address' ) . ' ' . implode( ', ', $valid_states ), 'error' );
                                    else
                                        $woocommerce->add_error('<strong>' . $field['label'] . '</strong> ' . __( 'is not valid. Please enter one of the following:', 'wc_shipping_multiple_address' ) . ' ' . implode( ', ', $valid_states ));
                                }

                            break;
                    }
                }
            }

        }

    }

    if ( count($errors) > 0 ) {
        die(json_encode(array( 'ack' => 'ERR', 'errors' => $errors, 'message' => __( 'Please enter the complete address', 'wc_shipping_multiple_address' ))));
    }

    $id  = intval($_POST['id']);

	$addresses = woo4over_get_user_addresses( $user );
   
    if ( $id >= 0 )
        $next = add_query_arg( 'updated', '1', $redirect_url );
    else
        $next = add_query_arg( 'new', '1', $redirect_url );

    // address is unique, save!
    if ( $id == -1 ) {
        $vals = '';
        foreach ($address as $key => $value) {
            $vals .= $value;
        }
        $md5 = md5($vals);

        foreach ($addresses as $addr) {
            $vals = '';
            if( !is_array($addr) ) { continue; }
            foreach ($addr as $key => $value) {
                $vals .= $value;
            }
            $addrMd5 = md5($vals);

            if ($md5 == $addrMd5) {
                // duplicate address!
                die(json_encode(array( 'ack' => 'ERR', 'message' => __( 'Address is already in your address book', 'wc_shipping_multiple_address' ))));
            }
        }

        $addresses[] = $address;
    } else {
        $addresses[$id] = $address;
    }

    // update the default address and remove it from the $addresses array
    if ( $user->ID > 0 ) {
        if ( $id == 0 ) {
            $default_address = $addresses[0];
            unset( $addresses[0] );

            if ( $default_address['shipping_address_1'] && $default_address['shipping_postcode'] ) {
                update_user_meta( $user->ID, 'shipping_first_name', $default_address['shipping_first_name'] );
                update_user_meta( $user->ID, 'shipping_last_name',  $default_address['shipping_last_name'] );
                update_user_meta( $user->ID, 'shipping_company',    $default_address['shipping_company'] );
                update_user_meta( $user->ID, 'shipping_address_1',  $default_address['shipping_address_1'] );
                update_user_meta( $user->ID, 'shipping_address_2',  $default_address['shipping_address_2'] );
                update_user_meta( $user->ID, 'shipping_city',       $default_address['shipping_city'] );
                update_user_meta( $user->ID, 'shipping_state',      $default_address['shipping_state'] );
                update_user_meta( $user->ID, 'shipping_postcode',   $default_address['shipping_postcode'] );
                update_user_meta( $user->ID, 'shipping_country',    $default_address['shipping_country'] );
            }
            unset( $addresses[0] );
        }

    }

    woo4over_save_user_addresses( $user->ID, $addresses );

    foreach ( $address as $key => $value ) {
        $new_key = str_replace( 'shipping_', '', $key);
        $address[$new_key] = $value;
    }

    $formatted_address  = wcms_get_formatted_address( $address );
    $json_address       = json_encode($address);

    if (!$formatted_address) return;

    if ( isset($_POST['return']) && $_POST['return'] == 'list' ) {
        $html = '<option value="'. $id .'">'. $formatted_address .'</option>';
    } else {
        $html = '
                <div class="account-address">
                    <address>'. $formatted_address .'</address>
                    <div style="display: none;">';

        ob_start();
        foreach ($shipFields as $key => $field) :
            $val = (isset($address[$key])) ? $address[$key] : '';
            $key .= '_'. $id;

            woocommerce_form_field( $key, $field, $val );
        endforeach;

        do_action( 'woocommerce_after_checkout_shipping_form', $checkout);
        $html .= ob_get_clean();

        $html .= '
                        <input type="hidden" name="addresses[]" value="'. $id .'" />
                    </div>

                    <ul class="items-column" id="items_column_'. $id .'">
                        <li class="placeholder">' . __( 'Drag items here', 'wc_shipping_multiple_address' ) . '</li>
                    </ul>
                </div>
                ';
    }

    $return = json_encode(array( 'ack' => 'OK', 'id' => $id, 'html' => $html, 'return' => $_POST['return'], 'next' => $next));
    die($return);

}
/*************************** Save Shipping Address  END *************************/

function woo4over_save_user_addresses( $user_id, $addresses ) {  
	global $woocommerce;    
	$keys = array();
	foreach ( $addresses as $index => $address ) {
		if ( ! empty( $address['default_address'] ) ) {
			// Remove default address
			unset( $addresses[ $index ] );
		} elseif ( $key = woo4over_unique_address_key( $address ) ) {
			// Save unique address key
			$keys[ $index ] = $key;
		} else {
			// Remove empty address
			unset( $addresses[ $index ] );
		}
	}

    

	// Remove any duplicate addresses
	$duplicates = array_diff_assoc( $keys, array_unique( $keys ) );
	foreach( array_keys( $duplicates ) as $index ) {
		unset( $addresses[ $index ] );
	}

	if ( $user_id > 0 ) {
		update_user_meta( $user_id, 'wc_other_addresses', $addresses );
	} else {
		// $woocommerce->session->__unset( 'user_addresses' );
		unset($_SESSION['user_addresses']);
		// $woocommerce->session->set( 'user_addresses', $addresses );
		$_SESSION['user_addresses'] = $addresses;
	}
}

function woo4over_unique_address_key( $address ) {

	if ( empty( $address ) || ! is_array( $address ) ) {
		return false;
	}

	return md5( implode( '_', $address ) );
}

/************** Add select dropdown for already added address on shipping address form ***********/
add_action('woocommerce_before_checkout_shipping_form', 'render_anonymous_address_dropdown');

function render_anonymous_address_dropdown() {
	$user = wp_get_current_user();
	$addresses = isset($_SESSION['user_addresses']) ? $_SESSION['user_addresses'] : [];

    if ( count( $addresses ) ):
        ?>
        <p id="ms_shipping_addresses_field" class="form-row form-row-wide ms-addresses-field">
            <label class=""><?php _e('Stored Addresses', 'wc_shipping_multiple_address'); ?></label>
            <select class="" id="ms_addresses">
                <option value=""><?php _e('Select an address to use...', 'wc_shipping_multiple_address'); ?></option>
                <?php
                foreach ( $addresses as $key => $address ) {
                    $formatted_address = $address['shipping_first_name'] .' '. $address['shipping_last_name'] .', '. $address['shipping_address_1'] .', '. $address['shipping_city'];
                    echo '<option value="'. $key .'"';
                    foreach ( $address as $key => $value ) {
                        echo ' data-'. $key .'="'. esc_attr( $value ) .'"';
                    }
                    echo '>'. $formatted_address .'</option>';
                }
                ?>
            </select>
        </p>
    <?php
    endif;
}
/********************************* Add select dropdown fo.... END  ********************************/



/**
 * @param array $packages
 * @param int $type 0=multi-shipping; 1=different packages; 2=same packages
 */
function woo4over_render_shipping_row($packages, $type = 2) {
	global $woocommerce; 
    $page_id            = wc_get_page_id( 'multiple_addresses' );
    $rates_available    = false;

    if ( function_exists('wc_add_notice') ) {
        $available_methods  = woo4over_get_available_shipping_methods();
    } else {
        $available_methods  = $woocommerce->shipping->get_available_shipping_methods();
    }

    $field_name         = 'shipping_methods';
    $post               = array();

    if ( function_exists('wc_add_notice') ) {
        $field_name = 'shipping_method';
    }

    if ( isset($_POST['post_data']) ) {
        parse_str($_POST['post_data'], $post);
    }
    				
    if ( $type == 0 || $type == 1):

    ?>
    <tr class="multi_shipping">
        <td style="vertical-align: top;" colspan="<?php if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) echo '2'; else echo '1'; ?>">
            <?php _e( 'Shipping Methods', 'wc_shipping_multiple_address' ); ?>

            <div id="shipping_addresses">
                <?php
                foreach ($packages as $x => $package):
                			
                    $has_address    = true;
                    if ( woo4over_is_address_empty( $package['destination'] ) ) {
                        $has_address = false;
                           
                    } elseif ( !isset( $package['rates'] ) || empty( $package['rates'] ) ) {
                        $has_address = false;
                    }


                    if (! $has_address ) {
                        // we have cart items with no set address
                        $products = $package['contents'];       
                        ?>
                        <div class="ship_address no_shipping_address">
                            <em><?php _e('The following items do not have shipping addresses assigned.', 'wc_shipping_multiple_address'); ?></em>
                            <ul>
                            <?php
                                foreach ($products as $i => $product):
                                    $attributes = html_entity_decode( WC_MS_Compatibility::get_item_data( $product ) );
                                    ?>
                                    <li>
                                        <strong><?php echo wp_kses_post( apply_filters( 'wcms_product_title', get_the_title($product['data']->get_id()), $product ) ); ?> x <?php echo $product['quantity']; ?></strong>
                                        <?php
                                        if ( !empty( $attributes ) ) {
                                            echo '<small class="data">'. str_replace( "\n", "<br/>", $attributes ) .'</small>';
                                        }
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                                <?php
                                $sess_cart_addresses =  isset($_SESSION['cart_item_addresses']) ? $_SESSION['cart_item_addresses'] : [];
                                //if ( $sess_cart_addresses && !empty($sess_cart_addresses) ) {
                                    echo '<p style="text-align: center"><a href="'. get_permalink($page_id) .'" class="button modify-address-button">'. __( 'Assign Shipping Address', 'wc_shipping_multiple_address' ) .'</a></p>';
                                //}
                        ?>
                        </div>
                        <?php
                        continue;
                    }

                    $shipping_methods   = array();
                    $products           = $package['contents'];
                    //$shipping_methods   = $package['rates'];
                    $selected           = isset($_SESSION['shipping_methods']) ? $_SESSION['shipping_methods'] : [];
                    $rates_available    = true;

                    if ( $type == 0 ):
                ?>
                <div class="ship_address">
                    <dl>
                    <?php
                        foreach ($products as $i => $product):
                            $attributes = html_entity_decode( WC_MS_Compatibility::get_item_data( $product, true ) );
                    ?>
                    <dd>
                        <strong><?php echo wp_kses_post( apply_filters( 'wcms_product_title', get_the_title($product['data']->get_id()), $product ) ); ?> x <?php echo $product['quantity']; ?></strong>
                        <?php
                            if ( !empty( $attributes ) ) {
                                echo '<small class="data">'. str_replace( "\n", "<br/>", $attributes )  .'</small>';
                            }
                        ?>
                    </dd>
                        <?php endforeach; ?>
                    </dl>
                        <?php
                        $formatted_address = wcms_get_formatted_address( $package['destination'] );
                        echo '<address>'. $formatted_address .'</address><br />'; ?>
                        <?php

                        do_action( 'wc_ms_shipping_package_block', $x, $package );

                        // If at least one shipping method is available
                        $ship_package['rates'] = array();

                        foreach ( $package['rates'] as $rate ) {
                            $ship_package['rates'][$rate->id] = $rate;
                        }

                        foreach ( $ship_package['rates'] as $method ) {
                            if ( $method->id == 'multiple_shipping' ) continue;

                            $method->label = esc_html( $method->label );

                            if ( $method->cost > 0 ) {
                                $shipping_tax = $method->get_shipping_tax();
                                $method->label .= ' &mdash; ';

                                // Append price to label using the correct tax settings
                                if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {

                                    if ( $shipping_tax > 0 ) {
                                        if ( $woocommerce->cart->prices_include_tax ) {
                                            $method->label .= wc_price( $method->cost ) .' '.$woocommerce->countries->ex_tax_or_vat();
                                        } else {
                                            $method->label .= wc_price( $method->cost );
                                        }
                                    } else {
                                        $method->label .= wc_price( $method->cost );
                                    }
                                } else {
                                    $method->label .= wc_price( $method->cost + $shipping_tax );
                                    if ( $shipping_tax > 0 && ! $woocommerce->cart->prices_include_tax ) {
                                        $method->label .= ' '.$woocommerce->countries->inc_tax_or_vat();
                                    }
                                }
                            }

                            $shipping_methods[] = $method;
                        }

                        // Print the single available shipping method as plain text
                        if ( 1 === count( $shipping_methods ) ) {
                            $method = $shipping_methods[0];

                            echo $method->label;
                            echo '<input type="hidden" class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']" value="'.esc_attr( $method->id ).'">';

                        // Show multiple shipping methods in a select list
                        } elseif ( count( $shipping_methods ) > 1 ) {
                            if ( !is_array( $selected ) || !isset( $selected[ $x ] ) ) {
                                $cheapest_rate = wcms_get_cheapest_shipping_rate( $package['rates'] );

                                if ( $cheapest_rate ) {
                                    $selected[ $x ] = $cheapest_rate;
                                }
                            }

                            echo '<select class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']">';

                            foreach ( $package['rates'] as $rate ) {
                                if ( $rate->id == 'multiple_shipping' ) continue;
                                $sel = '';

                                if ( $selected[$x]['id'] == $rate->id ) $sel = 'selected';

                                echo '<option value="'.esc_attr( $rate->id ).'" '. $sel .'>';
                                echo strip_tags( $rate->label );
                                echo '</option>';
                            }

                            echo '</select>';
                        } else {
                            echo '<p>'.__( '(1) Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'wc_shipping_multiple_address' ).'</p>';
                        }

                        $sess_cart_addresses = isset($_SESSION['cart_item_addresses']) ? $_SESSION['cart_item_addresses'] : [];
                        if ( $sess_cart_addresses && !empty($sess_cart_addresses) ) {
                            echo '<p><a href="'. get_permalink($page_id) .'" class="modify-address-button">'. __( 'Modify address', 'wc_shipping_multiple_address' ) .'</a></p>';
                        }
                ?>
                </div>
                <?php
                    elseif ($type == 1):
                ?>
                <div class="ship_address">
                    <dl>
                    <?php
                        foreach ($products as $i => $product):
                            $attributes = WC_MS_Compatibility::get_item_data( $product );
                    ?>
                    <dd>
                        <strong><?php echo esc_html( apply_filters( 'wcms_product_title', get_the_title($product['data']->get_id()), $product ) ); ?> x <?php echo $product['quantity']; ?></strong>
                            <?php
                            if ( !empty($attributes) ) {
                                echo '<small class="data">'. str_replace( "\n", "<br/>", $attributes )  .'</small>';
                            }
                            ?>
                    </dd>
                        <?php endforeach; ?>
                    </dl>
                    <?php
                        // If at least one shipping method is available
                        // Calculate shipping method rates
                        $ship_package['rates'] = array();

                        foreach ( $woocommerce->shipping->shipping_methods as $shipping_method ) {

                            if ( isset($package['method']) && !in_array($shipping_method->id, $package['method']) ) continue;

                            if ( $shipping_method->is_available( $package ) ) {

                                // Reset Rates
                                $shipping_method->rates = array();

                                // Calculate Shipping for package
                                $shipping_method->calculate_shipping( $package );

                                // Place rates in package array
                                if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) )
                                    foreach ( $shipping_method->rates as $rate )
                                        $ship_package['rates'][$rate->id] = $rate;
                            }

                        }

                        foreach ( $ship_package['rates'] as $method ) {
                            if ( $method->id == 'multiple_shipping' ) continue;

                            $method->label = esc_html( $method->label );

                            if ( $method->cost > 0 ) {
                                $shipping_tax = $method->get_shipping_tax();
                                $method->label .= ' &mdash; ';

                                // Append price to label using the correct tax settings
                                if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {

                                    if ( $shipping_tax > 0 ) {
                                        if ( $woocommerce->cart->prices_include_tax ) {
                                            $method->label .= wc_price( $method->cost ) .' '.$woocommerce->countries->ex_tax_or_vat();
                                        } else {
                                            $method->label .= wc_price( $method->cost );
                                        }
                                    } else {
                                        $method->label .= wc_price( $method->cost );
                                    }
                                } else {
                                    $method->label .= wc_price( $method->cost + $shipping_tax );
                                    if ( $shipping_tax > 0 && ! $woocommerce->cart->prices_include_tax ) {
                                        $method->label .= ' '.$woocommerce->countries->inc_tax_or_vat();
                                    }
                                }
                            }

                            $shipping_methods[] = $method;
                        }

                        // Print a single available shipping method as plain text
                        if ( 1 === count( $shipping_methods ) ) {
                            $method = $shipping_methods[0];

                            echo $method->label;
                            echo '<input type="hidden" class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']" value="'.esc_attr( $method->id ).'||'. strip_tags($method->label) .'">';

                        // Show multiple shipping methods in a select list
                        } elseif ( count( $shipping_methods ) > 1 ) {
                            echo '<select class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']">';
                            foreach ( $shipping_methods as $method ) {
                                if ($method->id == 'multiple_shipping' ) continue;
                                $current_selected = ( isset($selected[ $x ])  ) ? $selected[ $x ]['id'] : '';
                                echo '<option value="'.esc_attr( $method->id ).'||'. strip_tags($method->label) .'" '.selected( $current_selected, $method->id, false).'>';

                                if ( function_exists('wc_cart_totals_shipping_method_label') )
                                    echo wp_kses_post( wc_cart_totals_shipping_method_label( $method ));
                                else
                                    echo strip_tags( $method->label );

                                echo '</option>';
                            }
                            echo '</select>';
                        } else {
                            echo '<p>'.__( '(2) Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'wc_shipping_multiple_address' ).'</p>';
                        }

                        $sess_cart_addresses = isset($_SESSION['cart_item_addresses']) ? $_SESSION['cart_item_addresses'] : [];
                        if ( $sess_cart_addresses && !empty($sess_cart_addresses) ) {
                            echo '<p><a href="'. get_permalink($page_id) .'" class="modify-address-button">'. __( 'Modify address', 'wc_shipping_multiple_address' ) .'</a></p>';
                        }
                ?>
                </div>
                <?php endif;

                endforeach; ?>
                <div style="clear:both;"></div>

                <?php if (! function_exists('wc_add_notice') ): ?>
                <input type="hidden" name="shipping_method" value="multiple_shipping" />
                <?php endif; ?>
            </div>

        </td>
        <td style="vertical-align: top;">
            <?php
            $shipping_total = $woocommerce->cart->shipping_total;
            $shipping_tax   = $woocommerce->cart->shipping_tax_total;
            $inc_or_exc_tax = '';

            if ( $shipping_total > 0 ) {

                // Append price to label using the correct tax settings
                if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {

                    if ( $shipping_tax > 0 ) {

                        if ( $woocommerce->cart->prices_include_tax ) {
                            $shipping_total = $shipping_total;
                            $inc_or_exc_tax = $woocommerce->countries->ex_tax_or_vat();
                        } else {
                            $shipping_total += $shipping_tax;
                            $inc_or_exc_tax = $woocommerce->countries->inc_tax_or_vat();
                        }
                    }
                } else {
                    $shipping_total += $shipping_tax;

                    if ( $shipping_tax > 0 && ! $woocommerce->cart->prices_include_tax ) {
                        $inc_or_exc_tax = $woocommerce->countries->inc_tax_or_vat();
                    }
                }
            }

            echo wc_price( $shipping_total ) .' '. $inc_or_exc_tax;
            ?>
        </td>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery("tr.shipping").remove();
            });
        <?php
        if (isset($_SESSION['shipping_methods']) && null == $_SESSION['shipping_methods'] && $rates_available ) {
            echo 'jQuery("body").trigger("update_checkout");';
        }
        ?>
        </script>
    </tr>
    <?php
    else:
    ?>
    <tr class="multi_shipping">
        <td style="vertical-align: top;" colspan="<?php if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) echo '2'; else echo '1'; ?>">
            <?php _e( 'Shipping Methods', 'wc_shipping_multiple_address' ); ?>

            <?php
            foreach ($packages as $x => $package):
                $shipping_methods   = array();
                $products           = $package['contents'];

                if ($type == 2):
                    // If at least one shipping method is available
                    // Calculate shipping method rates
                    $ship_package['rates'] = array();

                    foreach ( $woocommerce->shipping->shipping_methods as $shipping_method ) {

                        if ( isset($package['method']) && !in_array($shipping_method->id, $package['method']) ) {
                            continue;
                        }

                        if ( $shipping_method->is_available( $package ) ) {

                            // Reset Rates
                            $shipping_method->rates = array();

                            // Calculate Shipping for package
                            $shipping_method->calculate_shipping( $package );

                            // Place rates in package array
                            if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) )
                                foreach ( $shipping_method->rates as $rate )
                                    $ship_package['rates'][$rate->id] = $rate;
                        }

                    }

                    foreach ( $ship_package['rates'] as $method ) {
                        if ( $method->id == 'multiple_shipping' ) continue;

                        $method->label = esc_html( $method->label );

                        if ( $method->cost > 0 ) {
                            $method->label .= ' &mdash; ';

                            // Append price to label using the correct tax settings
                            if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {
                            $method->label .= wc_price( $method->cost );
                                if ( $method->get_shipping_tax() > 0 && $woocommerce->cart->prices_include_tax ) {
                                    $method->label .= ' '.$woocommerce->countries->ex_tax_or_vat();
                        }
                            } else {
                                $method->label .= wc_price( $method->cost + $method->get_shipping_tax() );
                                if ( $method->get_shipping_tax() > 0 && ! $woocommerce->cart->prices_include_tax ) {
                                    $method->label .= ' '.$woocommerce->countries->inc_tax_or_vat();
                                }
                            }
                        }
                        $shipping_methods[] = $method;
                    }

                    // Print a single available shipping method as plain text
                    if ( 1 === count( $shipping_methods ) ) {
                        $method = $shipping_methods[0];
                        echo $method->label;
                        echo '<input type="hidden" class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']" value="'.esc_attr( $method->id ).'">';

                    // Show multiple shipping methods in a select list
                    } elseif ( count( $shipping_methods ) > 1 ) {
                        echo '<select class="shipping_methods shipping_method" name="'. $field_name .'['. $x .']">';
                        foreach ( $shipping_methods as $method ) {
                            if ($method->id == 'multiple_shipping' ) continue;
                            echo '<option value="'.esc_attr( $method->id ).'" '.selected( $method->id, (isset($post['shipping_method'])) ? $post['shipping_method'] : '', false).'>';
                            echo strip_tags( $method->label );
                            echo '</option>';
                        }
                        echo '</select>';
                    } else {
                        echo '<p>'.__( '(3) Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'wc_shipping_multiple_address' ).'</p>';
                    }

                    $sess_cart_addresses = isset($_SESSION['cart_item_addresses']) ? $_SESSION['cart_item_addresses'] : [];
                    if ( $sess_cart_addresses && !empty($sess_cart_addresses) ) {
                        echo '<p><a href="'. get_permalink($page_id) .'" class="modify-address-button">'. __( 'Modify address', 'wc_shipping_multiple_address' ) .'</a></p>';
                    }
                endif;
            endforeach;
            ?>
        </td>
        <td style="vertical-align: top;"><?php echo wc_price( $woocommerce->cart->shipping_total + $woocommerce->cart->shipping_tax_total ); ?></td>
        <script type="text/javascript">
        jQuery("tr.shipping").remove();
        <?php
        if (isset($_SESSION['shipping_methods']) && null == $_SESSION['shipping_methods'] && $rates_available ) {
            echo 'jQuery("body").trigger("update_checkout");';
        }
        ?>
        </script>
    </tr>
    <?php
    endif;
}

function woo4over_get_available_shipping_methods() {
	global $woocommerce;

    $packages = $woocommerce->cart->get_shipping_packages();

    // Loop packages and merge rates to get a total for each shipping method
    $available_methods = array();

    foreach ( $packages as $package ) {
        if ( !isset($package['rates']) || !$package['rates'] ) continue;

        foreach ( $package['rates'] as $id => $rate ) {

            if ( isset( $available_methods[$id] ) ) {
                // Merge cost and taxes - label and ID will be the same
                $available_methods[$id]->cost += $rate->cost;

                foreach ( array_keys( $available_methods[$id]->taxes + $rate->taxes ) as $key ) {
                    $available_methods[$id]->taxes[$key] = ( isset( $rate->taxes[$key] ) ? $rate->taxes[$key] : 0 ) + ( isset( $available_methods[$id]->taxes[$key] ) ? $available_methods[$id]->taxes[$key] : 0 );
                }
            } else {
                $available_methods[$id] = $rate;
            }

        }

    }

    return apply_filters( 'wcms_available_shipping_methods', $available_methods );
}


/** Check For Empty Adderess **/
function woo4over_is_address_empty( $address_array ) {
    if ( empty( $address_array['country'] ) ) {
        return true;
    }

    return false;
}

/*** Get Shipping Rates ***/

function woo4over_get_shipping_rates($product_id, $variation_id, $addresses, $quantity, $set) {
	$packages = [];
	if(!empty($addresses)) {
		global $woocommerce;
        $product = new WC_Product($product_id); 
		$cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);
		add_filter('woocommerce_product_needs_shipping', function(){return false;});
	 	$woocommerce->shipping->load_shipping_methods();
        $woocommerce->shipping->reset_shipping();
        if(!empty(woo4over_separate_multiple_address($addresses, $quantity, $product_id, $variation_id, $set))) {
        	$addrs = woo4over_separate_multiple_address($addresses, $quantity, $product_id, $variation_id, $set);
        	foreach ($addrs as $key => $addr) {
        		$first_name = $addr['first_name'];
        		$last_name = $addr['last_name'];
        		$country = $addr['country'];
        		$state = $addr['state'];
        		$postcode = $addr['postcode'];
        		$city = $addr['city'];
        		$address_1 = $addr['address_1'];
        		$address_2 = $addr['address_2'];
        		if ( $postcode && ! WC_Validation::is_postcode( $postcode, $country ) ) {
		            throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
		        } 
		        elseif ( $postcode ) {
		            $postcode = wc_format_postcode( $postcode, $country );
		        }

		        if ( $country ) {
		            $woocommerce->customer->set_location( $country, $state, $postcode, $city );
		            $woocommerce->customer->set_shipping_location( $country, $state, $postcode, $city );
		        } else {
		            $woocommerce->customer->set_to_base();
		            $woocommerce->customer->set_shipping_to_base();
		        }

		        $woocommerce->customer->set_calculated_shipping( true );
        		$woocommerce->customer->save();

        		do_action( 'woocommerce_calculated_shipping' );

        		$cu = get_woocommerce_currency_symbol();
        		$woocommerce->shipping->calculate_shipping( $woocommerce->cart->get_shipping_packages() );
				$package = $woocommerce->shipping->get_packages();
				$packages[$key] = $package[0];
				$packages[$key]['destination']['first_name'] = $first_name;
				$packages[$key]['destination']['last_name'] = $last_name;
				$packages[$key]['destination']['address_1'] = $address_1;
				$packages[$key]['destination']['address_2'] = $address_2;
				$packages[$key]['contents_cost'] = $product->price;
				$packages[$key]['contents'][$cart_key] = [
					'key' => $cart_key,
					'product_id' => $product_id,
					'quantity' => 1,
					'line_subtotal' => $product->price,
					'line_total' => $product->price,
					'data' => $product,
					'cart_key' => $cart_key
				] ;
        	}
        }
	}         
	return $packages;
}

function woo4over_separate_multiple_address($addresses, $quantity, $product_id, $variation_id, $set) {
	if(!empty($addresses)) {
        $cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);
		$addr = [];
        if(isset($addresses['shipping_country_'.$cart_key.'_'.$product_id.'_'.$set]) && isset($addresses['shipping_postcode_'.$cart_key.'_'.$product_id.'_'.$set]) && isset($addresses['shipping_state_'.$cart_key.'_'.$product_id.'_'.$set])) {
			$addr[] = [
				'country' => $addresses['shipping_country_'.$cart_key.'_'.$product_id.'_'.$set],
				'state' => $addresses['shipping_state_'.$cart_key.'_'.$product_id.'_'.$set],
				'postcode' => $addresses['shipping_postcode_'.$cart_key.'_'.$product_id.'_'.$set],
				'first_name' => $addresses['shipping_first_name_'.$cart_key.'_'.$product_id.'_'.$set],
				'last_name' => $addresses['shipping_last_name_'.$cart_key.'_'.$product_id.'_'.$set],
				'address_1' => $addresses['shipping_address_1_'.$cart_key.'_'.$product_id.'_'.$set],
				'address_2' => $addresses['shipping_address_2_'.$cart_key.'_'.$product_id.'_'.$set],
				'city' => $addresses['shipping_city_'.$cart_key.'_'.$product_id.'_'.$set],
			];
        }
		return $addr;
	}
	return [];
}

/** Get Shiiping Methods Drop Down **/
function woo4over_render_get_shipping_menthods($package, $selected = '') {
    global $woocommerce;
    $ship_package['rates'] = array();
    $html = '';
    foreach ($package as $rates) {
        foreach ( $rates['rates'] as $rate ) {
            $ship_package['rates'][$rate->id] = $rate;
        }  
    }
  
    foreach ( $ship_package['rates'] as $method ) {
        if ( $method->id == 'multiple_shipping' ) continue;

        $method->label = esc_html( $method->label );

        if ( $method->cost > 0 ) {
            $method->label .= ' &mdash; ';

            // Append price to label using the correct tax settings
            if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {
            $method->label .= wc_price( $method->cost );
                if ( $method->get_shipping_tax() > 0 && $woocommerce->cart->prices_include_tax ) {
                    $method->label .= ' '.$woocommerce->countries->ex_tax_or_vat();
        }
            } else {
                $method->label .= wc_price( $method->cost + $method->get_shipping_tax() );
                if ( $method->get_shipping_tax() > 0 && ! $woocommerce->cart->prices_include_tax ) {
                    $method->label .= ' '.$woocommerce->countries->inc_tax_or_vat();
                }
            }
        }
        $shipping_methods[] = $method;
    }
    $html .= '<option value="">Select One</option>';
    foreach ( $shipping_methods as $method ) {
        if ($method->id == 'multiple_shipping' ) continue;
        $selected_match = '';
        if($method->id == $selected) {
            $selected_match = 'selected="selected"';
        }
        $html .= '<option value="'.esc_attr( $method->id ).'" ' . $selected_match . '>';
        $html .= strip_tags( $method->label );
        $html .= '</option>';
    }
    return $html;
}
/******** Shipping Method drop down END *********/

/*********** Save Set Data's *************/
add_action('wp_ajax_save_product_set_data', 'woo4over_save_product_set_data');
add_action('wp_ajax_nopriv_save_product_set_data', 'woo4over_save_product_set_data');
function woo4over_save_product_set_data() {
    if(isset($_POST['shipping_address_action']) && $_POST['shipping_address_action'] == 'save') {
        $product_id = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
        $variation_id = isset($_POST['vid']) ? intval($_POST['vid']) : 0;
        $set = isset($_POST['set']) ? intval($_POST['set']) : 0;
        $quantity = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
        $cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);

        // Manage Session For Quantity Wise
        // If Session have more value that quantity then index above quantity will clear
        if(count($_SESSION[$cart_key]) > $quantity) {
            foreach ($_SESSION[$cart_key] as $p_key => $save_data) {
                list($cart_k, $index) = explode('#', $p_key);
                if($index > $quantity) {
                   unset($_SESSION[$cart_key][$cart_key.'#'.$index]); 
                   unset($_SESSION['wpf_umf_temp_data'][$variation_id.'#'.$cart_key][$index]);
                }
            }
        }
        else {
            // unset($_SESSION[$cart_key][$cart_key.'#'.$set]);
            // unset($_SESSION['wpf_umf_temp_data'][$variation_id.'#'.$cart_key][$set]);
        }
        $_SESSION[$cart_key][$cart_key.'#'.$set]['items'] = isset($_POST['items']) ? $_POST['items'] : [];
        $_SESSION[$cart_key][$cart_key.'#'.$set]['product_id'] = $product_id;
        $_SESSION[$cart_key][$cart_key.'#'.$set]['vid'] = $variation_id;
        $_SESSION[$cart_key][$cart_key.'#'.$set]['qty'] = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
        echo 'true';
        wp_die();   
    }
    else {
        echo 'false';
        wp_die(); 
    } 
}
/*********** Save Set Data's END*************/

/*************** Get file uploads structure *********************/
function woo4over_before_single_product_new($product_id, $var_id, $quantity, $set = '') {
   ob_start();           
    global $wpf_uploads_instance;
    $mp = $wpf_uploads_instance;

    $unique_product_ky = WC_Cart::generate_cart_id( $product_id, $var_id);
    $cart_info[$unique_product_ky]['product_id'] = $product_id;
    $cart_info[$unique_product_ky]['variation_id'] = $var_id;
    $cart_info[$unique_product_ky]['quantity'] = $quantity;

    $products = [];
    $cart_product_data = [];
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
        $count_set = count($upload_products[$var_id.'#'.$unique_product_ky]['boxes']);
        if($count_set > 0){
            foreach ($upload_products[$var_id.'#'.$unique_product_ky]['boxes'] as $key => $product_temp) {
                //echo $key.'->'.$set;
                if($key != $set){
                    unset($upload_products[$var_id.'#'.$unique_product_ky]['boxes'][$key]);
                }
            }
        }
        
    }

    // Current uploads

    if (isset($_SESSION['wpf_umf_temp_data']))
        $current_uploads = $_SESSION['wpf_umf_temp_data'];


    $upload_mode = 'before';
    // $var = '';
    if (is_array($upload_products)) {

        include(plugin_dir_path(__DIR__) . 'pages/upload-boxes-custom.php');
        
    }   
    $var = ob_get_contents(); 
    ob_end_clean();
    $html = $var;
    return $html;
    wp_die();
}

/*** Remove Product From Cart ***/
add_action('wp_ajax_woo4over_remove_item_from_cart', 'woo4over_remove_item_from_cart');
add_action('wp_ajax_nopriv_woo4over_remove_item_from_cart', 'woo4over_remove_item_from_cart');

function woo4over_remove_item_from_cart() {
    global $woocommerce;
    $product_unique_key = $_POST['unique_key'];
    $product_index = intval($_POST['index']);
    $product_id = intval($_POST['product_id']);
    $variation_id = intval($_POST['vid']);
    if( !empty($product_unique_key) ){

        if (isset($_SESSION['wpf_umf_temp_data'])) 
            $current_uploads = $_SESSION['wpf_umf_temp_data'];
        
        unset($_SESSION['wpf_umf_temp_data'][$variation_id.'#'.$product_unique_key][$product_index]);
        unset($_SESSION[$product_unique_key][$product_unique_key.'#'.$product_index]);
        foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
            if( $product_id == $cart_item['product_id'] ) {
                $woocommerce->cart->set_quantity( $cart_item_key, $cart_item['quantity'] - 1, true  );
                echo 'true';
                wp_die();
                break;
            }
        }
    }
    echo 'false';
    wp_die();
}

/** Session Manager **/
add_action('init', 'woo4over_session_manager');
function woo4over_session_manager() {   
    session_start();
    global $woocommerce, $product;
    if(function_exists('wcms_session_set'))
    wcms_session_set( 'wcms_packages', $_SESSION['packages'] );

    if($woocommerce->cart->cart_contents_count != 0) {
        woo4over_replace_cutom_cart_key_with_original_key($woocommerce);
    } 
}

/** Get Shipping Address According Selected From Drop Down **/
function woo4over_get_shipping_methods_address($product_id, $variation_id, $quantity, $set, $items) {
    global $woocommerce;
    $wc_multiple_ship = new WC_Ship_Multiple;
    $multishipping_addr_book = new WC_MS_Address_Book($wc_multiple_ship);
    
    /* @var $cart WC_Cart */
    $cart       = $woocommerce->cart;
    $checkout   = $woocommerce->checkout;

    $user_addresses = woo4over_get_user_addresses(wp_get_current_user());
            
    $fields = $woocommerce->countries->get_address_fields( $woocommerce->countries->get_base_country(), 'shipping_' );

    $cart->get_cart_from_session();
    
    $cart_key = WC_Cart::generate_cart_id($product_id, $variation_id);
    $data   = array();
    $rel    = array();

    if ( count($items) > 0 ) {
        foreach ( $items as $key => $item ) {
            $item_addresses = $item['shipping']['address'];
            $sig        = $cart_key .'_'. $product_id .'_';
            $_sig       = '';
            foreach ( $item_addresses as $idx => $item_address ) {
                $address_id = $item_address;        
                $user_address = $user_addresses[ $address_id ];
                $i = $set;
                for ( $x = 0; $x < 1; $x++ ) {

                    $rel[ $address_id ][]  = $cart_key;

                    while ( isset($data['shipping_first_name_'. $sig . $i]) ) {
                        $i++;
                    }
                    $_sig = $sig . $i;

                    if ( $fields ) foreach ( $fields as $key => $field ) :
                        $data[$key .'_'. $_sig] = $user_address[ $key ];
                    endforeach;
                }

            }
                                    
            $cart_address_ids_session = isset($_SESSION['cart_address_ids']) ? (array)$_SESSION['cart_address_ids'] : [];

            if ( !empty($_sig) && (isset($_SESSION['cart_address_ids']) && ! $_SESSION['cart_address_ids']) || ! in_array($_sig, $cart_address_ids_session) ) {
                $cart_address_sigs_session = isset($_SESSION['cart_address_sigs']) ? $_SESSION['cart_address_sigs'] : [];
                $cart_address_sigs_session[$_sig] = $address_id;
                $_SESSION['cart_address_sigs'] = $cart_address_sigs_session;
            }

        }

    }
                            
    if ( isset($_POST['update_quantities']) || isset($_POST['delete_line']) ) {
        $next_url = get_permalink( wc_get_page_id( 'multiple_addresses' ) );
    } else {
        // redirect to the checkout page
        $next_url = wc_get_checkout_url();
    }

    woo4over_clear_packages_cache($woocommerce);
    return ['data' => $data, 'rel' => $rel];
}

function woo4over_replace_cutom_cart_key_with_original_key($woocommerce) {
    $addresses = $_SESSION['cart_item_addresses'];
    $new_address = [];
    $packages = [];
    $items = $woocommerce->cart->get_cart();
    foreach ($items as $cart_original_key => $item) {
        for($i = 1; $i <= $item['quantity']; $i++ ) {
            $cart_key = WC_Cart::generate_cart_id($item['product_id'], $item['variation_id']);
            $new_address['shipping_first_name_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_first_name_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_last_name_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_last_name_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_company_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_company_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_country_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_country_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_address_1_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_address_1_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_address_2_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_address_2_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_city_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_city_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_state_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_state_'.$cart_key.'_'.$item['product_id'].'_'.$i];
            $new_address['shipping_postcode_'.$cart_original_key.'_'.$item['product_id'].'_'.$i] = $addresses['shipping_postcode_'.$cart_key.'_'.$item['product_id'].'_'.$i];
        }
    }
    
    if(function_exists('wcms_session_set'))        
    wcms_session_set( 'cart_item_addresses', $new_address );
}


/******** Clear Session on start from over button click **************/
add_action('wp_ajax_nopriv_start_from_over', 'woo4over_wp_start_from_over');
add_action('wp_ajax_start_from_over', 'woo4over_wp_start_from_over');
function woo4over_wp_start_from_over() {
    if(isset($_POST['form_action']) && $_POST['form_action'] == 'distroy_session') {
        if(session_destroy()) {
            echo 'true';
        }
        else {
            echo 'false';
        }
    }
    else {
        echo 'false';
    }
}