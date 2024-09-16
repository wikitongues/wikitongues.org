<?php
// custom search filter
add_filter('pre_get_posts', 'searchfilter');
function searchfilter($query)
{
    if ($query->is_search && !is_admin()) {
        $languages_search = get_query_var('s');
        if (empty($query->query_vars['post_type']) && !empty($languages_search)) {
            // only display results from these post types
            $query->set('post_type', array('languages', 'videos'));
            $query->set('order', 'ASC');

            // clear the default search query
            $query->set('s', '');

            $iso_code_regex = '#^w?[a-z]{3}$#';  // Also accounts for 4-letter Wikitongues-assigned codes
            $glottocode_regex = '#^[[:alnum:]]{4}\d{4}$#';
            preg_match($iso_code_regex, $languages_search, $iso_match);
            preg_match($glottocode_regex, $languages_search, $glottocode_match);

            if ($iso_match) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'iso_code',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'standard_name',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    'relation' => 'OR'
                ));
            } else if ($glottocode_match) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'glottocode',
                        'value' => $languages_search,
                        'compare' => '='
                    )
                ));
            } else {
                $query->set('meta_query', array(
                    array(
                        'key' => 'standard_name',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'alternate_names',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'nations_of_origin',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'writing_systems',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'linguistic_genealogy',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'video_title',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    'relation' => 'OR'
                ));
            }
        }
        // $query->set('post_type',array('languages','videos', 'lexicons', 'resources'));

        // for languages post type, search by X fields

            // post_title - identical
            // iso_code - identical
            // glottocode - identical
            // standard_name - like
            // alternate_names - like
            // nations_of_origin - like
            // linguistic_genealogy - identical
            // anything else included in Scott's code from January

        // for videos post type, search by Y fields

            // video_title
            // featured_languages
            // consider for later version: new fields that pull standard_name and alternate_names from featured_languages

        // for lexicons post type, search by Z fields

            // post_title

        // for resrouces post type search by Q fields
    }
    return $query;
}
