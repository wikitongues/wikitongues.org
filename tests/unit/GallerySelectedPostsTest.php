<?php
use WP_Mock\Tools\TestCase;

/**
 * Covers wt_gallery_selected_post_ids(), which backs the gallery's
 * `selected_posts` attribute.
 *
 * The parsing used to live inline in custom_gallery(), where it was assigned
 * into $args before $args existed and then overwritten wholesale — so a curated
 * selection kept its membership but silently lost its order.
 */
class GallerySelectedPostsTest extends TestCase {

	public function test_parses_comma_separated_ids() {
		$this->assertSame( array( 12, 7, 99 ), wt_gallery_selected_post_ids( '12,7,99' ) );
	}

	public function test_preserves_the_given_order() {
		// Selection order is the point: pairing this with orderby="post__in"
		// is how a curated list keeps the order an editor chose.
		$this->assertSame( array( 99, 7, 12 ), wt_gallery_selected_post_ids( '99,7,12' ) );
	}

	public function test_trims_surrounding_whitespace() {
		$this->assertSame( array( 12, 7 ), wt_gallery_selected_post_ids( ' 12 , 7 ' ) );
	}

	public function test_casts_values_to_int() {
		$this->assertSame( array( 42 ), wt_gallery_selected_post_ids( '42abc' ) );
	}

	public function test_drops_non_numeric_and_zero_entries() {
		// A malformed entry must not become 0 and widen the query.
		$this->assertSame( array( 12, 7 ), wt_gallery_selected_post_ids( '12,,abc,0,7' ) );
	}

	public function test_empty_string_returns_empty_array() {
		$this->assertSame( array(), wt_gallery_selected_post_ids( '' ) );
	}

	public function test_whitespace_only_returns_empty_array() {
		$this->assertSame( array(), wt_gallery_selected_post_ids( '   ' ) );
	}

	public function test_non_string_returns_empty_array() {
		// shortcode_atts() leaves the default array() in place when the
		// attribute is absent, so the helper must tolerate a non-string.
		$this->assertSame( array(), wt_gallery_selected_post_ids( array() ) );
	}
}
