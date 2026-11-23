<?php
use WP_Mock\Tools\TestCase;

class DisplayTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'esc_attr' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_html' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'esc_url' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
	}

	public function test_a_collapsed_letter_shows_its_first_paragraph() {
		$html = wt_rights_letter(
			array(
				'html' => '<p>One.</p><p>Two.</p>',
				'name' => 'A. Director',
				'role' => 'Director',
			),
			true
		);

		$this->assertStringStartsWith( '<div class="wt-rights__letter wt_prose"><p>One.</p><details', $html );
		$this->assertStringContainsString( '<summary>Read the full letter</summary><p>Two.</p>', $html );
		$this->assertStringContainsString( '<strong>A. Director</strong><br>Director', $html );
	}

	public function test_a_one_paragraph_letter_has_nothing_to_fold() {
		$html = wt_rights_letter(
			array(
				'html' => '<p>One.</p>',
				'name' => '',
				'role' => '',
			),
			true
		);

		$this->assertSame( '<div class="wt-rights__letter wt_prose"><p>One.</p></div>', $html );
	}

	public function test_links_line_falls_back_to_the_bare_url() {
		$html = wt_rights_links_line(
			array(
				array(
					'url'   => 'https://example.org/a',
					'label' => 'A',
				),
				array(
					'url'   => 'https://www.example.org/b',
					'label' => '',
				),
			)
		);

		$this->assertSame( '<p class="wt-rights__links"><a href="https://example.org/a" rel="noopener">A</a> · <a href="https://www.example.org/b" rel="noopener">example.org/b</a></p>', $html );
		$this->assertSame( '', wt_rights_links_line( array() ) );
	}

	public function test_the_trend_badge_carries_its_value() {
		$this->assertSame( '<span class="wt-rights__trend" data-trend="negative">Negative</span>', wt_rights_trend_badge( 'negative' ) );
		$this->assertSame( '', wt_rights_trend_badge( '' ) );
	}
}
