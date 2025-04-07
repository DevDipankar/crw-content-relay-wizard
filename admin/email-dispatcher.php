<?php
/**
 * ===========================================
 * Start Email Functionality
 * ===========================================
 * */
require_once ('email/class-crw-email.php');

/**
 * Filter for send email when post is publish
 */
if(isset($crw_init['crw_env_type']) && $crw_init['crw_env_type'] == 'source'){
    add_filter( 'redirect_post_location', 'send_email_on_post_insert', 10, 2 );
}

function send_email_on_post_insert($location, $post_id) {
    
    $post = get_post( $post_id );

    if (in_array( $post->post_status, ['auto-draft', 'inherit'] ) ) {
        // If this is an update, do nothing
        return $location;
    }

    $post_title = get_the_title($post_id);
    $post_type = get_post_type( $post_id );

    $post_status = get_post_status( $post_id );
    $status_label = "";
    if ( $post_status ) {
        $status_object = get_post_status_object( $post_status );
        $status_label = $status_object->label;
    }
        
    if( $post_title == "" ) {
        return $location;
    }

    $get_heading = "";
    $get_heading_creator = "";
    if ( ! is_wp_error( $post_type ) ) {
        $get_heading = 'A new ' . $post_type . ' has been added';
        $get_heading_creator = ucfirst($post_type) . ' has been updated';
    }

    // Get the author ID for the post
    $author_id = get_post_field( 'post_author', $post_id );

    // Get the author's display name, email
    $author_name = get_the_author_meta( 'display_name', $author_id );
    $author_email = get_the_author_meta('user_email', $author_id);

    // Get the edit post link
    $edit_link = get_edit_post_link($post_id);
    $content_link = "#";
    if ($edit_link) {
        $content_link = $edit_link;
    }
    
    // Get the site title
    $site_title = esc_html(get_bloginfo('name'));

    $approver_emails = get_all_content_approver_emails();
    $creator_emails = get_all_content_creator_emails();
    $mail_subject = "A new ".ucfirst($post_type)." is ready for review";
    $mail_subject_for_creator = ucfirst($post_type)." is updated status to ".ucfirst($status_label);

    $user = wp_get_current_user();
    
    $get_current_user_name = isset($user->display_name) ? $user->display_name : "";
    $get_current_user_email = isset($user->user_email) ? $user->user_email : "";
    
    $CRW_Email = new CRW_Email();

    if ( in_array( 'author', (array) $user->roles ) ) {
        $email = $CRW_Email->create_post_email_notification(
            array(
                'to' => $approver_emails,
                'subject' => $mail_subject,
                'heading' => $get_heading,
                'content_title' => $post_title,
                'creator_name' => $author_name,
                'content_link' => $content_link,
                'creator_email' => $author_email,
                'site_title' => $site_title,
                'post_status' => ucfirst($post_status),
            )
        );
    } else {
        if ( $post_status == 'publish' ) {
            $edit_link = get_edit_post_link($post_id);
            
            $get_content_link = "#";
            if ($edit_link) {
                $get_content_link = $edit_link;
            }

            // Set the delay time in seconds. For example, for 1 hour: 3600 seconds.
            $delay = 30; // 30sec
            
            // Schedule the custom action 'send_custom_email' to run after the delay.
            wp_schedule_single_event( time() + $delay, 'send_custom_email', array( $post_id, $get_content_link, $get_current_user_name ) );
        } else {
            $button_label = "Page link";

            $email = $CRW_Email->update_hrq_email_notification(
                array(
                    'to' => $creator_emails,
                    'subject' => $mail_subject_for_creator,
                    'heading' => $get_heading,
                    'content_title' => $post_title,
                    'creator_name' => $get_current_user_name,
                    'content_link' => $content_link,
                    'creator_email' => $get_current_user_email,
                    'site_title' => $site_title,
                    'post_status' => ucfirst($post_status),
                    'button_label' => $button_label,
                )
            );
        }
    }

    return $location;
}

/**
 * When Status is publish 
 * the send mail delay callback function
 * to get the remote link
 */
add_action( 'send_custom_email', 'send_custom_email_function', 10, 3 );

function send_custom_email_function( $post_id, $get_content_link, $user_name ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return; // Exit if the post is not found.
    }

    $creator_emails = get_all_content_creator_emails();
    $post_title = get_the_title($post_id);
    $post_type = get_post_type( $post_id );

    $post_status = get_post_status( $post_id );
    $status_label = "";
    if ( $post_status ) {
        $status_object = get_post_status_object( $post_status );
        $status_label = $status_object->label;
    }

    $mail_subject_for_creator = ucfirst($post_type)." status has been changed to ".ucfirst($status_label);

    $get_heading_creator = "";
    if ( ! is_wp_error( $post_type ) ) {
        $get_heading_creator = ucfirst($post_type) . ' has been updated';
    }

    $user = wp_get_current_user();
    $get_current_user_name = isset($user->display_name) ? $user->display_name : "";
    $get_current_user_email = isset($user->user_email) ? $user->user_email : "";

    // Get the site title
    $site_title = esc_html(get_bloginfo('name'));

    $link = get_post_meta( $post_id, CRW_REMOTE_LINK, true );
    $remote_link = "#"; 
    if($link){
        $remote_link = $link;
    }
    $button_label = "View The production page";

    $CRW_Email = new CRW_Email();
    
    $email = $CRW_Email->update_hrq_email_notification(
        array(
            'to' => $creator_emails,
            'subject' => $mail_subject_for_creator,
            'heading' => $get_heading_creator,
            'content_title' => $post_title,
            'creator_name' => $user_name,
            'content_link' => $get_content_link,
            'remote_link' => $remote_link,
            'creator_email' => $get_current_user_email,
            'site_title' => $site_title,
            'post_status' => ucfirst($post_status),
            'button_label' => $button_label,
        )
    );
}

/**
 * Get All Content Approvers users
 */
function get_all_content_approver_emails() {
    $users = get_users( array(
        'role__in' => array( 'editor' ),
        'orderby' => 'email',
        'order' => 'ASC'
    ) );
    
    $approvers = [];
    if(isset($users) && !empty($users)) {
        foreach ( $users as $user ) {
            if(isset($user->user_email) && !in_array($user->user_email, $approvers)) {
                array_push($approvers, $user->user_email);
            }
        }
    }

    return $approvers;
}


/**
 * Get All Content Creator users
 */
function get_all_content_creator_emails() {
    $users = get_users( array(
        'role__in' => array( 'author' ),
        'orderby' => 'email',
        'order' => 'ASC'
    ) );
    
    $creators = [];
    if(isset($users) && !empty($users)) {
        foreach ( $users as $user ) {
            if(isset($user->user_email) && !in_array($user->user_email, $creators)) {
                array_push($creators, $user->user_email);
            }
        }
    }

    return $creators;
}

/**
 * ===========================================
 * End Email Functionality
 * ===========================================
 * */