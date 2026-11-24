<?php
use WP_Mock\Tools\TestCase;

class ShortLinksTest extends TestCase {

	public function test_ai_points_to_native_language_ai() {
		$this->assertSame( '/revitalization/resources/native-language-ai/', wt_short_link_target( 'ai' ) );
		$this->assertSame( '/language-rights/', wt_short_link_target( '/rights/' ) );
	}

	public function test_slashes_and_case_are_ignored() {
		$expected = '/revitalization/resources/native-language-ai/';
		$this->assertSame( $expected, wt_short_link_target( '/ai/' ) );
		$this->assertSame( $expected, wt_short_link_target( 'AI' ) );
	}

	public function test_other_paths_are_not_short_links() {
		$this->assertNull( wt_short_link_target( 'aia' ) );
		$this->assertNull( wt_short_link_target( 'ai/extra' ) );
		$this->assertNull( wt_short_link_target( '' ) );
	}

	public function test_every_target_is_a_root_relative_path() {
		foreach ( wt_short_links() as $short => $target ) {
			$this->assertMatchesRegularExpression( '#^/[a-z0-9/-]+/$#', $target, "Short link /{$short}" );
		}
	}
}
