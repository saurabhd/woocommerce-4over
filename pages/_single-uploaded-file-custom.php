<?php

$html = '
<div class="wpf-umf-single-uploaded-file '.$upload['extension'].' '.$upload['status'].'" data-filenumber="'.$upload_info['file_number'].'">';

    $html .= '<div class="wpf-umf-suf-delete-wrapper alignleft">';
    if (get_option('wpf_umf_customer_delete') == 1 && $upload['status'] !== 'approved') {

        if (!isset($mode))
            $mode = '';

        $html .=  '
        <div class="wpf-umf-suf-delete">
            <a href="" title="'.__('Delete this upload').'" class="wpf-umf-suf-delete-button" data-productid="'.$upload_info['unique_product_key'].'" data-itemnumber="'.$upload_info['item_number'].'" data-uploadertype="'.$upload_info['uploader_type'].'" data-filenumber="'.$upload_info['file_number'].'" data-mode="'.$mode.'" data-uploadmode="'.(!empty($upload_mode)?$upload_mode:'after').'"><span class="dashicons dashicons-trash"></span></a>
        </div>';

    }
    $html .= '</div>';


    $html .=  '

    <div class="wpf-umf-suf-thumb alignleft">';

        if (!empty($upload['thumb']) && get_option('wpf_umf_thumbnail_enable') == 1) {

            if (isset($mp)) {
                $thumb = $mp->create_secret_image_url($upload['thumb']);
            } else {
                $thumb = $this->create_secret_image_url($upload['thumb']);
            }
            $html .= '<img src="'.$thumb.'" width="'.get_option('wpf_umf_thumbnail_size_width').'" height="'.get_option('wpf_umf_thumbnail_size_height').'" />';

        } else {

            $html .= '<div class="wpf-umf-suf-file-img">'.$upload['extension'].'</div>';

        }

    $html .= '</div>';


    if (get_option('wpf_umf_customer_download') == 1) {

        if (isset($mp)) {
            $url = $mp->create_secret_url($upload['path']);
        } else {
            $url = $this->create_secret_url($upload['path']);
        }

        $title = '<a href="'.$url.'">'.$upload['name'].'</a>';
    } else {
        $title = $upload['name'];
    }

    $html .= '

    <div class="wpf-umf-suf-info alignleft">

        <div class="wpf-umf-suf-file-name">'.$title.'</div>

        <div class="wpf-umf-suf-file-status"> <span class="dashicons dashicons-marker"></span>';


                    switch ($upload['status']) {

                        case 'on-hold':
                            $html .= (get_option('wpf_umf_message_enable') == 1)?get_option('wpf_umf_message_not_checked'):__('Your file will be manually verified.');
                            break;
                        case 'approved':
                            $html .= (get_option('wpf_umf_message_enable') == 1)?get_option('wpf_umf_message_accepted_files'):__('Your file is approved.');
                            break;
                        case 'declined':
                            $html .= (get_option('wpf_umf_message_enable') == 1)?get_option('wpf_umf_message_declined_files'):__('We have found a problem with this file. Please upload a new file.');
                            break;

                    }

      $html .= '

        </div>

    </div>

    <div class="clear"></div>

</div>  ';

return $html;
