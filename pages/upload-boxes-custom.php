<?php
$show_custom_cart_message = get_option('wpf_umf_before_show_custom_cart_message');
?>

<div id="wpf-umf-uploads-wrapper">

    <?php if (isset($upload_mode) && $upload_mode == 'before'): ?>

        <div class="wpf-umf-header before">

            <h2><?php _e('Upload files'); ?></h2>

            <?php if (empty($show_custom_cart_message) || $show_custom_cart_message == 'before'): ?>
                <?php echo apply_filters('wc_uploads_before_view_cart_button', '<a href="'.wc_get_cart_url().'" class="wpf-umf-view-cart-button button">'. __('View Cart', 'woocommerce').'</a>', $product_cart_info['product_id'], $product_cart_info['variation_id'], $product_cart_info['quantity']); ?>
            <?php endif; ?>

            <div class="clear"></div>

        </div>

    <?php else: ?>

        <div class="wpf-umf-header after">

            <h2><?php _e('Upload files'); ?></h2>

        </div>

    <?php endif; ?>

    <?php if (isset($upload_mode) && $upload_mode == 'before')
            do_action('wpf_umf_before_upload_description', $cart_product_data); ?>

    <div id="wpf-umf-upload-description">

        <?php echo stripslashes(get_option('wpf_umf_message_upload_description')); ?>

    </div>

    <?php if (get_option('wpf_umf_uploader') == 'ajax'): ?>

        <!-- <div id="wpf-umf-browser-check">Your browser doesn't have Flash, Silverlight or HTML5 support.</div> -->

    <?php else: ?>

        <div id="wpf-umf-uploading"><?php _e('Uploading'); ?>...</div>

    <?php endif; ?>

    <div id="wpf-umf-upload-boxes">

        <form method="post" enctype="multipart/form-data">

        <?php wp_nonce_field('uploads', '_wpf_umf_nonce'); ?>
        <input type="hidden" name="wpf_umf_order_id" value="<?php echo (isset($order_number))?$order_number:''; ?>" />

        <?php if (is_array($upload_products)): ?>

            <?php $m = 1; ?>

            <?php

            foreach ($upload_products AS $product_id => $product):

                $prod_ord = WPF_Uploads::split_raw_product_id($product_id);

                $product_id = $prod_ord['product_id'];

                $unique_product_key = WPF_Uploads::get_unique_product_key($prod_ord['product_id'], $prod_ord['unique_product_key']);

            ?>

                <fieldset>

                    <legend><?php echo $product['name']; ?> <?php echo (!empty($product['variation']))?'<span class="wpf-umf-upload-variation"> - '.$product['variation'].'</span>':''; ?></legend>

                    <?php if (isset($product['boxes']) && is_array($product['boxes'])): ?>

                    <?php foreach ($product['boxes'] AS $item_number => $boxes): ?>

                        <div class="wpf-umf-item-product-item">

                            <div class="wpf-umf-item-product-item-number"><?php echo (get_option('wpf_umf_upload_procedure') == 'multiple')?sprintf( __('Upload(s) for item #%d'), $item_number):sprintf( _n('Upload(s) for one item of this product', 'Upload(s) for %d items of this product', $product['quantity']), $product['quantity']); ?></div>

                            <?php foreach ($boxes AS $box_id => $box):

                                if (isset($order)) {
                                  $order_id = WPF_Uploads::get_order_id($order);
                                } else {
                                  $order_id = null;
                                }

                                $box['amount'] = apply_filters('wpf_umf_product_max_uploads', $box['amount'], $box, $product_id, $order_id);

                            ?>

                                <?php

                                if (isset($current_uploads[$unique_product_key][$item_number][$box_id]))
                                    $current_upload = $current_uploads[$unique_product_key][$item_number][$box_id];

                                ?>

                                <?php $upload_info = array(
                                    'unique_product_key' => $unique_product_key,
                                    'product_id' => $product_id,
                                    'item_number' => $item_number,
                                    'uploader_type' => $box_id
                                ); ?>

                                <div class="wpf-umf-single-upload">

                                    <div class="wpf-umf-single-upload-title"><?php echo $box['title']; ?></div>
                                    <div class="wpf-umf-single-upload-description"><?php echo $box['description']; ?></div>

                                        <?php if (get_option('wpf_umf_uploader') == 'ajax'): ?>

                                            <div id="wpf-umf-single-upload-field-<?php echo $set . '-' .$m; ?>" class="wpf-umf-single-upload-field" <?php echo ($box['blocktype'] == 'allow')?'data-allowed="'.esc_attr($box['filetypes']).'"':''; ?> data-productid="<?php echo $unique_product_key; ?>" data-uploadtype="<?php echo $box_id; ?>" data-itemnumber="<?php echo $item_number; ?>" data-maxuploads="<?php echo $box['amount']; ?>" data-maxfilesize="<?php echo $box['maxuploadsize']; ?>" data-uploadmode="<?php echo (empty($upload_mode))?'after':$upload_mode; ?>">

                                                <?php if (get_option('wpf_umf_uploader_dropzone') == 1): ?>
                                                    <div id="wpf-umf-dropzone-<?php echo $set . '-' .$m; ?>" class="wpf-umf-dropzone"><?php echo strtoupper(__('Drop your files')); ?></div>
                                                <?php endif; ?>

                                                <div class="wpf-umf-file-list"></div>
                                                <div class="wpf-umf-error-el"></div>

                                                <div class="wpf-umf-single-upload-buttons-wrapper">

                                                    <div class="wpf-umf-single-upload-buttons">
                                                        <a id="browse-<?php echo $set . '-' .$m; ?>" class="button wpf-umf-browse-button" href="javascript:;"><?php _e('Select files'); ?></a>
                                                        <?php if (get_option('wpf_umf_uploader_autostart') != 1): ?>
                                                            <a id="upload-<?php echo $set . '-' .$m; ?>" class="button wpf-umf-upload-button"  href="javascript:;"><?php _e('Start upload'); ?></a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="wpf-umf-single-upload-buttons-dummy" style="display: none;">
                                                        <button class="button disabled" disabled><?php _e('Select files'); ?></button>
                                                        <?php if (get_option('wpf_umf_uploader_autostart') != 1): ?>
                                                            <button class="button disabled" disabled><?php _e('Start upload'); ?></button>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>

                                                <?php /* <div class="wpf-umf-loading" style="display: none;"><img src="<?php echo plugins_url($this->plugin_id.'/assets/img/loader.gif'); ?>" alt="" /> <?php _e('Processing file...'); ?></div> */ ?>

                                                <div class="clear clearfix"></div>

                                                <div class="wpf-umf-uploaded-files-container">

                                                    <?php if (isset($current_upload) && is_array($current_upload)): ?>

                                                        <?php foreach ($current_upload AS $file_number => $upload):
                                                            $upload_info['file_number'] = $file_number;
                                                        ?>

                                                            <?php $mode = 'ajax'; ?>

