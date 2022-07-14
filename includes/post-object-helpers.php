<?php
function handle_post_object($request)
{
    // Decode request body as associative array
    $body = json_decode($request->get_body(), true);

    $post_id = $body['id'];

    foreach ($body as $field => $value) {
        $field_obj = acf_maybe_get_field($field, false, false);

        if (is_array($field_obj) && $field_obj['type'] == 'post_object') {
            set_post_object_field($post_id, $field, $value, $field_obj['post_type']);
        }
    }

    return true;
}

function set_post_object_field($post_id, $field, $value, $target_post_type)
{
    // Update post object field
    // Airtable API sends value as array
    $titles = is_array($value) ? $value : explode(',', $value);
    $ids = array();
    foreach ($titles as $title) {
        // Find post ID assigned by Wordpress given the post title
        $post = get_page_by_title(trim($title), OBJECT, $target_post_type);
        if ($post != null) {
            array_push($ids, $post->ID);
        }
    }
    // Set field to array of post ID's
    update_field($field, $ids, $post_id);
}
