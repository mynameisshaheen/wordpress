<?php

class Custom_Job_API {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'jobmanager/v1', '/jobs', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_jobs' ],
            'permission_callback' => '__return_true', // Adjust permissions as needed.
        ]);

        register_rest_route( 'jobmanager/v1', '/job/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_job_details' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( 'jobmanager/v1', '/create_applications', [
            'methods' => 'POST',
            'callback' => [ $this, 'submit_application' ],
            'permission_callback' => '__return_true', 
            'args' => array(
                'applicant_name' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return ! empty( $param );
                    },
                ),
                'applicant_email' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return is_email( $param );
                    },
                ),
                'message' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return ! empty( $param );
                    },
                ),
                'job_id' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return ! empty( $param );
                    },
                ),
                'resume' => array(
                    'required' => false,
                    'validate_callback' => function( $param, $request, $key ) {
                        return isset( $_FILES['resume'] ) && ! empty( $_FILES['resume']['name'] );
                    },
                ),
            ),
        ]);


    }

    public function get_jobs( $request ) {
        $args = [
            'post_type'      => 'job_listing', // Default post type for Job Manager.
            'posts_per_page' => -1,
        ];

        $jobs = get_posts( $args );
        $data = [];

        foreach ( $jobs as $job ) {
            $data[] = [
                'id'       => $job->ID,
                'title'    => $job->post_title,
                'content'  => $job->post_content,
                'location' => get_post_meta( $job->ID, '_job_location', true ), 
                'featured'   => get_post_meta( $job->ID, '_featured', true ),
                'job_expires'   => get_post_meta( $job->ID, '_job_expires', true ),
                'company_name'   => get_post_meta( $job->ID, '_company_name', true ),
                'company_website'   => get_post_meta( $job->ID, '_company_website', true )
            ];
        }

        return rest_ensure_response( $data );
    }

    public function get_job_details( $request ) {
        $id = $request['id'];

        $job = get_post( $id );

        if ( ! $job || $job->post_type !== 'job_listing' ) {
            return new WP_Error( 'no_job', 'Job not found', [ 'status' => 404 ] );
        }

        $data = [
            'id'       => $job->ID,
            'title'    => $job->post_title,
            'content'  => $job->post_content,
            'location' => get_post_meta( $job->ID, '_job_location', true ),
            'featured'   => get_post_meta( $job->ID, '_featured', true ),
            'job_expires'   => get_post_meta( $job->ID, '_job_expires', true ),
            'company_name'   => get_post_meta( $job->ID, '_company_name', true ),
            'company_website'   => get_post_meta( $job->ID, '_company_website', true )
        ];

        return rest_ensure_response( $data );
    }

    public function submit_application( WP_REST_Request $request ) {
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $applicant_name = sanitize_text_field( $request->get_param( 'applicant_name' ) );
        $applicant_email = sanitize_email( $request->get_param( 'applicant_email' ) );
        $message = sanitize_textarea_field( $request->get_param( 'message' ) );
        $job_id = sanitize_text_field( $request->get_param( 'job_id' ) );
        $resume_url = '';

        //validate the job post id
        $job_post = get_post( $job_id );
        if ( ! $job_post || $job_post->post_type !== 'job_listing' ) {
            return new WP_Error( 'invalid_job_id', 'The specified job does not exist or is invalid.', array( 'status' => 400 ) );
        }


        // Handle the resume file upload
        if ( isset( $_FILES['resume'] ) && ! empty( $_FILES['resume']['name'] ) ) {
            $file = $_FILES['resume'];
            // Validate file type (PDF or DOC)
            $allowed_types = array( 'application/pdf');
            if ( ! in_array( $file['type'], $allowed_types ) ) {
                return new WP_Error( 'invalid_file_type', 'Only PDF files are allowed for resume upload.', array( 'status' => 400 ) );
            }

            // Validate file size (limit: 5MB)
            $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
            if ( $file['size'] > $max_file_size ) {
                return new WP_Error( 'file_size_exceeded', 'The file size exceeds the allowed limit of 5MB.', array( 'status' => 400 ) );
            }


            $uploaded_file = media_handle_upload( 'resume', 0 );
            if ( ! is_wp_error( $uploaded_file ) ) {
                $resume_url = wp_get_attachment_url( $uploaded_file );
            } else {
                return new WP_Error( 'resume_upload_failed', 'Failed to upload resume.', array( 'status' => 500 ) );
            }
        }
    
        // Insert the application as a post
        $application_id = wp_insert_post( array(
            'post_type'    => 'application',
            'post_title'   => $applicant_name,
            'post_content' => $message,
            'post_status'  => 'publish',
        ));
    
        // Save custom meta fields
        if ( $application_id ) {
            update_post_meta( $application_id, '_applicant_name', $applicant_name );
            update_post_meta( $application_id, '_applicant_email', $applicant_email );
            update_post_meta( $application_id, '_message', $message );
            update_post_meta( $application_id, '_job_id', $job_id );
            if ( $resume_url ) {
                update_post_meta( $application_id, '_resume_url', $resume_url );
            }
        }
    
        return rest_ensure_response( array(
            'success' => true,
            'application_id' => $application_id,
        ));
    }



}

new Custom_Job_API();
?>
