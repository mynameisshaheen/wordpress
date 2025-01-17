<?php
/*
Plugin Name: Custom Job API
Description: Provides custom REST API endpoints for job listings.
Version: 1.0
Author: Muhammad Shaheen
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include REST API logic.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-api.php';
?>
