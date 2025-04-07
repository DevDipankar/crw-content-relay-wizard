<?php
global $crw_init;
/**
 * register menu link in pkugin list page
 */
add_action('admin_menu', 'crw_add_settings_link');
function crw_add_settings_link(){
    add_filter('plugin_action_links_' . CRW_CONTENT_RELAY_WIZARD_BASENAME , 'crw_settings_link');
}

function crw_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=crw-control-panel') . '">Control Panel</a>';
    array_unshift($links, $settings_link);
    return $links;
}


/** register plugin control panel */
// In your main plugin file or functions.php
add_action('admin_menu', 'crw_settings_menu');

function crw_settings_menu() {
    add_menu_page(
        'CRW Control Panel',   // Page title
        'CRW Control Panel',          // Menu title
        'manage_options',           // Capability
        'crw-control-panel',   // Menu slug
        'crw_control_panel_callback', // Callback function to display the settings page
        'dashicons-admin-generic',  // Icon (change as needed)
        30                          // Position in the menu
    );
}

function crw_control_panel_callback() {

    ?>
    <div class="wrap">
        <h1>Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('crw_plugin_settings_group');
            do_settings_sections('crw-plugin-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'custom_plugin_register_settings');

function custom_plugin_register_settings() {

    /** first field */
    register_setting(
        'crw_plugin_settings_group', // Settings group name
        'crw_env_type'           // Option name
    );

    add_settings_section(
        'custom_plugin_section',      // Section ID
        '',           // Section title
        'custom_plugin_section_cb',   // Callback function to display section content
        'crw-plugin-settings'      // Page slug
    );

    add_settings_field(
        'crw_current_instance',        // Field ID
        'Current instance is',               // Field title
        'crw_current_instance_cb',    // Callback function to display field content
        'crw-plugin-settings',     // Page slug
        'custom_plugin_section'       // Section ID
    );
    /** */

    register_setting(
        'crw_plugin_settings_group', // Settings group name
        'crw_other_instance'           // Option name
    );

  // if( get_option('crw_env_type') == 'source'){
    add_settings_field(
        'crw_other_instance',        // Field ID
        'Target website\'s domain URL',               // Field title
        'crw_other_instance_cb',    // Callback function to display field content
        'crw-plugin-settings',     // Page slug
        'custom_plugin_section',       // Section ID
        array('class' => 'target_website_row')
    );
  // }
    
}

function custom_plugin_section_cb() {
    echo '<p>How do you want to treat this instance? as Source or destination. Please Choose the option from the dorpdown below - </p>';
}

function crw_current_instance_cb() {
    $option_value = get_option('crw_env_type');
    $options = array(
        'null' => 'Select instace type',
        'source' => 'Source',
        'destination' => 'Destination'
    );
    echo "<select name='crw_env_type' id='crw_env_type'>";
    foreach( $options as $key => $value){
        $selected = $key == $option_value ? 'selected' : '';
        echo "<option value='".$key."' $selected>".$value."</option>";
    }
    echo "</select>";
}

function crw_other_instance_cb(){
    global $crw_init;
    $response = crw_check_rest_api_endpoint($crw_init['api_url__write_content']);
    $connection_text = $response == true ? '<p><strong style="color:green">Connected!</strong></p>' : '<p><strong style="color:red">Not Connected!</strong> It seems that the other instance does not have the REST API enabled or the permalinks might not be saved<p>';
    $option_value = get_option('crw_other_instance');

    if($crw_init['crw_env_type'] == 'destination'){
        $connection_text ='<p><strong style="color:green">Connected!</strong></p>';
    }
    echo "<input type='text' name='crw_other_instance' value='$option_value' />";
    echo $connection_text;
}

/**
 * rename the post status o publish
 */
function custom_rename_published_status($translated_text, $untranslated_text, $domain) {
    global $post;

    // Check if the untranslated text is 'Published' and the post status is 'publish'
    if ($untranslated_text === 'Published' ) {
        // Replace 'Published' with 'Your Custom Status'
        $translated_text = 'Approved';
    }

    return $translated_text;
}

// Hook the function to the gettext filter
add_filter('gettext', 'custom_rename_published_status', 10, 3);

// Add JavaScript to handle the change event and update the label dynamically
function crw_modify_published_status_label() {
    global $current_user;
    if($current_user->roles[0] != 'author'){
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Find the 'Published' option and change its value attribute
            $('select#post_status option[value="publish"]').text('Approved').attr('value', 'publish');

            var original_publish = $('#original_publish').val();

            if(original_publish == 'Publish'){
                $('select#post_status').append('<option value="publish">Approved</option>');
            }
            

            // Capture the change event of the status dropdown
            $('.save-post-status').on('click', function() {

                // Check if the selected option is 'Your Custom Status'
                var selectedValue = $('select#post_status').val();
                //alert(selectedValue);
                if (selectedValue === 'publish') {
                    // Update the displayed label
                    setTimeout(() => {
                        $('#post-status-display').text('Approved');
                    }, 1);
                    
                }
                setTimeout(() => {
                    $('select#post_status option[value="publish"]').text('Approved').attr('value', 'publish');
                }, 1);
                
            });

            $('select#post_status').on('change', function() {
                setTimeout(() => {
                    $('select#post_status option[value="publish"]').text('Approved').attr('value', 'publish');
                }, 1);
                    
            });
        });
    </script>
    <?php
    }
}

