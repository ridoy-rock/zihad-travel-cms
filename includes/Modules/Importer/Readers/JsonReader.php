<?php
/**
 * JSON reader.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer\Readers;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Parses a JSON file into records. Accepts either a plain array of
 * objects or an object with a `records` property (the demo-data
 * format).
 */
final class JsonReader {

	/**
	 * Read every record.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return array<array<string, mixed>>
	 *
	 * @throws RuntimeException When the file cannot be parsed.
	 */
	public function records( string $path ): array {
		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( sprintf( 'File "%s" is not readable.', $path ) );
		}

		$raw  = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'The JSON file is invalid: ' . json_last_error_msg() );
		}

		if ( isset( $data['records'] ) && is_array( $data['records'] ) ) {
			$data = $data['records'];
		}

		return array_values( array_filter( $data, 'is_array' ) );
	}
}
