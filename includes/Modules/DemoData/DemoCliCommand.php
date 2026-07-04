<?php
/**
 * WP-CLI demo data command.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use Throwable;
use WP_CLI;
use ZihadTravelCMS\Modules\Importer\ImportJob;

defined( 'ABSPATH' ) || exit;

/**
 * Generate and install demo content. Registered as `wp ztc demo`.
 */
final class DemoCliCommand {

	/**
	 * Constructor.
	 *
	 * @param DemoContentGenerator $generator The generator.
	 * @param DemoDataInstaller    $installer The installer.
	 */
	public function __construct(
		private DemoContentGenerator $generator,
		private DemoDataInstaller $installer,
	) {}

	/**
	 * Regenerate the demo JSON files from the sources.
	 *
	 * ## OPTIONS
	 *
	 * [--locale=<locale>]
	 * : Content locale (en or bn). Default: en.
	 *
	 * [--dir=<path>]
	 * : Target directory. Defaults to the plugin's /demo-data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ztc demo generate
	 *     wp ztc demo generate --locale=bn
	 *
	 * @param array<string>         $args       Positional args.
	 * @param array<string, string> $assoc_args Named args.
	 */
	public function generate( array $args, array $assoc_args ): void {
		try {
			$result = $this->generator->generate(
				(string) ( $assoc_args['locale'] ?? 'en' ),
				isset( $assoc_args['dir'] ) ? (string) $assoc_args['dir'] : null
			);

			WP_CLI::success(
				sprintf(
					'Generated %d countries, %d visas, %d tours (%s).',
					$result['counts']['country'],
					$result['counts']['visa'],
					$result['counts']['tour'],
					$result['locale']
				)
			);
		} catch ( Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Install the generated demo content through the importer.
	 *
	 * ## OPTIONS
	 *
	 * [--batch=<n>]
	 * : Records per batch (1–100). Default: 25.
	 *
	 * [--regenerate]
	 * : Rebuild the JSON files first.
	 *
	 * [--locale=<locale>]
	 * : Locale to regenerate with (used with --regenerate). Default: en.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ztc demo install
	 *     wp ztc demo install --regenerate --locale=bn
	 *
	 * @param array<string>         $args       Positional args.
	 * @param array<string, string> $assoc_args Named args.
	 */
	public function install( array $args, array $assoc_args ): void {
		try {
			if ( isset( $assoc_args['regenerate'] ) || ! $this->installer->files_ready() ) {
				$this->generator->generate( (string) ( $assoc_args['locale'] ?? 'en' ) );
			}

			$progress = null;

			$jobs = $this->installer->install(
				max( 1, min( 100, (int) ( $assoc_args['batch'] ?? 25 ) ) ),
				static function ( ImportJob $job, int $handled ) use ( &$progress ): void {
					if ( null === $progress || 0 === $job->processed - $handled ) {
						$progress = \WP_CLI\Utils\make_progress_bar( 'Importing ' . $job->type, $job->total );
					}

					$progress->tick( $handled );

					if ( $job->is_finished() ) {
						$progress->finish();
						$progress = null;
					}
				}
			);

			foreach ( $jobs as $type => $job ) {
				foreach ( $job->errors as $label => $message ) {
					WP_CLI::warning( $type . ' ' . $label . ': ' . $message );
				}

				WP_CLI::log(
					sprintf(
						'%s: created %d, updated %d, skipped %d, failed %d.',
						$type,
						$job->created,
						$job->updated,
						$job->skipped,
						$job->failed
					)
				);
			}

			WP_CLI::success( 'Demo data installed.' );
		} catch ( Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
}
