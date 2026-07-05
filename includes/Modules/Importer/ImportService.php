<?php
/**
 * Import engine.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use RuntimeException;
use Throwable;
use ZihadTravelCMS\Contracts\ImportMapping;
use ZihadTravelCMS\Modules\Importer\Readers\CsvReader;
use ZihadTravelCMS\Modules\Importer\Readers\JsonReader;

defined( 'ABSPATH' ) || exit;

/**
 * The generic, batched import engine.
 *
 * One code path serves REST (admin progress bar), WP-CLI and the demo
 * data installer: start() creates a persisted job, process() handles
 * the next batch from the stored offset (so interrupted imports simply
 * resume), and rollback() deletes everything a job created. Duplicate
 * detection matches by slug — explicit or derived from the title —
 * within the mapping's post type.
 */
final class ImportService {

	public const MODES = array( 'create', 'update', 'upsert' );

	/**
	 * Constructor.
	 *
	 * @param MappingRegistry $registry Mapping registry.
	 * @param JobRepository   $jobs     Job repository.
	 * @param CsvReader       $csv      CSV reader.
	 * @param JsonReader      $json     JSON reader.
	 * @param ImageImporter   $images   Image importer.
	 */
	public function __construct(
		private MappingRegistry $registry,
		private JobRepository $jobs,
		private CsvReader $csv,
		private JsonReader $json,
		private ImageImporter $images,
	) {}

	/**
	 * Validate inputs and create a pending job.
	 *
	 * @param string $type                Import type (registered mapping).
	 * @param string $file                Absolute path to a .csv or .json file.
	 * @param string $mode                create|update|upsert.
	 * @param bool   $rollback_on_failure Roll back created posts when any record fails.
	 *
	 * @throws RuntimeException When the type, mode or file is invalid.
	 */
	public function start( string $type, string $file, string $mode = 'upsert', bool $rollback_on_failure = false ): ImportJob {
		if ( null === $this->registry->get( $type ) ) {
			throw new RuntimeException( sprintf( 'Unknown import type "%s". Available: %s.', $type, implode( ', ', $this->registry->types() ) ) );
		}

		if ( ! in_array( $mode, self::MODES, true ) ) {
			throw new RuntimeException( sprintf( 'Unknown mode "%s".', $mode ) );
		}

		$records = $this->records( $file ); // Validates the file up front.

		$job        = $this->jobs->create( $type, $file, $mode, $rollback_on_failure );
		$job->total = count( $records );

		$this->jobs->save( $job );

		return $job;
	}

	/**
	 * Process the next batch. Call repeatedly until the job is
	 * finished; the stored offset makes interrupted runs resumable.
	 *
	 * @param string $job_id     Job id.
	 * @param int    $batch_size Records per call (1–100).
	 *
	 * @throws RuntimeException When the job or its mapping is missing.
	 */
	public function process( string $job_id, int $batch_size = 20 ): ImportJob {
		$job = $this->jobs->find( $job_id );

		if ( null === $job ) {
			throw new RuntimeException( sprintf( 'Unknown import job "%s".', $job_id ) );
		}

		if ( $job->is_finished() ) {
			return $job;
		}

		$mapping = $this->registry->get( $job->type );

		if ( null === $mapping ) {
			throw new RuntimeException( sprintf( 'No mapping registered for "%s".', $job->type ) );
		}

		$records     = $this->records( $job->file );
		$job->total  = count( $records );
		$job->status = ImportJob::STATUS_RUNNING;
		$batch       = array_slice( $records, $job->processed, max( 1, min( 100, $batch_size ) ), true );

		foreach ( $batch as $index => $record ) {
			$label = 'row ' . ( (int) $index + 1 );

			try {
				$result = $this->import_record( $mapping, (array) $record, $job );
				++$job->{$result};
			} catch ( Throwable $e ) {
				++$job->failed;
				$job->log_error( $label, $e->getMessage() );
			}

			++$job->processed;
		}

		if ( $job->processed >= $job->total ) {
			$job->status = ImportJob::STATUS_COMPLETED;

			if ( $job->rollback_on_failure && $job->failed > 0 ) {
				$this->delete_created( $job );
				$job->status = ImportJob::STATUS_ROLLED_BACK;
			}
		}

		$this->jobs->save( $job );

		/**
		 * Fires after an import batch has been processed.
		 *
		 * @param ImportJob $job The job state.
		 */
		do_action( 'ztc_import_batch_processed', $job );

		return $job;
	}

