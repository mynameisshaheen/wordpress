<?php


function custom_theme_assets() {
    // Enqueue the main stylesheet
    wp_enqueue_style( 'main-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', 'custom_theme_assets' );
?>
