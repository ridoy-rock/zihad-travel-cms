<?php
/**
 * Image importer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Sideloads images from URLs into the media library.
 *
 * Each attachment remembers its source URL (`_ztc_source_url`), so
 * re-imports and demo-data refreshes reuse the existing attachment
 * instead of downloading a duplicate — which also makes placeholder
 * images replaceable later by re-importing with new URLs.
 */
final class ImageImporter {

	public const SOURCE_META = '_ztc_source_url';

	/**
	 * Import one image URL, returning the attachment ID (0 for empty
	 * input).
	 *
	 * @param string $url       Image URL (http/https only).
	 * @param int    $parent_id Optional post to attach to.
	 *
	 * @throws RuntimeException When the download or sideload fails.
	 */
	public function import( string $url, int $parent_id = 0 ): int {
		$url = trim( $url );

		if ( '' === $url ) {
			return 0;
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			throw new RuntimeException( sprintf( 'Invalid image URL "%s".', $url ) );
		}

		$existing = $this->find_by_source( $url );

		if ( $existing > 0 ) {
			return $existing;
		}

		$this->load_media_functions();

		$tmp = download_url( $url, 30 );

		if ( is_wp_error( $tmp ) ) {
			throw new RuntimeException( sprintf( 'Could not download "%s": %s', $url, $tmp->get_error_message() ) );
		}

		$name = sanitize_file_name( (string) wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );

		$attachment_id = media_handle_sideload(
			array(
				'name'     => '' !== $name ? $name : 'ztc-import-' . md5( $url ) . '.jpg',
				'tmp_name' => $tmp,
			),
			$parent_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			throw new RuntimeException( sprintf( 'Could not import "%s": %s', $url, $attachment_id->get_error_message() ) );
		}

		update_post_meta( (int) $attachment_id, self::SOURCE_META, esc_url_raw( $url ) );

		return (int) $attachment_id;
	}

	/**
	 * Import a list of image URLs, skipping failures silently (the
	 * caller logs them per record).
	 *
	 * @param array<string> $urls      Image URLs.
	 * @param int           $parent_id Optional post to attach to.
	 *
	 * @return array<int>
	 */
	public function import_all( array $urls, int $parent_id = 0 ): array {
		$ids = array();

		foreach ( $urls as $url ) {
			$id = $this->import( (string) $url, $parent_id );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * An already-imported attachment for a source URL, or 0.
	 *
	 * @param string $url Source URL.
	 */
	private function find_by_source( string $url ): int {
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => esc_url_raw( $url ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return (int) ( $found[0] ?? 0 );
	}

	/**
	 * The sideload helpers live in admin includes; load them when
	 * running outside wp-admin (REST, WP-CLI).
	 */
	private function load_media_functions(): void {
		if ( function_exists( 'media_handle_sideload' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
}
