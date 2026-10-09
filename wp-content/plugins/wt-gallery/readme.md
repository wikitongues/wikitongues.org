# Wikitongues gallery plugin

Renders every grid of posts on the site. The full reference, covering parameters, templates, pagination and editorial gallery rows, is [docs/gallery.md](../../../docs/gallery.md) in the repository root.

## Quick example

```php
echo create_gallery_instance(
	wt_gallery_params(
		array(
			'title'          => 'Other videos of ' . $language_name,
			'post_type'      => 'videos',
			'columns'        => 5,
			'posts_per_page' => 5,
			'orderby'        => 'rand',
			'meta_key'       => 'featured_languages',
			'meta_value'     => $language_post_ids, // comma-separated post IDs, not ISO codes
			'exclude_self'   => 'true',
		)
	)
);
```

- `wt_gallery_params()` lives in the theme (`includes/template/template-helpers.php`) and fills in every default.
- Boolean parameters are strings: `'true'` or `'false'`.
- A new post type needs a template at `includes/templates/gallery-{post_type}.php`.
