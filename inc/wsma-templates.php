<?php 

function woo4over_products_sets_template( $contents ) {
	$template = '';	
	$contents['addresses'] = woo4over_get_user_addresses($contents['user']->ID);
	$data = $contents;
	$data['shipping_option_4over'] = '<option value="">Select Option</option>';
	$cart_key = WC_Cart::generate_cart_id($data['product_id'], $data['variation_id']);
	$_SESSION[$cart_key.'#image_upload'] = $data['image_upload'];
	if($contents['page'] == 'product_page') {
		if( $contents['quantity'] ) {
		 	$product = wc_get_product($contents['product_id']);
		 	for( $i = 0; $i < $contents['quantity']; $i++ ) {
		 		$data['key'] = $i;
		 		$key = $i + 1;
		 		$data['set'] = $key;
		 		$style = '';
		 		if (! $product->needs_shipping() ) continue;
		 		$wrapper_class = 'set'.$i.' mb-3 activeSet';
		 		if($i > 0) {
		 			$style = 'style="display:none"';
		 			$wrapper_class = 'pt-4 disabled cloned set'.$i;
		 		}
		 		$template .= '<div class="js-set wsma-dataset container set ' . $wrapper_class . '" data-set="'.$key.'" data-bleed="0.125">';
					$template .= '<div class="js-jobdetails bgwhite">';
						$template .= '<div class="bgfirst text-center p-2 font-600 js-productheader" style="background: #000;color: #fff;padding: 10px; clear:both;">';
							$template .= 'Set '.$key.'  Details';
						$template .= '</div>';
						$template .= '<div class="set-wrapper" '.$style.'>';
							$template .= '<div class="js-productset container">';
								$template .= '<div class="row mk--row">';
									$template .= '<div class="col-md-8 mk-col mk-col-8-12 pt-3">';
										$template .= '<div class="row mk--row">';
											$template .= '<div class="col-sm-6 mk-col mk-col-6-12">';
												$template .= '<strong class="type-150">Job Name</strong>';
											$template .= '</div>';
											$template .= '<div class="relative col-sm-6 mk-col mk-col-6-12 noline">';
												$template .= '<input class="left form-control jobLabel" name="label" placeholder="Enter a job name" type="text">';
											$template .= '</div>';
										$template .= '</div>';
									$template .= '</div>';
									$template .= '<div class="col-md-4 mk-col-4-12 mk-col pt-3 text-left text-md-right">';
										$template .= '<a data-deactive="detailstepTwo" data-active="detailstepOne" class="button bgfirst hover shipping switchTab">';
											$template .= '<span data-icon="" title="shipping"></span> Shipping';
										$template .= '</a>';
										if($contents['image_upload'] == 'yes') {
											$template .= '<a data-deactive="detailstepOne" data-active="detailstepTwo" class="button hover files notcomplete switchTab">';
												$template .= '<span data-icon="" title="files"></span> Files';
											$template .= '</a>';
										}
									$template .= '</div>';
								$template .= '</div>';
							$template .= '</div>';	
							$template .= '<div class="detailstepOne setOptions container">';
								$template .= '<div class="productsection pb-4">';
									$template .= '<div class="row mk--row mk-grid">';
										$template .= woo4over_shipping_address_set_form($data);
										$template .= '<div class="col-md-4 mk--col mk-col-4-12">';
											$template .= '<div class="js-shipblock group">';
												$template .= '<div class="border border-secondary rounded overflowhide">';
													$template .= '<div class="bgfirst colorwhite text-center font-600 p-2">';
														$template .= '<span class="js-design" style="display: none;">Design </span>Turnaround';
													$template .= '</div>';
													$template .= '<div class="js-schedule p-3">';
														$template .= '<label for="completion" class="mb-0 type-85 font-200">Estimated Date of Completion:</label>';
														$template .= '<div class="detailDate totalDays font-500 mb-2 type-125">-<sup class="colorattention">*</sup></div>';
														$template .= '<label for="estimated" class="mb-0 type-85 font-200" data-fac="LAS">Estimated Delivery: <small>(From California Facility)</small></label>';
														$template .= '<div class="detailDelivery totalDays font-500 mb-2 type-125">-<sup class="colorattention">*</sup></div>';
														$template .= '<div class="turn-disclaimer type-75">';
															$template .= '<sup class="colorattention">*</sup> While we make every attempt to meet the estimated turnaround times; <strong>turnaround times are not guaranteed</strong>. If your order is time sensitive we recommend contacting us prior to placing your order.';
														$template .= '</div>';
													$template .= '</div>';
													$template .= '<div class="js-design p-3 type-85" style="display: none;">';
														$template .= '<p class="mb-0">Production turnaround times begin when the design files are approved and uploaded. Design Services typically take an additional 2-3 days which is dependent on a timely response to proofs that are sent during the design process.</p>';
													$template .= '</div>';
												$template .= '</div>';
											$template .= '</div>';
										$template .= '</div>';
									$template .= '</div>';
								$template .= '</div>';
							$template .= '</div>';
						$template .= '</div>';
						if($contents['image_upload'] == 'yes') {
							$template .= '<div class="detailstepTwo setOptions container" style="display:none">';
								$template .= '<div class="productsection pb-4">';
									$template .= woo4over_before_single_product_new($contents['product_id'], $contents['variation_id'], $contents['quantity'], $key);
								$template .= '</div>';
							$template .= '</div>';
						}
					$template .= '</div>';
				$template .= '</div>';
				$_SESSION[$cart_key][$cart_key.'#'.$key]['product_id'] = $data['product_id'];
				$_SESSION[$cart_key][$cart_key.'#'.$key]['vid'] = $data['variation_id'];
				$_SESSION[$cart_key][$cart_key.'#'.$key]['qty'] = $data['quantity'];
				$_SESSION[$cart_key][$cart_key.'#'.$key]['image_upload'] = $contents['image_upload'];
		 	}
		}
		$template .= woo4over_get_prodcut_quick_summary($contents);
	}
	elseif($contents['page'] == 'cart_page') {
		$data['items'] = isset($_SESSION[$cart_key][$cart_key.'#'.$contents['set']]['items']) ? $_SESSION[$cart_key][$cart_key.'#'.$contents['set']]['items'] : [];
		$selected = isset($data['items'][$contents['set']]['4over_shipping_price']) ? $data['items'][$contents['set']]['4over_shipping_price'] : '';
		$response = woo4over_attribute_manager($data, $selected);			
		$data['shipping_option_4over'] = $response['shipping_option'];
	 	$product = wc_get_product($contents['product_id']);
 		$data['key'] = $contents['set']-1;
 		$style = '';
 		$wrapper_class = 'set'.$contents['set'].' mb-3 activeSet';
 		$template .= '<div class="js-set wsma-dataset container set ' . $wrapper_class . '" data-set="'.$contents['quantity'].'" data-bleed="0.125">';
			$template .= '<div class="js-jobdetails bgwhite">';
      			$template .= '<div class="bgfirst text-center p-2 font-600 js-productheader" style="background: #000;color: #fff;padding: 10px; clear:both;">';
     				$template .= $product->get_title();
      			$template .= '</div>';
      			$template .= '<div class="set-wrapper" '.$style.'>';
	      			$template .= '<div class="js-productset container">';
	      				$template .= '<div class="row mk--row">';
	      					$template .= '<div class="col-md-8 mk--col mk-col-8-12 pt-3">';
	      						$template .= '<div class="row mk--row">';
	      							$template .= '<div class="col-sm-6 mk--col mk-col-6-12">';
	      								$template .= '<strong class="type-150">Job Name</strong>';
	      							$template .= '</div>';
	      							$template .= '<div class="relative mk--col col-sm-6 mk-col-6-12 noline">';
	      							$template .= '<input class="left form-control jobLabel" name="label" placeholder="Enter a job name" type="text">';
	      							$template .= '</div>';
	      						$template .= '</div>';
	      					$template .= '</div>';
	      					$template .= '<div class="col-md-4 mk--col mk-col-4-12 pt-3 text-left text-md-right">';
	      						$template .= '<a data-deactive="detailstepTwo" data-active="detailstepOne" class="button bgfirst hover shipping switchTab">';
	      							$template .= '<span data-icon="" title="shipping"></span> Shipping';
	      						$template .= '</a>';
	      						if($contents['image_upload'] == 'yes') {
		      						$template .= '<a data-deactive="detailstepOne" data-active="detailstepTwo" class="button hover files notcomplete switchTab">';
		               					$template .= '<span data-icon="" title="files"></span> Files';
		               				$template .= '</a>';
	               				}
	      					$template .= '</div>';
	  					$template .= '</div>';
					$template .= '</div>';
					$template .= '<div class="detailstepOne setOptions container">';
						$template .= '<div class="productsection pb-4">';
							$template .= '<div class="row mk--row mk-grid">';
								$template .= woo4over_shipping_address_set_form($data);
								$template .= '<div class="col-md-4 mk--col mk-col-4-12">';
										$template .= '<div class="js-shipblock group">';
											$template .= '<div class="border border-secondary rounded overflowhide">';
												$template .= '<div class="bgfirst colorwhite text-center font-600 p-2">';
													$template .= '<span class="js-design" style="display: none;">Design </span>Turnaround';
												$template .= '</div>';
												$template .= '<div class="js-schedule p-3">';
													$template .= '<label for="completion" class="mb-0 type-85 font-200">Estimated Date of Completion:</label>';
													$template .= '<div class="detailDate totalDays font-500 mb-2 type-125">'.$response['shipping_time'].'<sup class="colorattention">*</sup></div>';
													$template .= '<label for="estimated" class="mb-0 type-85 font-200" data-fac="LAS">Estimated Delivery: <small>(From California Facility)</small></label>';
													$template .= '<div class="detailDelivery totalDays font-500 mb-2 type-125">'.$response['delivery_time'].'<sup class="colorattention">*</sup></div>';
													$template .= '<div class="turn-disclaimer type-75">';
														$template .= '<sup class="colorattention">*</sup> While we make every attempt to meet the estimated turnaround times; <strong>turnaround times are not guaranteed</strong>. If your order is time sensitive we recommend contacting us prior to placing your order.';
													$template .= '</div>';
												$template .= '</div>';
												$template .= '<div class="js-design p-3 type-85" style="display: none;">';
													$template .= '<p class="mb-0">Production turnaround times begin when the design files are approved and uploaded. Design Services typically take an additional 2-3 days which is dependent on a timely response to proofs that are sent during the design process.</p>';
												$template .= '</div>';
											$template .= '</div>';
										$template .= '</div>';
									$template .= '</div>';
							$template .= '</div>';
						$template .= '</div>';
					$template .= '</div>';
					$template .= '</div>';
					if($contents['image_upload'] == 'yes') {
						$template .= '<div class="detailstepTwo setOptions container" style="display:none">';
							$template .= '<div class="productsection pb-4">';
							$template .= woo4over_before_single_product_new($contents['product_id'], $contents['variation_id'], $contents['quantity'], $contents['set']);
						$template .= '</div>';
					}
				$template .= '</div>';
			$template .= '</div>';
		$template .= '</div>';
	}
	$scripts = '<script type="text/javascript" src="' . plugins_url() . '/woocommerce-uploads-before/assets/js/main.js"></script>';
    $scripts .= '<script type="text/javascript" src="' . plugins_url() . '/woocommerce-uploads/assets/js/uploader.js"></script>';
    $scripts .= '<script type="text/javascript" src="' . plugins_url() . '/woocommerce-uploads/assets/js/main.js"></script>';
	return array('scripts' => $scripts, 'html' => $template);
}


