<?php
/**
 * Array helper.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Pure array utilities (dot-notation access).
 *
 * Static by design: these are stateless functions, not services.
 */
final class Arr {

	/**
	 * Not instantiable.
	 */
	private function __construct() {}

	/**
	 * Get a nested value using dot notation, e.g. `general.currency`.
	 *
	 * @param array<string, mixed> $data          Source array.
	 * @param string               $key           Dot-notation key.
	 * @param mixed                $default_value Returned when the key is missing.
	 */
	public static function get( array $data, string $key, mixed $default_value = null ): mixed {
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $data ) || ! array_key_exists( $segment, $data ) ) {
				return $default_value;
			}

			$data = $data[ $segment ];
		}

		return $data;
	}

	/**
	 * Set a nested value using dot notation, creating intermediate arrays.
	 *
	 * @param array<string, mixed> $data  Target array, modified in place.
	 * @param string               $key   Dot-notation key.
	 * @param mixed                $value Value to set.
	 */
	public static function set( array &$data, string $key, mixed $value ): void {
		$segments = explode( '.', $key );
		$last     = array_pop( $segments );
		$cursor   = &$data;

		foreach ( $segments as $segment ) {
			if ( ! isset( $cursor[ $segment ] ) || ! is_array( $cursor[ $segment ] ) ) {
				$cursor[ $segment ] = array();
			}

			$cursor = &$cursor[ $segment ];
		}

		$cursor[ $last ] = $value;
	}
}
