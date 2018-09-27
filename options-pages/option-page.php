<?php

add_action('admin_menu', function() {

    // Add a new top-level menu (ill-advised):
    add_menu_page(__('4over Product Settings'), __('4over Product Settings'), 'manage_options', 'woocommerce-4over', 'woocommerce_4over_setting_page' );

    // Add a second submenu to the custom top-level menu:
    //add_submenu_page('woocommerce-4over', __('Front End Test Settings'), __('Front End Test Settings'), 'manage_options', 'woocommerce-4over-sub-page2', 'woocommerce_4over_front_end_text_setting_page');

    // Add a submenu to the custom top-level menu:
    //add_submenu_page('woocommerce-4over', __('Other Settings'), __('Other Settings'), 'manage_options', 'woocommerce-4over-sub-page', 'woocommerce_4over_other_setting_page');

    

    //add_options_page( '4over Product Settings', '4over Product Settings', 'manage_options', 'woocommerce-4over', 'woocommerce_4over_setting_page' );
});
        
 
add_action( 'admin_init', function() {
    
    register_setting( 'woocommerce-4over-settings', '4over_api_username' );
    register_setting( 'woocommerce-4over-settings', '4over_api_password' );
    register_setting( 'woocommerce-4over-settings', '4over_api_environment' );
    register_setting( 'woocommerce-4over-settings', '4over_shipping_cost' );
    register_setting( 'woocommerce-4over-settings', '4over_markup' );

    register_setting( 'woocommerce-4over-front-end-text-settings', 'design_service_yes_text' );
    register_setting( 'woocommerce-4over-front-end-text-settings', 'design_service_no_text' );
    register_setting( 'woocommerce-4over-front-end-text-settings', 'design_service_disclaimer_text' );
    register_setting( 'woocommerce-4over-front-end-text-settings', 'standard_shipping_option_language' );
    register_setting( 'woocommerce-4over-front-end-text-settings', 'rush_shipping_option_language' );

    register_setting( 'woocommerce-4over-other-settings', 'facility_zipcode' );
    register_setting( 'woocommerce-4over-other-settings', 'facility_notes' );
    register_setting( 'woocommerce-4over-other-settings', '4over_order_status' );
    register_setting( 'woocommerce-4over-other-settings', '4over_order_send_email' );
    register_setting( 'woocommerce-4over-other-settings', '4over_order_email_template' );
    register_setting( 'woocommerce-4over-other-settings', '4over_order_manager_email' );

});
 
 
function woocommerce_4over_setting_page() {
  ?>
    <div class="wrap">  
    <h1>4over Product Settings</h1>

      <form action="options.php" method="post">
    
        <h2>4over API</h2>

        <?php
          settings_fields( 'woocommerce-4over-settings' );
          do_settings_sections( 'woocommerce-4over-settings' );
          settings_errors();
        ?>
        <table class="form-table">
            
            <tr>
                <th>Environment</th>
                <td>
 
                    <select name="4over_api_environment">
                        <option value="">&mdash; Select &mdash;</option>
                        <option value="test" <?php echo esc_attr( get_option('4over_api_environment') ) == 'test' ? 'selected="selected"' : ''; ?>>Test (Sandbox)</option>
                        <option value="live" <?php echo esc_attr( get_option('4over_api_environment') ) == 'live' ? 'selected="selected"' : ''; ?>>Live</option>
                    </select>
 
                </td>
            </tr>

            <tr>
                <th>4Over API Username</th>
                <td><input type="text" class="regular-text" placeholder="4Over API Username" name="4over_api_username" value="<?php echo esc_attr( get_option('4over_api_username') ); ?>" size="50" /></td>
            </tr>
            <tr>
                <th>4Over API Password</th>
                <td><input type="text" class="regular-text" placeholder="4Over API Password" name="4over_api_password" value="<?php echo esc_attr( get_option('4over_api_password') ); ?>" size="50" /></td>
            </tr>

            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
            
        </table>

        <!-- <h2>Product Global Configuration</h2>

        <table class="form-table">
            
            <tr>
                <th>Shipping Cost</th>
                <td><input type="text" class="regular-text" placeholder="Shipping Cost" name="4over_shipping_cost" value="<?php echo esc_attr( get_option('4over_shipping_cost') ); ?>" size="50" /></td>
            </tr>
            <tr>
                <th>Markup %</th>
                <td><input type="text" class="regular-text" placeholder="Markup %" name="4over_markup" value="<?php echo esc_attr( get_option('4over_markup') ); ?>" size="50" /></td>
            </tr>
            
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
        </table> -->
 
      </form>
    </div>
  <?php
}

