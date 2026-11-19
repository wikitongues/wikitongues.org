<?php
use WP_Mock\Tools\TestCase;

/**
 * Covers wt_gallery_invert_id_lists(), which turns rows of serialized ID
 * arrays (ACF relationship / post_object fields) into target ID => post IDs.
 *
 * Gallery cards used to run one `meta_value LIKE` scan per card to answer
 * "which languages reference this territory" and "which videos feature this
 * language". The cards now read each meta key once per request and invert it
 * here, so this mapping decides every card's count and preview.
 */
class GalleryInvertIdListsTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction(
			'maybe_unserialize',
			array(
				'return' => function ( $value ) {
					$unserialized = is_string( $value ) ? @unserialize( $value ) : false; // phpcs:ignore
					return false === $unserialized ? $value : $unserialized;
				},
			)
		);
	}

	/**
	 * @param int|string $post_id    Post ID.
	 * @param mixed      $meta_value Value to serialize as stored.
	 * @return object
	 */
	private function row( $post_id, $meta_value ): object {
		return (object) array(
			'post_id'    => (string) $post_id,
			'meta_value' => is_string( $meta_value ) ? $meta_value : serialize( $meta_value ), // phpcs:ignore
		);
	}

	public function test_inverts_acf_string_id_arrays() {
		$rows = array(
			$this->row( 10, array( '500', '501' ) ),
			$this->row( 11, array( '501' ) ),
		);
		$this->assertSame(
			array(
				500 => array( 10 ),
				501 => array( 10, 11 ),
			),
			wt_gallery_invert_id_lists( $rows )
		);
	}

	public function test_keeps_row_order_so_callers_can_presort_by_title() {
		$rows = array(
			$this->row( 30, array( '500' ) ),
			$this->row( 10, array( '500' ) ),
			$this->row( 20, array( '500' ) ),
		);
		$this->assertSame( array( 500 => array( 30, 10, 20 ) ), wt_gallery_invert_id_lists( $rows ) );
	}

	public function test_matches_whole_ids_not_substrings() {
		// The old card query was LIKE '%500%', which also matched 1500 or 5001.
		$rows = array( $this->row( 10, array( '1500', '5001' ) ) );
		$this->assertArrayNotHasKey( 500, wt_gallery_invert_id_lists( $rows ) );
	}

	public function test_lists_each_post_once_per_target() {
		$rows = array(
			$this->row( 10, array( '500', '500' ) ),
			$this->row( 10, array( '500' ) ),
		);
		$this->assertSame( array( 500 => array( 10 ) ), wt_gallery_invert_id_lists( $rows ) );
	}

	public function test_skips_empty_text_and_non_numeric_values() {
		$rows = array(
			$this->row( 10, array() ),
			$this->row( 11, '' ),
			$this->row( 12, 'France, Spain' ),
			$this->row( 13, array( 'France', '0', '' ) ),
		);
		$this->assertSame( array(), wt_gallery_invert_id_lists( $rows ) );
	}

	public function test_empty_input() {
		$this->assertSame( array(), wt_gallery_invert_id_lists( array() ) );
	}
}
