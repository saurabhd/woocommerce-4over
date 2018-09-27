<?php

function woo4over_delete_product_attributes_variations ($post_id){

    global $wpdb;  

    $query = "DELETE FROM $wpdb->posts WHERE post_type = 'product_variation' AND post_status = 'publish' AND post_parent = %d";
    $wpdb->query($wpdb->prepare ($query, $post_id));

}

function woo4over_insert_product ($product_data, $post_id)  
{
     if (!$post_id) // If there is no post id something has gone wrong so don't proceed
    {
        return false;
    }

    update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
    update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

    //wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
    wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type

    //woo4over_insert_product_attributes($post_id, $product_data['available_attributes'], $product_data['variations']); // Add attributes passing the new post id, attributes & variations // temp comment
    
    //woo4over_insert_product_variations($post_id, $product_data['variations'], $product_data['product_name'], $product_data['sku']); // Insert variations passing the new post id & variations   // temp comment

    $product = wc_get_product( $post_id );
    $product->variable_product_sync();

}

function woo4over_insert_product_attributes ($post_id, $available_attributes, $variations)  
{
    foreach ($available_attributes as $attribute) // Go through each attribute
    {   
        $values = array(); // Set up an array to store the current attributes values.
        $uuids = array();

        foreach ($variations as $variation) // Loop each variation in the file
        {
            $attribute_keys = array_keys($variation['attributes']); // Get the keys for the current variations attributes

            foreach ($attribute_keys as $key) // Loop through each key
            {
                if ($key === $attribute) // If this attributes key is the top level attribute add the value to the $values array
                {
                    $values[] = $variation['attributes'][$key]['value'];
                    $uuids[$variation['attributes'][$key]['value']] = $variation['attributes'][$key]['uuid'];
                }
            }
        }

        // Essentially we want to end up with something like this for each attribute:
        // $values would contain: array('small', 'medium', 'medium', 'large');

        // Store the values to the attribute on the new post, for example without variables:
        // wp_set_object_terms(23, array('small', 'medium', 'large'), 'pa_size');
        $term_taxonomy_ids = wp_set_object_terms($post_id, $values, 'pa_' . $attribute);
        
        if(!empty($values)){
            foreach ($values as $attribute_value) // Loop through the variations attributes
            {   
                $attribute_title_to_slug = sanitize_title($attribute_value);
                $attribute_term = get_term_by('slug', $attribute_title_to_slug, 'pa_'.$attribute); // We need to 
                update_term_meta($attribute_term->term_id, 'uuid', $uuids[$attribute_value]);
            }
        }

    }

    $product_attributes_data = array(); // Setup array to hold our product attributes data

    foreach ($available_attributes as $attribute) // Loop round each attribute
    {
        $product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'

            'name'         => 'pa_'.$attribute,
            'value'        => '',
            'is_visible'   => '1',
            'is_variation' => '1',
            'is_taxonomy'  => '1'

        );
    }

    update_post_meta($post_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
}

function woo4over_insert_product_variations ($post_id, $variations, $product_name = null, $product_uuid = null)  
{

    foreach ($variations as $index => $variation)
    {
        $variation_post = array( // Setup the post data for the variation

            'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
            'post_name'   => 'product-'.$post_id.'-variation-'.$index,
            'post_status' => 'publish',
            'post_parent' => $post_id,
            'post_type'   => 'product_variation',
            'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
        );

        $variation_post_id = wp_insert_post($variation_post); // Insert the variation

        foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes
        {   
            $attribute_title_to_slug = sanitize_title($value['value']);
            $attribute_term = get_term_by('slug', $attribute_title_to_slug, 'pa_'.$attribute); // We need to insert the slug not the name into the variation post meta

            update_post_meta($variation_post_id, 'attribute_pa_'.$attribute, $attribute_term->slug);
          // Again without variables: update_post_meta(25, 'attribute_pa_size', 'small')
        }

        /*update_post_meta($variation_post_id, '_manage_stock', 'no');
        update_post_meta($variation_post_id, '_stock_status', 'instock');*/
        update_post_meta($variation_post_id, '_4over_product_name', $product_name);
        update_post_meta($variation_post_id, '_4over_product_uuid', $product_uuid);
        update_post_meta($variation_post_id, '_price', $variation['price']);
        update_post_meta($variation_post_id, '_regular_price', $variation['price']);
    }
}


?>