<?php //echo include(((isset($upload_mode) && $upload_mode == 'before')?$mp->plugin_dir:$this->plugin_dir) . 'pages/frontend/_single-uploaded-file.php'); ?>

<?php echo include (plugin_dir_path(__DIR__) . 'pages/_single-uploaded-file-custom.php'); ?>

                                                        <?php endforeach; ?>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        <?php else: ?>

                                            <?php for($c=1; $c<=$box['amount']; $c++): ?>

                                                <?php

                                                if (isset($current_upload[$c])) {
                                                    $upload = $current_upload[$c];
                                                }

                                                if (!empty($upload['name'])):
                                                    $upload_info['file_number'] = $c;
                                                ?>
                                                    <?php $mode = 'html'; ?>

                                                    <?php echo include(((isset($upload_mode) && $upload_mode == 'before')?$mp->plugin_dir:$this->plugin_dir) . 'pages/frontend/_single-uploaded-file.php'); ?>

                                                <?php else: ?>

                                                <div class="wpf-umf-single-upload-field">

                                                    <input type="file" name="wpf_upload[<?php echo $unique_product_key; ?>][<?php echo $item_number; ?>][<?php echo $box_id ?>][<?php echo $c; ?>]" />

                                                    <?php if (!empty($html_post_response[$unique_product_key][$item_number][$box_id][$c]['error'])): ?>
                                                        <div class="wpf-umf-error-el wpf-umf-html-error"><?php echo $html_post_response[$unique_product_key][$item_number][$box_id][$c]['error']; ?></div>
                                                    <?php endif; ?>

                                                </div>

                                                <?php endif; ?>

                                                <?php unset($upload); ?>

                                            <?php endfor; ?>

                                        <?php endif; ?>

                                         <div class="wpf-umf-single-upload-notice">

                                            <?php echo ($box['blocktype'] == 'disallow')?__('Disallowed filetype(s):'):__('Allowed filetype(s):'); ?> <?php echo $box['filetypes']; ?> | <?php _e('Max. uploads:'); ?> <?php echo $box['amount']; ?> | <?php _e('Max. filesize:'); ?> <?php echo $box['maxuploadsize']; ?>MB

                                            <?php if (!empty($box['min_resolution_width'])) echo ' | '.__('Min. width:').' '.$box['min_resolution_width'].'px'; ?>
                                            <?php if (!empty($box['min_resolution_height'])) echo ' | '.__('Min. height:').' '.$box['min_resolution_height'].'px'; ?>
                                            <?php if (!empty($box['max_resolution_width'])) echo ' | '.__('Max. width:').' '.$box['max_resolution_width'].'px'; ?>
                                            <?php if (!empty($box['max_resolution_height'])) echo ' | '.__('Max. height:').' '.$box['max_resolution_height'].'px'; ?>

                                         </div>

                                        <?php $m++; ?>

                                </div>

                                <?php
                                unset($current_upload);
                                unset($total_uploads);
                                ?>

                            <?php endforeach; ?>

                        </div>

                    <?php endforeach; ?>

                    <?php else: ?>

                        <div class="wpf-umf-single-upload-no-uploads-needed">
                            <?php _e('No uploads needed for this product'); ?>
                        </div>

                    <?php endif; ?>

                </fieldset>

            <?php endforeach; ?>

        <?php endif; ?>

        <?php if (get_option('wpf_umf_uploader') != 'ajax'): ?>

            <?php if (isset($upload_mode) && $upload_mode == 'before'): ?>

                <input type="hidden" name="wpf_umf_upload_mode" value="before" />
                <?php
                if (is_array($this->cart_product_data)):
                foreach ($this->cart_product_data AS $key => $value): ?>

                    <input type="hidden" name="wpf_umf_<?php echo $key; ?>" value="<?php echo $value; ?>" />

                <?php
                endforeach;
                endif; ?>

            <?php endif; ?>

            <input type="submit" class="button" value="<?php _e('Upload'); ?>" />
        <?php endif; ?>

        </form>

    </div>

    <?php if (isset($upload_mode) && $upload_mode == 'before' && $show_custom_cart_message == 'after'): ?>

        <div class="wpf-umf-footer before">
            <?php echo apply_filters('wc_uploads_before_view_cart_button', '<a href="'.$woocommerce->cart->get_cart_url().'" class="wpf-umf-view-cart-button button">'. __('View Cart', 'woocommerce').'</a>', $product_cart_info['product_id'], $product_cart_info['variation_id'], $product_cart_info['quantity']); ?>
            <div class="clear"></div>
        </div>

    <?php endif; ?>



    <?php do_action('wpf_umf_after_upload_boxes', (isset($upload_mode) && $upload_mode == 'before')?'before':'after'); ?>

</div>