	/**
	 * Delete every post a job created and mark it rolled back.
	 *
	 * @param string $job_id Job id.
	 *
	 * @throws RuntimeException When the job is missing.
	 */
	public function rollback( string $job_id ): ImportJob {
		$job = $this->jobs->find( $job_id );

		if ( null === $job ) {
			throw new RuntimeException( sprintf( 'Unknown import job "%s".', $job_id ) );
		}

		$this->delete_created( $job );
		$job->status = ImportJob::STATUS_ROLLED_BACK;
		$this->jobs->save( $job );

		return $job;
	}

	/**
	 * Import one record: duplicate detection, mode handling, post
	 * write, then every mapped field.
	 *
	 * @param ImportMapping        $mapping The mapping.
	 * @param array<string, mixed> $record  The record.
	 * @param ImportJob            $job     The running job.
	 *
	 * @return string created|updated|skipped
	 *
	 * @throws RuntimeException When a required field is missing or the post write fails.
	 */
	private function import_record( ImportMapping $mapping, array $record, ImportJob $job ): string {
		$fields  = $mapping->fields();
		$postarr = array(
			'post_type'   => $mapping->post_type(),
			'post_status' => 'publish',
		);

		foreach ( $fields as $key => $definition ) {
			$target   = (string) ( $definition['target'] ?? '' );
			$required = ! empty( $definition['required'] );
			$value    = $record[ $key ] ?? null;
			$missing  = null === $value || ( is_string( $value ) && '' === trim( $value ) );

			if ( $required && $missing ) {
				throw new RuntimeException( sprintf( 'Required field "%s" is missing.', $key ) );
			}

			if ( str_starts_with( $target, 'post:' ) && ! $missing ) {
				$field = substr( $target, 5 );

				// WordPress calls the slug column post_name.
				$postarr[ 'slug' === $field ? 'post_name' : 'post_' . $field ] = is_scalar( $value ) ? (string) $value : '';
			}
		}

		// Duplicate detection: explicit slug, or the slug WordPress
		// would derive from the title.
		$slug     = (string) ( $postarr['post_name'] ?? '' );
		$slug     = '' !== $slug ? $slug : sanitize_title( (string) ( $postarr['post_title'] ?? '' ) );
		$existing = $this->find_existing( $mapping->post_type(), $slug );

		if ( $existing > 0 && 'create' === $job->mode ) {
			return 'skipped';
		}

		if ( 0 === $existing && 'update' === $job->mode ) {
			return 'skipped';
		}

		if ( $existing > 0 ) {
			$postarr['ID'] = $existing;
			$result        = wp_update_post( $postarr, true );
		} else {
			$postarr['post_name'] = $slug;
			$result               = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( $result->get_error_message() );
		}

		$post_id = (int) $result;

		if ( 0 === $existing ) {
			$job->created_ids[] = $post_id;
		}

		$this->apply_fields( $mapping, $record, $post_id, $job );

		return $existing > 0 ? 'updated' : 'created';
	}

	/**
	 * Apply every non-core field to the post.
	 *
	 * @param ImportMapping        $mapping The mapping.
	 * @param array<string, mixed> $record  The record.
	 * @param int                  $post_id The post ID.
	 * @param ImportJob            $job     The running job (for soft warnings).
	 */
	private function apply_fields( ImportMapping $mapping, array $record, int $post_id, ImportJob $job ): void {
		foreach ( $mapping->fields() as $key => $definition ) {
			$target = (string) ( $definition['target'] ?? '' );
			$value  = $record[ $key ] ?? null;

			if ( null === $value || str_starts_with( $target, 'post:' ) ) {
				continue;
			}

			if ( is_string( $value ) && '' === trim( $value ) ) {
				continue; // Absent/empty columns never erase existing data.
			}

			[ $kind, $name ] = array_pad( explode( ':', $target, 2 ), 2, '' );

			switch ( $kind ) {
				case 'meta':
					update_post_meta( $post_id, $name, is_scalar( $value ) ? $value : '' );
					break;

				case 'list':
					update_post_meta( $post_id, $name, $this->to_list( $value ) );
					break;

				case 'json':
					update_post_meta( $post_id, $name, $this->to_structure( $value, $key ) );
					break;

				case 'terms':
					wp_set_object_terms( $post_id, $this->to_list( $value ), $name, false );
					break;

				case 'relation':
					$related = $this->find_existing( $this->relation_post_type( $name ), sanitize_title( $this->to_string( $value ) ) );

					if ( 0 === $related ) {
						$job->log_error( 'post ' . $post_id . ' (' . $key . ')', sprintf( 'Related record "%s" not found for "%s".', $this->to_string( $value ), $key ) );
					}

					update_post_meta( $post_id, $name, $related );
					break;

				// Image problems are soft warnings, like unresolved
				// relations: the record's post is already written, so a
				// broken URL must never flip the whole record to
				// "failed" (QA: failed used to equal processed when a
				// placeholder host rejected sideloads).
				case 'image':
					try {
						update_post_meta( $post_id, $name, $this->images->import( $this->to_string( $value ), $post_id ) );
					} catch ( Throwable $e ) {
						// Field-unique label: multiple warnings on one
						// post must not overwrite each other.
						$job->log_error( 'post ' . $post_id . ' (' . $key . ')', $e->getMessage() );
					}
					break;

				case 'gallery':
					$urls = $this->to_list( $value );
					$ids  = $this->images->import_all( $urls, $post_id );

					if ( count( $ids ) < count( $urls ) ) {
						$job->log_error(
							'post ' . $post_id . ' (' . $key . ')',
							sprintf( '%d of %d gallery images could not be imported for "%s".', count( $urls ) - count( $ids ), count( $urls ), $key )
						);
					}

					update_post_meta( $post_id, $name, $ids );
					break;

				case 'thumbnail':
					try {
						$thumbnail_id = $this->images->import( $this->to_string( $value ), $post_id );

						if ( $thumbnail_id > 0 ) {
							set_post_thumbnail( $post_id, $thumbnail_id );
						}
					} catch ( Throwable $e ) {
						$job->log_error( 'post ' . $post_id . ' (' . $key . ')', $e->getMessage() );
					}
					break;
			}
		}
	}

