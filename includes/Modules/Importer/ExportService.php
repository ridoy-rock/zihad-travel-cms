<?php
/**
 * Export engine.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use RuntimeException;
use WP_Post;
use ZihadTravelCMS\Contracts\ImportMapping;

defined( 'ABSPATH' ) || exit;

/**
 * Exports content by reversing the same import mappings, so an export
 * always round-trips through the importer (the demo-data files are
 * produced this way too).
 *
 * CSV: UTF-8 with BOM (Excel-safe Bangla), lists pipe-joined,
 * structured fields JSON-encoded per cell. JSON: pretty, unescaped
 * unicode.
 */
final class ExportService {

	public const FORMATS = array( 'csv', 'json' );

	/**
	 * Constructor.
	 *
	 * @param MappingRegistry $registry Mapping registry.
	 */
	public function __construct( private MappingRegistry $registry ) {}

	/**
	 * Export every published record of a type.
	 *
	 * @param string $type   Import type (registered mapping).
	 * @param string $format csv|json.
	 *
	 * @return array{filename: string, mime: string, body: string}
	 *
	 * @throws RuntimeException When the type or format is unknown.
	 */
	public function export( string $type, string $format = 'json' ): array {
		$mapping = $this->registry->get( $type );

		if ( null === $mapping ) {
			throw new RuntimeException( sprintf( 'Unknown export type "%s".', esc_html( $type ) ) );
		}

		if ( ! in_array( $format, self::FORMATS, true ) ) {
			throw new RuntimeException( sprintf( 'Unknown format "%s".', esc_html( $format ) ) );
		}

		$records = array_map(
			fn( WP_Post $post ): array => $this->record_for( $mapping, $post ),
			get_posts(
				array(
					'post_type'      => $mapping->post_type(),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			)
		);

		return array(
			'filename' => sprintf( 'ztc-%s-export.%s', $type, $format ),
			'mime'     => 'csv' === $format ? 'text/csv' : 'application/json',
			'body'     => 'csv' === $format ? $this->to_csv( $records ) : $this->to_json( $records ),
		);
	}

	/**
	 * Build one flat record from a post by reversing the mapping.
	 *
	 * @param ImportMapping $mapping The mapping.
	 * @param WP_Post       $post    The post.
	 *
	 * @return array<string, mixed>
	 */
	private function record_for( ImportMapping $mapping, WP_Post $post ): array {
		$record = array();

		foreach ( $mapping->fields() as $key => $definition ) {
			[ $kind, $name ] = array_pad( explode( ':', (string) ( $definition['target'] ?? '' ), 2 ), 2, '' );

			$record[ $key ] = match ( $kind ) {
				'post'      => $this->post_field( $post, $name ),
				'meta'      => get_post_meta( $post->ID, $name, true ),
				'list',
				'json'      => (array) get_post_meta( $post->ID, $name, true ),
				'terms'     => $this->term_names( $post->ID, $name ),
				'relation'  => get_the_title( (int) get_post_meta( $post->ID, $name, true ) ),
				'image'     => $this->image_url( (int) get_post_meta( $post->ID, $name, true ) ),
				'gallery'   => array_values(
					array_filter(
						array_map(
							fn( mixed $id ): string => $this->image_url( (int) $id ),
							(array) get_post_meta( $post->ID, $name, true )
						)
					)
				),
				'thumbnail' => $this->image_url( (int) get_post_thumbnail_id( $post ) ),
				default     => '',
			};
		}

		return $record;
	}

	/**
	 * A core post field value.
	 *
	 * @param WP_Post $post The post.
	 * @param string  $name Field name (title, slug, status…).
	 */
	private function post_field( WP_Post $post, string $name ): string {
		return (string) match ( $name ) {
			'title'   => $post->post_title,
			'slug'    => $post->post_name,
			'status'  => $post->post_status,
			'content' => $post->post_content,
			'excerpt' => $post->post_excerpt,
			default   => '',
		};
	}

	/**
	 * A post's term names in a taxonomy.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array<string>
	 */
	private function term_names( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		return is_array( $terms ) ? array_map( static fn( object $term ): string => (string) $term->name, $terms ) : array();
	}

	/**
	 * An attachment's exportable URL — the original import source when
	 * known, otherwise the file URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function image_url( int $attachment_id ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$source = (string) get_post_meta( $attachment_id, ImageImporter::SOURCE_META, true );

		return '' !== $source ? $source : (string) wp_get_attachment_url( $attachment_id );
	}

	/**
	 * Encode records as CSV (BOM + header row; arrays become pipe
	 * lists or JSON cells).
	 *
	 * @param array<array<string, mixed>> $records The records.
	 */
	private function to_csv( array $records ): string {
		if ( array() === $records ) {
			return '';
		}

		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Explicit escape argument ('' = none): PHP 8.4+ forward-compat.
		fputcsv( $handle, array_keys( $records[0] ), ',', '"', '' );

		foreach ( $records as $record ) {
			fputcsv( $handle, array_map( array( $this, 'to_cell' ), $record ), ',', '"', '' );
		}

		rewind( $handle );
		$csv = (string) stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return "\xEF\xBB\xBF" . $csv;
	}

	/**
	 * One CSV cell: scalars pass through; flat string lists join with
	 * pipes; structured arrays become JSON.
	 *
	 * @param mixed $value Record value.
	 */
	private function to_cell( mixed $value ): string {
		if ( ! is_array( $value ) ) {
			return (string) $value;
		}

		// array_values( $x ) === $x ≡ array_is_list( $x ) (WP core only polyfills the latter since 6.5).
		$is_flat = array_filter( $value, 'is_string' ) === $value && array_values( $value ) === $value;

		return $is_flat
			? implode( '|', $value )
			: (string) wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Encode records as pretty JSON (Bangla stays readable).
	 *
	 * @param array<array<string, mixed>> $records The records.
	 */
	private function to_json( array $records ): string {
		return (string) wp_json_encode(
			array( 'records' => $records ),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
	}
}
