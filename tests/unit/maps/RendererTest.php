<?php
use WP_Mock\Tools\TestCase;
use WT\Maps\Renderer;

class RendererTest extends TestCase {

	private const US = 'recErwkQpwxGCXW5F';

	private const GUAM = 'recguDPgV3cIXLO34';

	private const NMI = 'rec8BH6e7SD1A1J5I';

	private const SVG = '<svg viewBox="0 0 400 300" role="img" aria-labelledby="t-k d-k"><title id="t-k">Map of Guam</title><desc id="d-k">Map centred on Guam.</desc>'
		. '<g class="world"><image class="shade" href="shade/k.webp" width="400" height="300"/><image class="x" href="https://example.org/a.webp"/>'
		. '<g class="hls"><path class="hl" d="M0 0" data-place="recguDPgV3cIXLO34"/><path class="hl" d="M1 1" data-place="rec8BH6e7SD1A1J5I"/></g>'
		. '<g class="claims"><path class="disputed hatch hatch-claimed" d="M2 2" data-claimants="rec8BH6e7SD1A1J5I recguDPgV3cIXLO34"/>'
		. '<path class="disputed hatch hatch-claimed" d="M3 3" data-claimants="recZZZZZZZZZZZZZZ"/><path class="disputed plain" d="M4 4"/>'
		. '<path class="disputed plain" d="M5 5" data-claimants="recguDPgV3cIXLO34 recZZZZZZZZZZZZZZ"/></g>'
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
			'Map showing Guam and Northern Mariana Islands.',
			Renderer::describe(
				array(
					self::NMI  => 'primary',
					self::GUAM => 'primary',
				),
				$names
			)
		);
		$this->assertSame(
			'Map showing United States, with Guam and Northern Mariana Islands also marked.',
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

	public function test_origins_come_first_in_their_own_order() {
		$names   = array(
			'recFRANCEAAAAAAAA' => 'France',
			'recLEBANONAAAAAAA' => 'Lebanon',
			'recANDORRAAAAAAAA' => 'Andorra',
			'recDJIBOUTIAAAAAA' => 'Djibouti',
			'recMARTINIQUEAAAA' => 'Martinique',
		);
		$roles   = array(
			'recANDORRAAAAAAAA' => 'primary',
			'recDJIBOUTIAAAAAA' => 'primary',
			'recFRANCEAAAAAAAA' => 'primary',
			'recLEBANONAAAAAAA' => 'primary',
			'recMARTINIQUEAAAA' => 'secondary',
		);
		$origins = array( 'recFRANCEAAAAAAAA', 'recLEBANONAAAAAAA', 'recANDORRAAAAAAAA', 'recDJIBOUTIAAAAAA' );

		$this->assertSame(
			'Map showing France, Lebanon, Andorra, and 2 other places.',
			Renderer::caption( $roles, $names, $origins )
		);
		$this->assertSame(
			'Map showing France, Lebanon, Andorra, and Djibouti, with Martinique also marked.',
			Renderer::describe( $roles, $names, PHP_INT_MAX, $origins )
		);
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
			'Map showing Guam, the Northern Mariana Islands, and the United States.',
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
		$this->assertSame( 'Map showing Spain, with 5 other places also marked.', Renderer::caption( $roles, $many ) );
		$this->assertStringContainsString( 'Place 0, Place 1', Renderer::describe( $roles, $many ), 'the description still lists them all' );

		$all = array_fill_keys( array_keys( $roles ), 'primary' );
		$this->assertSame( 'Map showing Place 0, Place 1, Place 2, and 3 other places.', Renderer::caption( $all, $many ) );
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

		$this->assertStringContainsString( 'aria-label="Where Chamorro is spoken"', $out, 'the name is on the <svg>' );
		$this->assertStringContainsString( 'aria-describedby="d-k"', $out );
		$this->assertStringNotContainsString( 'aria-labelledby', $out );
		$this->assertStringContainsString( '<title id="t-k"></title>', $out, 'an empty <title>: no tooltip over the shapes' );
		$this->assertStringContainsString( '<desc id="d-k">Map centred on Guam &amp; the &lt;NMI&gt;.</desc>', $out );
		$this->assertStringContainsString( 'href="https://wikitongues.org/wp-content/plugins/wt-maps/maps/shade/k.webp"', $out );
		$this->assertStringContainsString( 'href="https://example.org/a.webp"', $out, 'absolute hrefs are left alone' );
		$this->assertStringContainsString( 'class="hl primary" d="M0 0"', $out );
		$this->assertStringContainsString( 'class="hl secondary" d="M1 1"', $out );
		$this->assertStringContainsString( 'class="marker secondary"', $out );
		$this->assertStringContainsString( 'class="disputed hatch hatch-primary" d="M2 2"', $out, 'strongest claimant wins' );
		$this->assertStringContainsString( 'class="disputed hatch hatch-land" d="M3 3"', $out, 'no claimant on the map' );
		$this->assertStringContainsString( 'class="disputed plain" d="M4 4"', $out, 'an inactive area is left as built' );
		$this->assertStringContainsString( 'class="disputed plain" d="M5 5"', $out, 'claimants alone do not make an area active' );
		$this->assertStringNotContainsString( 'hatch-claimed', $out );
	}

	public function test_a_map_shows_contested_areas_when_a_place_claims_or_is_one() {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$this->markTestSkipped( 'WordPress HTML API not available (no local install at the repository root).' );
		}
		$this->assertTrue( Renderer::shows_contested( self::SVG ), 'a claimed area' );
		$this->assertTrue( Renderer::shows_contested( '<svg><g class="disputes"><path class="disputed edge" d="M0 0"/></g></svg>' ), 'the place is the area (Taiwan)' );
		$this->assertFalse( Renderer::shows_contested( '<svg><g class="disputes"><path class="disputed plain" d="M0 0"/></g></svg>' ), 'only inactive areas' );
		$this->assertFalse( Renderer::shows_contested( '<svg><g class="disputes"><path class="disputed plain" d="M0 0" data-claimants="recZZZZZZZZZZZZZZ"/></g></svg>' ), 'an inactive area with claimants off the map' );
	}

	public function test_decorate_scales_the_globe_from_its_corner() {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$this->markTestSkipped( 'WordPress HTML API not available (no local install at the repository root).' );
		}
		WP_Mock::userFunction( 'wp_kses_uri_attributes' )->andReturn( array( 'href', 'src' ) );
		WP_Mock::userFunction( 'wp_has_noncharacters' )->andReturn( false );
		$svg = '<svg viewBox="0 0 400 300"><title>t</title><g class="inset"><circle class="globe-halo" cx="346" cy="54" r="45.5"/></g></svg>';

		$this->assertStringContainsString( '<g transform="translate(-80 0) scale(1.2)" class="inset">', Renderer::decorate( $svg, array(), '/', '', '', 1.2 ), 'top right: anchored at (400, 0)' );
		$this->assertStringNotContainsString( 'transform', Renderer::decorate( $svg, array(), '/', '', '' ), 'as built by default' );

		$bottom_right = str_replace( 'cy="54"', 'cy="246"', $svg );
		$this->assertStringContainsString( 'translate(-80 -60) scale(1.2)', Renderer::decorate( $bottom_right, array(), '/', '', '', 1.2 ) );
	}
}
