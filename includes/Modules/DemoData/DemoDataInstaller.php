<?php
/**
 * Demo data installer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use ZihadTravelCMS\Modules\Importer\ImportJob;
use ZihadTravelCMS\Modules\Importer\ImportService;

defined( 'ABSPATH' ) || exit;

/**
 * Installs the generated demo files by feeding them through the
 * existing import engine — nothing more. Duplicate detection,
 * batching, image sideloading, error logs, resume and rollback are all
 * the importer's; installs run in upsert mode, so re-installing
 * refreshes content instead of duplicating it.
 */
final class DemoDataInstaller {

	/**
	 * Install order: countries first so visa/tour relations resolve.
	 */
	public const TYPES = array( 'country', 'visa', 'tour' );

	/**
	 * Constructor.
	 *
	 * @param ImportService    $import  The import engine.
	 * @param SourceRepository $sources Demo data locations.
	 */
	public function __construct(
		private ImportService $import,
		private SourceRepository $sources,
	) {}

	/**
	 * Start an import job for one demo file (the caller then drives
	 * the importer's process loop — REST UI or CLI).
	 *
	 * @param string $type country|visa|tour.
	 */
	public function start( string $type ): ImportJob {
		return $this->import->start( $type, $this->sources->output_file( $type ), 'upsert', false );
	}

	/**
	 * Install everything synchronously (WP-CLI path).
	 *
	 * @param int           $batch_size Records per batch.
	 * @param callable|null $tick       Optional callback( ImportJob $job, int $handled ) per batch.
	 *
	 * @return array<string, ImportJob> Finished jobs, keyed by type.
	 */
	public function install( int $batch_size = 25, ?callable $tick = null ): array {
		$jobs = array();

		foreach ( self::TYPES as $type ) {
			$job = $this->start( $type );

			while ( ! $job->is_finished() ) {
				$before = $job->processed;
				$job    = $this->import->process( $job->id, $batch_size );

				if ( null !== $tick ) {
					$tick( $job, $job->processed - $before );
				}
			}

			$jobs[ $type ] = $job;
		}

		return $jobs;
	}

	/**
	 * Whether every generated file exists.
	 */
	public function files_ready(): bool {
		foreach ( self::TYPES as $type ) {
			if ( ! is_readable( $this->sources->output_file( $type ) ) ) {
				return false;
			}
		}

		return true;
	}
}