if($crw_init['crw_env_type'] == 'source'){
    // Hook the function to the admin_footer action
    add_action('admin_footer', 'crw_modify_published_status_label');
}
/**
 * 
 */
// Add 'post_status' column to the page list
function custom_add_post_status_column($columns) {
    global $crw_init;
    $new_columns['post_status'] = 'Status';
    if($crw_init['crw_env_type'] == 'source'){
        $new_columns['post_priority'] = 'Priority';
        $new_columns['post_qa_person'] = 'Q/A Person';
        $new_columns['post_published_link'] = 'Published URL';
    }
    
   // $new_columns['post_qa_status'] = 'Q/A Status';
   

    $columns = array_slice( $columns, 0, 4, true ) + $new_columns + array_slice( $columns, 2, null, true );
   
    return $columns;
}

add_filter('manage_page_posts_columns', 'custom_add_post_status_column');

// Populate the 'post_status' column with the actual status
function crw_populate_post_status_column($column_name, $post_id) {
    if ($column_name === 'post_status') {
        $post_status = get_post_status($post_id);
        if($post_status == 'publish'){
            echo '<strong class="st_approved">Approved</strong>';
        }else if($post_status == 'pending'){
            echo '<strong class="st_pending">Pending Review</strong>';
        }else{
            echo esc_html(ucfirst($post_status)); 
        }
    }
    if ($column_name === 'post_qa_person') {
        $approver_name = get_post_meta($post_id , CRW_APPROVER_NAME , true);
        if($approver_name){
            echo $approver_name;
        }else{
            echo '--'; 
        }
    }
    if ($column_name === 'post_qa_status') {
        $approver_name = get_post_meta($post_id , CRW_APPROVER_NAME , true);
        if($approver_name){
            echo 'QA Taken Over';
        }else{
            echo '<p style="color:blue">OPEN</p>'; 
        }
    }
    if ($column_name === 'post_priority') {
        $p = get_post_meta( $post_id, 'content_priority', true );
        /*if(!empty($p)){
            if($p== 'low'){
                $color = 'green';
            }elseif($p == 'medium'){
                $color = 'yollow';
            }else{
                $color = 'red';
            } 
        }*/
        $color = !empty($p) ? ($p == 'low' ? 'green' : ($p == 'medium' ? '#959500' : 'red')) : null;

        $string = '<strong style="color:'.$color.'">'.ucfirst($p).'</strong>';
        echo !empty($p) ?  $string : '--';
    }

    if ($column_name === 'post_published_link') {
        $link = get_post_meta( $post_id, CRW_REMOTE_LINK, true );    
        if($link){
            echo '<a href="'.$link.'" target="_blank">View <span class="dashicons dashicons-external"></span></a>';
        }
    }
}

add_action('manage_page_posts_custom_column', 'crw_populate_post_status_column', 10, 2);


/**
 * validate endpoint
 */
function crw_check_rest_api_endpoint($endpoint_url,$error_print=false) {
    $response = wp_remote_post($endpoint_url,array(
        'method'    => 'POST',
        'body'      => array(), // Add your POST data here if required
        'headers'   => array(),
    ));
    //return $response;
    // print_r($endpoint_url);
    // echo '<pre>';
    // print_r($response);die;
    if (is_wp_error($response)) {
        return $error_print ? $response : false ; // Error occurred
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        //return $response_code;
        if ($response_code === 200) {
            return true; // Endpoint is valid
        } else {
            return $error_print ? $response : false ; // Endpoint returned a non-200 status code
        }
    }
}

/**
 * hide visibility
 */
function crw_hide_visibility_option() {
    // Check if this is the edit page screen
    if (function_exists('get_current_screen') ) {
        ?>
        <style>
            #misc-publishing-actions .misc-pub-visibility {
                display: none;
            }
            #misc-publishing-actions .misc-pub-curtime {
                display: none;
            }
        </style>
        <?php
    }
}
if($crw_init['crw_env_type'] == 'source'){
    add_action('admin_head', 'crw_hide_visibility_option');
}



