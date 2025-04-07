<?php

class CRW_Content_Export{

	function __construct(){	
			

	}

	function content_export_approved_page($post_id, $post, $update){

		$_current_user = wp_get_current_user();

		if($post->post_status != 'publish'){
			return;
		}
		$action = $_POST['original_publish'];
		//error_log($post_id);
		//$post = get_post($post_id);
		global $crw_init;
    	$destination_url = $crw_init['api_url__write_content'];
		error_log($destination_url);
		$args=array();
		$args['entry_type']= $action;
		$args['_remote_id']= get_post_meta($post->ID , '_remote_id', true);
		$args['post_title']=get_the_title($post->ID);
		$args['post_name']=$post->post_name;
		$args['post_type']=$post->post_type;
		$args['post_content']=$post->post_content;
		$args['post_excerpt']=$post->post_excerpt;
		$args['post_date']=$post->post_date;
		$args['comment_status']=$post->comment_status;
		$args['ping_status']=$post->ping_status;
		$args['post_password']=$post->post_password;
		$args['to_ping']=$post->to_ping;
		$args['pinged']=$post->pinged;
		$args['post_modified']=$post->post_modified;
		$args['post_parent']=$post->post_parent;
		$args['menu_order']=$post->menu_order;
		$args['thumbnail_url']=has_post_thumbnail($post->ID)?get_the_post_thumbnail_url($post->ID,'full'):'';

		//Author
		$author = get_user_by('id', $post->post_author);
		$args['author']=array();
		$args['author']['user_login']=$author->data->user_login;
		$args['author']['user_pass']=$author->data->user_pass;
		$args['author']['user_nicename']=$author->data->user_nicename;
		$args['author']['user_email']=$author->data->user_email;
		$args['author']['user_url']=$author->data->user_url;
		$args['author']['display_name']=$author->data->display_name;
		$args['author']['roles']=$author->roles;
		$args['author']['allcaps']=$author->allcaps;
		

		//Terms
		$args['terms']=array();
		$taxonomies=get_taxonomies();
		$terms=wp_get_post_terms($post->ID,array_keys($taxonomies));
		if(!empty($terms))
		{
			foreach($terms as $term){
				$args['terms'][]=array(
					'name'=>$term->name,
					'slug'=>$term->slug,
					'taxonomy'=>$term->taxonomy,
					'description'=>$term->description,
				);
			}
		}
		//Meta
		$args['meta']=array();
		$custom_fields=get_post_meta($post->ID);
		if(!empty($custom_fields))
		{
			$image_urls = array();
			foreach($custom_fields as $key=>$value){
				$args['meta'][$key]=$value[0];
				if (is_array($value) && count($value) > 0 && wp_attachment_is_image($value[0])) {
					// Get the image URL using the attachment ID
					$image_url = wp_get_attachment_url($value[0]);
					if ($image_url) {
						// Store the image URL in the array
						$image_urls[] = $image_url;
						$args['meta_thumb_ids'][$key] = $image_url;
					}
				}
			}
		}

	//echo '<pre>';	print_r($custom_fields);die;
		
		$response = wp_remote_post( $destination_url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $args,
			'cookies'     => array()
			)
		);
		/*print_r($response);die;
		error_log($args);
		error_log($post);
		error_log($post_id);
		error_log($aaaf);*/

		
		if ( !is_wp_error( $response ) ) {
			$data=json_decode($response['body'],true);
			$ref_post_id=$data['post_id'];
			error_log($ref_post_id);
			update_post_meta($post->ID,CRW_REMOTE_ID,$ref_post_id);
			update_post_meta($post->ID,CRW_REMOTE_LINK,$data['permalink']);
			update_post_meta($post->ID,CRW_APPROVER_ID,$_current_user->ID );
			update_post_meta($post->ID,CRW_APPROVER_NAME,$_current_user->display_name);
		}
		
	}

	function content_decline_approved_page($post,$author){
		$remote_url=get_option('content_export_decline_endpoint');

		$args=array();
		$args['remote_id']=get_post_meta($post->ID,'_remote_id',true);
		
		$response = wp_remote_post( $remote_url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $args,
			'cookies'     => array()
			)
		);
		
		if ( !is_wp_error( $response ) ) {
			$data=json_decode($response['body'],true);
			delete_post_meta($post->ID,'_remote_id');
		}
	}

}
new CRW_Content_Export();

add_action('init' , function(){
	global $crw_init;
	$CRW_Content_Export = new CRW_Content_Export();
	if($crw_init['crw_env_type'] == 'source'){
		//print_r($_REQUEST);die;
		if( crw_is_admin() && isset($_REQUEST['admin_publish_page_to_the_target']) && !empty($_REQUEST['admin_publish_page_to_the_target'])){
			add_action( 'save_post',  array( $CRW_Content_Export ,'content_export_approved_page' ),10,3 );
		}
		if( crw_is_editor() ){
			add_action( 'save_post',  array( $CRW_Content_Export ,'content_export_approved_page' ),10,3 );
		}		
	}
});
