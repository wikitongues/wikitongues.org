# Wikitongues gallery features

## Simple implementation

``` php
  // Gallery
  $params = [
    'title' => 'Other videos of ' . $language_names,
    'post_type' => 'videos',
    'custom_class' => 'full',
    'columns' => 5,
    'posts_per_page' => 5,
    'orderby' => 'rand',
    'order' => 'asc',
    'pagination' => 'false',
    'meta_key' => 'featured_languages',
    'meta_value' => $language_iso_codes,
    'selected_posts' => '',
    'display_blank' => '',
    'taxonomy' => '',
    'term' => ''
  ];
  echo create_gallery_instance($params);
```

Note: Boolean type fields must be strings instead

## flexible post types:

* `languages`
* `videos`
* `fellows`
* `careers`
* `resources`

Any post type is accessible to the plugin. However custom templates will be required in order to render new types. Templates follow the name pattern gallery-[type].php

# Design
* Custom classes
* Grid control via number of columns and count of posts to load per page
* Sorting with `orderby` and `order`
* `Pagination`

# Filtering

* Filter my post metadata with `meta_key` and `meta_value`
* Filter by `taxonomy` and `term`
* Return arbitrary posts with `selected_posts`