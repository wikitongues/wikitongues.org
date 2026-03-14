<?php

use PHPUnit\Framework\TestCase;
use WT\DownloadGateway\IpHasher;

class IpHasherTest extends TestCase {

	// --- hash() ---

	public function test_hash_returns_64_char_hex_string(): void {
		$result = IpHasher::hash( '192.168.1.1' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $result );
	}

	public function test_hash_is_deterministic(): void {
		$this->assertSame( IpHasher::hash( '192.168.1.1' ), IpHasher::hash( '192.168.1.1' ) );
	}

	public function test_different_ips_produce_different_hashes(): void {
		$this->assertNotSame( IpHasher::hash( '192.168.1.1' ), IpHasher::hash( '192.168.1.2' ) );
	}

	// --- normalize() ---

	public function test_normalize_trims_whitespace(): void {
		$this->assertSame( '192.168.1.1', IpHasher::normalize( '  192.168.1.1  ' ) );
	}

	public function test_normalize_lowercases_ipv6(): void {
		$this->assertSame( '2001:db8::1', IpHasher::normalize( '2001:DB8::1' ) );
	}

	public function test_normalize_strips_ipv6_zone_id(): void {
		$this->assertSame( 'fe80::1', IpHasher::normalize( 'fe80::1%eth0' ) );
	}

	public function test_hash_is_consistent_after_normalization(): void {
		// Whitespace and case differences should not produce different hashes.
		$this->assertSame(
			IpHasher::hash( '2001:DB8::1' ),
			IpHasher::hash( '2001:db8::1' )
		);
	}

	// --- hash_from_server() ---

	public function test_hash_from_server_uses_remote_addr(): void {
		$server = [ 'REMOTE_ADDR' => '10.0.0.1' ];
		$this->assertSame( IpHasher::hash( '10.0.0.1' ), IpHasher::hash_from_server( $server ) );
	}

	public function test_hash_from_server_prefers_x_forwarded_for(): void {
		$server = [
			'REMOTE_ADDR'          => '10.0.0.1',
			'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
		];
		$this->assertSame( IpHasher::hash( '203.0.113.5' ), IpHasher::hash_from_server( $server ) );
	}

	public function test_hash_from_server_takes_first_forwarded_ip(): void {
		$server = [ 'HTTP_X_FORWARDED_FOR' => '203.0.113.5, 10.0.0.2, 10.0.0.3' ];
		$this->assertSame( IpHasher::hash( '203.0.113.5' ), IpHasher::hash_from_server( $server ) );
	}

	public function test_hash_from_server_falls_back_when_forwarded_is_empty(): void {
		$server = [
			'REMOTE_ADDR'          => '10.0.0.1',
			'HTTP_X_FORWARDED_FOR' => '',
		];
		$this->assertSame( IpHasher::hash( '10.0.0.1' ), IpHasher::hash_from_server( $server ) );
	}

	public function test_hash_from_server_handles_missing_keys(): void {
		$result = IpHasher::hash_from_server( [] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $result );
	}
}
