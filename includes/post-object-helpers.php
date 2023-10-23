<?php
function handle_post_object($request)
{
    $fields = $request->get_body_params()['meta'];
    $post_id = $request['id'];

    if (!$fields) {
      return true;
    }

    foreach ($fields as $raw_field_name => $value) {
        $field_name = preg_replace('#^_WT_TMP_#', '', $raw_field_name);
        $field_obj = acf_maybe_get_field($field_name, false, false);

        if (is_array($field_obj) && $field_obj['type'] == 'post_object') {
            set_post_object_field($post_id, $field_name, $value, $field_obj['post_type']);
        }
    }

    return true;
}

function set_post_object_field($post_id, $field_name, $value, $target_post_type)
{
    // Update post object field
    // Airtable API sends value as array
    $titles = is_array($value) ? $value : explode(',', $value);
    $ids = array();
    foreach ($titles as $raw_title) {
        // Trim &nbsp; and other whitespace
        $title = str_replace(chr(194) . chr(160), '', $raw_title);
        $title = trim($title);
        // Find post ID assigned by Wordpress given the post title
        $post = get_page_by_title($title, OBJECT, $target_post_type);
        if ($post != null) {
            array_push($ids, $post->ID);
        }
    }
    // Set field to array of post ID's
    update_field($field_name, $ids, $post_id);
}
