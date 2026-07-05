<?php
/**
 * Demo data status.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use ZihadTravelCMS\Modules\Country\CountryRepository;
use ZihadTravelCMS\Modules\Importer\ImportJob;
use ZihadTravelCMS\Modules\Importer\JobRepository;
use ZihadTravelCMS\Modules\Tour\TourRepository;
use ZihadTravelCMS\Modules\Visa\VisaRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The truthful demo-data state, computed from what actually exists:
 * published record counts measured against the generated files' record
 * counts — never from a flag or the latest import job, which drift the
 * moment an install predates the flag or a job is abandoned mid-run.
 * Unfinished demo jobs are surfaced (with their interrupted/stale
 * state) so the UI can offer resume/reset instead of a stuck
 * "running".
 */
final class DemoDataStatus {

	/**
	 * Cached expected counts, keyed by the demo files' signature.
	 */
	private const EXPECTED_TRANSIENT = 'ztc_demo_expected_counts';

	/**
	 * Constructor.
	 *
	 * @param CountryRepository $countries Country data access.
	 * @param VisaRepository    $visas     Visa data access.
	 * @param TourRepository    $tours     Tour data access.
	 * @param SourceRepository  $sources   Demo file locations.
	 * @param JobRepository     $jobs      Import job records.
	 * @param DemoDataInstaller $installer File readiness.
	 */
	public function __construct(
		private CountryRepository $countries,
		private VisaRepository $visas,
		private TourRepository $tours,
		private SourceRepository $sources,
		private JobRepository $jobs,
		private DemoDataInstaller $installer,
	) {}

	/**
	 * Everything the dashboard/import UIs need in one call.
	 *
	 * @return array{files_ready: bool, installed: bool, counts: array<string, int>, expected: array<string, int>, job: array<string, mixed>|null, stale: bool}
	 */
	public function status(): array {
		$job = $this->active_job();

		return array(
			'files_ready' => $this->installer->files_ready(),
			'installed'   => $this->installed(),
			'counts'      => $this->actual_counts(),
			'expected'    => $this->expected_counts(),
			'job'         => null !== $job ? $job->to_array() : null,
			'stale'       => null !== $job && $job->is_stale(),
		);
	}

	/**
	 * Whether the demo dataset is present: every content type holds at
	 * least as many published records as its generated file provides.
	 * Re-installs and manual edits keep this truthful — no flag to
	 * drift.
	 */
	public function installed(): bool {
		$expected = $this->expected_counts();
		$actual   = $this->actual_counts();

		foreach ( DemoDataInstaller::TYPES as $type ) {
			if ( ( $expected[ $type ] ?? 0 ) < 1 || ( $actual[ $type ] ?? 0 ) < $expected[ $type ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Published record counts per content type.
	 *
	 * @return array<string, int>
	 */
	public function actual_counts(): array {
		return array(
			'country' => $this->countries->count(),
			'visa'    => $this->visas->count(),
			'tour'    => $this->tours->count(),
		);
	}

	/**
	 * Record counts inside the generated demo files, cached against the
	 * files' modification times (regeneration busts the cache
	 * automatically).
	 *
	 * @return array<string, int> Zero for a missing/unreadable file.
	 */
	public function expected_counts(): array {
		$signature = $this->files_signature();
		$cached    = get_transient( self::EXPECTED_TRANSIENT );

		if ( is_array( $cached ) && ( $cached['signature'] ?? '' ) === $signature && is_array( $cached['counts'] ?? null ) ) {
			return array_map( 'intval', $cached['counts'] );
		}

		$counts = array();

		foreach ( DemoDataInstaller::TYPES as $type ) {
			$file            = $this->sources->output_file( $type );
			$counts[ $type ] = 0;

			if ( is_readable( $file ) ) {
				$data            = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
				$counts[ $type ] = is_array( $data['records'] ?? null ) ? count( $data['records'] ) : 0;
			}
		}

		set_transient(
			self::EXPECTED_TRANSIENT,
			array(
				'signature' => $signature,
				'counts'    => $counts,
			),
			DAY_IN_SECONDS
		);

		return $counts;
	}

	/**
	 * The newest unfinished demo import job, if any (the resume/reset
	 * target).
	 */
	public function active_job(): ?ImportJob {
		foreach ( $this->jobs->all() as $job ) {
			if ( ! $job->is_finished() && $this->is_demo_job( $job ) ) {
				return $job;
			}
		}

		return null;
	}

	/**
	 * Whether a job imports one of the generated demo files.
	 *
	 * @param ImportJob $job The job.
	 */
	public function is_demo_job( ImportJob $job ): bool {
		foreach ( DemoDataInstaller::TYPES as $type ) {
			if ( $job->file === $this->sources->output_file( $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The first demo type whose records are still short of the file's
	 * count ('' when everything is present) — the resume order.
	 */
	public function next_missing_type(): string {
		$expected = $this->expected_counts();
		$actual   = $this->actual_counts();

		foreach ( DemoDataInstaller::TYPES as $type ) {
			if ( ( $expected[ $type ] ?? 0 ) > 0 && ( $actual[ $type ] ?? 0 ) < $expected[ $type ] ) {
				return $type;
			}
		}

		return '';
	}

	/**
	 * A cache key that changes whenever a demo file changes.
	 */
	private function files_signature(): string {
		$parts = array();

		foreach ( DemoDataInstaller::TYPES as $type ) {
			$file    = $this->sources->output_file( $type );
			$parts[] = $file . ':' . (int) @filemtime( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- missing file is a valid state.
		}

		return md5( implode( '|', $parts ) );
	}
}
