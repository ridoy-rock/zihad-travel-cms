<?php
/**
 * Import job repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Persists import jobs in wp_options (one non-autoloaded row per job,
 * plus a small capped index).
 */
final class JobRepository {

	private const OPTION_PREFIX = 'ztc_import_job_';
	private const INDEX_OPTION  = 'ztc_import_jobs';
	private const MAX_JOBS      = 20;

	/**
	 * Create a new job with a unique id.
	 *
	 * @param string $type                Import type.
	 * @param string $file                Source file path.
	 * @param string $mode                create|update|upsert.
	 * @param bool   $rollback_on_failure All-or-nothing mode.
	 */
	public function create( string $type, string $file, string $mode, bool $rollback_on_failure ): ImportJob {
		$job = new ImportJob( uniqid( 'ztc', false ), $type, $file, $mode, $rollback_on_failure );

		$this->save( $job );
		$this->index( $job->id );

		return $job;
	}

	/**
	 * Persist a job.
	 *
	 * @param ImportJob $job The job.
	 */
	public function save( ImportJob $job ): void {
		update_option( self::OPTION_PREFIX . $job->id, $job->to_array(), false );
	}

	/**
	 * Load a job by id.
	 *
	 * @param string $job_id Job id.
	 */
	public function find( string $job_id ): ?ImportJob {
		$data = get_option( self::OPTION_PREFIX . $job_id );

		return is_array( $data ) ? ImportJob::from_array( $data ) : null;
	}

	/**
	 * All known jobs, newest first.
	 *
	 * @return array<ImportJob>
	 */
	public function all(): array {
		$jobs = array();

		foreach ( array_reverse( $this->ids() ) as $job_id ) {
			$job = $this->find( $job_id );

			if ( null !== $job ) {
				$jobs[] = $job;
			}
		}

		return $jobs;
	}

	/**
	 * Delete a job record.
	 *
	 * @param string $job_id Job id.
	 */
	public function delete( string $job_id ): void {
		delete_option( self::OPTION_PREFIX . $job_id );
		update_option( self::INDEX_OPTION, array_values( array_diff( $this->ids(), array( $job_id ) ) ), false );
	}

	/**
	 * Add a job to the index, pruning the oldest beyond the cap.
	 *
	 * @param string $job_id Job id.
	 */
	private function index( string $job_id ): void {
		$ids   = $this->ids();
		$ids[] = $job_id;

		while ( count( $ids ) > self::MAX_JOBS ) {
			$oldest = array_shift( $ids );
			delete_option( self::OPTION_PREFIX . $oldest );
		}

		update_option( self::INDEX_OPTION, $ids, false );
	}

	/**
	 * Known job ids, oldest first.
	 *
	 * @return array<string>
	 */
	private function ids(): array {
		return array_map( 'strval', (array) get_option( self::INDEX_OPTION, array() ) );
	}
}
