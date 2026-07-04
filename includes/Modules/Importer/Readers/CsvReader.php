<?php
/**
 * CSV reader.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer\Readers;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Parses a CSV file into records keyed by the header row.
 *
 * UTF-8 throughout (Bangla-safe); a leading BOM is stripped; quoted
 * cells may contain JSON for structured fields.
 */
final class CsvReader {

	/**
	 * Read every record.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return array<array<string, string>>
	 *
	 * @throws RuntimeException When the file cannot be parsed.
	 */
	public function records( string $path ): array {
		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( sprintf( 'File "%s" is not readable.', $path ) );
		}

		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			throw new RuntimeException( sprintf( 'Could not open "%s".', $path ) );
		}

		$header = fgetcsv( $handle );

		if ( ! is_array( $header ) || array() === $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			throw new RuntimeException( 'The CSV file has no header row.' );
		}

		// Strip a UTF-8 BOM from the first header cell.
		$header[0] = (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] );
		$header    = array_map( static fn( ?string $cell ): string => trim( (string) $cell ), $header );
		$columns   = count( $header );

		$records = array();

		while ( true ) {
			$row = fgetcsv( $handle );

			if ( false === $row || null === $row ) {
				break;
			}

			if ( array( null ) === $row ) {
				continue; // Blank line.
			}

			$row = array_map( static fn( ?string $cell ): string => (string) $cell, $row );
			$row = array_pad( array_slice( $row, 0, $columns ), $columns, '' );

			if ( '' === implode( '', $row ) ) {
				continue;
			}

			$records[] = array_combine( $header, $row );
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $records;
	}
}
