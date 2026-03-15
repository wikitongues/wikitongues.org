<?php
/**
 * FileResolverRegistry — maps post types to FileResolver implementations.
 *
 * Resolvers are registered at plugin bootstrap. The download endpoint calls
 * for_post() without needing to know which CPT it's dealing with.
 *
 * Usage:
 *   // Register (in plugin bootstrap, after all resolver classes are loaded).
 *   FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );
 *
 *   // Resolve (in download endpoint).
 *   $resolver = FileResolverRegistry::for_post( $post_id );
 *   if ( null === $resolver ) { // no resolver registered for this post type }
 *   $url = $resolver->resolve( $post_id );
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class FileResolverRegistry {

	/** @var array<string, FileResolver> post_type → resolver map */
	private static array $resolvers = [];

	/**
	 * Register a resolver for a post type.
	 *
	 * @param string       $post_type CPT slug, e.g. 'document_files'.
	 * @param FileResolver $resolver  Resolver instance.
	 */
	public static function register( string $post_type, FileResolver $resolver ): void {
		self::$resolvers[ $post_type ] = $resolver;
	}

	/**
	 * Return the resolver for the post type of a given post ID, or null if
	 * no resolver has been registered for that type.
	 *
	 * @param int $post_id Post ID of the downloadable item.
	 * @return FileResolver|null
	 */
	public static function for_post( int $post_id ): ?FileResolver {
		$post_type = get_post_type( $post_id );

		if ( false === $post_type ) {
			Logger::debug( "FileResolverRegistry: post {$post_id} not found." );
			return null;
		}

		if ( ! isset( self::$resolvers[ $post_type ] ) ) {
			Logger::debug( "FileResolverRegistry: no resolver registered for post_type={$post_type}." );
			return null;
		}

		return self::$resolvers[ $post_type ];
	}

	/**
	 * Check whether a resolver is registered for a given post type.
	 *
	 * @param string $post_type CPT slug.
	 * @return bool
	 */
	public static function has_resolver( string $post_type ): bool {
		return isset( self::$resolvers[ $post_type ] );
	}

	/**
	 * Return all registered post type slugs.
	 *
	 * @return string[]
	 */
	public static function registered_post_types(): array {
		return array_keys( self::$resolvers );
	}

	/**
	 * Clear all registered resolvers.
	 *
	 * For use in tests only — allows each test to start from a clean registry.
	 */
	public static function reset(): void {
		self::$resolvers = [];
	}
}
