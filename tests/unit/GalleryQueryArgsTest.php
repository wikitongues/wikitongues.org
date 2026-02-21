<?php
use WP_Mock\Tools\TestCase;

class GalleryQueryArgsTest extends TestCase {

	/**
	 * Returns a full atts array with defaults, merged with any overrides.
	 *
	 * @param array $overrides Key/value pairs to override.
	 * @return array
	 */
	private function base_atts( array $overrides = array() ): array {
		return array_merge(
			array(
				'post_status'    => 'publish',
				'post_type'      => 'videos',
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => '',
				'meta_value'     => '',
				'paged'          => 1,
				'taxonomy'       => '',
				'term'           => '',
				'exclude_self'   => 'false',
			),
			$overrides
		);
	}

	/**
	 * Mocks wp_parse_args to return the given array unchanged.
	 *
	 * @param array $atts The merged atts array to return.
	 * @return void
	 */
	private function mock_wp_parse_args( array $atts ): void {
		WP_Mock::userFunction( 'wp_parse_args', array( 'return' => $atts ) );
	}

	public function test_featured_languages_single_id_builds_like_meta_query() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'featured_languages',
				'meta_value' => '42',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args       = build_gallery_query_args( $atts );
		$meta_query = $args['meta_query'];

		$this->assertSame( 'OR', $meta_query['relation'] );
		$this->assertCount( 2, $meta_query ); // relation + 1 clause
		$this->assertSame( 'featured_languages', $meta_query[0]['key'] );
		$this->assertSame( '"42"', $meta_query[0]['value'] );
		$this->assertSame( 'LIKE', $meta_query[0]['compare'] );
	}

	public function test_featured_languages_multiple_ids_build_or_meta_query() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'featured_languages',
				'meta_value' => '42, 99',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args       = build_gallery_query_args( $atts );
		$meta_query = $args['meta_query'];

		$this->assertSame( 'OR', $meta_query['relation'] );
		$this->assertCount( 3, $meta_query ); // relation + 2 clauses
		$this->assertSame( '"42"', $meta_query[0]['value'] );
		$this->assertSame( '"99"', $meta_query[1]['value'] );
	}

	public function test_featured_languages_id_is_cast_to_int() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'featured_languages',
				'meta_value' => '42abc',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args = build_gallery_query_args( $atts );

		$this->assertSame( '"42"', $args['meta_query'][0]['value'] );
	}

	public function test_fellow_language_wraps_ids_in_quotes() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'fellow_language',
				'meta_value' => '7, 8',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args    = build_gallery_query_args( $atts );
		$clauses = array_filter( $args['meta_query'], 'is_array' );
		$values  = array_column( $clauses, 'value' );

		$this->assertContains( '"7"', $values );
		$this->assertContains( '"8"', $values );
	}

	public function test_nations_of_origin_uses_equals_compare() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'nations_of_origin',
				'meta_value' => 'Nigeria',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args = build_gallery_query_args( $atts );

		$this->assertSame( '=', $args['meta_query'][0]['compare'] );
	}

	public function test_linguistic_genealogy_uses_equals_compare() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'linguistic_genealogy',
				'meta_value' => 'Indo-European',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$result = build_gallery_query_args( $atts );
		$this->assertSame( '=', $result['meta_query'][0]['compare'] );
	}

	public function test_generic_meta_key_multiple_values_build_or_meta_query() {
		$atts = $this->base_atts(
			array(
				'meta_key'   => 'fellow_year',
				'meta_value' => '2022, 2023',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args       = build_gallery_query_args( $atts );
		$meta_query = $args['meta_query'];

		$this->assertSame( 'OR', $meta_query['relation'] );
		$this->assertCount( 2, array_filter( $meta_query, 'is_array' ) );
	}

	public function test_tax_query_single_term_builds_correct_structure() {
		$atts = $this->base_atts(
			array(
				'taxonomy' => 'fellow-category',
				'term'     => 'revitalization',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args = build_gallery_query_args( $atts );

		$this->assertSame( 'fellow-category', $args['tax_query'][0]['taxonomy'] );
		$this->assertSame( 'slug', $args['tax_query'][0]['field'] );
		$this->assertSame( 'revitalization', $args['tax_query'][0]['terms'] );
	}

	public function test_tax_query_comma_separated_terms_build_or_query() {
		$atts = $this->base_atts(
			array(
				'taxonomy' => 'fellow-category',
				'term'     => 'revitalization, preservation',
			)
		);
		$this->mock_wp_parse_args( $atts );

		$args = build_gallery_query_args( $atts );

		$this->assertSame( 'OR', $args['tax_query']['relation'] );
		$this->assertCount( 2, array_filter( $args['tax_query'], 'is_array' ) );
	}

	public function test_exclude_self_adds_post_not_in_for_matching_post_type() {
		$atts = $this->base_atts(
			array(
				'post_type'    => 'videos',
				'exclude_self' => 'true',
			)
		);
		$this->mock_wp_parse_args( $atts );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'videos' ) );
		WP_Mock::userFunction( 'get_the_ID', array( 'return' => 123 ) );

		$args = build_gallery_query_args( $atts );

		$this->assertSame( array( 123 ), $args['post__not_in'] );
	}

	public function test_exclude_self_skipped_when_post_type_differs() {
		$atts = $this->base_atts(
			array(
				'post_type'    => 'videos',
				'exclude_self' => 'true',
			)
		);
		$this->mock_wp_parse_args( $atts );
		WP_Mock::userFunction( 'get_post_type', array( 'return' => 'languages' ) );

		$args = build_gallery_query_args( $atts );

		$this->assertArrayNotHasKey( 'post__not_in', $args );
	}
}
