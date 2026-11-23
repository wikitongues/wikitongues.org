<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Release_Status;

class ReleasesAdminTest extends TestCase {

	private function entry( string $to ): array {
		return array(
			'at'   => '2027-02-19T09:00:00+00:00',
			'by'   => 'frederico',
			'from' => 'embargoed',
			'to'   => $to,
		);
	}

	public function test_the_log_puts_the_newest_change_first_and_keeps_a_limit() {
		$log = Release_Status::log( array( $this->entry( 'embargoed' ) ), $this->entry( 'released' ) );
		$this->assertSame( 'released', $log[0]['to'] );
		$this->assertCount( 2, $log );

		$full = array_fill( 0, 20, $this->entry( 'embargoed' ) );
		$log  = Release_Status::log( $full, $this->entry( 'released' ) );
		$this->assertCount( 20, $log );
		$this->assertSame( 'released', $log[0]['to'] );
	}

	public function test_each_change_says_what_it_does_before_it_runs() {
		$this->assertStringContainsString( '13 reports are made public and indexed', wt_rights_status_confirmation( 'Testland Region', Release_Status::RELEASED, 13 ) );
		$this->assertStringContainsString( 'Press accounts and admins can read its 13 reports', wt_rights_status_confirmation( 'Testland Region', Release_Status::EMBARGOED, 13 ) );
		$this->assertStringContainsString( 'PDFs are deleted from the server', wt_rights_status_confirmation( 'Testland Region', Release_Status::UPCOMING, 1 ) );
		$this->assertStringContainsString( 'Its 1 report is hidden', wt_rights_status_confirmation( 'Testland Region', Release_Status::UPCOMING, 1 ) );
	}

	public function test_buttons_name_the_move() {
		$this->assertSame( 'Release', wt_rights_status_action( Release_Status::RELEASED ) );
		$this->assertSame( 'Embargo', wt_rights_status_action( Release_Status::EMBARGOED ) );
		$this->assertSame( 'Back to upcoming', wt_rights_status_action( Release_Status::UPCOMING ) );
	}
}
