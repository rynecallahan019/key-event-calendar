<?php
// Get Bootstrap and jQuery
function enqueue_bootstrap_and_jquery() {
    // Register jQuery
    wp_enqueue_script('jquery');

    // Register Bootstrap CSS
    wp_register_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2', 'all');
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap-css');

    // Register Bootstrap JS
    wp_register_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), '4.5.2', true);
    // Enqueue Bootstrap JS
    wp_enqueue_script('bootstrap-js');
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap_and_jquery');


// Function to render the FullCalendar with events
function display_calendar_shortcode() {
    ob_start(); // Start output buffering

    // Query Key Events posts
    $events_query = new WP_Query(array(
        'post_type' => 'key-events',
        'posts_per_page' => -1, // Get all events
    ));

    // Prepare events data array
    $events_data = array();

    // Loop through each event
    if ($events_query->have_posts()) {
        while ($events_query->have_posts()) {
            $events_query->the_post();

            // Get event details
            $event_title = get_the_title();
            $event_title_sanitized = esc_html(html_entity_decode($event_title));
            $event_date = get_post_meta(get_the_ID(), 'event_date', true);
            $event_time = get_post_meta(get_the_ID(), 'event_time', true);
            $event_location = get_post_meta(get_the_ID(), 'event_location', true);
            $event_color = get_post_meta(get_the_ID(), 'event_color', true); // Retrieve event color

            // Format event date
            $event_start = date('Y-m-d', strtotime($event_date));
            $event_end = date('Y-m-d', strtotime($event_date));

            // Format event time
            $event_time_formatted = $event_time ? date('h:i A', strtotime($event_time)) : '';

            // Append event data to the array
            $events_data[] = array(
                'title' => $event_title_sanitized,
                'start' => $event_start,
                'end' => $event_end,
                'time' => $event_time,
                'time_formatted' => $event_time_formatted,
                'location' => $event_location,
                'color' => $event_color, // Add event color to the data array
            );
        }
    }

    // Output the HTML structure for the FullCalendar
    ?>
    <div id="calendar"></div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="eventDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            // Your FullCalendar options here
            initialView: 'dayGridMonth',
            events: <?php echo json_encode($events_data); ?>,
            eventClick: function(info) {
                // Modify the event time format in the modal
                var eventTime = info.event.extendedProps.time ? 'Time: ' + info.event.extendedProps.time_formatted : '';

                // Display event details in modal
                $('#eventModal #eventDetails').html(
                    '<p><strong>Title:</strong> ' + info.event.title + '</p>' +
                    '<p><strong>Date:</strong> ' + info.event.start.toLocaleDateString() + '</p>' +
                    '<p><strong>' + eventTime + '</strong></p>' +
                    '<p><strong>Location:</strong> ' + info.event.extendedProps.location + '</p>'
                );
                $('#eventModal').modal('show');
            },
            eventDidMount: function(info) {
                // Set event background color
                if (info.event.extendedProps.color) {
                    info.el.style.backgroundColor = info.event.extendedProps.color;
                }
            }
        });
        calendar.render();
    });
    </script>
    <?php

    // Reset post data
    wp_reset_postdata();

    return ob_get_clean(); // Return the buffered output
}


