<?php
get_header();
?>
    <main>
        <h2>Job Listings</h2>
        <div class="jobs-list">
            <?php
            $api_url = rest_url( 'jobmanager/v1/jobs' ); // REST API endpoint
            $response = wp_remote_get( $api_url );

            if ( is_wp_error( $response ) ) {
                echo '<p>Unable to fetch jobs. Please try again later.</p>';
            } else {
                $jobs = json_decode( wp_remote_retrieve_body( $response ) );

                if ( ! empty( $jobs ) ) {
                    foreach ( $jobs as $job ) {
                        $job_permalink = get_permalink( $job->id );

                        echo '<div class="job-item">';
                        echo '<h3>' . esc_html( $job->title ) . '</h3>';
                        echo '<p>' . esc_html( $job->content ) . '</p>';
                        echo '<p><strong>Salary:</strong> ' . esc_html( $job->salary ?? 'Not specified' ) . '</p>';
                        echo '<a href="' . esc_url( add_query_arg( 'id', $job->id, site_url( '/job-details/' ) ) ) . '">View Details</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No jobs found.</p>';
                }
            }
            ?>
        </div>
    </main>
  <?php
  get_footer();
  ?>
