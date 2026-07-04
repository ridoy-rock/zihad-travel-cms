<?php
/**
 * Import job state.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

defined( 'ABSPATH' ) || exit;

/**
 * The persisted state of one import run.
 *
 * Persistence makes batching, the progress bar, resuming interrupted
 * imports and rollback all work from the same record: `processed` is
 * the resume offset, `created_ids` is the rollback set, `errors` is
 * the error log.
 */
final class ImportJob {

	public const STATUS_PENDING     = 'pending';
	public const STATUS_RUNNING     = 'running';
	public const STATUS_COMPLETED   = 'completed';
	public const STATUS_FAILED      = 'failed';
	public const STATUS_ROLLED_BACK = 'rolled_back';

	private const MAX_ERRORS = 100;

	/**
	 * Constructor.
	 *
	 * @param string     $id                  Job id.
	 * @param string     $type                Import type (mapping id).
	 * @param string     $file                Absolute path of the source file.
	 * @param string     $mode                create|update|upsert.
	 * @param bool       $rollback_on_failure All-or-nothing mode.
	 * @param string     $status              Job status.
	 * @param int        $total               Total records.
	 * @param int        $processed           Records handled so far (resume offset).
	 * @param int        $created             Created posts.
	 * @param int        $updated             Updated posts.
	 * @param int        $skipped             Skipped records.
	 * @param int        $failed              Failed records.
	 * @param array      $errors              Error log (label => message).
	 * @param array<int> $created_ids         Post IDs created by this job.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $type,
		public readonly string $file,
		public readonly string $mode = 'upsert',
		public readonly bool $rollback_on_failure = false,
		public string $status = self::STATUS_PENDING,
		public int $total = 0,
		public int $processed = 0,
		public int $created = 0,
		public int $updated = 0,
		public int $skipped = 0,
		public int $failed = 0,
		public array $errors = array(),
		public array $created_ids = array(),
	) {}

	/**
	 * Record an error for a row, capping the log size.
	 *
	 * @param string $label   Row label, e.g. `row 12`.
	 * @param string $message What went wrong.
	 */
	public function log_error( string $label, string $message ): void {
		if ( count( $this->errors ) < self::MAX_ERRORS ) {
			$this->errors[ $label ] = $message;
		}
	}

	/**
	 * Percentage complete (0–100).
	 */
	public function progress(): float {
		return $this->total > 0 ? round( $this->processed / $this->total * 100, 1 ) : 0.0;
	}

	/**
	 * Whether the job can still process records.
	 */
	public function is_finished(): bool {
		return in_array( $this->status, array( self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_ROLLED_BACK ), true );
	}

	/**
	 * Serialize for storage / REST responses.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                  => $this->id,
			'type'                => $this->type,
			'file'                => $this->file,
			'mode'                => $this->mode,
			'rollback_on_failure' => $this->rollback_on_failure,
			'status'              => $this->status,
			'total'               => $this->total,
			'processed'           => $this->processed,
			'created'             => $this->created,
			'updated'             => $this->updated,
			'skipped'             => $this->skipped,
			'failed'              => $this->failed,
			'errors'              => $this->errors,
			'created_ids'         => $this->created_ids,
			'progress'            => $this->progress(),
			'finished'            => $this->is_finished(),
		);
	}

	/**
	 * Rebuild from stored data.
	 *
	 * @param array<string, mixed> $data Stored job data.
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['id'] ?? '' ),
			(string) ( $data['type'] ?? '' ),
			(string) ( $data['file'] ?? '' ),
			(string) ( $data['mode'] ?? 'upsert' ),
			(bool) ( $data['rollback_on_failure'] ?? false ),
			(string) ( $data['status'] ?? self::STATUS_PENDING ),
			(int) ( $data['total'] ?? 0 ),
			(int) ( $data['processed'] ?? 0 ),
			(int) ( $data['created'] ?? 0 ),
			(int) ( $data['updated'] ?? 0 ),
			(int) ( $data['skipped'] ?? 0 ),
			(int) ( $data['failed'] ?? 0 ),
			(array) ( $data['errors'] ?? array() ),
			array_map( 'intval', (array) ( $data['created_ids'] ?? array() ) ),
		);
	}
}