// Register the shortcode
add_shortcode('display_calendar', 'display_calendar_shortcode');
// Function to display all key events in chronological order
function display_all_key_events_shortcode() {
    ob_start(); // Start output buffering

    // Get current date
    $current_date = date('Y-m-d');

    // Query Key Events posts
    $events_query = new WP_Query(array(
        'post_type' => 'key-events',
        'posts_per_page' => -1, // Get all events
        'orderby' => 'meta_value', // Order by event date
        'meta_key' => 'event_date', // Meta key for event date
        'order' => 'ASC' // Order in ascending chronological order
    ));

    // Check if there are any events
    if ($events_query->have_posts()) {
        ?>
        <table class="key-events-table">
            <thead>
                <tr class="tr-head">
                    <th class="th-head">Date</th>
                    <th class="th-head">Time</th>
                    <th class="th-head">Title</th>
                    <th class="th-head">Location</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $row_class = ''; // Initialize row class variable
            // Loop through each event
            while ($events_query->have_posts()) {
                $events_query->the_post();

                // Get event details
                $event_title = get_the_title();
                $event_date = get_post_meta(get_the_ID(), 'event_date', true);
                $event_time = get_post_meta(get_the_ID(), 'event_time', true);
                $event_location = get_post_meta(get_the_ID(), 'event_location', true);

                // Compare event date with current date
                if (strtotime($event_date) < strtotime($current_date)) {
                    continue; // Skip this event if it has passed
                }

                // Format event date as "Month, Day, Year"
                $event_date_formatted = date('F j, Y', strtotime($event_date));
                
                // Format event time with AM/PM
                $event_time_formatted = $event_time ? date('h:i A', strtotime($event_time)) : '';

                // Alternate row classes for striped effect
                $row_class = ($row_class == 'even') ? 'odd' : 'even';

                // Output event details in table row
                ?>
                <tr class="<?php echo esc_attr($row_class); ?>">
                    <td class="event-date"><?php echo esc_html($event_date_formatted); ?></td>
                    <td class="event-time"><?php echo esc_html($event_time_formatted); ?></td>
                    <td class="event-title"><?php echo esc_html($event_title); ?></td>
                    <td class="event-location"><?php echo esc_html($event_location); ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    } else {
        // If no events found
        echo 'No events found.';
    }

    // Reset post data
    wp_reset_postdata();

    return ob_get_clean(); // Return the buffered output
}

// Register the shortcode to display all key events
add_shortcode('display_all_key_events', 'display_all_key_events_shortcode');




// Register Custom Post Type for Key Events
function register_key_events_post_type() {
    $labels = array(
        'name'               => 'Key Events',
        'singular_name'      => 'Key Event',
        'add_new'            => 'Add New Key Event',
        'add_new_item'       => 'Add New Key Event',
        'edit_item'          => 'Edit Key Event',
        'new_item'           => 'New Key Event',
        'view_item'          => 'View Key Event',
        'search_items'       => 'Search Key Events',
        'not_found'          => 'No key events found',
        'not_found_in_trash' => 'No key events found in Trash',
        'parent_item_colon'  => '',
        'menu_name'          => 'Calendar'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'key-event' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ) // Add or remove features as needed
    );

    register_post_type( 'key-events', $args );
}
add_action( 'init', 'register_key_events_post_type' );

// Register custom fields for event details
function register_event_details_custom_fields() {
    register_meta('post', 'event_date', array(
        'type' => 'string',
        'description' => 'Event Date',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ));
    register_meta('post', 'event_time', array(
        'type' => 'string',
        'description' => 'Event Time',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ));
    register_meta('post', 'event_location', array(
        'type' => 'string',
        'description' => 'Event Location',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ));
    register_meta('post', 'event_color', array(
        'type' => 'string',
        'description' => 'Event Color',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ));
}
add_action('init', 'register_event_details_custom_fields');

// Add meta box for event details
function add_event_details_meta_box() {
    add_meta_box(
        'event_details_meta_box', // Meta box ID
        'Event Details', // Meta box title
        'render_event_details_meta_box', // Callback function to render the meta box
        'key-events', // Post type
        'normal', // Context (normal, side, advanced)
        'high' // Priority (high, core, default, low)
    );
}

// Render meta box content
function render_event_details_meta_box($post) {
    // Add nonce field
    wp_nonce_field('event_details_meta_box_nonce', 'event_details_meta_box_nonce');

    // Retrieve existing values for event fields
    $event_date = get_post_meta($post->ID, 'event_date', true);
    $event_time = get_post_meta($post->ID, 'event_time', true);
    $event_location = get_post_meta($post->ID, 'event_location', true);
    $event_color = get_post_meta($post->ID, 'event_color', true);

    // Output fields
    ?>
    <p>
        <label for="event_date">Event Date:</label><br>
        <input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($event_date); ?>">
    </p>
    <p>
        <label for="event_time">Event Time:</label><br>
        <input type="time" id="event_time" name="event_time" value="<?php echo esc_attr($event_time); ?>">
    </p>
    <p>
        <label for="event_location">Event Location:</label><br>
        <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($event_location); ?>">
    </p>
    <p>
        <label for="event_color">Event Color:</label><br>
        <input type="text" id="event_color" name="event_color" value="<?php echo esc_attr($event_color); ?>" class="event-color-picker">
    </p>
    <script>
        jQuery(document).ready(function($) {
            $('.event-color-picker').wpColorPicker(); // Initialize WordPress color picker
        });
    </script>
    <?php
}

add_action('add_meta_boxes', 'add_event_details_meta_box');

// Save meta box data when post is saved
function save_event_details_meta_data($post_id) {
    // Verify nonce
    if (!isset($_POST['event_details_meta_box_nonce']) || !wp_verify_nonce($_POST['event_details_meta_box_nonce'], 'event_details_meta_box_nonce')) {
        return;
    }

    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save event data
    if (isset($_POST['event_date'])) {
        update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event_date']));
    }
    if (isset($_POST['event_time'])) {
        update_post_meta($post_id, 'event_time', sanitize_text_field($_POST['event_time']));
    }
    if (isset($_POST['event_location'])) {
        update_post_meta($post_id, 'event_location', sanitize_text_field($_POST['event_location']));
    }
    if (isset($_POST['event_color'])) {
        update_post_meta($post_id, 'event_color', sanitize_text_field($_POST['event_color']));
    }
}

add_action('save_post', 'save_event_details_meta_data');




