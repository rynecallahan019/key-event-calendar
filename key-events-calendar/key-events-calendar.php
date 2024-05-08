<?php
/*
Plugin Name: Key Events Calendar
Plugin URI: 
Description: This is a Horizon Performance custom plugin that creates key events and stores them in a calendar.
Version: 1.0
Author: Horizon Performance L.L.C
Author URI: https://horizonperformance.com/
*/

include_once('functions.php');

// Enqueue Plugin Styles and Scripts
function my_custom_plugin_scripts() {
    // Enqueue FullCalendar CSS
    wp_enqueue_style('fullcalendar-css', plugin_dir_url(__FILE__) . 'css/main.min.css');

    // Enqueue jQuery (if not already enqueued)
    wp_enqueue_script('jquery');

    // Enqueue FullCalendar JS
    wp_enqueue_script('fullcalendar-js', plugin_dir_url(__FILE__) . 'js/main.min.js', array('jquery'), null, true);

    // Enqueue Plugin JS
    wp_enqueue_script('my-custom-plugin-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), null, true);
}

add_action('wp_enqueue_scripts', 'my_custom_plugin_scripts');

