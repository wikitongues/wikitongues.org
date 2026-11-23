<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Import_Plan;
use WT\Rights\Release_Status;

class ImportPlanTest extends TestCase {

	private function report( array $overrides = array() ): array {
		return array_merge(
			array(
				'record_id' => 'recTestland00001',
				'name'      => 'Testland',
				'slug'      => 'testland',
				'region'    => 'south-central-asia',
				'draft'     => false,
				'version'   => '1.0',
				'hash'      => 'aaa',
			),
			$overrides
		);
	}

	private function existing( array $overrides = array() ): array {
		return array_merge(
			array(
				'post_id' => 42,
				'slug'    => 'testland',
				'title'   => 'Testland',
				'status'  => 'draft',
				'hash'    => 'aaa',
				'version' => '1.0',
				'region'  => 'south-central-asia',
				'draft'   => false,
			),
			$overrides
		);
	}

	private function release( array ...$reports ): array {
		return array( 'reports' => $reports );
	}

	public function test_region_status_sets_post_status() {
		$this->assertSame( 'draft', Release_Status::post_status( Release_Status::UPCOMING, false ) );
		$this->assertSame( 'private', Release_Status::post_status( Release_Status::EMBARGOED, false ) );
		$this->assertSame( 'publish', Release_Status::post_status( Release_Status::RELEASED, false ) );
		$this->assertSame( 'draft', Release_Status::post_status( 'nonsense', false ) );
	}

	public function test_a_preview_draft_stays_a_draft_even_when_released() {
		$this->assertSame( 'draft', Release_Status::post_status( Release_Status::RELEASED, true ) );
	}

	public function test_a_new_report_is_created_with_its_regions_post_status() {
		$plan = Import_Plan::plan( $this->release( $this->report() ), array(), array( 'south-central-asia' => Release_Status::EMBARGOED ) );

		$this->assertSame( 'create', $plan[0]['action'] );
		$this->assertSame( 'private', $plan[0]['post_status'] );
	}

	public function test_a_second_import_plans_nothing() {
		$plan = Import_Plan::plan(
			$this->release( $this->report() ),
			array( 'recTestland00001' => $this->existing() ),
			array( 'south-central-asia' => Release_Status::UPCOMING )
		);

		$this->assertSame(
			array(
				'create'    => 0,
				'update'    => 0,
				'unpublish' => 0,
				'unchanged' => 1,
			),
			Import_Plan::summary( $plan )
		);
	}

	public function test_changed_content_and_version_are_an_update_naming_them() {
		$plan = Import_Plan::plan(
			$this->release(
				$this->report(
					array(
						'hash'    => 'bbb',
						'version' => '1.1',
					)
				)
			),
			array( 'recTestland00001' => $this->existing() ),
			array( 'south-central-asia' => Release_Status::UPCOMING )
		);

		$this->assertSame( 'update', $plan[0]['action'] );
		$this->assertSame( array( 'content', 'version 1.0 → 1.1' ), $plan[0]['changes'] );
		$this->assertSame( 42, $plan[0]['post_id'] );
	}

	public function test_reports_are_matched_by_record_id_not_name() {
		$plan = Import_Plan::plan(
			$this->release( $this->report( array( 'name' => 'Nauru' ) ) ),
			array( 'recTestland00001' => $this->existing( array( 'title' => 'Naoero' ) ) ),
			array()
		);

		$this->assertSame( 'update', $plan[0]['action'] );
		$this->assertSame( array( 'title Naoero → Nauru' ), $plan[0]['changes'] );
	}

	public function test_a_report_missing_from_the_release_is_unpublished_not_deleted() {
		$plan = Import_Plan::plan(
			$this->release(),
			array(
				'recGone'  => $this->existing(
					array(
						'status' => 'publish',
						'title'  => 'Goneland',
					)
				),
				'recDraft' => $this->existing( array( 'status' => 'draft' ) ),
			),
			array()
		);

		$this->assertCount( 1, $plan );
		$this->assertSame( 'unpublish', $plan[0]['action'] );
		$this->assertSame( 'draft', $plan[0]['post_status'] );
	}

	public function test_a_released_region_publishes_on_import() {
		$plan = Import_Plan::plan(
			$this->release( $this->report() ),
			array( 'recTestland00001' => $this->existing( array( 'status' => 'private' ) ) ),
			array( 'south-central-asia' => Release_Status::RELEASED )
		);

		$this->assertSame( array( 'status private → publish' ), $plan[0]['changes'] );
	}

	public function test_a_preview_draft_becoming_final_is_an_update_even_with_the_same_content() {
		$plan = Import_Plan::plan(
			$this->release( $this->report() ),
			array( 'recTestland00001' => $this->existing( array( 'draft' => true ) ) ),
			array( 'south-central-asia' => Release_Status::UPCOMING )
		);

		$this->assertSame( 'update', $plan[0]['action'] );
		$this->assertSame( array( 'no longer a preview draft' ), $plan[0]['changes'] );
	}

	public function test_a_new_archive_nation_id_updates_an_unchanged_report() {
		$plan = Import_Plan::plan(
			$this->release( $this->report( array( 'archive_nation_id' => 'recArchive000001' ) ) ),
			array( 'recTestland00001' => $this->existing() ),
			array( 'south-central-asia' => Release_Status::UPCOMING )
		);

		$this->assertSame( 'update', $plan[0]['action'] );
		$this->assertSame( array( 'archive Nation ID (none) → recArchive000001' ), $plan[0]['changes'] );

		$again = Import_Plan::plan(
			$this->release( $this->report( array( 'archive_nation_id' => 'recArchive000001' ) ) ),
			array( 'recTestland00001' => $this->existing( array( 'archive_nation_id' => 'recArchive000001' ) ) ),
			array( 'south-central-asia' => Release_Status::UPCOMING )
		);
		$this->assertSame( 'unchanged', $again[0]['action'] );
	}
}
