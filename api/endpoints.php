<?php
class CRW_Content_Import_Api{

	function __construct(){	
		add_action( 'rest_api_init',array($this,'register_content_importer_endpoint'));
	}

	function register_content_importer_endpoint(){
		register_rest_route( CRW_API_BASE.'/v1','/'.CRW_EP_WRITE_CONTENT , array(			
			  'methods'             => 'POST',
			  'callback'            => array( $this, 'crw_write_content' ),
			  'permission_callback' => array( $this, 'check_permissions' )			
		  ) );
	}

	function crw_write_content(WP_REST_Request $request)
	{

		$params = $request->get_params();

		$data=array('params'=>$params);
		$_remote_id = $params['_remote_id'];

		global $crw_init;
		$crw_other_instance = $crw_init['crw_other_instance'];
		//error_log('test');
		if($crw_other_instance){
			$upload_dir = rtrim( $crw_other_instance , '/' ) . '/wp-content/uploads/' ;
			//error_log('test');
			//error_log(serialize($params));
			$final_content = crw_getContentAndUploadNewContent( $params['post_content'] , $upload_dir );
			if($final_content){
				$params['post_content'] = $final_content;
			}

			
		}

		

		$args = array(
			'post_title'    => wp_strip_all_tags( $params['post_title'] ),
			'post_content'  => $params['post_content'],
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_name'		=> $params['post_name'],
			'post_type'		=> $params['post_type'],
			'post_excerpt'	=> $params['post_excerpt'],
			'post_date'		=> $params['post_date'],
			'comment_status'=> $params['comment_status'],
			'ping_status'	=> $params['ping_status'],
			'post_password'	=> $params['post_password'],
			'to_ping'		=> $params['to_ping'],
			'pinged'		=> $params['pinged'],
			'post_modified'	=> $params['post_modified'],
			'post_parent'	=> $params['post_parent'],
			'menu_order'	=> $params['menu_order'],
		);

		if($_remote_id){
			$args['ID'] = $_remote_id;
		}
		
		// Insert the post into the database
		$post_exists = get_page_by_title( $params['post_title'], OBJECT, $params['post_type'] );
		if($post_exists){
			$args['ID'] = $post_exists->ID;
			$post_id = wp_update_post( $args );
		}else{
			$post_id = wp_insert_post( $args );
		}
		
		//error_log($post_id);
		//$data=array('post_id'=>$post_id);
			//return new WP_REST_Response( $data, 200 );
		if(!is_wp_error($post_id)){

			//Handle meta
			if(!empty($params['meta']))
			{
				foreach($params['meta'] as $key=>$value)
				{
					//$final_right_side_plain_text_content = crw_getContentAndUploadNewContent( $params['meta']['right_side_plain_text_content'] , $upload_dir );
					update_post_meta(
						$post_id,
						$key,
						crw_getContentAndUploadNewContent( maybe_unserialize($value) , $upload_dir )
					);
				}

				if(!empty($params['meta_thumb_ids'])){
					foreach( $params['meta_thumb_ids'] as $mkey=>$mvalue ){
						if(!empty($mvalue)){
							$newUrlArray = crw_rs_upload_from_url($mvalue,null,null,null,true);
							update_post_meta(
								$post_id,
								$mkey,
								$newUrlArray['id']
							);
						}	
						
					}
				}

				//update_post_meta($post_id,'right_side_plain_text_content',$final_right_side_plain_text_content);
			}

			//Handle terms
			if(!empty($params['terms']))
			{
				foreach($params['terms'] as $term)
				{
					$term_details='';
					$term_details=term_exists($term['name'],$term['taxonomy']);
					
					if(empty($term_details))
					{
						$term_details=wp_insert_term(
							$term['name'],
							$term['taxonomy'], 
							array(
								'description'   => $term['description'],
								'slug'          => $term['slug'],
							)
						);
					}

					if(!empty($term_details) && !is_wp_error($term_details) && !has_term($term['name'],$term['taxonomy'],$post_id))
					{
						wp_set_object_terms($post_id,intval($term_details['term_id']),$term['taxonomy']);
					}
				}
			}


			$data = array(
				'post_id'=>$post_id,
				'permalink' => get_permalink($post_id),
			);
			return new WP_REST_Response( $data, 200 );
		}

		return new WP_Error( 'cant-update', __( 'Error with publishing post', '' ), array( 'status' => 500 ) );
	}

	function process_decline_data(WP_REST_Request $request)
	{
		$params = $request->get_params();
		
		if(!empty($params['remote_id'])){
			$deleted_post=wp_delete_post(intval($params['remote_id']),true);					
		}
		
		if(!empty($deleted_post)){
			$data=array('deleted_post'=>$deleted_post);
			return new WP_REST_Response( $data, 200 );
		}

		return new WP_Error( 'cant-update', __( 'Error with deleting the post', '' ), array( 'status' => 500 ) );
	}

	function check_permissions($request){
		return true;
	}



}

new CRW_Content_Import_Api();



?>