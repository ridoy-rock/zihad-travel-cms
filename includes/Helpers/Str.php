<?php
/**
 * String helper.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Pure string utilities.
 *
 * Static by design: these are stateless functions, not services.
 */
final class Str {

	/**
	 * Not instantiable.
	 */
	private function __construct() {}

	/**
	 * Strip everything except digits from a phone number.
	 *
	 * @param string $number Raw phone number, e.g. "+1 (555) 123-4567".
	 */
	public static function digits( string $number ): string {
		return (string) preg_replace( '/\D+/', '', $number );
	}

	/**
	 * Build a wa.me chat link, optionally pre-filled with a message.
	 * Returns '' when the number contains no digits.
	 *
	 * @param string $number  Phone number in any format.
	 * @param string $message Optional pre-filled message.
	 */
	public static function wa_me( string $number, string $message = '' ): string {
		$digits = self::digits( $number );

		if ( '' === $digits ) {
			return '';
		}

		$url = 'https://wa.me/' . $digits;

		return '' !== $message ? $url . '?text=' . rawurlencode( $message ) : $url;
	}
}
