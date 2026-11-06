<?php
use WP_Mock\Tools\TestCase;

class ContentBundleHelpersTest extends TestCase {

	const LOCAL = 'http://localhost:8888/wikitongues';
	const PROD  = 'https://wikitongues.org';

	public function test_tokenizes_home_url_in_nested_values() {
		$value = array(
			'text' => '<a href="http://localhost:8888/wikitongues/revitalization/">Fellowship</a>',
			'link' => array(
				'title' => 'Donate',
				'url'   => 'http://localhost:8888/wikitongues/donate/',
			),
			'rows' => 3,
		);

		$out = wt_bundle_tokenize_urls( $value, self::LOCAL . '/' );

		$this->assertSame( '<a href="{{home}}/revitalization/">Fellowship</a>', $out['text'] );
		$this->assertSame( '{{home}}/donate/', $out['link']['url'] );
		$this->assertSame( 3, $out['rows'] );
	}

	public function test_detokenizes_to_target_home() {
		$this->assertSame(
			'https://wikitongues.org/revitalization/',
			wt_bundle_detokenize_urls( '{{home}}/revitalization/', self::PROD )
		);
	}

	public function test_round_trip_moves_links_between_environments() {
		$local = 'See <a href="http://localhost:8888/wikitongues/languages/igl/">Igala</a>.';
		$moved = wt_bundle_detokenize_urls( wt_bundle_tokenize_urls( $local, self::LOCAL ), self::PROD );

		$this->assertSame( 'See <a href="https://wikitongues.org/languages/igl/">Igala</a>.', $moved );
	}

	public function test_leaves_external_urls_alone() {
		$url = 'https://wikitongues.donorsupport.co/-/XTRAFEBU';
		$this->assertSame( $url, wt_bundle_tokenize_urls( $url, self::PROD ) );
	}

	public function test_ref_kind_maps_acf_types() {
		$this->assertSame( 'post', wt_bundle_ref_kind( 'post_object' ) );
		$this->assertSame( 'post', wt_bundle_ref_kind( 'relationship' ) );
		$this->assertSame( 'attachment', wt_bundle_ref_kind( 'image' ) );
		$this->assertSame( 'attachment', wt_bundle_ref_kind( 'gallery' ) );
		$this->assertSame( 'term', wt_bundle_ref_kind( 'taxonomy' ) );
		$this->assertNull( wt_bundle_ref_kind( 'wysiwyg' ) );
	}

	public function test_ref_ids_normalises_scalars_arrays_and_empties() {
		$this->assertSame( array( 42 ), wt_bundle_ref_ids( '42' ) );
		$this->assertSame( array( 1, 2 ), wt_bundle_ref_ids( array( '1', '2' ) ) );
		$this->assertSame( array(), wt_bundle_ref_ids( '' ) );
		$this->assertSame( array(), wt_bundle_ref_ids( false ) );
		$this->assertSame( array( 7 ), wt_bundle_ref_ids( array( '', '7', 'x' ) ) );
	}
}
