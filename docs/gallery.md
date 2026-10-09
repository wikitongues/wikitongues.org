# Galleries (wt-gallery)

The `wt-gallery` plugin (`wp-content/plugins/wt-gallery/`) renders every grid of posts on the site: "Other videos of…", "Languages of…", fellows by cohort, the people sections on the Board and Staff pages, careers, and any gallery row an editor adds to a page. It turns a set of parameters into a `WP_Query`, renders each post through a template for its post type, and paginates in place over AJAX.

## Table of Contents

- [Adding a gallery](#adding-a-gallery)
- [Parameters](#parameters)
- [custom_class does two jobs](#custom_class-does-two-jobs)
- [Templates per post type](#templates-per-post-type)
- [Card styling is opt-in](#card-styling-is-opt-in)
- [Pagination and random order](#pagination-and-random-order)
- [Gallery rows in editorial content](#gallery-rows-in-editorial-content)
- [Where galleries are used](#where-galleries-are-used)
- [Tests](#tests)
- [Known gaps](#known-gaps)
- [History](#history)

---

## Adding a gallery

There are three ways in:

**1. In a template.** `wt_gallery_params()` (theme, `includes/template/template-helpers.php`) fills in every parameter's default, so a call site states only what differs. Its PHPDoc array shape lets PHPStan check keys and types.

```php
echo create_gallery_instance(
	wt_gallery_params(
		array(
			'title'          => 'Languages of ' . wt_prefix_the( get_the_title() ),
			'post_type'      => 'languages',
			'columns'        => 3,
			'posts_per_page' => 6,
			'selected_posts' => implode( ',', $language_ids ),
			'link_out'       => add_query_arg( 'territory', $slug, get_post_type_archive_link( 'languages' ) ),
		)
	)
);
```

**2. In the admin.** Add a *Gallery* row to a page's editorial content (see [Gallery rows in editorial content](#gallery-rows-in-editorial-content)).

**3. In post content.** Use the `[custom_gallery]` shortcode, which takes the same attributes. `create_gallery_instance()` builds that shortcode string and runs it.

Boolean parameters are strings: `'true'` or `'false'`.

---

## Parameters

Defaults differ by entry point. Template calls get `wt_gallery_params()`'s defaults, listed below. A bare `[custom_gallery]` shortcode falls back to the plugin's own: `languages`, 3 columns, 6 per page, newest first, no pagination, `exclude_self="true"`.

| Parameter | `wt_gallery_params()` default | Does |
|---|---|---|
| `title` | `''` | Heading above the grid. Rendered as-is, so escape anything that didn't come from code. |
| `subtitle` | `''` | Paragraph under the heading |
| `show_total` | `'true'` | Adds the result count ("403 Languages") to the heading |
| `link_out` | `''` | URL for a *See all* link, shown only when there are more results than `posts_per_page` |
| `post_type` | `''` | What to list. Needs a template at `includes/templates/gallery-{post_type}.php` |
| `columns` | `5` | Grid columns |
| `posts_per_page` | `100` | Page size |
| `orderby` / `order` | `'title'` / `'asc'` | Any `WP_Query` ordering. `rand` is seeded per render; `post__in` keeps the `selected_posts` order |
| `pagination` | `'true'` | Adds AJAX pagination when there is more than one page |
| `selected_posts` | `''` | Comma-separated post IDs to limit the query to. `wt_gallery_selected_post_ids()` drops zero and non-numeric entries, so a bad value can't widen the query |
| `meta_key` / `meta_value` | `''` | Filter by a field; comma-separated values are OR'd (see below) |
| `taxonomy` / `term` | `''` | Filter by term slug; comma-separated slugs are OR'd, child terms included. Ignored if `meta_key` is set |
| `exclude_self` | `'false'` | `'true'` leaves out the post being viewed, when it is the same type ("Other videos" on a video page) |
| `display_blank` | `'false'` | `'true'` renders an empty gallery with "There are no {type} to display—yet.", linking to `/submit-a-{type}` |
| `custom_class` | `''` | Wrapper class, and a render mode for some types; see [below](#custom_class-does-two-jobs) |

**Meta filters by field**, in `build_gallery_query_args()`:

| `meta_key` | How `meta_value` matches |
|---|---|
| `featured_languages`, `fellow_language` | Language post IDs, matched against ACF's serialized arrays (`LIKE '"42"'`) |
| `nations_of_origin` | Exact `=`. The field is comma-separated text, so combined values miss (#241) |
| Anything else | `LIKE` |

---

## custom_class does two jobs

1. **Styling.** It is appended to the wrapper's classes: `custom-gallery {post_type} {custom_class}`.
2. **Render mode.** Two templates branch on it:

| Post type | Value | Renders |
|---|---|---|
| `fellows` | empty, or containing `full` | The standard fellow card |
| | `display` | A card with the fellow's banner copy (single language pages) |
| | `custom fundraiser` | A fundraiser list item (giving-campaign page) |
| `people` | `wide` (default) | Portrait beside the full profile (`modules/people/person--wide.php`) |
| | `list` | Name and role only (`person--list.php`) |
| | `grid` | A compact card (`person--grid.php`) |

It isn't exposed to editors, on purpose (#421). Gallery rows set it themselves: `full` for most types, and the row's *Layout* field for people. An editor-facing style picker would be new design work, not a missing setting.

---

## Templates per post type

`render_gallery_items()` loops the query and includes `includes/templates/gallery-{post_type}.php` for each post. A type without a template renders "No template found".

| Template | Card shows |
|---|---|
| `gallery-languages.php` | The standard name, the ISO code, and a thumbnail from one random video of the language, which costs one extra query per card |
| `gallery-videos.php` | Video thumbnail and title |
| `gallery-fellows.php` | Banner image, name, language, categories, location and cohort (see the modes above) |
| `gallery-people.php` | Hands off to the theme's `modules/people/person--{mode}.php`, so people look the same everywhere |
| `gallery-territories.php` | The territory, its language count and a short preview, using two small queries per card (#491) |
| `gallery-lexicons.php`, `gallery-resources.php`, `gallery-careers.php`, `gallery-faq.php` | Simpler items for those types |

The card title comes from `get_custom_title()` in `wt-gallery.php`: `video_title` for videos, first plus last name for fellows, `standard_name` for languages, and the post title for people. Images come from `get_custom_image()`.

---

## Card styling is opt-in

A template that renders a card adds `gallery-item--card` to its `<li>`, and the card look in `stylus/require/gallery.styl` keys off that class. People items carry `gallery-item--person` instead and get their styling from the person modules.

Don't style `.gallery-item` on its own. An earlier rule did, with a `:not()` exclusion for people, and it silently outranked the per-type overrides and flattened the fellow cards (#623). Opting in keeps specificity flat, and correctness doesn't depend on the order of `@require`s in `main.styl`.

---

## Pagination and random order

- **Unique containers.** Each render gets an ID, `gallery_{n}` (a counter per request), so several paginated galleries can share a page.
- **Paging.** Clicking a page number runs `js/custom-gallery-ajax.js` (jQuery), which requests that page from `admin-ajax.php` (`action=load_custom_gallery`). The response replaces the container's contents. Two pagination bars render: a 9-page one, and a 5-page one for mobile.
- **Random order is seeded (#380).** When `orderby` is `rand`, `custom_gallery()` picks a random seed that every page request for that gallery reuses. `build_gallery_query_args()` turns it into `ORDER BY RAND(seed)`, so every page draws from the same shuffle. A full page reload reshuffles.

---

## Gallery rows in editorial content

The *Gallery* layout (`gallery_layout`) of **Global: Editorial page** lets editors add a gallery to any page that has editorial content. `modules/flexible-content/gallery-layout.php` renders it.

| Field | Values | Becomes |
|---|---|---|
| Type (`custom_gallery_type`) | careers, faq, fellows, languages, people, resources, videos | `post_type` |
| Title | text | `title` |
| Columns, Posts per page | numbers | `columns`, `posts_per_page` |
| Paginate | true, false | `pagination` |
| Order by | Random, Title, Date, Selection order | `orderby` (`rand`, `title`, `date`, `post__in`) |
| Posts (`custom_gallery_post`) | hand-picked posts | `selected_posts` |
| Layout | wide, list, grid (people only) | `custom_class` |
| People type | terms (people only) | `taxonomy` = `people-type`, `term` = slugs |
| Career type | terms (careers only) | `taxonomy` = `career_type`, `term` = slugs |

Other types always get `custom_class = 'full'`. Rows saved before *Order by* existed fall back to Random. To show hand-picked posts in the order they were picked, set *Order by* to *Selection order*.

---

## Where galleries are used

About two dozen template call sites (`grep -rn "create_gallery_instance(" wp-content/themes/blankslate-child`), plus every editorial gallery row, which is stored in the database. The main ones:

- **Language pages:** the language's videos, fellows, lexicons and resources, and "other languages from" each of its territories.
- **Video pages:** other videos of the same language.
- **Territory and region pages:** fellows, languages and territories, each with a *See all* link to a filtered archive.
- **Filtered archives:** `archive-languages.php`, `archive-videos.php`, `archive-fellows.php` and `archive-territories.php`.
- **Fellowship:** fellows by cohort (`template-revitalization-fellows.php`) and by category (`taxonomy-fellow-category.php`).
- **People pages:** the Board, Advisors and Staff sections (editorial rows), and careers galleries.
- **Donate page:** case-study fellows (`template-donate.php`).

`docs/gallery-inventory.csv` and `docs/gallery-inventory-main.csv` record every template gallery's settings before and after the March 2026 refactor (#536). They are a historical snapshot and are no longer updated.

---

## Tests

| Test | Covers |
|---|---|
| `GalleryQueryArgsTest` | `build_gallery_query_args()`: the meta-key branches and the random seed |
| `GallerySelectedPostsTest` | `wt_gallery_selected_post_ids()` |
| `GalleryPaginationTest` | `generate_gallery_pagination()` |
| `GetDomainFromUrlTest` | `getDomainFromUrl()` in `includes/helpers.php` |

---

## Known gaps

- **Empty galleries** show a blank state rather than falling back to something useful, like the territory's languages or a random set (#377).
- **Visitors can't filter, sort or search within a gallery (#378).** Design this together with the enhanced search page.
- **Language cards cost one extra query each** for the video thumbnail.
- **Continent pages** build one fellows `LIKE` clause per territory (#533).
- **Pagination needs jQuery**; it could move to vanilla JavaScript when the front-end build changes.
- **Two sets of defaults.** The plugin and `wt_gallery_params()` disagree on defaults, which makes a bare shortcode behave differently from a template call. Moving the theme helper's defaults into the plugin would leave one source.

---

## History

| When | Change | PRs |
|---|---|---|
| 2026-02-19 | Raw SQL removed from the featured-languages branch; `build_gallery_query_args()` extracted and unit-tested | #438 |
| 2026-02-21 | `link_out` and filtered archive pages | #462 |
| 2026-02-28 | Territory cards no longer exhaust memory on large regions | #491 |
| 2026-03-06 | `wt_gallery_params()` adopted by every call site | #536 |
| 2026-03-06 | `include_children` made explicit | #538 |
| 2026-09-08 | Dead `custom_gallery_id` field removed (#421) | #612 |
| 2026-09-08 | Undefined `$post_ids` warning fixed | #613 |
| 2026-09-08 | Seeded random pagination (#380) | #615 |
| 2026-09-09 | People galleries; `selected_posts` keeps its order; new gallery-row fields | #622 |
| 2026-09-11 | Card treatment made opt-in | #623 |
