<?php
use WP_Mock\Tools\TestCase;

/**
 * Site banner selection (plan 1.4), tested against the spec's example cases.
 */
class SiteBannerTest extends TestCase {

	private const HOME = 'https://wikitongues.org/';

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction(
			'wp_parse_url',
			array(
				'return' => function ( $url, $component = -1 ) {
					return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				},
			)
		);
	}

	private function giving_tuesday( array $overrides = array() ): array {
		return array_merge(
			array(
				'banner_id' => 'giving-tuesday-2026',
				'message'   => 'Gifts are matched today, Giving Tuesday.',
				'cta_label' => 'Give today',
				'cta_type'  => 'form',
				'cta_url'   => '',
				'cta_form'  => 'FUNTESTCODE',
				'enabled'   => true,
				'start'     => '2026-12-01 00:00:00',
				'end'       => '2026-12-01 23:59:00',
			),
			$overrides
		);
	}

	private function rights_launch( array $overrides = array() ): array {
		return array_merge(
			array(
				'banner_id' => 'language-rights-launch',
				'message'   => 'The Language Rights report is here.',
				'cta_label' => 'Read it',
				'cta_type'  => 'link',
				'cta_url'   => 'https://wikitongues.org/language-rights/',
				'cta_form'  => '',
				'enabled'   => true,
				'start'     => '2026-12-10 00:00:00',
				'end'       => '2026-12-24 23:59:00',
			),
			$overrides
		);
	}

	private function eastern( string $time ): DateTimeImmutable {
		return new DateTimeImmutable( $time, new DateTimeZone( 'America/New_York' ) );
	}

	private function pick( array $rows, DateTimeImmutable $now, string $path = '/languages/yoruba/', string $home = self::HOME ): ?array {
		return wt_site_banner_pick( $rows, $now, $path, $home );
	}

	// Example case: Giving Tuesday.

	public function test_giving_tuesday_shows_at_midnight_eastern() {
		$banner = $this->pick( array( $this->giving_tuesday() ), $this->eastern( '2026-12-01 00:00:00' ) );
		$this->assertSame( 'giving-tuesday-2026', $banner['banner_id'] ?? null );
	}

	public function test_midnight_eastern_is_five_utc() {
		$rows = array( $this->giving_tuesday() );
		$this->assertNull( $this->pick( $rows, new DateTimeImmutable( '2026-12-01 04:59:59', new DateTimeZone( 'UTC' ) ) ) );
		$this->assertNotNull( $this->pick( $rows, new DateTimeImmutable( '2026-12-01 05:00:00', new DateTimeZone( 'UTC' ) ) ) );
	}

	public function test_giving_tuesday_is_gone_at_its_end() {
		$rows = array( $this->giving_tuesday() );
		$this->assertNotNull( $this->pick( $rows, $this->eastern( '2026-12-01 23:58:59' ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-01 23:59:00' ) ) );
	}

	public function test_giving_tuesday_not_before_its_start() {
		$this->assertNull( $this->pick( array( $this->giving_tuesday() ), $this->eastern( '2026-11-30 23:59:59' ) ) );
	}

	public function test_donation_form_banner_is_hidden_on_donate() {
		$this->assertNull( $this->pick( array( $this->giving_tuesday() ), $this->eastern( '2026-12-01 12:00:00' ), '/donate/' ) );
		$this->assertNull( $this->pick( array( $this->giving_tuesday() ), $this->eastern( '2026-12-01 12:00:00' ), '/donate' ) );
	}

	public function test_donation_form_href_opens_the_campaign() {
		$this->assertSame( '?form=FUNTESTCODE', wt_site_banner_href( $this->giving_tuesday() ) );
	}

	// Example case: no campaign code.

	public function test_donation_form_without_code_never_shows() {
		$rows = array( $this->giving_tuesday( array( 'cta_form' => '' ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-01 12:00:00' ) ) );
	}

	// Example case: Rights announcement.

	public function test_rights_banner_shows_elsewhere() {
		$this->assertNotNull( $this->pick( array( $this->rights_launch() ), $this->eastern( '2026-12-10 09:00:00' ), '/' ) );
	}

	public function test_rights_banner_is_hidden_on_its_target() {
		$this->assertNull( $this->pick( array( $this->rights_launch() ), $this->eastern( '2026-12-10 09:00:00' ), '/language-rights/' ) );
	}

	public function test_relative_link_target_is_hidden_too() {
		$rows = array( $this->rights_launch( array( 'cta_url' => '/language-rights' ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-10 09:00:00' ), '/language-rights/' ) );
	}

	public function test_target_under_a_subdirectory_home() {
		$home = 'http://localhost:8888/wikitongues/';
		$rows = array( $this->rights_launch( array( 'cta_url' => 'http://localhost:8888/wikitongues/language-rights/' ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-10 09:00:00' ), '/language-rights/', $home ) );
		$this->assertNotNull( $this->pick( $rows, $this->eastern( '2026-12-10 09:00:00' ), '/', $home ) );
	}

	public function test_off_site_link_is_never_a_target() {
		$this->assertSame( '', wt_site_banner_target_path( $this->rights_launch( array( 'cta_url' => 'https://example.org/language-rights/' ) ), self::HOME ) );
	}

	// Example case: overlap.

	public function test_first_live_banner_in_list_order_wins() {
		$year_end  = $this->rights_launch(
			array(
				'banner_id' => 'year-end-2026',
				'start'     => '2026-12-26 00:00:00',
				'end'       => '2027-01-01 00:00:00',
			)
		);
		$evergreen = $this->rights_launch(
			array(
				'banner_id' => 'evergreen',
				'start'     => '',
				'end'       => '',
			)
		);
		$rows      = array( $year_end, $evergreen );
		$this->assertSame( 'year-end-2026', $this->pick( $rows, $this->eastern( '2026-12-28 12:00:00' ) )['banner_id'] );
		$this->assertSame( 'evergreen', $this->pick( $rows, $this->eastern( '2027-01-01 00:00:00' ) )['banner_id'] );
	}

	// Example case: emergency off.

	public function test_off_always_wins() {
		$rows = array( $this->giving_tuesday( array( 'enabled' => false ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-01 14:00:00' ) ) );
	}

	// Example case: nothing live.

	public function test_empty_list_shows_nothing() {
		$this->assertNull( $this->pick( array(), $this->eastern( '2026-12-01 12:00:00' ) ) );
	}

	public function test_open_window_is_live_while_on() {
		$rows = array(
			$this->rights_launch(
				array(
					'start' => '',
					'end'   => '',
				)
			),
		);
		$this->assertNotNull( $this->pick( $rows, $this->eastern( '2030-01-01 00:00:00' ) ) );
	}

	public function test_blank_message_never_shows() {
		$rows = array( $this->rights_launch( array( 'message' => '  ' ) ) );
		$this->assertNull( $this->pick( $rows, $this->eastern( '2026-12-12 09:00:00' ) ) );
	}

	// Admin validation.

	public function test_end_before_start_is_refused() {
		$_POST['acf'] = array( 'field_wt_site_banner_rows' => array( 'row-0' => array( 'field_wt_site_banner_start' => '2026-12-01 00:00:00' ) ) );
		WP_Mock::passthruFunction( 'wp_unslash' );
		WP_Mock::passthruFunction( 'sanitize_text_field' );
		$input = 'acf[field_wt_site_banner_rows][row-0][field_wt_site_banner_end]';
		$this->assertIsString( wt_site_banner_validate_end( true, '2026-11-30 23:00:00', array(), $input ) );
		$this->assertTrue( wt_site_banner_validate_end( true, '2026-12-01 23:59:00', array(), $input ) );
		unset( $_POST['acf'] );
	}

	public function test_banner_id_must_be_a_slug() {
		$this->assertTrue( wt_site_banner_validate_id( true, 'giving-tuesday-2026' ) );
		$this->assertIsString( wt_site_banner_validate_id( true, 'Giving Tuesday' ) );
		$this->assertIsString( wt_site_banner_validate_id( true, 'giving--tuesday' ) );
	}
}
