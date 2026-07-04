<?php
/**
 * WP-CLI import/export command.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use Throwable;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Import and export travel content.
 *
 * Registered as `wp ztc` on cli_init. Unlike REST, the CLI may import
 * from any local path (the operator already has filesystem access).
 */
final class ImportCliCommand {

	/**
	 * Constructor.
	 *
	 * @param ImportService $import Import engine.
	 * @param ExportService $export Export engine.
	 * @param JobRepository $jobs   Job repository.
	 */
	public function __construct(
		private ImportService $import,
		private ExportService $export,
		private JobRepository $jobs,
	) {}

	/**
	 * Import records from a CSV or JSON file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the .csv or .json file.
	 *
	 * --type=<type>
	 * : Import type (country, visa, tour, or a registered custom type).
	 *
	 * [--mode=<mode>]
	 * : create, update or upsert. Default: upsert.
	 *
	 * [--batch=<n>]
	 * : Records per batch (1–100). Default: 50.
	 *
	 * [--rollback-on-failure]
	 * : Delete everything this run created if any record fails.
	 *
	 * [--resume=<job_id>]
	 * : Continue an interrupted job instead of starting a new one.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ztc import demo-data/tours.json --type=tour
	 *     wp ztc import visas.csv --type=visa --mode=update --batch=25
	 *
	 * @param array<string>         $args       Positional args.
	 * @param array<string, string> $assoc_args Named args.
	 */
	public function import( array $args, array $assoc_args ): void {
		$batch = max( 1, min( 100, (int) ( $assoc_args['batch'] ?? 50 ) ) );

		try {
			$resume = (string) ( $assoc_args['resume'] ?? '' );

			$job = '' !== $resume
				? $this->jobs->find( $resume )
				: $this->import->start(
					(string) ( $assoc_args['type'] ?? '' ),
					(string) ( $args[0] ?? '' ),
					(string) ( $assoc_args['mode'] ?? 'upsert' ),
					isset( $assoc_args['rollback-on-failure'] )
				);

			if ( null === $job ) {
				WP_CLI::error( 'Unknown job id.' );
			}

			$progress = \WP_CLI\Utils\make_progress_bar( 'Importing ' . $job->type, $job->total - $job->processed );

			while ( ! $job->is_finished() ) {
				$before = $job->processed;
				$job    = $this->import->process( $job->id, $batch );
				$progress->tick( $job->processed - $before );
			}

			$progress->finish();

			foreach ( $job->errors as $label => $message ) {
				WP_CLI::warning( $label . ': ' . $message );
			}

			$summary = sprintf(
				'%s — created %d, updated %d, skipped %d, failed %d (job %s).',
				$job->status,
				$job->created,
				$job->updated,
				$job->skipped,
				$job->failed,
				$job->id
			);

			if ( ImportJob::STATUS_COMPLETED === $job->status && 0 === $job->failed ) {
				WP_CLI::success( $summary );
			} else {
				WP_CLI::warning( $summary );
			}
		} catch ( Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Export records to CSV or JSON.
	 *
	 * ## OPTIONS
	 *
	 * --type=<type>
	 * : Export type (country, visa, tour, or a registered custom type).
	 *
	 * [--format=<format>]
	 * : csv or json. Default: json.
	 *
	 * [--output=<path>]
	 * : File to write. Defaults to the export filename in the current directory.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ztc export --type=tour --format=json --output=tours.json
	 *
	 * @param array<string>         $args       Positional args.
	 * @param array<string, string> $assoc_args Named args.
	 */
	public function export( array $args, array $assoc_args ): void {
		try {
			$result = $this->export->export(
				(string) ( $assoc_args['type'] ?? '' ),
				(string) ( $assoc_args['format'] ?? 'json' )
			);

			$output = (string) ( $assoc_args['output'] ?? $result['filename'] );

			file_put_contents( $output, $result['body'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			WP_CLI::success( sprintf( 'Exported to %s.', $output ) );
		} catch ( Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Show an import job's status and error log.
	 *
	 * ## OPTIONS
	 *
	 * <job_id>
	 * : The job id.
	 *
	 * @subcommand import-status
	 *
	 * @param array<string> $args Positional args.
	 */
	public function import_status( array $args ): void {
		$job = $this->jobs->find( (string) ( $args[0] ?? '' ) );

		if ( null === $job ) {
			WP_CLI::error( 'Unknown job id.' );
		}

		WP_CLI::log( (string) wp_json_encode( $job->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Delete everything an import job created.
	 *
	 * ## OPTIONS
	 *
	 * <job_id>
	 * : The job id.
	 *
	 * @subcommand import-rollback
	 *
	 * @param array<string> $args Positional args.
	 */
	public function import_rollback( array $args ): void {
		try {
			$job = $this->import->rollback( (string) ( $args[0] ?? '' ) );
			WP_CLI::success( sprintf( 'Job %s rolled back.', $job->id ) );
		} catch ( Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
}
