<?php 
/**
* common helper class for Email
*/
class CRW_Email {

	public $blogname;
	public $fromAdminEmail = 'no-reply@crw.com';
	public $mail_priority;

	function __construct() {
		$this->blogname = esc_html(get_bloginfo('name'));
		$this->mail_priority = '';
	}

	/**
	 * Generate function for send mail
	 *
	 * @param array/String $to
	 * @param string $cc  
	 * @param string $subject  
	 * @param string $message  
	 * @return object $response
	 */

	function priority($prio=''){
		$this->mail_priority = $prio;
	}
	
	public function mail($to,$subject,$message,$attachment='',$cc='') {
		$headers = $this->mail_headers($cc);

		if(is_array($to)){
			foreach($to as $receip){
				$sent = wp_mail($receip, $subject, $message, $headers , $attachment);
			}
		}else{
			$sent = wp_mail($to, $subject, $message, $headers , $attachment);
		}
		
		
		$response = ($sent) ? json_encode(array('status'=>1,'message'=>'success')):json_encode(array('status'=>0,'message'=>'error'));
		$this->mail_priority = '';
		return $response;
	
	}

	function test_email($to='dipankar.pal@capitalnumbers.com'){
		$this->mail($to,'test subject' , 'test message');
	}

	
	/**
	 * private function for mail headers
	 *
	 * @param string $cc  
	 * @return string $headers
	 */
	private function mail_headers($cc='') {
        
		$admin = $this->blogname;
		$admin_email = $this->fromAdminEmail;

		if($this->mail_priority == 'high'){
			$headers = 'From: '.$admin.' '.' <'.$admin_email.'>' . "\r\n" .
			'Content-Type: text/html' . "\r\n" .
			'X-Mailer: PHP/' . phpversion() . "\r\n" .
			'X-Priority: 1 (Highest)' . "\r\n" .
			'X-MSMail-Priority: High' . "\r\n" .
			'Importance: High' ;
		}else{
			$headers = 'From: '.$admin.' '.' <'.$admin_email.'>' . "\r\n" .
			'Content-Type: text/html' . "\r\n" .
			'X-Mailer: PHP/' . phpversion() ;
		}
		
		if($cc){
			$headers .= "CC: $cc"."\r\n";
		}
		
		return $headers;
	}

	function disclaimer(){
		return 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.';
	}

	function footer(){
		return 'Cheers,<br>Lorem Ipsum';
	}

	function create_post_email_notification($data,$return_template=false){
        $file_path = plugin_dir_path(__FILE__) . 'html/create_req_temp.html';

        $html = "";
        if ( file_exists( $file_path ) ) {
            $html = file_get_contents( $file_path );
        }

		$logo_html = "";
		
		$header_logo = AtsGetField('header_logo', 'option');
		
		if (!empty($header_logo)) {
			$alt_text = ats_get_image_alt_text($header_logo);
			if (empty($alt_text)) $alt_text = 'A&T Sysyems Inc.';
			$img = wp_get_attachment_image($header_logo, 'full', '', array('class' => 'ats-logo__img', 'alt' => $alt_text));
			$logo_html = '<a href="'.home_url().'" title="'.get_bloginfo('name').'">'.$img.'</a>';
		}

		$html = str_replace('{heading}' , $data['heading'] , $html );
		$html = str_replace('{content_title}' , $data['content_title'] , $html );
		$html = str_replace('{creator_name}' , $data['creator_name'] , $html );
		$html = str_replace('{content_link}' , $data['content_link'] , $html );
		$html = str_replace('{creator_email}' , $data['creator_email'] , $html );
		$html = str_replace('{site_title}' , $data['site_title'] , $html );
		$html = str_replace('{post_status}' , $data['post_status'] , $html );

		if( isset($data['button_label']) && $data['button_label'] != "" ) {
			$html = str_replace('{button_label}' , $data['button_label'] , $html );
		}

		$html = str_replace('{logo}' , $logo_html , $html );

		if($return_template){
			return $html;
		}
		
		if(!empty($data['to'])){
			return $this->mail( $data['to'] , $data['subject'] , $html);
		}
	}

	function update_hrq_email_notification($data){
		$file_path = plugin_dir_path(__FILE__) . 'html/update_req_temp.html';

        $html = "";
        if ( file_exists( $file_path ) ) {
            $html = file_get_contents( $file_path );
        }

		$logo_html = "";
		
		$header_logo = AtsGetField('header_logo', 'option');
		
		if (!empty($header_logo)) {
			$alt_text = ats_get_image_alt_text($header_logo);
			if (empty($alt_text)) $alt_text = 'A&T Sysyems Inc.';
			$img = wp_get_attachment_image($header_logo, 'full', '', array('class' => 'ats-logo__img', 'alt' => $alt_text));
			$logo_html = '<a href="'.home_url().'" title="'.get_bloginfo('name').'">'.$img.'</a>';
		}

		$html = str_replace('{heading}' , $data['heading'] , $html );
		$html = str_replace('{content_title}' , $data['content_title'] , $html );
		$html = str_replace('{creator_name}' , $data['creator_name'] , $html );
		$html = str_replace('{content_link}' , $data['content_link'] , $html );
		$html = str_replace('{creator_email}' , $data['creator_email'] , $html );
		$html = str_replace('{site_title}' , $data['site_title'] , $html );
		$html = str_replace('{post_status}' , $data['post_status'] , $html );
		$html = str_replace('{remote_link}' , $data['remote_link'] , $html );
		if( isset($data['button_label']) && $data['button_label'] != "" ) {
			$html = str_replace('{button_label}' , $data['button_label'] , $html );
		}
		$html = str_replace('{logo}' , $logo_html , $html );

		if($return_template){
			return $html;
		}
		
		if(!empty($data['to'])){
			return $this->mail( $data['to'] , $data['subject'] , $html);
		}
	}
}