	/**
	 * Read the records for a file by extension.
	 *
	 * @param string $file Absolute file path.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws RuntimeException On unsupported extensions.
	 */
	public function records( string $file ): array {
		$extension = strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) );

		return match ( $extension ) {
			'csv'   => $this->csv->records( $file ),
			'json'  => $this->json->records( $file ),
			default => throw new RuntimeException( sprintf( 'Unsupported file type ".%s" — use .csv or .json.', $extension ) ),
		};
	}

	/**
	 * A published/draft post of a type by slug, or 0.
	 *
	 * @param string $post_type Post type.
	 * @param string $slug      Post slug.
	 */
	private function find_existing( string $post_type, string $slug ): int {
		if ( '' === $slug ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) ( $found[0] ?? 0 );
	}

	/**
	 * The post type a relation meta key points at. Currently every
	 * relation (`ztc_country`) targets Countries; filterable for
	 * future mappings.
	 *
	 * @param string $meta_key Relation meta key.
	 */
	private function relation_post_type( string $meta_key ): string {
		/**
		 * Filter the post type a relation field resolves against.
		 *
		 * @param string $post_type Target post type.
		 * @param string $meta_key  Relation meta key.
		 */
		return (string) apply_filters( 'ztc_import_relation_post_type', 'ztc_country', $meta_key );
	}

	/**
	 * Delete every post the job created (bypassing trash).
	 *
	 * @param ImportJob $job The job.
	 */
	private function delete_created( ImportJob $job ): void {
		foreach ( $job->created_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}

		$job->created_ids = array();
	}

	/**
	 * Normalize a value into a list of strings: arrays pass through,
	 * strings may be JSON arrays or pipe-separated.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return array<string>
	 */
	private function to_list( mixed $value ): array {
		if ( is_array( $value ) ) {
			$items = $value;
		} else {
			$raw     = trim( $this->to_string( $value ) );
			$decoded = str_starts_with( $raw, '[' ) ? json_decode( $raw, true ) : null;
			$items   = is_array( $decoded ) ? $decoded : explode( '|', $raw );
		}

		$items = array_map( static fn( mixed $item ): string => trim( (string) ( is_scalar( $item ) ? $item : '' ) ), $items );

		return array_values( array_filter( $items, static fn( string $item ): bool => '' !== $item ) );
	}

	/**
	 * Normalize a structured value: arrays pass through (JSON import),
	 * strings must be valid JSON (CSV cells).
	 *
	 * @param mixed  $value Raw value.
	 * @param string $key   Field key (for error messages).
	 *
	 * @return array<mixed>
	 *
	 * @throws RuntimeException On invalid JSON.
	 */
	private function to_structure( mixed $value, string $key ): array {
		if ( is_array( $value ) ) {
			return $value;
		}

		$decoded = json_decode( $this->to_string( $value ), true );

		if ( ! is_array( $decoded ) ) {
			throw new RuntimeException( sprintf( 'Field "%s" contains invalid JSON.', $key ) );
		}

		return $decoded;
	}

	/**
	 * Scalar to string, safely.
	 *
	 * @param mixed $value Raw value.
	 */
	private function to_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}
