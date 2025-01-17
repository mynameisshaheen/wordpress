<?php
/*
Plugin Name: Application Manager
Plugin URI: http://yourwebsite.com/
Description: A plugin to manage job applications with custom post type, custom fields.
Version: 1.0
Author: Muhammad Shaheen
Author URI: http://yourwebsite.com/

*/

function register_application_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Applications',
        'supports' => array( 'title', 'editor', 'custom-fields' ),
        'menu_icon' => 'dashicons-clipboard',
    );
    register_post_type( 'application', $args );
}
add_action( 'init', 'register_application_post_type' );

function add_application_meta_boxes() {
    add_meta_box(
        'application_details', 
        'Application Details', 
        'application_meta_box_callback', 
        'application', 
        'normal', 
        'high'
    );
}
add_action('add_meta_boxes', 'add_application_meta_boxes');

function application_meta_box_callback( $post ) {
    wp_nonce_field( 'save_application_details', 'application_nonce' );
    
    $applicant_name = get_post_meta( $post->ID, '_applicant_name', true );
    $applicant_email = get_post_meta( $post->ID, '_applicant_email', true );
    $message = get_post_meta( $post->ID, '_message', true );
    $job_id = get_post_meta( $post->ID, '_job_id', true );
    $resume_url = get_post_meta( $post->ID, '_resume_url', true );

    ?>
    <p>
        <label for="applicant_name">Applicant Name</label><br>
        <input type="text" id="applicant_name" name="applicant_name" value="<?php echo esc_attr( $applicant_name ); ?>" class="widefat">
    </p>
    <p>
        <label for="applicant_email">Applicant Email</label><br>
        <input type="email" id="applicant_email" name="applicant_email" value="<?php echo esc_attr( $applicant_email ); ?>" class="widefat">
    </p>
    <p>
        <label for="message">Message</label><br>
        <textarea id="message" name="message" class="widefat"><?php echo esc_textarea( $message ); ?></textarea>
    </p>
    <p>
        <label for="job_id">Job Name (Job ID)</label><br>
        <input type="text" id="job_id" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" class="widefat">
    </p>
    <p>
        <label for="resume">Upload Resume</label><br>
        <input type="file" id="resume" name="resume" class="widefat">
        <?php if ( $resume_url ) { ?>
            <p>Uploaded Resume: <a href="<?php echo esc_url( $resume_url ); ?>" target="_blank">View Resume</a></p>
        <?php } ?>
    </p>
    <?php
}

function save_application_meta_data( $post_id ) {
    if ( ! isset( $_POST['application_nonce'] ) || ! wp_verify_nonce( $_POST['application_nonce'], 'save_application_details' ) ) {
        return;
    }

    // Save custom meta fields
    if ( isset( $_POST['applicant_name'] ) ) {
        update_post_meta( $post_id, '_applicant_name', sanitize_text_field( $_POST['applicant_name'] ) );
    }
    if ( isset( $_POST['applicant_email'] ) ) {
        update_post_meta( $post_id, '_applicant_email', sanitize_email( $_POST['applicant_email'] ) );
    }
    if ( isset( $_POST['message'] ) ) {
        update_post_meta( $post_id, '_message', sanitize_textarea_field( $_POST['message'] ) );
    }
    if ( isset( $_POST['job_id'] ) ) {
        update_post_meta( $post_id, '_job_id', sanitize_text_field( $_POST['job_id'] ) );
    }

    // Save uploaded resume
    if ( isset( $_FILES['resume'] ) && ! empty( $_FILES['resume']['name'] ) ) {
        $uploaded_file = media_handle_upload( 'resume', $post_id );
        if ( is_wp_error( $uploaded_file ) ) {
            return;
        }
        update_post_meta( $post_id, '_resume_url', wp_get_attachment_url( $uploaded_file ) );
    }
}
add_action( 'save_post', 'save_application_meta_data' );

function add_application_columns( $columns ) {
    // Adding custom columns for Applicant Name, Job Name, and Resume URL
    $columns['applicant_name'] = 'Applicant Name';
    $columns['job_name'] = 'Job Name';
    
    
    return $columns;
}
add_filter( 'manage_edit-application_columns', 'add_application_columns' );

function display_application_column_data( $column, $post_id ) {
    switch ( $column ) {
        case 'applicant_name':
            $applicant_name = get_post_meta( $post_id, '_applicant_name', true );
            echo esc_html( $applicant_name );
            break;
        
        case 'job_name':
            $job_id = get_post_meta( $post_id, '_job_id', true );
            if ( ! empty( $job_id ) ) {
                $job_post = get_post( $job_id );
                if ( $job_post ) {
                    echo esc_html( $job_post->post_title ); // Display Job Name
                } else {
                    echo 'Job not found';
                }
            }
            break;
        
        case 'resume':
            $resume_url = get_post_meta( $post_id, '_resume_url', true );
            if ( ! empty( $resume_url ) ) {
                echo '<a href="' . esc_url( $resume_url ) . '" target="_blank">View Resume</a>';
            } else {
                echo 'No Resume';
            }
            break;
    }
}
add_action( 'manage_application_posts_custom_column', 'display_application_column_data', 10, 2 );
