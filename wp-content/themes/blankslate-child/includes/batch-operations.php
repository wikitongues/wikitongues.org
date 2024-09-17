<?php
// ====================
// Define Available Batch Operations
// ====================
$batch_operations = array(
    'update_custom_fields_counts' => array(
        'label'       => 'Update Custom Fields Counts',
        'callback'    => 'update_custom_fields_counts', // Core function
        'description' => 'Updates the counts of custom fields for languages post type.',
    ),
    'update_video_featured_language_iso_codes' => array(
        'label'       => 'Update Video Featured Language ISO Codes',
        'callback'    => 'update_video_featured_language_iso_codes', // Core function
        'description' => 'Updates the language ISO codes for videos.',
    ),
    // Add more operations as needed
);

// ====================
// Batch Update Functions
// ====================
function batch_update_posts($post_type, $batch_operation, $batch_size = 50) {
    global $batch_operations;
    $GLOBALS['batch_update_in_progress'] = true;

    // Start output buffering
    ob_start();

    // Ensure the batch operation is valid
    if (empty($batch_operation) || !isset($batch_operations[$batch_operation])) {
        error_log('Invalid batch operation specified.');
        wp_die('Invalid batch operation specified.');
    }

    $callback = $batch_operations[$batch_operation]['callback'];

    // Ensure the callback is callable
    if (!is_callable($callback)) {
        error_log('Invalid callback provided to batch_update_posts');
        wp_die('Invalid callback function.');
    }

    $offset = isset($_GET['batch_offset']) ? intval($_GET['batch_offset']) : 0;

    // Verify nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'batch_update_nonce')) {
        error_log('Nonce verification failed.');
        wp_die('Nonce verification failed.');
    }
    $nonce = $_GET['nonce'];

    // Query posts
    $args = array(
        'post_type'      => $post_type,
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'fields'         => 'ids',
        'post_status'    => 'any',
    );

    error_log("Batch update args: " . print_r($args, true)); // Debug log for query args

    $posts = get_posts($args);

    if (!empty($posts)) {
        foreach ($posts as $post_id) {
            call_user_func($callback, $post_id);
        }

        // Prepare the URL for the next batch
        $next_offset = $offset + $batch_size;
        $next_url = add_query_arg(array(
            'batch_update'    => 'true',
            'batch_offset'    => $next_offset,
            'post_type'       => $post_type,
            'batch_operation' => $batch_operation,
            'nonce'           => $nonce,
            'page'            => 'batch-update',
        ), admin_url('admin.php'));

        // Log progress and next URL for debugging
        error_log("Processed batch from offset $offset for post type $post_type using operation $batch_operation");
        error_log("Next URL: $next_url");

        // Correctly escape URL for JavaScript
        $escaped_url = esc_url_raw($next_url);

        // Display a message and redirect to the next batch after a delay
        echo '<div class="wrap"><h1>Batch Update in Progress</h1>';
        echo '<p>Processing batch from offset ' . $offset . ' for post type ' . esc_html($post_type) . '.</p>';
        echo '<p>You will be redirected to the next batch automatically.</p>';
        echo '</div>';
        echo '<script type="text/javascript">setTimeout(function(){ window.location.href = "' . $escaped_url . '"; }, 1000);</script>';

        // Flush output and end buffering
        ob_end_flush();
        exit;
    } else {
        // No more posts to process
        error_log("Batch update completed for post type $post_type using operation $batch_operation");

        unset($GLOBALS['batch_update_in_progress']);

        // Clean the buffer before redirect
        ob_end_clean();
        // Redirect to a completion page or display a message
        wp_redirect(admin_url('admin.php?page=batch-update&batch_completed=true'));
        exit;
    }
}


// ====================
// Add Admin Menu Page
// ====================
function batch_update_admin_menu() {
    add_menu_page(
        'Batch Update',
        'Batch Update',
        'manage_options',
        'batch-update',
        'batch_update_admin_page'
    );
}
add_action('admin_menu', 'batch_update_admin_menu');

// ====================
// Build Admin Page Interface
// ====================
function batch_update_admin_page() {
    global $batch_operations;

    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Check if a batch update has been initiated
    if (isset($_GET['batch_update']) && $_GET['batch_update'] === 'true') {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'batch_update_nonce')) {
            wp_die('Nonce verification failed.');
        }

        // Get the post type and batch operation from the query parameters
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
        $batch_operation = isset($_GET['batch_operation']) ? sanitize_text_field($_GET['batch_operation']) : '';

        if (empty($post_type)) {
            wp_die('No post type specified.');
        }

        if (empty($batch_operation) || !isset($batch_operations[$batch_operation])) {
            wp_die('Invalid batch operation specified.');
        }

        // Run the batch update
        batch_update_posts($post_type, $batch_operation);

    } else {
        // Display the batch update initiation form
        ?>

        <div class="wrap">
            <h1>Batch Update</h1>
            <?php
            if (isset($_GET['batch_completed']) && $_GET['batch_completed'] == 'true') {
                echo '<div class="notice notice-success is-dismissible"><p>Batch update completed successfully.</p></div>';
            }
            ?>
            <form method="get" action="">
                <input type="hidden" name="page" value="batch-update">
                <input type="hidden" name="batch_update" value="true">
                <input type="hidden" name="batch_offset" value="0">
                <?php wp_nonce_field('batch_update_nonce', 'nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="post_type">Post Type</label></th>
                        <td>
                            <select name="post_type" id="post_type">
                                <?php
                                // Get all public post types
                                $post_types = get_post_types(array('public' => true), 'objects');
                                foreach ($post_types as $post_type_obj) {
                                    echo '<option value="' . esc_attr($post_type_obj->name) . '">' . esc_html($post_type_obj->label) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="batch_operation">Batch Operation</label></th>
                        <td>
                            <select name="batch_operation" id="batch_operation">
                                <?php
                                foreach ($batch_operations as $key => $operation) {
                                    echo '<option value="' . esc_attr($key) . '">' . esc_html($operation['label']) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Start Batch Update'); ?>
            </form>
        </div>

        <?php
    }
}

// ====================
// Existing Callback Functions
// ====================

// Place your existing callback functions here or include them separately