<?php
get_header();

$job_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $job_id ) {
    $api_url = rest_url( "jobmanager/v1/job/$job_id" ); // REST API endpoint
    $response = wp_remote_get( $api_url );

    echo '<main><h2>Job Details</h2>';

    if ( is_wp_error( $response ) ) {
        echo '<p>Unable to fetch job details. Please try again later.</p>';
    } else {
        $job = json_decode( wp_remote_retrieve_body( $response ) );

        if ( $job ) {
            echo '<div class="job-details">';
            echo '<h3>' . esc_html( $job->title ) . '</h3>';
            echo '<p>' . esc_html( $job->content ) . '</p>';
            echo '<p><strong>Location:</strong> ' . esc_html( $job->location ?? 'Not specified' ) . '</p>';
            echo '<p><strong>Salary:</strong> ' . esc_html( $job->salary ?? 'Not specified' ) . '</p>';
            echo '</div>';
        } else {
            echo '<p>Job not found.</p>';
        }
    }

    echo '</main>';
} else {
    echo '<main><p>Invalid Job ID.</p></main>';
}

get_footer();
?>
