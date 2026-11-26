<?php
use WP_Mock\Tools\TestCase;
use WT\Rights\Editorial;

class EditorialTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );
	}

	private function region( array $overview = null ): array {
		$region = array(
			'key'  => 'testland-region',
			'name' => 'Testland Region',
		);
		if ( null !== $overview ) {
			$region['overview'] = $overview;
		}
		return $region;
	}

	private function overview(): array {
		return array(
			'headline' => ' Testland finds its voice ',
			'html'     => '<p>Testland has two languages.</p>',
			'sources'  => array(
				array(
					'url'   => 'https://example.org/a',
					'label' => 'A source',
				),
				array( 'label' => 'No URL, dropped' ),
			),
			'cards'    => array(
				array(
					'report_id' => 'recTestland00001',
					'country'   => 'Testland',
					'title'     => 'Schools teach Testish.',
					'html'      => '<p>They do.</p>',
					'links'     => array(),
				),
				'not a card',
			),
			'letter'   => array(
				'html' => '<p>Dear reader.</p>',
				'name' => 'A. Director',
			),
		);
	}

	public function test_an_overview_is_normalized() {
		$overview = Editorial::overview( $this->region( $this->overview() ) );

		$this->assertSame( 'Testland finds its voice', $overview['headline'] );
		$this->assertCount( 1, $overview['sources'], 'a link without a URL is dropped' );
		$this->assertCount( 1, $overview['cards'], 'a non-array card is dropped' );
		$this->assertSame( 'recTestland00001', $overview['cards'][0]['report_id'] );
		$this->assertSame( array(), $overview['findings'] );
		$this->assertSame(
			array(
				'html' => '<p>Dear reader.</p>',
				'name' => 'A. Director',
				'role' => '',
			),
			$overview['letter']
		);
	}

	public function test_a_region_without_an_overview_has_none() {
		$this->assertNull( Editorial::overview( $this->region() ) );
		$this->assertNull( Editorial::overview( $this->region( array( 'headline' => '  ' ) ) ) );
	}

	public function test_a_letter_without_text_is_no_letter() {
		$raw           = $this->overview();
		$raw['letter'] = array( 'name' => 'A. Director' );

		$this->assertNull( Editorial::overview( $this->region( $raw ) )['letter'] );
	}

	public function test_front_matter_normalizes_answers_and_dates() {
		$front = Editorial::front_matter(
			array(
				'front_matter' => array(
					'summary'    => 'How Testland treats its languages.',
					'answer_key' => array(
						'answers' => array(
							'Yes'            => 'The threshold is met.',
							'Not applicable' => 'Does not apply.',
						),
					),
					'indicators' => array(
						'i1' => array(
							'html'    => '<p>Look for barriers.</p>',
							'example' => 'A rule.',
						),
					),
					'synthesis'  => array( 'release_date' => 'August 2027' ),
				),
			)
		);

		$this->assertSame( array( 'yes', 'not applicable' ), array_keys( $front['answer_key']['answers'] ) );
		$this->assertSame( 'A rule.', $front['indicators']['i1']['example'] );
		$this->assertSame( '', $front['synthesis']['release_date'], 'only YYYY-MM-DD is a date' );
		$this->assertNull( $front['letter'] );
		$this->assertNull( Editorial::front_matter( array() ) );
	}

	public function test_the_announcement_keeps_only_a_date_and_a_web_link() {
		$front = Editorial::front_matter(
			array(
				'front_matter' => array(
					'announcement' => array(
						'release_date' => '2026-12-10',
						'paper_url'    => ' https://wikitongues.org/wp-content/uploads/2026/12/white-paper.pdf ',
					),
				),
			)
		);
		$this->assertSame(
			array(
				'release_date' => '2026-12-10',
				'paper_url'    => 'https://wikitongues.org/wp-content/uploads/2026/12/white-paper.pdf',
			),
			$front['announcement']
		);

		$bad = Editorial::front_matter( array( 'front_matter' => array( 'announcement' => array( 'paper_url' => 'javascript:alert(1)' ) ) ) );
		$this->assertSame(
			array(
				'release_date' => '',
				'paper_url'    => '',
			),
			$bad['announcement'],
			'no announcement is an empty one, never a script link'
		);
	}

	public function test_the_plan_says_create_update_remove_or_nothing() {
		$release = array(
			'regions'      => array( $this->region( $this->overview() ) ),
			'front_matter' => array( 'summary' => 'Deck.' ),
		);
		$stored  = Editorial::hash( Editorial::overview( $release['regions'][0] ) );
		$front   = Editorial::hash( Editorial::front_matter( $release ) );

		$this->assertSame(
			array( 'Region overview, Testland Region: create', 'Front matter: create' ),
			Editorial::plan( $release, array(), '' )
		);
		$this->assertSame( array(), Editorial::plan( $release, array( 'testland-region' => $stored ), $front ), 'a second run changes nothing' );
		$this->assertSame(
			array( 'Region overview, Testland Region: update' ),
			Editorial::plan( $release, array( 'testland-region' => 'old' ), $front )
		);
		$this->assertSame(
			array( 'Region overview, Testland Region: remove', 'Front matter: remove' ),
			Editorial::plan( array( 'regions' => array( $this->region() ) ), array( 'testland-region' => $stored ), $front )
		);
	}
}