function woo4over_get_prodcut_quick_summary($contents = []) {
	if(count($contents) > 0) {
		$product = wc_get_product($contents['product_id']);
		$shipping = 0;
		$price_without_currency = substr(utf8_decode($contents['price']), 1);
		$total = $shipping + $price_without_currency;
		$html = '<div class="wp-quick-summary">';
			$html .= '<div class="wp-quick-title">';
				$html .= '<h2>Quick Summary</h2>';
			$html .= '</div>';
		$html .= '<div class="summary-details">';	
			$html .= '<table>';
				$html .='<tbody>';
					$html .= '<tr>';
						$html .= '<th>Product :</th>';
						$html .= '<td id="td-product-info">'.$contents['color_type'].' - '.$product->get_title().'</td>';
						$html .= '<th>Order Date :</th>';
						$html .= '<td>'.date('d/m/Y').'</td>';
					$html .= '</tr>';
					$html .= '<tr>';
						$html .= '<th>Sets :</th>';
						$html .= '<td id="td-product-sets">'.$contents['quantity'].'</td>';
						//$html .= '<th>Quantity :</th>';
						//$html .= '<td id="td-product-quantity">'.$contents['batch_size'].'</td>';
						$html .= '<th>Subtotal :</th>';
						
						$sub_total = ( substr(utf8_decode($contents['price']), 1) * (int) $contents['quantity']);
						$html .= '<td id="td-product-subtotal"><strong>'. $sub_total.'</strong></td>';
					$html .= '</tr>';
					$html .= '<tr>';
						
						//$html .= '<th>Shipping:</th>';
						//$html .= '<td id="td-product-shipping"><strong>$'.$shipping.'</strong></td>';
					$html .= '</tr>';
					/*$html .= '<tr>';
						$html .= '<th colspan="2"></th>';
						$html .= '<th>Total :</th>';
						$html .= '<td id="td-product-total"><strong>$'.$total.'</strong></td>';
					$html .= '</tr>';*/
				$html .= '</tbody>';
			$html .= '</table>';
		$html .= '</div>';
		$html .= '<div class="cart-checkout-wrapper">';
			$html .= '<button type="button" class="button custom-add-to-cart">Add to Cart</button> &nbsp;';
			$html .= '<button type="button" class="button custom-checkout">Quick Checkout</button>';	
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}
	return '';
}