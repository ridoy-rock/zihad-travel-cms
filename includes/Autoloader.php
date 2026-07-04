<?php
/**
 * PSR-4 fallback autoloader.
 *
 * Used only when the Composer-generated autoloader is unavailable
 * (e.g. a distribution build shipped without the vendor directory).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader for the ZihadTravelCMS namespace.
 */
final class Autoloader {

	/**
	 * The namespace prefix handled by this autoloader.
	 */
	private const PREFIX = 'ZihadTravelCMS\\';

	/**
	 * Register the autoloader with SPL.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	/**
	 * Load a class file for the given fully-qualified class name.
	 *
	 * @param string $class_name Fully-qualified class name.
	 */
	public static function autoload( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$path     = ZTC_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
