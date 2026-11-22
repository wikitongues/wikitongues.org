<?php
use WP_Mock\Tools\TestCase;
use WT\Rights\Airtable;
use WT\Rights\Google_Docs;

class ClientsTest extends TestCase {

	/** A transport that answers from a queue and records each request. */
	private function transport( array $responses, array &$requests ): callable {
		return function ( string $method, string $url, array $headers, ?string $body ) use ( &$responses, &$requests ): array {
			$requests[] = compact( 'method', 'url', 'headers', 'body' );
			return array_shift( $responses );
		};
	}

	private function key(): array {
		$private = openssl_pkey_new(
			array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			)
		);
		openssl_pkey_export( $private, $pem );
		return array(
			'type'         => 'service_account',
			'client_email' => 'test@example.iam.gserviceaccount.com',
			'private_key'  => $pem,
		);
	}

	public function test_airtable_follows_pagination_and_asks_for_field_ids() {
		$requests = array();
		$client   = new Airtable(
			'pat123',
			$this->transport(
				array(
					array(
						'status' => 200,
						'body'   => '{"records":[{"id":"rec1","fields":{"fldA":"x"}}],"offset":"next"}',
					),
					array(
						'status' => 200,
						'body'   => '{"records":[{"id":"rec2","fields":{}}]}',
					),
				),
				$requests
			)
		);

		$records = $client->records( 'appBase', 'tblTable', array( 'fldA' ) );

		$this->assertSame( array( 'rec1', 'rec2' ), array_column( $records, 'id' ) );
		$this->assertStringContainsString( 'returnFieldsByFieldId=true', $requests[0]['url'] );
		$this->assertStringContainsString( 'fields%5B%5D=fldA', $requests[0]['url'] );
		$this->assertStringContainsString( 'offset=next', $requests[1]['url'] );
		$this->assertSame( 'Bearer pat123', $requests[0]['headers']['Authorization'] );
	}

	public function test_an_airtable_error_names_the_service_and_status() {
		$requests = array();
		$client   = new Airtable(
			'bad',
			$this->transport(
				array(
					array(
						'status' => 401,
						'body'   => '{"error":{"type":"AUTHENTICATION_REQUIRED"}}',
					),
				),
				$requests
			)
		);

		$this->expectExceptionMessage( 'Airtable returned HTTP 401: AUTHENTICATION_REQUIRED' );
		$client->records( 'appBase', 'tblTable' );
	}

	public function test_google_signs_in_once_and_reads_docs_with_tabs_and_without_suggestions() {
		WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( fn( $v ) => json_encode( $v ) );
		$requests = array();
		$client   = new Google_Docs(
			$this->key(),
			$this->transport(
				array(
					array(
						'status' => 200,
						'body'   => '{"access_token":"tok"}',
					),
					array(
						'status' => 200,
						'body'   => '{"title":"A"}',
					),
					array(
						'status' => 200,
						'body'   => '{"title":"B"}',
					),
				),
				$requests
			)
		);

		$this->assertSame( 'A', $client->document( 'docA' )['title'] );
		$this->assertSame( 'B', $client->document( 'docB' )['title'] );
		$this->assertCount( 3, $requests );
		$this->assertStringContainsString( 'grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Ajwt-bearer', $requests[0]['body'] );
		$this->assertStringContainsString( 'includeTabsContent=true', $requests[1]['url'] );
		$this->assertStringContainsString( 'suggestionsViewMode=PREVIEW_WITHOUT_SUGGESTIONS', $requests[1]['url'] );
		$this->assertSame( 'Bearer tok', $requests[2]['headers']['Authorization'] );
	}

	public function test_a_key_that_is_not_a_service_account_is_refused() {
		$this->expectException( InvalidArgumentException::class );
		new Google_Docs(
			array( 'type' => 'authorized_user' ),
			fn(): array => array(
				'status' => 0,
				'body'   => '',
			)
		);
	}

	public function test_doc_ids_come_from_doc_links_only() {
		$this->assertSame( '1TestlandDocIdTestlandDocIdTestland0000', Google_Docs::document_id( 'https://docs.google.com/document/d/1TestlandDocIdTestlandDocIdTestland0000' ) );
		$this->assertSame( '1TestlandDocIdTestlandDocIdTestland0000', Google_Docs::document_id( 'https://docs.google.com/document/u/0/d/1TestlandDocIdTestlandDocIdTestland0000/edit?tab=t.0' ) );
		$this->assertSame( '', Google_Docs::document_id( 'https://drive.google.com/file/d/1TestlandDocIdTestlandDocIdTestland0000/view' ) );
	}
}