/**
 * get user list
 */
function crw_get_users_by_role( $role ) {
    $users = get_users( array(
        'role'   => $role, // Specify the role
        'fields' => array( 'ID', 'display_name' ), // Specify the fields to retrieve
    ) );

    $users_array = array();

    foreach ( $users as $user ) {
        $users_array[ $user->ID ] = $user->display_name;
    }

    return $users_array;
}

// Add custom field inside 'Publish' meta box
function add_custom_field_publish_meta_box() {
    global $post;
    $_array = array(
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High'
    );
    // Check if the post is of type 'page'
    if ( 'page' === $post->post_type || 'post' === $post->post_type ) {
        ?>
        <div class="misc-pub-section misc-pub-custom-field">
            <label for="content_priority">Priority:</label>
            <select name="content_priority" id="content_priority">
                <?php foreach($_array as $k=>$v){
                    $selected = $k == get_post_meta( $post->ID, 'content_priority', true ) ? 'selected' : '';
                    echo '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                }?>
            </select>
        </div>
        <?php
    }
}
if($crw_init['crw_env_type'] == 'source'){
    add_action( 'post_submitbox_misc_actions', 'add_custom_field_publish_meta_box' );
}


// Save custom field value
function crw_save_custom_field_publish_meta_box( $post_id ) {
    // Check if this is an autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the post type
   

    // Check if the current user has permission to edit the post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save custom field value
    if ( isset( $_POST['content_priority'] ) ) {
        update_post_meta( $post_id, 'content_priority', sanitize_text_field( $_POST['content_priority'] ) );
    }
}
if($crw_init['crw_env_type'] == 'source'){
    add_action( 'save_post', 'crw_save_custom_field_publish_meta_box' );
}

//add_action('admin_footer' , 'crw_global_admin_footer_script');
function crw_global_admin_footer_script(){
    ?>
    <script>
        jQuery('#crw_env_type').on('change' , function(){
            var val = jQuery(this).val();
            if(val == 'destination'){
                jQuery('.target_website_row').hide();
            }else{
                jQuery('.target_website_row').show();
            }
        })
    </script>
    <?php
}

function rename_published_text( $views ) {
    // Replace 'Published' with your desired text
    $views['publish'] = str_replace( 'Published', 'Approved', $views['publish'] );
    $views['pending'] = str_replace( 'Pending', 'Pending Review', $views['pending'] );
    unset($views['yoast_cornerstone']);

    return $views;
}
add_filter( 'views_edit-page', 'rename_published_text' );



/**
 * If current user is author post status set draft only
 */
function crw_set_draft_for_authors($data, $postarr) {
    // Check if the current user is an author
    if(current_user_can('author') && !current_user_can('edit_others_posts')) {
        // Change post status to draft
        if($data['post_status'] == 'publish') {
            $data['post_status'] = 'draft';
        }
    }

    return $data;
}
add_filter('wp_insert_post_data', 'crw_set_draft_for_authors', '99', 2);


/**
 * "restrict_manage_posts" action hook used
 * Here, Page Template filters are added for the Page list table using the restrict_manage_posts action hook.
 */
add_action('restrict_manage_posts', 'crw_custom_page_status_filter');

function crw_custom_page_status_filter()
{
    global $typenow;

    // Check if you are on the 'page' post type (or any other post type you want to apply the filter to).
    if ($typenow == 'page' || $typenow == 'post') {
        // Get the currently selected page template
        $current_status = isset($_GET['custom_page_status_filter']) ? $_GET['custom_page_status_filter'] : '';

        //$get_all_page_templates = get_all_page_templates();
        global $TemplateFunctions;
        $all_status = array(
            'publish' => 'Approved',
            'pending' => 'Pending Review',
            'draft' => 'Draft'
        );

        // Display a filter for the specific page template
        echo '<select name="custom_page_status_filter">';
        echo '<option value="">Current Status</option>';
        if (isset($all_status) && !empty($all_status)) {
            foreach ($all_status as $_key => $_value) {
                echo '<option value="' . $_key . '" ' . selected($_key, $current_status, false) . '>' . $_value . '</option>';
            }
        }
        echo '</select>';
    }
}

/**
 * "pre_get_posts" action hook used
 * Filtering the page against template selection
 */
function crw_filter_page_status_posts($query)
{
    global $pagenow;
    if (is_admin() && isset($_GET['custom_page_status_filter'])) {
        $_filter = $_GET['custom_page_status_filter'];

        if (!empty($_filter)) {
            // Set the meta query to filter pages with the selected page template.
            $query->set('post_status', $_filter);
        }
    }
}
add_action('pre_get_posts', 'crw_filter_page_status_posts');
