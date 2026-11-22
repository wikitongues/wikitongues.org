<?php
use PHPUnit\Framework\TestCase;
use WT\Rights\Docs_Adapter;

class DocsAdapterTest extends TestCase {

	public function test_tabs_come_in_order_with_child_tabs_after_their_parent() {
		$parent              = RightsFixture::tab( 'Report', array( RightsFixture::text( 'One' ) ) );
		$parent['childTabs'] = array( RightsFixture::tab( 'Notes', array( RightsFixture::text( 'Two' ) ) ) );
		$tabs                = Docs_Adapter::tabs(
			array(
				'tabs' => array( $parent, RightsFixture::tab( 'WordPress', array( RightsFixture::text( 'Three' ) ) ) ),
			)
		);

		$this->assertSame( array( 'Report', 'Notes', 'WordPress' ), array_column( $tabs, 'title' ) );
		$this->assertSame( 'Two', $tabs[1]['paragraphs'][0]['text'] );
	}

	public function test_a_document_without_tabs_is_one_untitled_tab() {
		$tabs = Docs_Adapter::tabs( array( 'body' => array( 'content' => array( RightsFixture::text( 'Hello' ) ) ) ) );

		$this->assertCount( 1, $tabs );
		$this->assertSame( '', $tabs[0]['title'] );
		$this->assertSame( 'Hello', $tabs[0]['paragraphs'][0]['text'] );
	}

	public function test_soft_line_breaks_stay_as_newlines_and_the_paragraph_newline_goes() {
		$paragraphs = Docs_Adapter::paragraphs( array( RightsFixture::sources( 'https://example.org/a', 'https://example.org/b' ) ) );

		$this->assertSame( "https://example.org/a\nhttps://example.org/b", $paragraphs[0]['text'] );
		$this->assertSame( 'https://example.org/a', $paragraphs[0]['runs'][0]['url'] );
	}

	public function test_neighbouring_runs_with_the_same_style_are_joined() {
		$paragraphs = Docs_Adapter::paragraphs(
			array(
				RightsFixture::p(
					'NORMAL_TEXT',
					'Test',
					'land ',
					array(
						'text' => 'bold',
						'bold' => true,
					)
				),
			)
		);

		$this->assertSame( 'Testland ', $paragraphs[0]['runs'][0]['text'] );
		$this->assertSame( array( 'bold' ), array_column( array_filter( $paragraphs[0]['runs'], fn( $r ) => $r['bold'] ), 'text' ) );
	}

	public function test_empty_paragraphs_are_dropped_and_table_cells_are_read() {
		$table = array(
			'table' => array(
				'tableRows' => array(
					array( 'tableCells' => array( array( 'content' => array( RightsFixture::text( 'In a cell' ) ) ) ) ),
				),
			),
		);

		$paragraphs = Docs_Adapter::paragraphs( array( RightsFixture::text( '   ' ), $table ) );

		$this->assertSame( array( 'In a cell' ), array_column( $paragraphs, 'text' ) );
	}

	public function test_a_rich_link_chip_is_a_link_run() {
		$paragraphs = Docs_Adapter::paragraphs(
			array(
				array(
					'paragraph' => array(
						'elements' => array(
							array(
								'richLink' => array(
									'richLinkProperties' => array(
										'title' => 'A report',
										'uri'   => 'https://example.org/report',
									),
								),
							),
						),
					),
				),
			)
		);

		$this->assertSame( 'https://example.org/report', $paragraphs[0]['runs'][0]['url'] );
		$this->assertSame( 'NORMAL_TEXT', $paragraphs[0]['style'] );
	}
}