function woocommerce_4over_other_setting_page() {
  ?>
    <div class="wrap">  
    <h1>Other Settings</h1>

      <form action="options.php" method="post">
    
        <?php
          settings_fields( 'woocommerce-4over-other-settings' );
          do_settings_sections( 'woocommerce-4over-other-settings' );
          settings_errors();
        ?>
        <table class="form-table">
            
           <tr>
                <th>Facility Zip Code</th>
                <td><input type="text" class="regular-text" placeholder="Facility Zip Code" name="facility_zipcode" value="<?php echo esc_attr( get_option('facility_zipcode') ); ?>" size="50" /></td>
            </tr>
          
            <tr>
                <th>Facility Notes</th>
                <td><input type="text" class="regular-text" placeholder="Facility Notes" name="facility_notes" value="<?php echo esc_attr( get_option('facility_notes') ); ?>" size="50" /></td>
            </tr>            

            <tr>
                <th>Order Status</th>
                <td>
                
                <?php $order_status_internal_options = array('Processing', 'Awaiting Response', 'Design', 'Printing', 'Shipped', 'On Hold')
                ?>
                <select name="4over_order_status">
                        <option value="">&mdash; Select &mdash;</option>
                <?php        
                foreach ($order_status_internal_options as $value) { ?>
                    
                    <option value="<?php echo $value; ?>" <?php echo esc_attr( get_option('4over_order_status') ) == $value ? 'selected="selected"' : ''; ?>><?php echo $value; ?></option>
                <?php } 

                ?>
                    </select>
                    <br/><br/>
                    <label>
                        <input type="checkbox" name="4over_order_send_email" <?php echo esc_attr( get_option('4over_order_send_email') ) == 'on' ? 'checked="checked"' : ''; ?> />Send email using below selected email template, when order status matched with above selected one.
                    </label>
                 </td>
            </tr>

            <tr>
                <th>Email Templates</th>
                <td>
                    <?php

                    $emails = array();
                    $args = array('post_type' => 'email_template','posts_per_page' => -1);
                    $posts = get_posts( $args );

                ?>
                <select name="4over_order_email_template">
                        <option value="">&mdash; Select &mdash;</option>
                <?php  
                if(!empty($posts)){      
                foreach ($posts as $key => $value) { ?>
                    
                    <option value="<?php echo $value->ID; ?>" <?php echo esc_attr( get_option('4over_order_email_template') ) == $value->ID ? 'selected="selected"' : ''; ?>><?php echo $value->post_title; ?></option>
                <?php } 
                    }
                ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>Managers Email</th>
                <td><input type="text" placeholder="Managers Email" name="4over_order_manager_email" value="<?php echo esc_attr( get_option('4over_order_manager_email') ); ?>" size="70" /><br/>
                Enter commaa separted email to who will receive the order transaction related emails.</td>
            </tr>  
        
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
           
        </table>
       
      </form>
    </div>
  <?php
}

function woocommerce_4over_front_end_text_setting_page() {
  ?>
    <div class="wrap">  
    <h1>Front End Text Settings</h1>

      <form action="options.php" method="post">
    
        <?php
          settings_fields( 'woocommerce-4over-front-end-text-settings' );
          do_settings_sections( 'woocommerce-4over-front-end-text-settings' );
          settings_errors();
        ?>
        <table class="form-table">

            <tr>
                <th>Design service Text (i.e. Yes text)</th>
                <td><input type="text" class="regular-text" placeholder="Design service Text (i.e. Yes text)" name="design_service_yes_text" value="<?php echo esc_attr( get_option('design_service_yes_text') ); ?>" size="50" /></td>
            </tr>
          
            <tr>
                <th>Design service Text (i.e. No text)</th>
                <td><input type="text" class="regular-text" placeholder="Design service Text (i.e. No text)" name="design_service_no_text" value="<?php echo esc_attr( get_option('design_service_no_text') ); ?>" size="50" /></td>
            </tr>
            
            <tr>
                <th>Design approval disclaimer</th>
                <td><textarea placeholder="Design approval disclaimer" name="design_service_disclaimer_text" rows="5" cols="50"><?php echo esc_attr( get_option('design_service_disclaimer_text') ); ?></textarea></td>
            </tr>  
            
            <tr>
                <th>Standard Shipping Option Language</th>
                <td><input type="text" class="regular-text" placeholder="Standard Shipping Option Language" name="standard_shipping_option_language" value="<?php echo esc_attr( get_option('standard_shipping_option_language') ); ?>" size="50" /></td>
            </tr>        

            <tr>
                <th>Rush Shipping Option Language</th>
                <td><input type="text" class="regular-text" placeholder="Rush Shipping Option Language" name="rush_shipping_option_language" value="<?php echo esc_attr( get_option('rush_shipping_option_language') ); ?>" size="50" /></td>
            </tr>      
        
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
           
        </table>
       
      </form>
    </div>
  <?php
}