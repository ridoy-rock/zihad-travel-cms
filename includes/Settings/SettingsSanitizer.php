<?php
/**
 * Settings sanitizer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Settings;

use ZihadTravelCMS\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Validates any settings write against the defaults schema: unknown
 * keys (at every depth) are dropped and scalars are coerced to the
 * default's type. Shared by the admin form, the REST controller and
 * the Settings API registration, so every write path enforces the
 * same shape.
 *
 * Field-level sanitization (kses, URL cleaning…) happens before this
 * in the field components / REST args; this is the structural gate.
 */
final class SettingsSanitizer {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Sanitize a full settings array.
	 *
	 * @param mixed $value Raw settings.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $value ): array {
		return $this->clean( is_array( $value ) ? $value : array(), $this->config->defaults() );
	}

	/**
	 * Recursively intersect a value with the defaults shape.
	 *
	 * @param array<string, mixed> $value    Submitted values.
	 * @param array<string, mixed> $defaults Defaults (the schema).
	 *
	 * @return array<string, mixed>
	 */
	private function clean( array $value, array $defaults ): array {
		$clean = array();

		foreach ( $defaults as $key => $default_value ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}

			$submitted = $value[ $key ];

			if ( is_array( $default_value ) ) {
				$clean[ $key ] = is_array( $submitted ) ? $this->clean( $submitted, $default_value ) : array();
				continue;
			}

			$clean[ $key ] = match ( true ) {
				is_bool( $default_value )  => (bool) $submitted,
				is_int( $default_value )   => (int) ( is_scalar( $submitted ) ? $submitted : 0 ),
				is_float( $default_value ) => (float) ( is_scalar( $submitted ) ? $submitted : 0 ),
				default                    => is_scalar( $submitted ) ? (string) $submitted : '',
			};
		}

		return $clean;
	}
}
