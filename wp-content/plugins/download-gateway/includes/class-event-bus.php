<?php
/**
 * EventBus — namespaced wrapper over WP do_action / add_action.
 *
 * Keeps event names consistent across the plugin and decouples callers
 * from the raw WP hook API. All hook names are prefixed `gateway/`.
 *
 * Dispatching:
 *   EventBus::dispatch( 'download/click', $payload );
 *   // fires do_action( 'gateway/download/click', $payload )
 *
 * Listening:
 *   EventBus::listen( 'download/click', function( array $payload ) { ... } );
 *
 * Defined event names (sub-phases 3, 5, 7):
 *   download/click        — user triggered a download (token issued or reused)
 *   download/gate_view    — gate modal shown
 *   download/gate_submit  — gate form submitted
 *   download/redirect     — token redeemed, file redirect sent
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class EventBus {

	private const HOOK_PREFIX = 'gateway/';

	/**
	 * Fire an event.
	 *
	 * @param string $event   Short event name, e.g. 'download/click'.
	 * @param array  $payload Arbitrary data passed to listeners.
	 */
	public static function dispatch( string $event, array $payload = [] ): void {
		do_action( self::HOOK_PREFIX . $event, $payload );
	}

	/**
	 * Register a listener for an event.
	 *
	 * @param string   $event    Short event name, e.g. 'download/click'.
	 * @param callable $listener Callback receiving the payload array.
	 * @param int      $priority WP hook priority (default 10).
	 */
	public static function listen( string $event, callable $listener, int $priority = 10 ): void {
		add_action( self::HOOK_PREFIX . $event, $listener, $priority, 1 );
	}
}
