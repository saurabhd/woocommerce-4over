<?php

function woo4over_taxonomy() {  
   
    register_taxonomy(  
        'email_template_categories',
        'email_template',
        array(  
            'hierarchical' => true,  
            'label' => 'Email template Categories',
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'email-template-category',
                'with_front' => false
            )
        )  
    ); 
}  

//add_action( 'init', 'woo4over_taxonomy');


function woo4over_register_themepost() {

    $labels = array(
        'name' => _x( 'Email templates', 'my_custom_post','custom' ),
        'singular_name' => _x( 'Email template', 'my_custom_post', 'custom' ),
        'add_new' => _x( 'Add New', 'my_custom_post', 'custom' ),
        'add_new_item' => _x( 'Add New Email template', 'my_custom_post', 'custom' ),
        'edit_item' => _x( 'Edit Email template', 'my_custom_post', 'custom' ),
        'new_item' => _x( 'New Email template', 'my_custom_post', 'custom' ),
        'view_item' => _x( 'View Email template', 'my_custom_post', 'custom' ),
        'search_items' => _x( 'Search Email template', 'my_custom_post', 'custom' ),
        'not_found' => _x( 'No Email templates found', 'my_custom_post', 'custom' ),
        'not_found_in_trash' => _x( 'No Email template found in Trash', 'my_custom_post', 'custom' ),
        'parent_item_colon' => _x( 'Parent Email template:', 'my_custom_post', 'custom' ),
        'menu_name' => _x( 'All Email templates', 'my_custom_post', 'custom' ),
    );

    $args = array(
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'supports'        => array( 'title', 'editor', 'excerpt', 'author', 'custom-fields', 'revisions' ),
        'capability_type' => 'post',
        'hierarchical'    => false,
        'rewrite'         => array( 'slug' => 'email-template', 'with_front' => false ),
        'menu_position'   => 5,
        'has_archive'     => true
    );

    register_post_type( 'email_template', $args );

}

//registering custom post types
add_action( 'init', 'woo4over_register_themepost', 20 );