<?php
global $crw_init;

require_once( CRW_CONTENT_RELAY_WIZARD_PATH .'/includes/constants.php');


if($crw_init['status']){
    if( $crw_init['crw_env_type'] == 'destination'){ //die;
        require_once( CRW_CONTENT_RELAY_WIZARD_PATH .'api/endpoints.php');
    }
}

if($crw_init['status']){
    if( $crw_init['crw_env_type'] == 'source'){
        require_once( CRW_CONTENT_RELAY_WIZARD_PATH .'api/export.php');
    }
}


require_once( CRW_CONTENT_RELAY_WIZARD_PATH .'admin/email-dispatcher.php');

function crw_rename_user_role_label() {
    global $wp_roles;

    // Check if the $wp_roles global variable is set
    if ( isset( $wp_roles ) ) {
        // Modify the label of the user role
        $wp_roles->roles['editor']['name'] = 'Content Approver';
        $wp_roles->roles['author']['name'] = 'Content Creator';
    }
}
add_action( 'init', 'crw_rename_user_role_label' );

function crw_remove_default_user_role( $roles ) {
    // Specify the default user role you want to remove
    $default_role = 'contributor';

    // Remove the default user role from the list of available roles
    if ( isset( $roles[ $default_role ] ) ) {
        unset( $roles[ $default_role ] );
    }

    return $roles;
}
add_filter( 'editable_roles', 'crw_remove_default_user_role' );

/**
 * add option to admin top bar
 */
function crw_add_custom_text_to_admin_bar( $wp_admin_bar ) {
    global $crw_init;
    $response = crw_check_rest_api_endpoint($crw_init['api_url__write_content']);
    $conn_string = $response == true ? '<b style="color: white;background: #007300;padding: 9px 15px;font-weight: 500;font-size: 13px;">Target Site is Connected</b>' : '<b style="color: white;background: #ff0000;padding: 9px 15px;font-weight: 500;
    font-size: 13px;">Target Site is not Connected</b>';

    if($crw_init['crw_env_type'] == 'destination'){
        $conn_string ='<b style="color: white;background: #007300;padding: 9px 15px;font-weight: 500;font-size: 13px;">Target Site is Connected</b>';
    }
    // Add a custom node to the admin bar
    $wp_admin_bar->add_node( array(
        'id'    => 'crw_connection',
        'title' => $conn_string, // Replace 'Your Custom Text' with your desired text
        'href'  => admin_url('admin.php?page=crw-control-panel'),
        'meta'  => array(
            'class' => 'crw_connection',
        ),
    ) );
}

add_action('init' , function(){
    if ( current_user_can( 'administrator' ) ) {
        add_action( 'admin_bar_menu', 'crw_add_custom_text_to_admin_bar', 999 );
    }
});

/**
 * add edit cap to author
 */
add_action( 'admin_init','crw_assign_cap_to_author');

function crw_assign_cap_to_author(){
    $role = get_role( 'author' );
    $role->add_cap( 'edit_pages' );
    $role->add_cap( 'edit_published_pages' );
    $role->add_cap( 'edit_posts' ); 
    $role->add_cap( 'publish_posts' );  
    $role->add_cap( 'publish_pages' );  
    $role->add_cap( 'edit_published_posts' );   
    $role->add_cap( 'edit_published_pages' ); 
}


/**
 * this function use to upload images by the url
 */
function crw_rs_upload_from_url( $url, $title = null, $content = null, $alt = null, $returnID = false ) {
	require_once( ABSPATH . "/wp-load.php");
	require_once( ABSPATH . "/wp-admin/includes/image.php");
	require_once( ABSPATH . "/wp-admin/includes/file.php");
	require_once( ABSPATH . "/wp-admin/includes/media.php");
	
	// Download url to a temp file
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) return false;
	
	// Get the filename and extension ("photo.png" => "photo", "png")
	$filename = pathinfo($url, PATHINFO_FILENAME);
	$extension = pathinfo($url, PATHINFO_EXTENSION);
	
	// An extension is required or else WordPress will reject the upload
	if ( ! $extension ) {
		// Look up mime type, example: "/photo.png" -> "image/png"
		$mime = mime_content_type( $tmp );
		$mime = is_string($mime) ? sanitize_mime_type( $mime ) : false;
		
		// Only allow certain mime types because mime types do not always end in a valid extension (see the .doc example below)
		$mime_extensions = array(
			// mime_type         => extension (no period)
			'text/plain'         => 'txt',
			'text/csv'           => 'csv',
			'application/msword' => 'doc',
			'image/jpg'          => 'jpg',
			'image/jpeg'         => 'jpeg',
			'image/gif'          => 'gif',
			'image/png'          => 'png',
			'video/mp4'          => 'mp4',
			'application/pdf'	 => 'pdf'
		);
		
		if ( isset( $mime_extensions[$mime] ) ) {
			// Use the mapped extension
			$extension = $mime_extensions[$mime];
		}else{
			// Could not identify extension. Clear temp file and abort.
			wp_delete_file($tmp);
			return false;
		}
	}
	
	// Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
	$args = array(
		'name' => "$filename.$extension",
		'tmp_name' => $tmp,
	);
	
	// Post data to override the post title, content, and alt text
	$post_data = array();
	if ( $title )   $post_data['post_title'] = $title;
	if ( $content ) $post_data['post_content'] = $content;
	
	// Do the upload
	$attachment_id = media_handle_sideload( $args, 0, null, $post_data );
	
	// Clear temp file
	wp_delete_file($tmp);
	
	// Error uploading
	if ( is_wp_error($attachment_id) ) return false;
	
	// Save alt text as post meta if provided
	if ( $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}
	
	// Success, return attachment ID
	//return (int) $attachment_id;
	$newfileurl = wp_get_attachment_url( $attachment_id );

    if($returnID){
        return array(
            'url' => $newfileurl,
            'id' => $attachment_id
        );
    }
	return $newfileurl;
}


/**
 * find all url form the content
 */
function crw_getUrls($string)
{
    $regex = '/https?\:\/\/[^\" ]+/i';
    preg_match_all($regex, $string, $matches);
    return ($matches[0]);
}



function crw_getContentAndUploadNewContent($_post_content, $sourceMediaUrl ){
    //Count string length form for validation the url
    $urlLenght =  strlen($sourceMediaUrl);

    //Call funtion to get all urls
    $content_urls = crw_getUrls($_post_content);
    $newUrlArray = array_unique($content_urls);

        if(!empty($newUrlArray)){
        // Loop all urls 
            foreach($newUrlArray as $url){
                if (substr($url, 0, $urlLenght ) == $sourceMediaUrl){
                    $newUrl = crw_rs_upload_from_url($url);  // call function to upload files by the url
                    if($newUrl != ''){
                        $_post_content = str_replace($url,$newUrl, $_post_content);  // Replace old url with new uploaded url
                    }

                }

            }
        }

    return $_post_content;

  }

  

  function crw_is_author(){
    global $current_user;
    if($current_user->roles[0] == 'author'){
        return true;
    }
    return false;
  }

  function crw_is_admin(){
    global $current_user;
    if($current_user->roles[0] == 'administrator'){
        return true;
    }
    return false;
  }

  function crw_is_editor(){
    global $current_user;
    if($current_user->roles[0] == 'editor'){
        return true;
    }
    return false;
  }





