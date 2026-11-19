<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Renderer;

class RendererTest extends TestCase {

	private const US = 'recErwkQpwxGCXW5F';

	private const GUAM = 'recguDPgV3cIXLO34';

	private const NMI = 'rec8BH6e7SD1A1J5I';

	private const SVG = '<svg viewBox="0 0 400 300" role="img"><title id="t-k">Map of Guam</title><desc id="d-k">Map centred on Guam.</desc>'
		. '<g class="world"><image class="shade" href="shade/k.webp" width="400" height="300"/><image class="x" href="https://example.org/a.webp"/>'
		. '<g class="hls"><path class="hl" d="M0 0" data-place="recguDPgV3cIXLO34"/><path class="hl" d="M1 1" data-place="rec8BH6e7SD1A1J5I"/></g>'
		. '<g class="claims"><path class="disputed hatch hatch-claimed" d="M2 2" data-claimants="rec8BH6e7SD1A1J5I recguDPgV3cIXLO34"/>'
		. '<path class="disputed hatch hatch-claimed" d="M3 3" data-claimants="recZZZZZZZZZZZZZZ"/><path class="disputed hatch hatch-land" d="M4 4"/></g>'
		. '<g class="markers"><circle class="marker" data-place="rec8BH6e7SD1A1J5I" r="7"/></g></g></svg>';

	public function test_origins_are_primary_and_the_rest_secondary() {
		$this->assertSame(
			array(
				self::US   => 'primary',
				self::GUAM => 'secondary',
			),
			Renderer::roles( array( self::US, self::GUAM ), array( self::US ) )
		);
	}

	public function test_every_place_is_primary_when_none_is_an_origin() {
		$this->assertSame(
			array(
				self::GUAM => 'primary',
				self::NMI  => 'primary',
			),
			Renderer::roles( array( self::GUAM, self::NMI ), array( self::US ) ),
			'Chamorro: its nation is the United States, its map Guam and the NMI'
		);
	}

	public function test_describe_lists_primary_then_secondary_places() {
		$names = array(
			self::US   => 'United States',
			self::GUAM => 'Guam',
			self::NMI  => 'Northern Mariana Islands',
		);

		$this->assertSame(
			'Map centred on Guam and Northern Mariana Islands.',
			Renderer::describe(
				array(
					self::NMI  => 'primary',
					self::GUAM => 'primary',
				),
				$names
			)
		);
		$this->assertSame(
			'Map centred on United States, with Guam and Northern Mariana Islands also marked.',
			Renderer::describe(
				array(
					self::US   => 'primary',
					self::GUAM => 'secondary',
					self::NMI  => 'secondary',
				),
				$names
			)
		);
		$this->assertSame( '', Renderer::describe( array( self::US => 'primary' ), array() ) );
	}

	public function test_caption_counts_long_lists_and_sorts_past_the() {
		$names = array(
			self::US   => 'the United States',
			self::GUAM => 'Guam',
			self::NMI  => 'the Northern Mariana Islands',
		);
		$roles = array(
			self::US   => 'primary',
			self::GUAM => 'primary',
			self::NMI  => 'primary',
		);

		$this->assertSame(
			'Map centred on Guam, the Northern Mariana Islands, and the United States.',
			Renderer::caption( $roles, $names ),
			'three places are still named, sorted without "the"'
		);

		$many  = array( self::US => 'Spain' );
		$roles = array( self::US => 'primary' );
		for ( $i = 0; $i < 5; $i++ ) {
			$id           = 'recSECONDARY' . $i . 'AAAA';
			$many[ $id ]  = 'Place ' . $i;
			$roles[ $id ] = 'secondary';
		}
		$this->assertSame( 'Map centred on Spain, with 5 other places also marked.', Renderer::caption( $roles, $many ) );
		$this->assertStringContainsString( 'Place 0, Place 1', Renderer::describe( $roles, $many ), 'the description still lists them all' );

		$all = array_fill_keys( array_keys( $roles ), 'primary' );
		$this->assertSame( 'Map of the 6 places where it is spoken.', Renderer::caption( $all, $many ) );
	}

	public function test_decorate_adds_roles_urls_and_text() {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$this->markTestSkipped( 'WordPress HTML API not available (no local install at the repository root).' );
		}
		WP_Mock::userFunction( 'wp_kses_uri_attributes' )->andReturn( array( 'href', 'src' ) );
		WP_Mock::userFunction( 'esc_url' )->andReturnUsing( fn( $url ) => $url );
		WP_Mock::userFunction( 'wp_has_noncharacters' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );

		$out = Renderer::decorate(
			self::SVG,
			array(
				self::GUAM => 'primary',
				self::NMI  => 'secondary',
			),
			'https://wikitongues.org/wp-content/plugins/wt-maps/maps/',
			'Where Chamorro is spoken',
			'Map centred on Guam & the <NMI>.'
		);

		$this->assertStringContainsString( '<title id="t-k">Where Chamorro is spoken</title>', $out );
		$this->assertStringContainsString( '<desc id="d-k">Map centred on Guam &amp; the &lt;NMI&gt;.</desc>', $out );
		$this->assertStringContainsString( 'href="https://wikitongues.org/wp-content/plugins/wt-maps/maps/shade/k.webp"', $out );
		$this->assertStringContainsString( 'href="https://example.org/a.webp"', $out, 'absolute hrefs are left alone' );
		$this->assertStringContainsString( 'class="hl primary" d="M0 0"', $out );
		$this->assertStringContainsString( 'class="hl secondary" d="M1 1"', $out );
		$this->assertStringContainsString( 'class="marker secondary"', $out );
		$this->assertStringContainsString( 'class="disputed hatch hatch-primary" d="M2 2"', $out, 'strongest claimant wins' );
		$this->assertStringContainsString( 'class="disputed hatch hatch-land" d="M3 3"', $out, 'no claimant on the map' );
		$this->assertStringNotContainsString( 'hatch-claimed', $out );
	}
}
