<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Versioner;

class VersionerTest extends TestCase {

	private const ANSWERS = array(
		'i1'    => 'yes',
		'i2'    => 'unclear',
		'i3'    => 'no',
		'i4'    => 'yes',
		'i5'    => 'no',
		'ante'  => 'positive',
		'trend' => 'negative',
	);

	private function previous( string $version = '1.0', string $hash = 'aaa' ): array {
		return array(
			'version' => $version,
			'hash'    => $hash,
			'answers' => self::ANSWERS,
		);
	}

	public function test_a_report_new_to_the_release_is_1_0() {
		$next = Versioner::next( null, 'aaa', self::ANSWERS, true, array() );

		$this->assertSame( '1.0', $next['version'] );
		$this->assertSame( array(), $next['problems'] );
	}

	public function test_edits_before_publication_stay_1_0_and_need_no_note() {
		$answers       = self::ANSWERS;
		$answers['i3'] = 'yes';

		$next = Versioner::next( $this->previous(), 'bbb', $answers, false, array() );

		$this->assertSame( '1.0', $next['version'] );
		$this->assertSame( array(), $next['problems'] );
	}

	public function test_unchanged_content_keeps_its_version() {
		$next = Versioner::next( $this->previous( '1.2' ), 'aaa', self::ANSWERS, true, array() );

		$this->assertSame( '1.2', $next['version'] );
		$this->assertFalse( $next['changed'] );
	}

	public function test_a_text_change_such_as_a_new_headline_is_a_minor_version() {
		$next = Versioner::next( $this->previous( '1.1' ), 'bbb', self::ANSWERS, true, array( 'Reworded the headline.' ) );

		$this->assertSame( '1.2', $next['version'] );
		$this->assertSame( array(), $next['changed_answers'] );
		$this->assertSame( array(), $next['problems'] );
	}

	public function test_an_answer_change_is_a_major_version_naming_the_answer() {
		$answers       = self::ANSWERS;
		$answers['i3'] = 'yes';

		$next = Versioner::next( $this->previous( '1.3' ), 'bbb', $answers, true, array( 'New law recognizes Valley Sign Language.' ) );

		$this->assertSame( '2.0', $next['version'] );
		$this->assertSame( array( 'i3' ), $next['changed_answers'] );
	}

	public function test_a_published_change_without_a_note_is_an_error() {
		$next = Versioner::next( $this->previous(), 'bbb', self::ANSWERS, true, array() );

		$this->assertSame( array( 'missing_change_note' ), RightsFixture::codes( $next['problems'] ) );
		$this->assertSame( 'error', $next['problems'][0]['severity'] );
	}

	public function test_a_note_without_a_change_is_only_a_warning() {
		$next = Versioner::next( $this->previous(), 'aaa', self::ANSWERS, true, array( 'Reverted.' ) );

		$this->assertSame( array( 'note_without_change' ), RightsFixture::codes( $next['problems'] ) );
		$this->assertSame( 'warning', $next['problems'][0]['severity'] );
	}

	public function test_the_hash_ignores_key_order_but_not_list_order() {
		$a = array(
			'b' => 1,
			'a' => array(
				'y' => 2,
				'x' => 3,
			),
		);
		$b = array(
			'a' => array(
				'x' => 3,
				'y' => 2,
			),
			'b' => 1,
		);

		$this->assertSame( Versioner::hash( $a ), Versioner::hash( $b ) );
		$this->assertNotSame( Versioner::hash( array( 1, 2 ) ), Versioner::hash( array( 2, 1 ) ) );
	}

	public function test_the_changelog_adds_the_new_version_once() {
		$log = Versioner::changelog( array(), '1.0', '2027-02-19', array(), array() );
		$log = Versioner::changelog( $log, '1.1', '2027-03-03', array( 'Corrected a date.' ), array() );
		$log = Versioner::changelog( $log, '1.1', '2027-03-10', array( 'Corrected a date.' ), array() );

		$this->assertSame( array( '1.1', '1.0' ), array_column( $log, 'version' ) );
		$this->assertSame( '2027-03-03', $log[0]['date'] );
	}
}
